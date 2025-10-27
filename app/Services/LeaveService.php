<?php
// app/Services/LeaveService.php
namespace App\Services;

use App\Repositories\LeaveRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\HolidayRepository;
use App\Services\HolidayService;
use App\Repositories\LogRepository;
use DateTime;
use DatePeriod;
use DateInterval;
use Exception;

/**
 * 연차 및 휴가 관련 비즈니스 로직을 처리하는 서비스 클래스입니다.
 * 연차 일수 계산, 부여, 신청, 승인/반려/취소 등의 작업을 담당합니다.
 * 
 * 향상된 연차 관리 비즈니스 로직 포함:
 * - 고급 연차 계산 알고리즘
 * - 포괄적인 유효성 검사 규칙
 * - 승인을 위한 워크플로우 관리
 * - 직원 및 휴일 시스템과의 통합
 */
class LeaveService {
    private LeaveRepository $leaveRepository;
    private EmployeeRepository $employeeRepository;
    private HolidayService $holidayService;
    private LogRepository $logRepository;
    private AuthService $authService;
    private \App\Repositories\DepartmentRepository $departmentRepository;
    private OrganizationService $organizationService;

    public function __construct(
        LeaveRepository $leaveRepository,
        EmployeeRepository $employeeRepository,
        HolidayService $holidayService,
        LogRepository $logRepository,
        AuthService $authService,
        \App\Repositories\DepartmentRepository $departmentRepository,
        OrganizationService $organizationService
    ) {
        $this->leaveRepository = $leaveRepository;
        $this->employeeRepository = $employeeRepository;
        $this->holidayService = $holidayService;
        $this->logRepository = $logRepository;
        $this->authService = $authService;
        $this->departmentRepository = $departmentRepository;
        $this->organizationService = $organizationService;
    }

    /**
     * 직원의 근속 연수에 따라 연차를 계산하고 데이터베이스에 부여(저장)합니다.
     *
     * @param int $employeeId 연차를 부여할 직원의 ID
     * @param int $year 부여 기준 연도
     * @return bool 연차 부여 성공 여부
     * @throws Exception 직원 정보가 없거나 입사일이 설정되지 않은 경우
     */
    public function grantCalculatedAnnualLeave(int $employeeId, int $year): bool {
        $employee = $this->employeeRepository->findById($employeeId);
        if (!$employee || !$employee['hire_date']) {
            throw new Exception("직원 정보 또는 입사일이 존재하지 않습니다.");
        }

        $leaveData = $this->calculateAnnualLeaveDays($employee['hire_date'], $year);
        $total_days = $leaveData['total_days'];

        return $this->leaveRepository->createOrUpdateEntitlement([
            'employee_id' => $employeeId,
            'year' => $year,
            'total_days' => $total_days
        ]);
    }

    /**
     * 직원의 입사일과 기준 연도를 바탕으로 발생 연차 일수를 계산하여 상세 내역을 반환합니다.
     *
     * @param string $hireDate 직원의 입사일 (Y-m-d 형식)
     * @param int $calculationYear 계산 기준 연도
     * @return array 계산된 연차 상세 정보 ('base_days', 'long_service_days', 'total_days')
     */
    public function calculateAnnualLeaveDays(string $hireDate, int $calculationYear): array {
        $hire = new DateTime($hireDate);
        $startOfYear = new DateTime("$calculationYear-01-01");
        $endOfYear = new DateTime("$calculationYear-12-31");

        $serviceYears = $startOfYear->diff($hire)->y;

        $base_days = 0.0;
        $long_service_days = 0.0;

        // 1. 1년 미만 입사자 (입사 당해년도)
        if ($hire >= $startOfYear && $hire <= $endOfYear) {
            $startMonth = (int)$hire->format('m');
            if ((int)$hire->format('d') > 1) {
                $startMonth++;
            }
            $base_days = (float)max(0, 12 - $startMonth + 1);
        }
        // 2. 1년차 (작년에 입사하여 올해 1년이 되는 경우 - 회계연도 기준)
        else if ($serviceYears == 0) {
            $endOfPreviousYear = new DateTime(($calculationYear - 1) . "-12-31");
            $daysServedLastYear = $endOfPreviousYear->diff($hire)->days + 1;
            $proRatedLeave = 15 * ($daysServedLastYear / 365);
            $base_days = round($proRatedLeave, 1);
        }
        // 3. 2년차 이상
        else {
            $base_days = 15.0;
            $calculated_long_service_days = floor(($serviceYears - 1) / 2);
            $long_service_days = min($calculated_long_service_days, 10.0);
        }

        $total_days = $base_days + $long_service_days;

        return [
            'base_days' => $base_days,
            'long_service_days' => $long_service_days,
            'total_days' => $total_days
        ];
    }

