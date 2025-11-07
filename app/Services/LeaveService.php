<?php
// app/Services/LeaveService.php
namespace App\Services;

use App\Repositories\LeaveRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\DepartmentRepository;
use App\Services\HolidayService;
use App\Services\DataScopeService;
use DateTime;
use DatePeriod;
use DateInterval;
use Exception;

/**
 * 로그 기반 연차 관리 시스템의 일반 사용자 비즈니스 로직을 처리하는 서비스 클래스입니다.
 * 
 * 주요 기능:
 * - 연차 계산 엔진 (중도입사자 월차, 비례연차, 근속연차)
 * - 로그 기반 연차 변동 관리 (부여, 사용, 조정, 소멸)
 * - 잔여 연차 실시간 계산
 * - 연차 신청 및 취소
 * - 본인 연차 통계 조회
 */
class LeaveService {
    private LeaveRepository $leaveRepository;
    private EmployeeRepository $employeeRepository;
    private DepartmentRepository $departmentRepository;
    private HolidayService $holidayService;
    private DataScopeService $dataScopeService;

    public function __construct(
        LeaveRepository $leaveRepository,
        EmployeeRepository $employeeRepository,
        DepartmentRepository $departmentRepository,
        HolidayService $holidayService,
        DataScopeService $dataScopeService
    ) {
        $this->leaveRepository = $leaveRepository;
        $this->employeeRepository = $employeeRepository;
        $this->departmentRepository = $departmentRepository;
        $this->holidayService = $holidayService;
        $this->dataScopeService = $dataScopeService;
    }

    // ===================================================================
    // 연차 계산 엔진 (Calculation Engine)
    // ===================================================================

    /**
     * 입사 첫 해 월차 계산 (일괄 부여)
     * 입사일 기준으로 그해 입사일 다음달부터 12월까지 월차 부여
     * 예: 2024.08.27 입사 → 2024.9.27, 10.27, 11.27, 12.27 = 4개월
     * 
     * @param DateTime $hireDate 입사일
     * @param int $year 계산 연도
     * @return float 월차 일수
     */
    public function calculateFirstYearMonthlyLeave(DateTime $hireDate, int $year): float {
        $hireMonth = (int)$hireDate->format('m');
        
        // 입사월 다음달부터 12월까지 계산
        $startMonth = $hireMonth + 1;
        
        if ($startMonth > 12) {
            return 0; // 12월 입사는 그해 월차 없음
        }
        
        // 다음달부터 12월까지의 개월 수
        $monthlyDays = 12 - $startMonth + 1;
        
        return (float)max(0, $monthlyDays);
    }

    /**
     * 비례 연차 계산 (2년차용)
     * 공식: (입사년도 재직일수 ÷ 365일 또는 366일) × 15일
     * 
     * @param DateTime $hireDate 입사일
     * @param int $targetYear 계산 대상 연도 (2년차)
     * @return float 비례연차 일수
     */
    public function calculateProportionalAnnualLeave(DateTime $hireDate, int $targetYear): float {
        $hireYear = (int)$hireDate->format('Y');
        $previousYear = $targetYear - 1;
        
        // 입사년도와 전년도가 다르면 0
        if ($hireYear !== $previousYear) {
            return 0.0;
        }
        
        // 입사년도의 총 일수 (윤년 고려)
        $totalDays = (date('L', mktime(0, 0, 0, 1, 1, $hireYear))) ? 366 : 365;
        
        // 입사일부터 연말까지의 일수 계산
        $yearEnd = new DateTime("{$hireYear}-12-31");
        $workDays = $yearEnd->diff($hireDate)->days + 1;
        
        // 비례 연차 계산
        $proportionalDays = round(($workDays / $totalDays) * 15, 1);
        
        return $proportionalDays;
    }

    /**
     * 2년차 월차 계산 (일괄 부여)
     * 2년차에는 1월부터 입사월 전까지 월차 부여
     * 예: 8월 27일 입사 → 2025.1.27 ~ 7.27 = 7개월 → 7일 일괄 부여
     * 
     * @param DateTime $hireDate 입사일
     * @param int $targetYear 계산 대상 연도 (2년차)
     * @return float 2년차 월차 일수
     */
    public function calculateSecondYearMonthlyLeave(DateTime $hireDate, int $targetYear): float {
        $hireMonth = (int)$hireDate->format('m');
        
        // 1월부터 입사월 전까지의 개월 수
        $monthsInYear = $hireMonth - 1;
        
        return (float)max(0, $monthsInYear);
    }

