<?php
// app/Repositories/LeaveRepository.php
namespace App\Repositories;

use App\Core\Database;
use App\Services\DataScopeService;

/**
 * 연차 관리 시스템의 데이터 접근 계층
 * 로그 기반 연차 변동 추적, 연차 신청/승인, 취소 신청 등의 데이터 처리를 담당합니다.
 * 데이터 스코프 서비스를 통한 부서별 권한 관리를 지원합니다.
 */
class LeaveRepository {
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService) {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    // =================================================================
    // 연차 로그 (Leave Logs) 관련 메소드
    // =================================================================

    /**
     * 연차/월차 변동 로그 생성 (생성만 허용, 수정/삭제 금지)
     * 
     * @param array $logData 로그 데이터
     * @return bool 생성 성공 여부
     */
    public function createLog(array $logData): bool {
        $sql = "INSERT INTO hr_leave_logs (employee_id, leave_type, grant_year, log_type, transaction_type, amount, balance_after, reason, reference_id, created_by)
                VALUES (:employee_id, :leave_type, :grant_year, :log_type, :transaction_type, :amount, :balance_after, :reason, :reference_id, :created_by)";
        
        $params = [
            ':employee_id' => $logData['employee_id'],
            ':leave_type' => $logData['leave_type'] ?? '연차',
            ':grant_year' => $logData['grant_year'] ?? null,
            ':log_type' => $this->mapToLogType($logData['transaction_type'] ?? '조정'),
            ':transaction_type' => $logData['transaction_type'] ?? '기타',
            ':amount' => $logData['amount'],
            ':balance_after' => $logData['balance_after'] ?? 0,
            ':reason' => $logData['reason'] ?? null,
            ':reference_id' => $logData['reference_id'] ?? $logData['leave_request_id'] ?? null,
            ':created_by' => $logData['created_by'] ?? $logData['actor_employee_id'] ?? 1
        ];
        
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * transaction_type을 log_type으로 매핑
     */
    private function mapToLogType(string $transactionType): string {
        $mapping = [
            '초기부여' => '부여',
            '연차부여' => '부여', 
            '근속연차부여' => '부여',
            '월차부여' => '부여',
            '포상부여' => '부여',
            '포상' => '부여',
            '연차사용' => '사용',
            '연차추가' => '조정',
            '연차차감' => '조정', 
            '연차소멸' => '소멸',
            '사용취소' => '취소'
        ];
        
        return $mapping[$transactionType] ?? '조정';
    }

    /**
     * 직원별 로그 조회
     * 
     * @param int $employeeId 직원 ID
     * @param array $filters 필터 조건
     * @return array 로그 목록
     */
    public function getLogsByEmployee(int $employeeId, array $filters = []): array {
        $queryParts = [
            'sql' => "SELECT ll.*, e.name as created_by_name
                      FROM hr_leave_logs ll
                      LEFT JOIN hr_employees e ON ll.created_by = e.id
                      WHERE ll.employee_id = :employee_id",
            'params' => [':employee_id' => $employeeId],
            'where' => []
        ];

        if (!empty($filters['log_type'])) {
            $queryParts['where'][] = "ll.log_type = :log_type";
            $queryParts['params'][':log_type'] = $filters['log_type'];
        }

        if (!empty($filters['start_date'])) {
            $queryParts['where'][] = "DATE(ll.created_at) >= :start_date";
            $queryParts['params'][':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $queryParts['where'][] = "DATE(ll.created_at) <= :end_date";
            $queryParts['params'][':end_date'] = $filters['end_date'];
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " AND " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY ll.created_at DESC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 잔여량 계산용 로그 집계
     * 
     * @param int $employeeId 직원 ID
     * @param string $leaveType 연차 구분 (annual, monthly, all)
     * @return array 로그 목록
     */
    public function getBalanceLogs(int $employeeId, string $leaveType = 'all'): array {
        $sql = "SELECT log_type, transaction_type, leave_type, amount FROM hr_leave_logs 
                WHERE employee_id = :employee_id";
        
        $params = [':employee_id' => $employeeId];
        
        // leave_type 필터링
        if ($leaveType !== 'all') {
            $sql .= " AND leave_type = :leave_type";
            $params[':leave_type'] = $leaveType === 'annual' ? '연차' : ($leaveType === 'monthly' ? '월차' : $leaveType);
        }
        
        $sql .= " ORDER BY created_at ASC";
        
        return $this->db->query($sql, $params);
    }

    // =================================================================
    // 연차 신청 (Leave Applications) 관련 메소드
    // =================================================================

    /**
     * 연차 신청 저장
     * 
     * @param array $applicationData 신청 데이터
     * @return string 생성된 신청 ID
     */
    public function saveApplication(array $applicationData): string {
        $sql = "INSERT INTO hr_leave_applications (employee_id, start_date, end_date, days, leave_type, day_type, status, reason)
                VALUES (:employee_id, :start_date, :end_date, :days, :leave_type, :day_type, :status, :reason)";
        
        $params = [
            ':employee_id' => $applicationData['employee_id'],
            ':start_date' => $applicationData['start_date'],
            ':end_date' => $applicationData['end_date'],
            ':days' => $applicationData['days'],
            ':leave_type' => $applicationData['leave_type'],
            ':day_type' => $applicationData['day_type'] ?? '전일',
            ':status' => $applicationData['status'] ?? '대기',
            ':reason' => $applicationData['reason'] ?? null
        ];
        
        $this->db->execute($sql, $params);
        return (string)$this->db->lastInsertId();
    }

    /**
     * 신청 ID로 조회
     * 
     * @param int $applicationId 신청 ID
     * @return array|false 신청 정보
     */
    public function getApplicationById(int $applicationId) {
        $sql = "SELECT la.*, e.name as employee_name, d.name as department_name
                FROM hr_leave_applications la
                JOIN hr_employees e ON la.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id
                WHERE la.id = :id";
        
        return $this->db->fetchOne($sql, [':id' => $applicationId]);
    }

    /**
     * 신청 상태 업데이트
     * 
     * @param int $applicationId 신청 ID
     * @param string $status 새 상태
     * @param int|null $approverId 승인자 ID
     * @param string|null $reason 승인/반려 사유
     * @return bool 업데이트 성공 여부
     */
    public function updateApplicationStatus(int $applicationId, string $status, ?int $approverId, ?string $reason): bool {
        $sql = "UPDATE hr_leave_applications 
                SET status = :status, approver_id = :approver_id, approval_reason = :reason, approved_at = :approved_at
                WHERE id = :id";
        
        $approvedAt = ($status === '승인') ? date('Y-m-d H:i:s') : null;
        
        return $this->db->execute($sql, [
            ':id' => $applicationId,
            ':status' => $status,
            ':approver_id' => $approverId,
            ':reason' => $reason,
            ':approved_at' => $approvedAt
        ]) > 0;
    }

    /**
     * 중복 신청 확인
     * 
     * @param int $employeeId 직원 ID
     * @param string $startDate 시작일
     * @param string $endDate 종료일
     * @param int|null $excludeId 제외할 신청 ID
     * @return bool 중복 여부
     */
    public function findOverlappingApplications(int $employeeId, string $startDate, string $endDate, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) as count
                FROM hr_leave_applications
                WHERE employee_id = :employee_id
                  AND status NOT IN ('반려', '취소')
                  AND start_date <= :end_date
                  AND end_date >= :start_date";
        
        $params = [
            ':employee_id' => $employeeId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ];
        
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * 데이터 스코프 서비스 적용된 직원별 신청 목록 조회
     * 
     * @param int $employeeId 직원 ID
     * @param array $filters 필터 조건
     * @return array 신청 목록
     */
    public function getEmployeeApplications(int $employeeId, array $filters = []): array {
        $queryParts = [
            'sql' => "SELECT la.*, approver.name as approver_name, e.name as employee_name, d.name as department_name
                      FROM hr_leave_applications la
                      LEFT JOIN hr_employees approver ON la.approver_id = approver.id
                      JOIN hr_employees e ON la.employee_id = e.id
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      WHERE la.employee_id = :employee_id",
            'params' => [':employee_id' => $employeeId],
            'where' => []
        ];

        // 데이터 스코프 적용
        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($filters['year'])) {
            $queryParts['where'][] = "YEAR(la.start_date) = :year";
            $queryParts['params'][':year'] = $filters['year'];
        }

        if (!empty($filters['status'])) {
            $queryParts['where'][] = "la.status = :status";
            $queryParts['params'][':status'] = $filters['status'];
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " AND " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY la.start_date DESC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 승인 대기 중인 신청 일수 조회
     * 
     * @param int $employeeId 직원 ID
     * @return float 대기 중인 총 일수
     */
    public function getPendingApplicationDays(int $employeeId): float {
        $sql = "SELECT COALESCE(SUM(days), 0) as total_days
                FROM hr_leave_applications
                WHERE employee_id = :employee_id AND status = '대기'";
        
        $result = $this->db->fetchOne($sql, [':employee_id' => $employeeId]);
        return (float)$result['total_days'];
    }

    // =================================================================
    // 연차 취소 신청 (Leave Cancellations) 관련 메소드
    // =================================================================

    /**
     * 취소 신청 저장
     * 
     * @param array $cancellationData 취소 신청 데이터
     * @return string 생성된 취소 신청 ID
     */
    public function saveCancellationRequest(array $cancellationData): string {
        $sql = "INSERT INTO hr_leave_cancellations (application_id, employee_id, reason, status)
                VALUES (:application_id, :employee_id, :reason, :status)";
        
        $params = [
            ':application_id' => $cancellationData['application_id'],
            ':employee_id' => $cancellationData['employee_id'],
            ':reason' => $cancellationData['reason'],
            ':status' => $cancellationData['status'] ?? '대기'
        ];
        
        $this->db->execute($sql, $params);
        return (string)$this->db->lastInsertId();
    }

    /**
     * 취소 신청 ID로 조회
     * 
     * @param int $cancellationId 취소 신청 ID
     * @return array|false 취소 신청 정보
     */
    public function getCancellationById(int $cancellationId) {
        $sql = "SELECT lc.*, la.start_date, la.end_date, la.days, e.name as employee_name
                FROM hr_leave_cancellations lc
                JOIN hr_leave_applications la ON lc.application_id = la.id
                JOIN hr_employees e ON lc.employee_id = e.id
                WHERE lc.id = :id";
        
        return $this->db->fetchOne($sql, [':id' => $cancellationId]);
    }

    /**
     * 취소 신청 상태 업데이트
     * 
     * @param int $cancellationId 취소 신청 ID
     * @param string $status 새 상태
     * @param int|null $approverId 승인자 ID
     * @param string|null $reason 승인/반려 사유
     * @return bool 업데이트 성공 여부
     */
    public function updateCancellationStatus(int $cancellationId, string $status, ?int $approverId, ?string $reason): bool {
        $sql = "UPDATE hr_leave_cancellations 
                SET status = :status, approver_id = :approver_id, approval_reason = :reason, approved_at = :approved_at
                WHERE id = :id";
        
        $approvedAt = ($status === '승인') ? date('Y-m-d H:i:s') : null;
        
        return $this->db->execute($sql, [
            ':id' => $cancellationId,
            ':status' => $status,
            ':approver_id' => $approverId,
            ':reason' => $reason,
            ':approved_at' => $approvedAt
        ]) > 0;
    }

    // =================================================================
    // 관리자 기능은 LeaveAdminRepository로 이동됨
    // =================================================================

    // =================================================================
    // 유틸리티 메서드 (Utility Methods)
    // =================================================================



    /**
     * 특정 연도 월차 부여 개수 조회
     * 
     * @param int $employeeId 직원 ID
     * @param int $year 조회할 연도
     * @return int 부여된 월차 개수
     */
    public function getMonthlyGrantedCount(int $employeeId, int $year): int {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total_granted
                FROM hr_leave_logs
                WHERE employee_id = :employee_id 
                  AND log_type = '부여'
                  AND (reason LIKE '%월차%' OR transaction_type = '월차부여')
                  AND YEAR(created_at) = :year";
        
        $result = $this->db->fetchOne($sql, [
            ':employee_id' => $employeeId,
            ':year' => $year
        ]);
        
        return (int)$result['total_granted'];
    }

    /**
     * 특정 직원의 특정 연도 연차/월차 부여 내역 조회 (중복 부여 방지용)
     * 
     * @param int $employeeId 직원 ID
     * @param int $year 조회할 연도
     * @return array|false 부여 내역 (없으면 false)
     */
    public function findEntitlement(int $employeeId, int $year) {
        $sql = "SELECT *
                FROM hr_leave_logs
                WHERE employee_id = :employee_id 
                  AND log_type = '부여'
                  AND (transaction_type IN ('연차부여', '초기부여', '근속연차부여', '월차부여') 
                       OR reason LIKE '%부여%')
                  AND YEAR(created_at) = :year
                ORDER BY created_at DESC
                LIMIT 1";
        
        return $this->db->fetchOne($sql, [
            ':employee_id' => $employeeId,
            ':year' => $year
        ]);
    }

    /**
     * 직원별 연차 신청 내역 조회
     * 
     * @param int $employeeId 직원 ID
     * @param string $status 상태 필터 ('ALL', 'PENDING', 'APPROVED', 'REJECTED', 'CANCELLED')
     * @param int $year 대상 연도
     * @return array 신청 내역
     */
    public function getApplicationsByEmployee(int $employeeId, string $status = 'ALL', int $year = null): array {
        $queryParts = [
            'sql' => "SELECT la.*, e.name as employee_name, d.name as department_name,
                             approver.name as approver_name
                      FROM hr_leave_applications la
                      LEFT JOIN hr_employees e ON la.employee_id = e.id
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_employees approver ON la.approver_id = approver.id",
            'where' => ['la.employee_id = :employee_id'],
            'params' => [':employee_id' => $employeeId]
        ];

        // 데이터 스코프 적용
        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if ($status !== 'ALL') {
            $queryParts['where'][] = 'la.status = :status';
            $queryParts['params'][':status'] = $status;
        }

        if ($year) {
            $queryParts['where'][] = 'YEAR(la.start_date) = :year';
            $queryParts['params'][':year'] = $year;
        }

        $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        $queryParts['sql'] .= " ORDER BY la.created_at DESC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 연차 사용 이력 조회
     * 
     * @param int $employeeId 직원 ID
     * @param int $year 대상 연도
     * @param string|null $status 상태 필터
     * @return array 사용 이력
     */
    public function getLeaveHistory(int $employeeId, int $year, string $status = null): array {
        $queryParts = [
            'sql' => "SELECT la.*, e.name as employee_name, d.name as department_name,
                             approver.name as approver_name,
                             lc.id as cancellation_id,
                             lc.status as cancellation_status
                      FROM hr_leave_applications la
                      LEFT JOIN hr_employees e ON la.employee_id = e.id
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_employees approver ON la.approver_id = approver.id
                      LEFT JOIN hr_leave_cancellations lc ON la.id = lc.application_id AND lc.status = '대기'",
            'where' => [
                'la.employee_id = :employee_id',
                'YEAR(la.start_date) = :year'
            ],
            'params' => [
                ':employee_id' => $employeeId,
                ':year' => $year
            ]
        ];

        // 데이터 스코프 적용
        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if ($status) {
            $queryParts['where'][] = 'la.status = :status';
            $queryParts['params'][':status'] = $status;
        }

        $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        $queryParts['sql'] .= " ORDER BY la.start_date DESC, la.created_at DESC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

}