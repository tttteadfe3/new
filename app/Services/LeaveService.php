<?php

namespace App\Services;

use App\Repositories\LeaveRepository;
use App\Repositories\EmployeeRepository;
use DateTime;
use App\Services\DataScopeService;

/**
 * 연차 관리 시스템의 핵심 비즈니스 로직을 담당하는 서비스
 */
class LeaveService
{
    private $leaveRepository;
    private $employeeRepository;
    private $dataScopeService;

    public function __construct(LeaveRepository $leaveRepository, EmployeeRepository $employeeRepository, DataScopeService $dataScopeService)
    {
        $this->leaveRepository = $leaveRepository;
        $this->employeeRepository = $employeeRepository;
        $this->dataScopeService = $dataScopeService;
    }

    /**
     * 특정 직원의 현재 시점 연차/월차 잔여량을 계산하여 반환한다.
     *
     * @param int $employeeId 직원 ID
     * @return array ['annual' => float, 'monthly' => float]
     */
    public function getLeaveBalance(int $employeeId): array
    {
        $logs = $this->leaveRepository->getLogsByEmployeeId($employeeId);

        $balance = [
            'annual' => 0.0,
            'monthly' => 0.0,
        ];

        foreach ($logs as $log) {
            if ($log['leave_type'] === 'annual') {
                $balance['annual'] += $log['amount'];
            } elseif ($log['leave_type'] === 'monthly') {
                $balance['monthly'] += $log['amount'];
            }
        }

        return $balance;
    }

    /**
     * 신규 입사자에게 입사 연도에 해당하는 월차를 즉시 부여한다.
     * (예: 7월 1일 입사 시, 8월~12월에 해당하는 5일의 월차를 미리 부여)
     *
     * @param int $employeeId
     * @return bool 성공 여부
     */
    public function grantInitialMonthlyLeave(int $employeeId): bool
    {
        $employee = $this->employeeRepository->findById($employeeId);
        if (!$employee || !$employee['hire_date']) {
            // TODO: Log error - employee or hire_date not found
            return false;
        }

        $hireDate = new DateTime($employee['hire_date']);
        $currentYear = (int)$hireDate->format('Y');
        $hireMonth = (int)$hireDate->format('m');

        // 입사 연도에 부여될 월차 개수 계산 (입사 월은 제외)
        $monthlyLeaveAmount = 12 - $hireMonth;

        if ($monthlyLeaveAmount > 0) {
            $this->leaveRepository->createLog([
                'employee_id' => $employeeId,
                'leave_request_id' => null,
                'leave_type' => 'monthly',
                'transaction_type' => 'grant_initial',
                'amount' => $monthlyLeaveAmount,
                'reason' => $currentYear . '년 신규 입사자 월차 부여',
                'actor_employee_id' => null, // 시스템에 의한 자동 부여
            ]);
        }

        return true;
    }

    /**
     * 지정된 연도의 정기 연차를 모든 재직중인 직원에게 부여한다. (관리자용 기능)
     *
     * @param int $year 부여할 연도
     * @param int $actorEmployeeId 실행한 관리자 ID
     */
    public function grantAnnualLeaveForYear(int $year, int $actorEmployeeId)
    {
        $activeEmployees = $this->employeeRepository->findAllActive();

        foreach ($activeEmployees as $employee) {
            $grantAmount = $this->calculateAnnualLeaveForEmployee($employee, $year);

            if ($grantAmount > 0) {
                $this->leaveRepository->createLog([
                    'employee_id' => $employee['id'],
                    'leave_request_id' => null,
                    'leave_type' => 'annual',
                    'transaction_type' => 'grant_annual',
                    'amount' => $grantAmount,
                    'reason' => $year . '년 정기 연차 부여',
                    'actor_employee_id' => $actorEmployeeId,
                ]);
            }
        }
    }