    /**
     * 잔여 연차/월차 실시간 계산
     * 로그 기반으로 현재 잔여량을 계산
     * 
     * @param int $employeeId 직원 ID
     * @param string $leaveType 연차 구분 (annual: 연차, monthly: 월차, all: 전체)
     * @return float 현재 잔여량
     */
    public function calculateCurrentBalance(int $employeeId, string $leaveType = 'all'): float {
        $logs = $this->leaveRepository->getBalanceLogs($employeeId, $leaveType);
        
        $balance = 0.0;
        foreach ($logs as $log) {
            // transaction_type이 있으면 더 정확한 계산 가능 (한글 코드값)
            if (!empty($log['transaction_type'])) {
                switch ($log['transaction_type']) {
                    case '초기부여':
                    case '연차부여':
                    case '근속연차부여':
                    case '월차부여':
                    case '연차추가':
                    case '사용취소':
                        $balance += $log['amount'];
                        break;
                    case '연차사용':
                    case '연차소멸':
                    case '연차차감':
                        $balance -= abs($log['amount']);
                        break;
                    case '연차조정':
                        // amount의 부호로 판단
                        $balance += $log['amount'];
                        break;
                }
            } else {
                // 기존 log_type 기반 계산 (한글 코드값)
                switch ($log['log_type']) {
                    case '부여':
                    case '취소':
                        $balance += $log['amount'];
                        break;
                    case '조정':
                        // amount의 부호로 판단
                        $balance += $log['amount'];
                        break;
                    case '사용':
                    case '소멸':
                        $balance -= abs($log['amount']);
                        break;
                }
            }
        }
        
        return round($balance, 2);
    }

    /**
     * 연차 잔여량 계산
     * 
     * @param int $employeeId 직원 ID
     * @return float 연차 잔여량
     */
    public function calculateAnnualBalance(int $employeeId): float {
        return $this->calculateCurrentBalance($employeeId, 'annual');
    }

    /**
     * 월차 잔여량 계산
     * 
     * @param int $employeeId 직원 ID
     * @return float 월차 잔여량
     */
    public function calculateMonthlyBalance(int $employeeId): float {
        return $this->calculateCurrentBalance($employeeId, 'monthly');
    }

    // ===================================================================
    // 로그 관리 기능 (Log Management)
    // ===================================================================

    /**
     * 연차/월차 변동 로그 생성
     * 모든 연차/월차 변동사항을 추적 가능한 로그로 기록
     * 
     * @param int $employeeId 직원 ID
     * @param string $leaveType 연차 구분 (연차, 월차)
     * @param string $transactionType 거래 유형 (초기부여, 연차부여, 연차사용, 사용취소, 연차추가, 연차차감, 연차소멸)
     * @param float $amount 변동량 (양수/음수)
     * @param string|null $reason 사유
     * @param int|null $leaveRequestId 연차 신청 ID (신청 관련 시)
     * @param int $actorEmployeeId 처리자 직원 ID
     * @param int|null $grantYear 연차 부여연도 (부여 시 필수)
     * @return void
     * @throws Exception 로그 생성 실패 시
     */
    public function createLog(int $employeeId, string $leaveType, string $transactionType, float $amount, ?string $reason = null, ?int $leaveRequestId = null, int $actorEmployeeId = 1, ?int $grantYear = null): void {
        // Calculate balance after this transaction
        $currentBalance = $this->calculateCurrentBalance($employeeId, 'all');
        $balanceAfter = $currentBalance;
        
        // Adjust balance based on transaction type
        switch ($transactionType) {
            case '초기부여':
            case '연차부여':
            case '근속연차부여':
            case '월차부여':
            case '연차추가':
            case '사용취소':
                $balanceAfter = $currentBalance + $amount;
                break;
            case '연차사용':
            case '연차소멸':
            case '연차차감':
                $balanceAfter = $currentBalance - abs($amount);
                break;
        }
        
        $logData = [
            'employee_id' => $employeeId,
            'leave_type' => $leaveType,
            'grant_year' => $grantYear,
            'transaction_type' => $transactionType,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'reason' => $reason,
            'reference_id' => $leaveRequestId,
            'created_by' => $actorEmployeeId
        ];

        if (!$this->leaveRepository->createLog($logData)) {
            throw new Exception("연차 로그 생성에 실패했습니다.");
        }
    }