    /**
     * 직원이 휴가(연차, 반차 등) 사용을 신청합니다.
     * 신청 기간의 유효성을 검사하고, 실제 사용 일수를 계산하며, 잔여 연차를 확인한 후 신청을 등록합니다.
     *
     * @param array $data 휴가 신청 데이터 ('start_date', 'end_date', 'leave_type' 등)
     * @param int $employeeId 신청하는 직원의 ID
     * @return array [성공 여부(bool), 메시지(string)]
     */
    public function requestLeave(array $data, int $employeeId): array {
        try {
            if (empty($data['start_date']) || empty($data['end_date'])) {
                return [false, "휴가 기간을 올바르게 입력해주세요."];
            }
            $startDate = new DateTime($data['start_date']);
            $endDate = new DateTime($data['end_date']);
            if ($startDate > $endDate) {
                return [false, "시작일이 종료일보다 늦을 수 없습니다."];
            }

            // 중복 휴가 확인
            if ($this->leaveRepository->findOverlappingLeaves($employeeId, $data['start_date'], $data['end_date'])) {
                return [false, "신청하신 기간에 이미 다른 연차 신청 내역이 존재합니다."];
            }

            $isHalfDay = ($data['leave_type'] ?? '') === '반차';
            $calculatedDays = $this->calculateLeaveDays($data['start_date'], $data['end_date'], $employeeId, $isHalfDay);

            $daysToUse = $calculatedDays;
            $data['leave_type'] = $isHalfDay ? '반차' : ($data['leave_type'] ?? '연차');

            if ($daysToUse <= 0) {
                 return [false, "신청하신 기간은 연차 사용일수에 포함되지 않습니다. (주말 또는 휴일)"];
            }

            if (in_array($data['leave_type'], ['연차', '반차'])) {
                $year = (int)$startDate->format('Y');
                $entitlement = $this->leaveRepository->findEntitlement($employeeId, $year);
                $remaining_days = ($entitlement['total_days'] ?? 0) - ($entitlement['used_days'] ?? 0);

                if ($remaining_days < $daysToUse) {
                    return [false, "잔여 연차가 부족합니다. (신청: {$daysToUse}일, 잔여: {$remaining_days}일)"];
                }
            }

            $data['employee_id'] = $employeeId;
            $data['days_count'] = $daysToUse;

            $leaveId = $this->leaveRepository->create($data);

            return $leaveId ? [true, "연차 신청이 완료되었습니다."] : [false, "신청 처리 중 오류가 발생했습니다."];

        } catch (Exception $e) {
            error_log("Leave request error: " . $e->getMessage());
            return [false, "서버 오류가 발생했습니다: " . $e->getMessage()];
        }
    }