    /**
     * 직원의 연차 부여량을 계산한다. (private 헬퍼 메소드)
     */
    private function calculateAnnualLeaveForEmployee(array $employee, int $year): float
    {
        $hireDate = new DateTime($employee['hire_date']);
        $grantDate = new DateTime($year . "-01-01");

        // 입사 연도와 부여 연도가 같으면, 정기 연차는 부여되지 않음 (월차만 부여)
        if ($hireDate->format('Y') == $year) {
            return 0.0;
        }

        // 입사 다음 해인 경우, 비례하여 연차 부여
        if ((int)$hireDate->format('Y') === $year - 1) {
            $firstDayOfHireYear = new DateTime($hireDate->format('Y') . '-01-01');
            $daysWorked = $firstDayOfHireYear->diff($hireDate)->days;
            $totalDaysInYear = 366; // 윤년 고려
            $proportionalLeave = ( ( $totalDaysInYear - $daysWorked ) / $totalDaysInYear) * 15;
            return round($proportionalLeave, 1); // 소수점 첫째 자리까지 반올림
        }

        // 일반적인 경우: 기본 15일 + 근속 연차
        $serviceYears = $this->calculateServiceYears($hireDate, $grantDate);
        $longServiceLeave = $this->calculateLongServiceLeave($serviceYears);

        return 15.0 + $longServiceLeave;
    }

    /**
     * 근속 연수를 계산한다. (중도 입사자는 다음 해 1월 1일부터 카운트)
     */
    private function calculateServiceYears(DateTime $hireDate, DateTime $grantDate): int
    {
        $startYear = (int)$hireDate->format('Y');
        $startMonth = (int)$hireDate->format('m');
        $startDay = (int)$hireDate->format('d');

        // 1월 1일 입사자가 아니면, 근속년수 계산 시작일을 다음해 1월 1일로 조정
        if ($startMonth !== 1 || $startDay !== 1) {
            $hireDate = new DateTime(($startYear + 1) . '-01-01');
        }

        $interval = $grantDate->diff($hireDate);
        return $interval->y;
    }

    /**
     * 근속 연수에 따른 추가 연차를 계산한다.
     */
    private function calculateLongServiceLeave(int $serviceYears): int
    {
        if ($serviceYears < 3) {
            return 0;
        }
        // 만 3년부터 시작, 2년마다 1일 추가
        return (int)(($serviceYears - 1) / 2);
    }