    // ===================================================================
    // 연차 신청 관리 기능 (Application Management)
    // ===================================================================

    /**
     * 연차 신청 (전일/반차)
     * 
     * @param int $employeeId 신청자 ID
     * @param DateTime $startDate 시작일
     * @param DateTime $endDate 종료일
     * @param float $days 신청 일수
     * @param string $type 신청 타입 (전일/반차)
     * @param string|null $reason 신청 사유
     * @return int 신청 ID
     * @throws Exception 신청 실패 시
     */
    public function applyLeave(int $employeeId, DateTime $startDate, DateTime $endDate, float $days, string $type = '전일', ?string $reason = null): int {
        // 잔여량 검증
        if (!$this->validateLeaveBalance($employeeId, $days)) {
            throw new Exception("잔여 연차가 부족합니다.");
        }

        // 중복 신청 방지
        if ($this->leaveRepository->findOverlappingApplications($employeeId, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'))) {
            throw new Exception("신청하신 기간에 이미 다른 연차 신청 내역이 존재합니다.");
        }

        // 반차 검증
        if ($type === '반차' && $startDate->format('Y-m-d') !== $endDate->format('Y-m-d')) {
            throw new Exception("반차는 하루만 선택 가능합니다.");
        }

        $applicationData = [
            'employee_id' => $employeeId,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'days' => $days,
            'leave_type' => '연차',
            'day_type' => $type,
            'reason' => $reason,
            'status' => '대기'
        ];

        return (int)$this->leaveRepository->saveApplication($applicationData);
    }

    /**
     * 신청 승인/반려
     * 
     * @param int $applicationId 신청 ID
     * @param bool $approved 승인 여부
     * @param int $approverId 승인자 ID
     * @param string|null $reason 승인/반려 사유
     * @return void
     * @throws Exception 처리 실패 시
     */
    public function approveApplication(int $applicationId, bool $approved, int $approverId, ?string $reason = null): void {
        $application = $this->leaveRepository->getApplicationById($applicationId);
        if (!$application || $application['status'] !== '대기') {
            throw new Exception("승인할 수 없는 신청 건입니다.");
        }

        $status = $approved ? '승인' : '반려';
        
        // 신청 상태 업데이트
        $this->leaveRepository->updateApplicationStatus($applicationId, $status, $approverId, $reason);

        // 승인된 경우 사용 로그 생성
        if ($approved) {
            $this->createLog(
                $application['employee_id'],
                $application['leave_type'] === '연차' ? '연차' : '월차',
                '연차사용',
                $application['days'],
                "연차 사용 승인 (신청 ID: {$applicationId})",
                $applicationId,
                $approverId
            );
        }
    }

    /**
     * 신청 취소 (승인 전)
     * 
     * @param int $applicationId 신청 ID
     * @param int $employeeId 신청자 ID
     * @return void
     * @throws Exception 취소 실패 시
     */
    public function cancelApplication(int $applicationId, int $employeeId): void {
        $application = $this->leaveRepository->getApplicationById($applicationId);
        if (!$application || $application['employee_id'] != $employeeId) {
            throw new Exception("권한이 없습니다.");
        }

        if ($application['status'] !== '대기') {
            throw new Exception("대기 중인 신청만 취소할 수 있습니다.");
        }

        $this->leaveRepository->updateApplicationStatus($applicationId, '취소', null, "신청자 취소");
    }

    /**
     * 승인된 연차 취소 신청
     * 
     * @param int $applicationId 원본 신청 ID
     * @param int $employeeId 신청자 ID
     * @param string $reason 취소 사유
     * @return int 취소 신청 ID
     * @throws Exception 취소 신청 실패 시
     */
    public function requestCancellation(int $applicationId, int $employeeId, string $reason): int {
        $application = $this->leaveRepository->getApplicationById($applicationId);
        if (!$application || $application['employee_id'] != $employeeId) {
            throw new Exception("권한이 없습니다.");
        }

        if ($application['status'] !== '승인') {
            throw new Exception("승인된 연차만 취소 신청할 수 있습니다.");
        }

        if (empty($reason)) {
            throw new Exception("취소 사유는 필수입니다.");
        }

        $cancellationData = [
            'application_id' => $applicationId,
            'employee_id' => $employeeId,
            'reason' => $reason,
            'status' => '대기'
        ];

        return (int)$this->leaveRepository->saveCancellationRequest($cancellationData);
    }

    /**
     * 취소 신청 승인/반려
     * 
     * @param int $cancellationId 취소 신청 ID
     * @param bool $approved 승인 여부
     * @param int $approverId 승인자 ID
     * @param string|null $reason 승인/반려 사유
     * @return void
     * @throws Exception 처리 실패 시
     */
    public function approveCancellation(int $cancellationId, bool $approved, int $approverId, ?string $reason = null): void {
        $cancellation = $this->leaveRepository->getCancellationById($cancellationId);
        if (!$cancellation || $cancellation['status'] !== '대기') {
            throw new Exception("처리할 수 없는 취소 신청입니다.");
        }

        $application = $this->leaveRepository->getApplicationById($cancellation['application_id']);
        if (!$application) {
            throw new Exception("원본 신청을 찾을 수 없습니다.");
        }

        $status = $approved ? '승인' : '반려';
        
        // 취소 신청 상태 업데이트
        $this->leaveRepository->updateCancellationStatus($cancellationId, $status, $approverId, $reason);

        if ($approved) {
            // 원본 신청을 취소 상태로 변경
            $this->leaveRepository->updateApplicationStatus($cancellation['application_id'], '취소', $approverId, "취소 승인");
            
            // 사용 취소 로그 생성 (잔여량 복원)
            $this->createLog(
                $application['employee_id'],
                $application['leave_type'] === '연차' ? '연차' : '월차',
                '사용취소',
                $application['days'],
                "연차 사용 취소 승인 (취소 신청 ID: {$cancellationId})",
                $cancellationId,
                $approverId
            );
        }
    }

    /**
     * 잔여량 검증
     * 
     * @param int $employeeId 직원 ID
     * @param float $requestDays 신청 일수
     * @return bool 신청 가능 여부
     */
    public function validateLeaveBalance(int $employeeId, float $requestDays): bool {
        $currentBalance = $this->calculateCurrentBalance($employeeId, 'all');
        
        // 승인 대기 중인 신청 일수도 고려
        $pendingDays = $this->leaveRepository->getPendingApplicationDays($employeeId);
        
        $availableBalance = $currentBalance - $pendingDays;
        
        return $availableBalance >= $requestDays;
    }

    // ===================================================================
    // 유틸리티 메서드 (Utility Methods)
    // ===================================================================

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

    // ===================================================================
    // 조회 및 통계 메서드 (Query and Statistics Methods) - 본인 조회용
    // ===================================================================

    /**
     * 직원의 종합적인 연차 통계 조회 (본인 조회용)
     * 
     * @param int $employeeId 직원 ID
     * @param int $year 대상 연도
     * @return array 종합 연차 통계
     */
    public function getEmployeeLeaveStatistics(int $employeeId, int $year): array {
        $currentBalance = $this->calculateCurrentBalance($employeeId, 'all');
        $applications = $this->leaveRepository->getEmployeeApplications($employeeId, ['year' => $year]);
        
        $stats = [
            'current_balance' => $currentBalance,
            'pending_requests' => 0,
            'approved_requests' => 0,
            'rejected_requests' => 0,
            'cancelled_requests' => 0,
            'used_days_this_year' => 0,
            'application_history' => []
        ];
        
        foreach ($applications as $application) {
            switch ($application['status']) {
                case 'PENDING':
                case '대기':
                    $stats['pending_requests']++;
                    break;
                case 'APPROVED':
                case '승인':
                    $stats['approved_requests']++;
                    $stats['used_days_this_year'] += $application['days'];
                    break;
                case 'REJECTED':
                case '반려':
                    $stats['rejected_requests']++;
                    break;
                case 'CANCELLED':
                case '취소':
                    $stats['cancelled_requests']++;
                    break;
            }
            
            $stats['application_history'][] = $application;
        }
        
        return $stats;
    }

    /**
     * 현재 연차 잔여량 조회 (본인 조회용)
     * 
     * @param int $employeeId 직원 ID
     * @param int|null $year 대상 연도 (null이면 현재 연도) - 현재는 사용하지 않음
     * @return float 현재 잔여량
     */
    public function getCurrentBalance(int $employeeId, int $year = null): float {
        return $this->calculateCurrentBalance($employeeId, 'all');
    }

}
