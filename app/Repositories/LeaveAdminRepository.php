<?php
// app/Repositories/LeaveAdminRepository.php
namespace App\Repositories;

use App\Core\Database;
use App\Services\DataScopeService;

/**
 * 연차 관리 시스템의 관리자 전용 데이터 접근 계층
 * 팀별 현황 조회, 승인 대기 목록, 처리 완료 요청 등 관리자 기능을 담당합니다.
 */
class LeaveAdminRepository {
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService) {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    // =================================================================
    // 현황 조회 (Status Queries) 관련 메소드 - 데이터 스코프 적용
    // =================================================================

    /**
     * 팀별 연차 현황 조회 (데이터 스코프 적용)
     * 
     * @param int|null $departmentId 부서 ID
     * @return array 팀별 현황
     */
    public function getTeamLeaveStatus(int $departmentId = null): array {
        $queryParts = [
            'sql' => "SELECT 
                        e.id as employee_id,
                        e.name as employee_name,
                        e.hire_date,
                        d.name as department_name,
                        p.name as position_name,
                        v.current_balance,
                        v.granted_days,
                        v.pending_applications,
                        v.used_days_this_year,
                        v.remaining_days
                      FROM hr_employees e
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_positions p ON e.position_id = p.id
                      LEFT JOIN v_employee_leave_status v ON e.id = v.employee_id",
            'params' => [],
            'where' => ["e.termination_date IS NULL"]
        ];

        if ($departmentId !== null) {
            $queryParts['where'][] = "e.department_id = :department_id";
            $queryParts['params'][':department_id'] = $departmentId;
        }

        // 데이터 스코프 적용
        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY d.name, e.name";

        $teamStatus = $this->db->query($queryParts['sql'], $queryParts['params']);
        
        // 각 직원의 이번 달 계획 추가
        $currentMonth = date('Y-m');
        foreach ($teamStatus as &$member) {
            $member['monthly_plans'] = $this->getEmployeeMonthlyPlans($member['employee_id'], $currentMonth);
        }
        
        return $teamStatus;
    }
    
    /**
     * 직원의 월별 연차 계획 조회
     * 
     * @param int $employeeId 직원 ID
     * @param string $month 월 (Y-m 형식)
     * @return array 월별 계획 목록
     */
    private function getEmployeeMonthlyPlans(int $employeeId, string $month): array {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $sql = "SELECT 
                    start_date,
                    end_date,
                    days,
                    day_type,
                    status
                FROM hr_leave_applications
                WHERE employee_id = :employee_id
                  AND status IN ('대기', '승인')
                  AND start_date >= :start_date
                  AND start_date <= :end_date
                ORDER BY start_date";
        
        return $this->db->query($sql, [
            ':employee_id' => $employeeId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
    }

    /**
     * 승인 대기 목록 조회 (데이터 스코프 적용)
     * 
     * @param int|null $departmentId 부서 ID
     * @return array 승인 대기 목록
     */
    public function getPendingApplications(int $departmentId = null): array {
        $queryParts = [
            'sql' => "SELECT 
                        la.*,
                        e.name as employee_name,
                        d.name as department_name,
                        p.name as position_name,
                        COALESCE(v.granted_days, 0) as total_days,
                        COALESCE(v.used_days_this_year, 0) as used_days,
                        COALESCE(v.remaining_days, 0) as remaining_days,
                        COALESCE(v.current_balance, 0) as current_balance
                      FROM hr_leave_applications la
                      JOIN hr_employees e ON la.employee_id = e.id
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_positions p ON e.position_id = p.id
                      LEFT JOIN v_employee_leave_status v ON la.employee_id = v.employee_id",
            'params' => [],
            'where' => ["la.status = '대기'"]
        ];

        if ($departmentId !== null) {
            $queryParts['where'][] = "e.department_id = :department_id";
            $queryParts['params'][':department_id'] = $departmentId;
        }

        // 데이터 스코프 적용
        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY la.created_at ASC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 취소 신청 대기 목록 조회 (데이터 스코프 적용)
     * 
     * @param int|null $departmentId 부서 ID
     * @return array 취소 신청 대기 목록
     */
    public function getPendingCancellations(int $departmentId = null): array {
        $queryParts = [
            'sql' => "SELECT 
                        lc.*,
                        la.start_date,
                        la.end_date,
                        la.days,
                        e.name as employee_name,
                        d.name as department_name
                      FROM hr_leave_cancellations lc
                      JOIN hr_leave_applications la ON lc.application_id = la.id
                      JOIN hr_employees e ON lc.employee_id = e.id
                      LEFT JOIN hr_departments d ON e.department_id = d.id",
            'params' => [],
            'where' => ["lc.status = '대기'"]
        ];

        if ($departmentId !== null) {
            $queryParts['where'][] = "e.department_id = :department_id";
            $queryParts['params'][':department_id'] = $departmentId;
        }

        // 데이터 스코프 적용
        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY lc.created_at ASC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 연차 미사용자 조회 (데이터 스코프 적용)
     * 
     * @param int|null $departmentId 부서 ID
     * @return array 미사용자 목록
     */
    public function getUnusedLeaveEmployees(int $departmentId = null): array {
        $queryParts = [
            'sql' => "SELECT 
                        e.id as employee_id,
                        e.name as employee_name,
                        d.name as department_name,
                        v.current_balance,
                        v.used_days_this_year
                      FROM hr_employees e
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN v_employee_leave_status v ON e.id = v.employee_id",
            'params' => [],
            'where' => [
                "e.termination_date IS NULL",
                "(v.used_days_this_year = 0 OR v.used_days_this_year IS NULL)"
            ]
        ];

        if ($departmentId !== null) {
            $queryParts['where'][] = "e.department_id = :department_id";
            $queryParts['params'][':department_id'] = $departmentId;
        }

        // 데이터 스코프 적용
        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY d.name, e.name";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 승인된 연차 일정 조회 (팀 캘린더용, 데이터 스코프 적용)
     * 
     * @param string $startDate 시작일
     * @param string $endDate 종료일
     * @param int|null $departmentId 부서 ID
     * @return array 승인된 연차 목록
     */
    public function getApprovedLeavesByDateRange(string $startDate, string $endDate, int $departmentId = null): array {
        $queryParts = [
            'sql' => "SELECT 
                        la.id,
                        la.start_date,
                        la.end_date,
                        la.days,
                        la.day_type,
                        e.id as employee_id,
                        e.name as employee_name,
                        d.name as department_name,
                        p.name as position_name
                      FROM hr_leave_applications la
                      JOIN hr_employees e ON la.employee_id = e.id
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_positions p ON e.position_id = p.id",
            'params' => [
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ],
            'where' => [
                "la.status = '승인'",
                "la.start_date <= :end_date",
                "la.end_date >= :start_date"
            ]
        ];

        if ($departmentId !== null) {
            $queryParts['where'][] = "e.department_id = :department_id";
            $queryParts['params'][':department_id'] = $departmentId;
        }

        // 데이터 스코프 적용
        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY la.start_date, e.name";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 처리 완료된 요청 목록 조회 (연차 신청 + 취소 신청)
     * 
     * @param array $filters 필터 조건
     * @return array 처리 완료된 요청 목록
     */
    public function getProcessedRequests(array $filters = []): array {
        $results = [];
        
        // 1. 연차 신청 조회
        $leaveQueryParts = [
            'sql' => "SELECT 
                        la.id,
                        la.employee_id,
                        la.start_date,
                        la.end_date,
                        la.days,
                        la.leave_type,
                        la.day_type,
                        la.status,
                        la.reason,
                        la.approver_id,
                        la.approved_at as processed_at,
                        la.created_at,
                        e.name as employee_name,
                        d.name as department_name,
                        p.name as position_name,
                        approver.name as approver_name,
                        'leave_request' as request_type
                      FROM hr_leave_applications la
                      JOIN hr_employees e ON la.employee_id = e.id
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_positions p ON e.position_id = p.id
                      LEFT JOIN hr_employees approver ON la.approver_id = approver.id",
            'params' => [],
            'where' => ["la.status IN ('승인', '반려')"]
        ];
        
        // 필터 적용
        if (!empty($filters['department_id'])) {
            $leaveQueryParts['where'][] = "e.department_id = :department_id";
            $leaveQueryParts['params'][':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['type_filter'])) {
            if ($filters['type_filter'] === 'approved') {
                $leaveQueryParts['where'][] = "la.status = '승인'";
            } elseif ($filters['type_filter'] === 'rejected') {
                $leaveQueryParts['where'][] = "la.status = '반려'";
            }
        }
        
        if (!empty($filters['date_from'])) {
            $leaveQueryParts['where'][] = "DATE(la.approved_at) >= :date_from";
            $leaveQueryParts['params'][':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $leaveQueryParts['where'][] = "DATE(la.approved_at) <= :date_to";
            $leaveQueryParts['params'][':date_to'] = $filters['date_to'];
        }
        
        // 데이터 스코프 적용
        $leaveQueryParts = $this->dataScopeService->applyEmployeeScope($leaveQueryParts, 'e');

        if (!empty($leaveQueryParts['where'])) {
            $leaveQueryParts['sql'] .= " WHERE " . implode(" AND ", $leaveQueryParts['where']);
        }

        $leaveRequests = $this->db->query($leaveQueryParts['sql'], $leaveQueryParts['params']);
        $results = array_merge($results, $leaveRequests);

        // 2. 취소 신청 조회
        $cancellationQueryParts = [
            'sql' => "SELECT 
                        lc.id,
                        lc.employee_id,
                        la.start_date,
                        la.end_date,
                        la.days,
                        la.leave_type,
                        la.day_type,
                        lc.status,
                        lc.reason,
                        lc.approver_id,
                        lc.approved_at as processed_at,
                        lc.created_at,
                        e.name as employee_name,
                        d.name as department_name,
                        p.name as position_name,
                        approver.name as approver_name,
                        'cancellation_request' as request_type
                      FROM hr_leave_cancellations lc
                      JOIN hr_leave_applications la ON lc.application_id = la.id
                      JOIN hr_employees e ON lc.employee_id = e.id
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_positions p ON e.position_id = p.id
                      LEFT JOIN hr_employees approver ON lc.approver_id = approver.id",
            'params' => [],
            'where' => ["lc.status IN ('승인', '반려')"]
        ];

        // 필터 적용
        if (!empty($filters['department_id'])) {
            $cancellationQueryParts['where'][] = "e.department_id = :department_id";
            $cancellationQueryParts['params'][':department_id'] = $filters['department_id'];
        }

        if (!empty($filters['type_filter'])) {
            if ($filters['type_filter'] === 'approved') {
                $cancellationQueryParts['where'][] = "lc.status = '승인'";
            } elseif ($filters['type_filter'] === 'rejected') {
                $cancellationQueryParts['where'][] = "lc.status = '반려'";
            }
        }

        if (!empty($filters['date_from'])) {
            $cancellationQueryParts['where'][] = "DATE(lc.approved_at) >= :date_from";
            $cancellationQueryParts['params'][':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $cancellationQueryParts['where'][] = "DATE(lc.approved_at) <= :date_to";
            $cancellationQueryParts['params'][':date_to'] = $filters['date_to'];
        }

        // 데이터 스코프 적용
        $cancellationQueryParts = $this->dataScopeService->applyEmployeeScope($cancellationQueryParts, 'e');

        if (!empty($cancellationQueryParts['where'])) {
            $cancellationQueryParts['sql'] .= " WHERE " . implode(" AND ", $cancellationQueryParts['where']);
        }

        $cancellationRequests = $this->db->query($cancellationQueryParts['sql'], $cancellationQueryParts['params']);
        $results = array_merge($results, $cancellationRequests);

        // 처리일시 기준 정렬
        usort($results, function($a, $b) {
            return strtotime($b['processed_at']) - strtotime($a['processed_at']);
        });

        return $results;
    }


    /**
     * 연차 조정 내역 조회
     * 
     * @param int $limit 조회 제한 수
     * @return array 조정 내역 목록
     */
    public function getAdjustmentHistory(int $limit = 50): array {
        $queryParts = [
            'sql' => "SELECT 
                        ll.*,
                        e.name as employee_name,
                        d.name as department_name,
                        creator.name as created_by_name
                      FROM hr_leave_logs ll
                      JOIN hr_employees e ON ll.employee_id = e.id
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_employees creator ON ll.created_by = creator.id",
            'params' => [],
            'where' => ["ll.log_type = '조정'"]
        ];

        // 데이터 스코프 적용
        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY ll.created_at DESC LIMIT :limit";
        $queryParts['params'][':limit'] = $limit;

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 날짜 범위별 연차 사용량 조회
     * 
     * @param string $startDate 시작일
     * @param string $endDate 종료일
     * @param int|null $departmentId 부서 ID
     * @return int 사용량
     */
    public function getUsageCountByDateRange(string $startDate, string $endDate, int $departmentId = null): int {
        $queryParts = [
            'sql' => "SELECT COALESCE(SUM(la.days), 0) as usage_count
                      FROM hr_leave_applications la
                      LEFT JOIN hr_employees e ON la.employee_id = e.id",
            'where' => [
                'la.status = :status',
                'la.start_date >= :start_date',
                'la.start_date <= :end_date'
            ],
            'params' => [
                ':status' => '승인',
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]
        ];

        // 데이터 스코프 적용
        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if ($departmentId) {
            $queryParts['where'][] = 'e.department_id = :department_id';
            $queryParts['params'][':department_id'] = $departmentId;
        }

        $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);

        $result = $this->db->fetchOne($queryParts['sql'], $queryParts['params']);
        return (int)$result['usage_count'];
    }

    /**
     * 월별 연차 통계 조회
     * 
     * @param int $year 연도
     * @param int $month 월
     * @param int|null $departmentId 부서 ID
     * @return array 월별 통계
     */
    public function getMonthlyLeaveStats(int $year, int $month, ?int $departmentId = null): array {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $totalLeavesQuery = [
            'sql' => "SELECT COUNT(*) as total_leaves
                      FROM hr_leave_applications la
                      JOIN hr_employees e ON la.employee_id = e.id",
            'where' => [
                'la.status = :status',
                '(la.start_date <= :end_date AND la.end_date >= :start_date)'
            ],
            'params' => [
                ':status' => '승인',
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]
        ];

        if ($departmentId !== null) {
            $totalLeavesQuery['where'][] = 'e.department_id = :department_id';
            $totalLeavesQuery['params'][':department_id'] = $departmentId;
        }

        $totalLeavesQuery = $this->dataScopeService->applyEmployeeScope($totalLeavesQuery, 'e');
        $totalLeavesQuery['sql'] .= " WHERE " . implode(" AND ", $totalLeavesQuery['where']);
        $totalLeavesResult = $this->db->fetchOne($totalLeavesQuery['sql'], $totalLeavesQuery['params']);

        return [
            'total_leaves' => (int)$totalLeavesResult['total_leaves'],
            'year' => $year,
            'month' => $month
        ];
    }

    /**
     * 특정 날짜의 연차 상세 정보 조회
     * 
     * @param string $date 조회할 날짜 (Y-m-d 형식)
     * @param int|null $departmentId 부서 ID
     * @return array 일별 연차 상세 정보
     */
    public function getDayLeaveDetail(string $date, ?int $departmentId = null): array {
        $queryParts = [
            'sql' => "SELECT 
                        la.id,
                        la.start_date,
                        la.end_date,
                        la.days,
                        la.day_type,
                        la.leave_type,
                        la.reason,
                        e.id as employee_id,
                        e.name as employee_name,
                        d.name as department_name,
                        p.name as position_name
                      FROM hr_leave_applications la
                      JOIN hr_employees e ON la.employee_id = e.id
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_positions p ON e.position_id = p.id",
            'params' => [
                ':start_date' => $date,
                ':end_date' => $date
            ],
            'where' => [
                "la.status = '승인'",
                "la.start_date <= :start_date",
                "la.end_date >= :end_date"
            ]
        ];

        if ($departmentId !== null) {
            $queryParts['where'][] = "e.department_id = :department_id";
            $queryParts['params'][':department_id'] = $departmentId;
        }

        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY d.name, e.name";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 연차 소멸 대상 조회
     * 
     * @param int $year 대상 연도
     * @param int|null $departmentId 부서 ID
     * @param bool $previewMode 미리보기 모드
     * @return array 소멸 대상 목록
     */
    public function getExpireTargets(int $year, ?int $departmentId = null, bool $previewMode = true): array {
        $queryParts = [
            'sql' => "SELECT 
                        e.id as employee_id,
                        e.name as employee_name,
                        d.name as department_name,
                        v.current_balance,
                        v.granted_days,
                        v.used_days_this_year
                      FROM hr_employees e
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN v_employee_leave_status v ON e.id = v.employee_id",
            'params' => [],
            'where' => [
                "e.termination_date IS NULL",
                "v.current_balance > 0"
            ]
        ];

        if ($departmentId !== null) {
            $queryParts['where'][] = "e.department_id = :department_id";
            $queryParts['params'][':department_id'] = $departmentId;
        }

        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY d.name, e.name";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 현재 상태 내보내기용 데이터 조회
     * 
     * @param int $year 대상 연도
     * @param int|null $departmentId 부서 ID
     * @return array 현재 상태 데이터
     */
    public function getCurrentStatusForExport(int $year, ?int $departmentId = null): array {
        $queryParts = [
            'sql' => "SELECT 
                        e.id as employee_id,
                        e.name as employee_name,
                        e.employee_number,
                        d.name as department_name,
                        p.name as position_name,
                        e.hire_date,
                        v.current_balance,
                        v.granted_days,
                        v.used_days_this_year,
                        v.remaining_days,
                        v.pending_applications
                      FROM hr_employees e
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_positions p ON e.position_id = p.id
                      LEFT JOIN v_employee_leave_status v ON e.id = v.employee_id",
            'params' => [],
            'where' => ["e.termination_date IS NULL"]
        ];

        if ($departmentId !== null) {
            $queryParts['where'][] = "e.department_id = :department_id";
            $queryParts['params'][':department_id'] = $departmentId;
        }

        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY d.name, e.name";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 사용 이력 내보내기용 데이터 조회
     * 
     * @param int $year 대상 연도
     * @param int|null $departmentId 부서 ID
     * @return array 사용 이력 데이터
     */
    public function getUsageHistoryForExport(int $year, ?int $departmentId = null): array {
        $queryParts = [
            'sql' => "SELECT 
                        la.id,
                        e.name as employee_name,
                        e.employee_number,
                        d.name as department_name,
                        la.start_date,
                        la.end_date,
                        la.days,
                        la.leave_type,
                        la.day_type,
                        la.status,
                        la.reason,
                        approver.name as approver_name,
                        la.approved_at,
                        la.created_at
                      FROM hr_leave_applications la
                      JOIN hr_employees e ON la.employee_id = e.id
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_employees approver ON la.approver_id = approver.id",
            'params' => [':year' => $year],
            'where' => [
                "YEAR(la.start_date) = :year",
                "la.status = '승인'"
            ]
        ];

        if ($departmentId !== null) {
            $queryParts['where'][] = "e.department_id = :department_id";
            $queryParts['params'][':department_id'] = $departmentId;
        }

        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY la.start_date DESC, e.name";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 신청 이력 내보내기용 데이터 조회
     * 
     * @param int $year 대상 연도
     * @param int|null $departmentId 부서 ID
     * @return array 신청 이력 데이터
     */
    public function getApplicationHistoryForExport(int $year, ?int $departmentId = null): array {
        $queryParts = [
            'sql' => "SELECT 
                        la.id,
                        e.name as employee_name,
                        e.employee_number,
                        d.name as department_name,
                        la.start_date,
                        la.end_date,
                        la.days,
                        la.leave_type,
                        la.day_type,
                        la.status,
                        la.reason,
                        approver.name as approver_name,
                        la.approval_reason,
                        la.approved_at,
                        la.created_at
                      FROM hr_leave_applications la
                      JOIN hr_employees e ON la.employee_id = e.id
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_employees approver ON la.approver_id = approver.id",
            'params' => [':year' => $year],
            'where' => ["YEAR(la.start_date) = :year"]
        ];

        if ($departmentId !== null) {
            $queryParts['where'][] = "e.department_id = :department_id";
            $queryParts['params'][':department_id'] = $departmentId;
        }

        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY la.created_at DESC, e.name";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 조정 이력 내보내기용 데이터 조회
     * 
     * @param int $year 대상 연도
     * @param int|null $departmentId 부서 ID
     * @return array 조정 이력 데이터
     */
    public function getAdjustmentHistoryForExport(int $year, ?int $departmentId = null): array {
        $queryParts = [
            'sql' => "SELECT 
                        ll.id,
                        e.name as employee_name,
                        e.employee_number,
                        d.name as department_name,
                        ll.leave_type,
                        ll.log_type,
                        ll.transaction_type,
                        ll.amount,
                        ll.balance_after,
                        ll.reason,
                        creator.name as created_by_name,
                        ll.created_at
                      FROM hr_leave_logs ll
                      JOIN hr_employees e ON ll.employee_id = e.id
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_employees creator ON ll.created_by = creator.id",
            'params' => [':year' => $year],
            'where' => [
                "YEAR(ll.created_at) = :year",
                "ll.log_type = '조정'"
            ]
        ];

        if ($departmentId !== null) {
            $queryParts['where'][] = "e.department_id = :department_id";
            $queryParts['params'][':department_id'] = $departmentId;
        }

        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY ll.created_at DESC, e.name";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }
}