    /**
     * 새로운 휴가 신청을 생성한다.
     *
     * @param int $employeeId
     * @param array $data
     * @return array
     */
    public function createLeaveRequest(int $employeeId, array $data): array
    {
        // 1. 잔여 연차 검증
        $balance = $this->getLeaveBalance($employeeId);
        $leaveType = $data['leave_type']; // 'annual' or 'monthly'
        $daysCount = (float)$data['days_count'];

        if ($balance[$leaveType] < $daysCount) {
            return ['success' => false, 'message' => '잔여 휴가 일수가 부족합니다.'];
        }

        // 2. 데이터베이스에 신청서 생성
        $requestData = [
            'employee_id' => $employeeId,
            'leave_type' => $leaveType,
            'request_unit' => $data['request_unit'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_count' => $daysCount,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ];

        $this->leaveRepository->createRequest($requestData);

        return ['success' => true, 'message' => '휴가 신청이 성공적으로 접수되었습니다.'];
    }

    /**
     * 휴가 신청을 승인한다. (관리자용)
     */
    public function approveRequest(int $requestId, int $actorEmployeeId): array
    {
        $this->leaveRepository->beginTransaction();

        try {
            $request = $this->leaveRepository->findRequestById($requestId);

            if (!$request || $request['status'] !== 'pending') {
                $this->leaveRepository->rollBack();
                return ['success' => false, 'message' => '승인할 수 없는 신청서입니다.'];
            }

            // 1. 'use' 로그 생성하여 연차 차감
            $this->leaveRepository->createLog([
                'employee_id' => $request['employee_id'],
                'leave_request_id' => $requestId,
                'leave_type' => $request['leave_type'],
                'transaction_type' => 'use',
                'amount' => -$request['days_count'], // 차감이므로 음수
                'reason' => '휴가 사용 승인 (' . $request['start_date'] . '~' . $request['end_date'] . ')',
                'actor_employee_id' => $actorEmployeeId,
            ]);

            // 2. 신청서 상태를 'approved'로 변경
            $this->leaveRepository->updateRequestStatus($requestId, 'approved', $actorEmployeeId);

            $this->leaveRepository->commit();
            return ['success' => true, 'message' => '휴가 신청을 승인했습니다.'];

        } catch (\Exception $e) {
            $this->leaveRepository->rollBack();
            // TODO: Log the actual error $e->getMessage()
            return ['success' => false, 'message' => '처리 중 오류가 발생했습니다.'];
        }
    }

    /**
     * 휴가 취소 신청을 승인한다. (관리자용)
     */
    public function approveCancellation(int $requestId, int $actorEmployeeId): array
    {
        $this->leaveRepository->beginTransaction();

        try {
            $request = $this->leaveRepository->findRequestById($requestId);

            if (!$request || $request['status'] !== 'cancellation_requested') {
                $this->leaveRepository->rollBack();
                return ['success' => false, 'message' => '취소 승인할 수 없는 신청서입니다.'];
            }

            // 1. 'cancel_use' 로그 생성하여 연차 복구
            $this->leaveRepository->createLog([
                'employee_id' => $request['employee_id'],
                'leave_request_id' => $requestId,
                'leave_type' => $request['leave_type'],
                'transaction_type' => 'cancel_use',
                'amount' => $request['days_count'], // 복구이므로 양수
                'reason' => '휴가 사용 취소 (' . $request['start_date'] . '~' . $request['end_date'] . ')',
                'actor_employee_id' => $actorEmployeeId,
            ]);

            // 2. 신청서 상태를 'cancelled'로 변경
            $this->leaveRepository->updateRequestStatus($requestId, 'cancelled', $actorEmployeeId);

            $this->leaveRepository->commit();
            return ['success' => true, 'message' => '휴가 취소 요청을 승인했습니다.'];

        } catch (\Exception $e) {
            $this->leaveRepository->rollBack();
            return ['success' => false, 'message' => '처리 중 오류가 발생했습니다.'];
        }
    }

    /**
     * 관리자용 휴가 신청 목록을 조회한다. (데이터 접근 범위 적용)
     */
    public function getRequestsForAdmin(array $filters): array
    {
        $visibleDeptIds = $this->dataScopeService->getVisibleDepartmentIdsForCurrentUser();
        $filters['department_ids'] = $visibleDeptIds;
        
        return $this->leaveRepository->findRequestsByFilters($filters);
    }

    /**
     * 휴가 신청을 반려한다. (관리자용)
     */
    public function rejectRequest(int $requestId, int $actorEmployeeId, string $reason): array
    {
        $request = $this->leaveRepository->findRequestById($requestId);

        if (!$request || $request['status'] !== 'pending') {
            return ['success' => false, 'message' => '반려할 수 없는 신청서입니다.'];
        }

        if (empty($reason)) {
            return ['success' => false, 'message' => '반려 사유는 필수입니다.'];
        }

        $this->leaveRepository->updateRequestStatusAndReason($requestId, 'rejected', $actorEmployeeId, $reason);

        return ['success' => true, 'message' => '휴가 신청을 반려했습니다.'];
    }

    /**
     * 연차/월차를 수동으로 조정한다. (관리자용)
     */
    public function adjustLeave(array $data, int $actorEmployeeId): array
    {
        $employeeId = $data['employee_id'];
        $leaveType = $data['leave_type'];
        $amount = (float)$data['amount'];
        $reason = $data['reason'];

        if (empty($employeeId) || empty($leaveType) || empty($amount) || empty($reason)) {
            return ['success' => false, 'message' => '모든 필드를 입력해야 합니다.'];
        }

        $transactionType = $amount > 0 ? 'adjust_add' : 'adjust_subtract';

        $this->leaveRepository->createLog([
            'employee_id' => $employeeId,
            'leave_request_id' => null,
            'leave_type' => $leaveType,
            'transaction_type' => $transactionType,
            'amount' => $amount,
            'reason' => $reason,
            'actor_employee_id' => $actorEmployeeId,
        ]);

        return ['success' => true, 'message' => '연차 조정이 성공적으로 완료되었습니다.'];
    }

    /**
     * 휴가 취소를 요청한다. (직원용)
     */
    public function requestCancellation(int $requestId, int $employeeId): array
    {
        $request = $this->leaveRepository->findRequestById($requestId);

        if (!$request || $request['employee_id'] != $employeeId) {
            return ['success' => false, 'message' => '권한이 없습니다.'];
        }

        if ($request['status'] !== 'approved') {
            return ['success' => false, 'message' => '승인된 휴가만 취소 요청할 수 있습니다.'];
        }

        $this->leaveRepository->updateRequestStatus($requestId, 'cancellation_requested');

        return ['success' => true, 'message' => '휴가 취소 요청이 접수되었습니다. 관리자 승인 후 최종 처리됩니다.'];
    }
}