    /**
     * 특정 기간 동안의 실제 휴가 사용 일수를 계산합니다.
     * 주말, 공휴일(연차차감 여부 고려)을 제외하고 계산합니다.
     *
     * @param string $startDateStr 시작일 (Y-m-d 형식)
     * @param string $endDateStr 종료일 (Y-m-d 형식)
     * @param int $employeeId 직원 ID
     * @param bool $isHalfDay 반차 신청 여부
     * @return float 계산된 휴가 일수 (e.g., 2.0, 0.5)
     * @throws Exception 반차 신청 기간이 하루가 아닐 경우
     */
    public function calculateLeaveDays(string $startDateStr, string $endDateStr, int $employeeId, bool $isHalfDay = false): float {
        if ($isHalfDay) {
            if ($startDateStr !== $endDateStr) {
                throw new Exception("반차는 하루만 선택 가능합니다.");
            }
            $leaveDays = $this->calculateLeaveDays($startDateStr, $endDateStr, $employeeId, false);
            return $leaveDays < 1 ? 0 : 0.5;
        }

        $employee = $this->employeeRepository->findById($employeeId);
        $departmentId = $employee['department_id'] ?? null;

        $holidaysRaw = $this->holidayService->getHolidaysForDateRange($startDateStr, $endDateStr, $departmentId);
        $holidays = [];
        foreach($holidaysRaw as $h) {
            if(!isset($holidays[$h['date']]) || $h['department_id'] !== null) {
                $holidays[$h['date']] = $h;
            }
        }

        $period = new DatePeriod(new DateTime($startDateStr), new DateInterval('P1D'), (new DateTime($endDateStr))->add(new DateInterval('P1D')));
        $leaveDaysCount = 0;

        foreach ($period as $date) {
            $isWorkDay = ((int)$date->format('N') < 6); // 월~금 = true
            $holidayInfo = $holidays[$date->format('Y-m-d')] ?? null;

            if ($holidayInfo) {
                if ($holidayInfo['type'] === 'workday') {
                    $isWorkDay = true;
                } elseif ($holidayInfo['type'] === 'holiday' && $holidayInfo['deduct_leave'] == 0) {
                    $isWorkDay = false;
                }
            }

            if ($isWorkDay) {
                $leaveDaysCount++;
            }
        }

        return (float)$leaveDaysCount;
    }

    /**
     * 관리자가 직원의 휴가 신청을 승인합니다.
     * 신청 상태를 'approved'로 변경하고, 사용자의 사용 연차 일수를 업데이트합니다.
     *
     * @param int $leaveId 승인할 휴가 신청의 ID
     * @param int $adminUserId 승인 작업을 수행한 관리자의 ID
     * @return array [성공 여부(bool), 메시지(string)]
     */
    public function approveRequest(int $leaveId, int $adminUserId): array {
        $leave = $this->leaveRepository->findById($leaveId);
        if (!$leave || $leave['status'] !== '대기') {
            return [false, "승인할 수 없는 신청 건입니다."];
        }

        $year = (int)(new DateTime($leave['start_date']))->format('Y');

        $updatedStatus = $this->leaveRepository->updateStatus($leaveId, '승인', $adminUserId, null);
        if ($updatedStatus) {
            $updatedDays = $this->leaveRepository->updateUsedDays($leave['employee_id'], $year, $leave['days_count']);
            if ($updatedDays) {
                return [true, "연차 신청이 승인되었습니다."];
            }
        }
        return [false, "처리 중 오류가 발생했습니다."];
    }

    /**
     * 관리자가 직원의 휴가 신청을 반려합니다.
     *
     * @param int $leaveId 반려할 휴가 신청의 ID
     * @param int $adminUserId 반려 작업을 수행한 관리자의 ID
     * @param string $reason 반려 사유
     * @return array [성공 여부(bool), 메시지(string)]
     */
    public function rejectRequest(int $leaveId, int $adminUserId, string $reason): array {
        $leave = $this->leaveRepository->findById($leaveId);
        if (!$leave || $leave['status'] !== '대기') {
            return [false, "반려할 수 없는 신청 건입니다."];
        }

        if ($this->leaveRepository->updateStatus($leaveId, '반려', $adminUserId, $reason)) {
            return [true, "연차 신청을 반려 처리했습니다."];
        }
        return [false, "처리 중 오류가 발생했습니다."];
    }

    /**
     * 직원이 자신의 휴가 신청을 취소합니다.
     * 'pending' 상태는 바로 취소됩니다.
     * 'approved' 상태는 'cancellation_requested' 상태로 변경되고 관리자 승인을 대기합니다.
     *
     * @param int $leaveId 취소할 휴가 신청의 ID
     * @param int $employeeId 신청을 취소하는 직원의 ID
     * @param string|null $reason 승인된 휴가 취소 시 사유
     * @return array [성공 여부(bool), 메시지(string)]
     */
    public function cancelRequest(int $leaveId, int $employeeId, ?string $reason = null): array {
        $leave = $this->leaveRepository->findById($leaveId);
        if (!$leave || $leave['employee_id'] != $employeeId) {
            return [false, "권한이 없습니다."];
        }

        switch ($leave['status']) {
            case '대기':
                if ($this->leaveRepository->updateStatus($leaveId, '취소', null, "사용자 취소")) {
                    return [true, "신청이 취소되었습니다."];
                }
                break;
            
            case '승인':
                if (empty($reason)) {
                    return [false, "승인된 연차를 취소하려면 사유를 반드시 입력해야 합니다."];
                }
                if ($this->leaveRepository->updateStatus($leaveId, '취소요청', null, $reason)) {
                    return [true, "연차 취소 요청이 완료되었습니다. 관리자 승인 후 최종 처리됩니다."];
                }
                break;

            default:
                return [false, "이미 처리되었거나 취소된 신청은 변경할 수 없습니다."];
        }
        
        return [false, "처리 중 오류가 발생했습니다."];
    }

    /**
     * 모든 활성 직원에 대해 연차를 일괄적으로 계산하고 부여합니다.
     *
     * @param int $year 연차를 부여할 기준 연도
     * @return array [성공 여부(bool), 결과 메시지(string)]
     */
    public function grantAnnualLeaveForAllEmployees(int $year): array
    {
        $employees = $this->employeeRepository->findAllActive();
        $total_count = count($employees);
        $success_count = 0;
        $failed_count = 0;
        $errors = [];

        foreach ($employees as $employee) {
            try {
                $this->grantCalculatedAnnualLeave($employee['id'], $year);
                $success_count++;
            } catch (Exception $e) {
                $failed_count++;
                $errors[] = "{$employee['name']}: " . $e->getMessage();
            }
        }

        $message = "총 {$total_count}명 중 {$success_count}명에게 연차 부여를 완료했습니다.";
        if ($failed_count > 0) {
            $message .= "\n{$failed_count}명 실패. 오류: " . implode(', ', $errors);
            return [false, $message];
        }

        return [true, $message];
    }

    /**
     * 관리자가 특정 직원의 부여된 연차를 수동으로 조정하고, 조정 내역을 로그로 기록합니다.
     *
     * @param int $employeeId 조정할 직원의 ID
     * @param int $year 조정할 연도
     * @param float $adjustedDays 조정할 일수 (양수: 추가, 음수: 차감)
     * @param string $reason 조정 사유
     * @param int $adminId 조정을 수행한 관리자의 ID
     * @return bool 조정 성공 여부
     * @throws Exception 조정 사유가 없거나 조정 일수가 0일 경우
     */
    public function adjustLeaveEntitlement(int $employeeId, int $year, float $adjustedDays, string $reason, int $adminId): bool
    {
        if (empty($reason)) {
            throw new Exception("조정 사유는 필수입니다.");
        }
        if ($adjustedDays == 0) {
            throw new Exception("조정할 일수가 0일 수 없습니다.");
        }

        $success = $this->leaveRepository->adjustEntitlement($employeeId, $year, $adjustedDays);
        if ($success) {
            $this->leaveRepository->logAdjustment($employeeId, $year, $adjustedDays, $reason, $adminId);
        }
        return $success;
    }

    /**
     * 관리자가 직원의 연차 취소 요청을 승인합니다.
     *
     * @param int $leaveId 취소 요청을 승인할 휴가 ID
     * @param int $adminUserId 승인 작업을 수행한 관리자 ID
     * @return array [성공 여부(bool), 메시지(string)]
     */
    public function approveCancellation(int $leaveId, int $adminUserId): array {
        $leave = $this->leaveRepository->findById($leaveId);
        if (!$leave || $leave['status'] !== '취소요청') {
            return [false, "승인할 수 없는 취소 요청입니다."];
        }

        $year = (int)(new DateTime($leave['start_date']))->format('Y');
        
        // 1. 연차 상태를 'cancelled'로 변경
        $reason = "관리자 승인에 의한 취소";
        if ($this->leaveRepository->updateStatus($leaveId, '취소', $adminUserId, $reason)) {
            // 2. 사용했던 연차 일수 복원
            $this->leaveRepository->updateUsedDays($leave['employee_id'], $year, -$leave['days_count']);
            return [true, "연차 취소 요청이 승인되었습니다."];
        }
        
        return [false, "처리 중 오류가 발생했습니다."];
    }
    
    /**
     * 관리자가 직원의 연차 취소 요청을 반려합니다.
     *
     * @param int $leaveId 취소 요청을 반려할 휴가 ID
     * @param int $adminUserId 반려 작업을 수행한 관리자 ID
     * @param string $reason 반려 사유
     * @return array [성공 여부(bool), 메시지(string)]
     */
    public function rejectCancellation(int $leaveId, int $adminUserId, string $reason): array {
        $leave = $this->leaveRepository->findById($leaveId);
        if (!$leave || $leave['status'] !== '취소요청') {
            return [false, "반려할 수 없는 취소 요청입니다."];
        }

        // 상태를 다시 'approved'로 변경하고 반려 사유를 기록
        if ($this->leaveRepository->updateStatus($leaveId, '승인', $adminUserId, $reason)) {
            return [true, "연차 취소 요청을 반려 처리했습니다."];
        }
        
        return [false, "처리 중 오류가 발생했습니다."];
    }

    /**
     * 직원의 종합적인 휴가 통계를 가져오는 향상된 메소드
     * 
     * @param int $employeeId 직원 ID
     * @param int $year 대상 연도
     * @return array 종합 휴가 통계
     */
    public function getEmployeeLeaveStatistics(int $employeeId, int $year): array
    {
        $entitlement = $this->leaveRepository->findEntitlement($employeeId, $year);
        $leaveHistory = $this->leaveRepository->findByEmployeeId($employeeId, ['year' => $year]);
        
        $stats = [
            'total_days' => $entitlement['total_days'] ?? 0,
            'used_days' => $entitlement['used_days'] ?? 0,
            'remaining_days' => ($entitlement['total_days'] ?? 0) - ($entitlement['used_days'] ?? 0),
            'pending_requests' => 0,
            'approved_requests' => 0,
            'rejected_requests' => 0,
            'cancelled_requests' => 0,
            'leave_history' => []
        ];
        
        foreach ($leaveHistory as $leave) {
            switch ($leave['status']) {
                case '대기':
                    $stats['pending_requests']++;
                    break;
                case '승인':
                    $stats['approved_requests']++;
                    break;
                case '반려':
                    $stats['rejected_requests']++;
                    break;
                case '취소':
                    $stats['cancelled_requests']++;
                    break;
            }
            
            $stats['leave_history'][] = [
                'id' => $leave['id'],
                'start_date' => $leave['start_date'],
                'end_date' => $leave['end_date'],
                'days_count' => $leave['days_count'],
                'leave_type' => $leave['leave_type'],
                'status' => $leave['status'],
                'reason' => $leave['reason'],
                'created_at' => $leave['created_at']
            ];
        }
        
        return $stats;
    }

    /**
     * 포괄적인 비즈니스 규칙으로 휴가 요청을 검증하는 향상된 메소드
     * 
     * @param array $data 휴가 요청 데이터
     * @param int $employeeId 직원 ID
     * @return array [유효성 여부, 오류 메시지 배열]
     */
    public function validateLeaveRequest(array $data, int $employeeId): array
    {
        $errors = [];
        
        // 기본 유효성 검사
        if (empty($data['start_date']) || empty($data['end_date'])) {
            $errors[] = "시작일과 종료일을 모두 입력해주세요.";
        }
        
        if (empty($data['leave_type'])) {
            $errors[] = "휴가 종류를 선택해주세요.";
        }
        
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $startDate = new DateTime($data['start_date']);
            $endDate = new DateTime($data['end_date']);
            
            // 날짜 유효성 검사
            if ($startDate > $endDate) {
                $errors[] = "시작일이 종료일보다 늦을 수 없습니다.";
            }
            
            // 과거 날짜 유효성 검사
            $today = new DateTime();
            if ($startDate < $today->setTime(0, 0, 0)) {
                $errors[] = "과거 날짜로는 연차를 신청할 수 없습니다.";
            }
            
            // 미래 제한 유효성 검사 (예: 1년 이상 미리 요청할 수 없음)
            $maxFutureDate = (new DateTime())->add(new DateInterval('P1Y'));
            if ($startDate > $maxFutureDate) {
                $errors[] = "1년 이후의 날짜로는 연차를 신청할 수 없습니다.";
            }
            
            // 중복 휴가 확인
            if ($this->leaveRepository->findOverlappingLeaves($employeeId, $data['start_date'], $data['end_date'])) {
                $errors[] = "신청하신 기간에 이미 다른 연차 신청 내역이 존재합니다.";
            }
            
            // 반차 유효성 검사
            if (($data['leave_type'] ?? '') === '반차' && $data['start_date'] !== $data['end_date']) {
                $errors[] = "반차는 하루만 선택 가능합니다.";
            }
        }
        
        // 직원 유효성 검사
        $employee = $this->employeeRepository->findById($employeeId);
        if (!$employee) {
            $errors[] = "직원 정보를 찾을 수 없습니다.";
        } elseif ($employee['status'] !== '활성') {
            $errors[] = "비활성 상태의 직원은 연차를 신청할 수 없습니다.";
        }
        
        return [empty($errors), $errors];
    }

    /**
     * 부서 수준의 가시성을 적용하여 필터가 있는 휴가 기록을 가져옵니다.
     *
     * @param array $filters
     * @return array
     */
    public function getLeaveHistory(array $filters = []): array
    {
        $visibleDeptIds = $this->organizationService->getVisibleDepartmentIdsForCurrentUser();
        return $this->leaveRepository->findAll($filters, $visibleDeptIds);
    }

    /**
     * 부서 수준의 가시성이 적용된 보류 중인 휴가 요청을 가져옵니다.
     * @return array
     */
    public function getPendingLeaveRequests(): array
    {
        $visibleDeptIds = $this->organizationService->getVisibleDepartmentIdsForCurrentUser();
        return $this->leaveRepository->findByStatus('대기', [], $visibleDeptIds);
    }

    /**
     * 부서 수준의 가시성이 적용된 모든 연차 부여 내역을 가져옵니다.
     * @param array $filters
     * @return array
     */
    public function getAllEntitlements(array $filters = []): array
    {
        $visibleDeptIds = $this->organizationService->getVisibleDepartmentIdsForCurrentUser();
        return $this->leaveRepository->getAllEntitlements($filters, $visibleDeptIds);
    }

    /**
     * 예측을 포함하여 휴가 잔액을 계산하는 향상된 메소드
     * 
     * @param int $employeeId 직원 ID
     * @param int $year 대상 연도
     * @return array 예측이 포함된 휴가 잔액
     */
    public function calculateLeaveBalance(int $employeeId, int $year): array
    {
        $entitlement = $this->leaveRepository->findEntitlement($employeeId, $year);
        $pendingLeaves = $this->leaveRepository->findByStatus('대기', ['employee_id' => $employeeId, 'year' => $year]);
        
        $totalDays = $entitlement['total_days'] ?? 0;
        $usedDays = $entitlement['used_days'] ?? 0;
        $pendingDays = array_sum(array_column($pendingLeaves, 'days_count'));
        
        return [
            'total_days' => $totalDays,
            'used_days' => $usedDays,
            'pending_days' => $pendingDays,
            'available_days' => $totalDays - $usedDays - $pendingDays,
            'remaining_days' => $totalDays - $usedDays,
            'projected_remaining' => $totalDays - $usedDays - $pendingDays
        ];
    }
}
