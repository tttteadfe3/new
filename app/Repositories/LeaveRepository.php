<?php
// app/Repositories/LeaveRepository.php
namespace App\Repositories;

use App\Core\Database;

/**
 * 'hr_leave_entitlements', 'hr_leaves', 'hr_leave_adjustments_log' 테이블에 대한 데이터베이스 작업을 관리합니다.
 * 연차 부여, 연차 신청, 연차 조정과 관련된 모든 데이터 처리를 담당합니다.
 */
class LeaveRepository {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    // =================================================================
    // 연차 부여 (Leave Entitlements) 관련 메소드
    // =================================================================

    /**
     * 특정 직원의 특정 연도 연차 부여 정보를 조회합니다.
     *
     * @param int $employeeId 조회할 직원의 ID
     * @param int $year 조회할 연도
     * @return array|false 연차 부여 정보 배열 또는 정보가 없는 경우 false
     */
    public function findEntitlement(int $employeeId, int $year) {
        $sql = "SELECT * FROM hr_leave_entitlements WHERE employee_id = :employee_id AND year = :year";
        return $this->db->fetchOne($sql, [':employee_id' => $employeeId, ':year' => $year]);
    }

    /**
     * 연차 부여 정보를 생성하거나 업데이트합니다.
     *
     * @param array $data 생성/수정할 데이터. ['employee_id', 'year', 'total_days'] 포함.
     * @return bool 작업 성공 여부
     */
    public function createOrUpdateEntitlement(array $data): bool {
        $existing = $this->findEntitlement($data['employee_id'], $data['year']);

        if ($existing) {
            // Update
            $sql = "UPDATE hr_leave_entitlements SET total_days = :total_days WHERE id = :id";
            $params = [':total_days' => $data['total_days'], ':id' => $existing['id']];
        } else {
            // Insert
            $sql = "INSERT INTO hr_leave_entitlements (employee_id, year, total_days) VALUES (:employee_id, :year, :total_days)";
            $params = [
                ':employee_id' => $data['employee_id'],
                ':year' => $data['year'],
                ':total_days' => $data['total_days']
            ];
        }
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * 모든 활성 직원의 특정 연도 연차 부여 현황을 조회합니다.
     * 연차 부여 내역이 없는 직원도 목록에 포함됩니다.
     *
     * @param array $filters 필터 조건. e.g., ['year' => 2024, 'department_id' => 1]
     * @return array 직원별 연차 부여 현황 목록
     */
    public function getAllEntitlements(array $filters = []): array {
        $year = $filters['year'] ?? date('Y');

        $sql = "SELECT
                    e.id as employee_id,
                    e.name as employee_name,
                    e.hire_date,
                    d.name as department_name,
                    le.id as entitlement_id,
                    le.total_days,
                    le.used_days,
                    (SELECT SUM(lal.adjusted_days) FROM hr_leave_adjustments_log lal WHERE lal.employee_id = e.id AND lal.year = :year1) as adjusted_days
                FROM
                    hr_employees e
                LEFT JOIN
                    hr_departments d ON e.department_id = d.id
                LEFT JOIN
                    hr_leave_entitlements le ON e.id = le.employee_id AND le.year = :year2
                WHERE
                    e.termination_date IS NULL";

        $params = [':year1' => $year, ':year2' => $year];

        if (!empty($filters['department_id'])) {
            $sql .= " AND e.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }

        $sql .= " ORDER BY e.name ASC";

        return $this->db->query($sql, $params);
    }

    /**
     * 직원의 특정 연도 사용 연차 일수를 업데이트합니다.
     *
     * @param int $employeeId 직원의 ID
     * @param int $year 해당 연도
     * @param float $daysDelta 변경할 일수 (양수: 증가, 음수: 감소)
     * @return bool 업데이트 성공 여부
     */
    public function updateUsedDays(int $employeeId, int $year, float $daysDelta): bool {
        $sql = "UPDATE hr_leave_entitlements
                SET used_days = used_days + :days_delta
                WHERE employee_id = :employee_id AND year = :year";

        return $this->db->execute($sql, [
            ':days_delta' => $daysDelta,
            ':employee_id' => $employeeId,
            ':year' => $year
        ]) > 0;
    }

    /**
     * 연차 부여 일수를 수동으로 조정합니다.
     * 대상 연도의 부여 내역이 없으면 먼저 0일로 생성한 후 조정합니다.
     *
     * @param int $employeeId 직원의 ID
     * @param int $year 해당 연도
     * @param float $adjustment_days 조정할 일수 (양수: 추가, 음수: 차감)
     * @return bool 조정 성공 여부
     */
    public function adjustEntitlement(int $employeeId, int $year, float $adjustment_days): bool {
        $existing = $this->findEntitlement($employeeId, $year);
        if (!$existing) {
            $this->createOrUpdateEntitlement([
                'employee_id' => $employeeId,
                'year' => $year,
                'total_days' => 0
            ]);
        }

        $sql = "UPDATE hr_leave_entitlements SET total_days = total_days + :adjustment WHERE employee_id = :employee_id AND year = :year";
        return $this->db->execute($sql, [
            ':adjustment' => $adjustment_days,
            ':employee_id' => $employeeId,
            ':year' => $year
        ]) > 0;
    }

    /**
     * 연차 수동 조정 내역을 로그 테이블에 기록합니다.
     *
     * @param int $employeeId 직원의 ID
     * @param int $year 해당 연도
     * @param float $adjustedDays 조정된 일수
     * @param string $reason 조정 사유
     * @param int $adminId 조정을 수행한 관리자의 ID
     * @return bool 로그 기록 성공 여부
     */
    public function logAdjustment(int $employeeId, int $year, float $adjustedDays, string $reason, int $adminId): bool {
        $sql = "INSERT INTO hr_leave_adjustments_log (employee_id, year, adjusted_days, reason, admin_id)
                VALUES (:employee_id, :year, :adjusted_days, :reason, :admin_id)";
        return $this->db->execute($sql, [
            ':employee_id' => $employeeId,
            ':year' => $year,
            ':adjusted_days' => $adjustedDays,
            ':reason' => $reason,
            ':admin_id' => $adminId
        ]) > 0;
    }

    // =================================================================
    // 연차 신청 (Leave Requests) 관련 메소드
    // =================================================================

    /**
     * ID로 특정 연차 신청 건을 조회합니다.
     *
     * @param int $id 조회할 연차 신청 ID
     * @return array|false 연차 신청 정보 배열 또는 false
     */
    public function findById(int $id) {
        $sql = "SELECT l.*, e.name as employee_name
                FROM hr_leaves l
                JOIN hr_employees e ON l.employee_id = e.id
                WHERE l.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    /**
     * 특정 직원의 모든 연차 신청 내역을 조회합니다.
     *
     * @param int $employeeId 직원의 ID
     * @param array $filters 필터 조건. e.g., ['year' => 2024]
     * @return array 해당 직원의 연차 신청 목록
     */
    public function findByEmployeeId(int $employeeId, array $filters = []): array {
         $sql = "SELECT l.*, u.nickname as approver_name
                FROM hr_leaves l
                LEFT JOIN sys_users u ON l.approved_by = u.id
                WHERE l.employee_id = :employee_id";

        $params = [':employee_id' => $employeeId];

        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(l.start_date) = :year";
            $params[':year'] = $filters['year'];
        }

        $sql .= " ORDER BY l.start_date DESC";

        return $this->db->query($sql, $params);
    }

    /**
     * 새로운 연차 신청을 생성합니다.
     *
     * @param array $data 생성할 연차 신청 데이터
     * @return string|null 생성된 연차 신청의 ID 또는 null
     */
    public function create(array $data): ?string {
        $sql = "INSERT INTO hr_leaves (employee_id, leave_type, start_date, end_date, days_count, reason)
                VALUES (:employee_id, :leave_type, :start_date, :end_date, :days_count, :reason)";

        $this->db->execute($sql, [
            ':employee_id' => $data['employee_id'],
            ':leave_type' => $data['leave_type'],
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':days_count' => $data['days_count'],
            ':reason' => $data['reason']
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * 지정된 기간과 중복되는 연차 신청 건이 있는지 확인합니다.
     *
     * @param int $employeeId 직원 ID
     * @param string $startDate 시작일
     * @param string $endDate 종료일
     * @param int|null $excludeLeaveId 검사에서 제외할 연차 ID (수정 시 사용)
     * @return bool 중복되는 경우 true, 그렇지 않으면 false
     */
    public function findOverlappingLeaves(int $employeeId, string $startDate, string $endDate, ?int $excludeLeaveId = null): bool {
        $sql = "SELECT COUNT(*)
                FROM hr_leaves
                WHERE employee_id = :employee_id
                  AND status NOT IN ('rejected', 'cancelled')
                  AND start_date <= :end_date
                  AND end_date >= :start_date";

        $params = [
            ':employee_id' => $employeeId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ];

        if ($excludeLeaveId !== null) {
            $sql .= " AND id != :exclude_leave_id";
            $params[':exclude_leave_id'] = $excludeLeaveId;
        }

        $count = $this->db->fetchOne($sql, $params)['COUNT(*)'];
        return $count > 0;
    }

    /**
     * 연차 신청의 상태를 변경합니다. (e.g., 'approved', 'rejected', 'canceled')
     *
     * @param int $id 상태를 변경할 연차 신청의 ID
     * @param string $status 새로운 상태
     * @param int|null $adminUserId 처리한 관리자의 사용자 ID
     * @param string|null $rejectionReason 반려 사유 (반려 시에만 사용)
     * @return bool 업데이트 성공 여부
     */
    public function updateStatus(int $id, string $status, ?int $adminUserId, ?string $rejectionReason): bool {
        $sql = "UPDATE hr_leaves
                SET status = :status, approved_by = :approved_by, rejection_reason = :rejection_reason
                WHERE id = :id";

        return $this->db->execute($sql, [
            ':id' => $id,
            ':status' => $status,
            ':approved_by' => $adminUserId,
            ':rejection_reason' => $rejectionReason
        ]) > 0;
    }

    /**
     * 승인된 연차에 대해 취소 요청을 기록합니다.
     *
     * @param int $id 취소 요청할 연차 ID
     * @param string $reason 취소 사유
     * @return bool 업데이트 성공 여부
     */
    public function requestCancellation(int $id, string $reason): bool {
        $sql = "UPDATE hr_leaves
                SET status = 'cancellation_requested', cancellation_reason = :reason
                WHERE id = :id AND status = 'approved'";

        return $this->db->execute($sql, [
            ':id' => $id,
            ':reason' => $reason
        ]) > 0;
    }

    /**
     * 모든 연차 신청 목록을 조건에 따라 조회합니다. (관리자용)
     *
     * @param array $filters 필터 조건. e.g., ['status' => 'pending', 'start_date' => '2024-01-01']
     * @return array 필터링된 연차 신청 목록
     */
    public function findAll(array $filters = []): array {
        $sql = "SELECT l.*, e.name as employee_name, d.name as department_name, p.name as position_name
                FROM hr_leaves l
                JOIN hr_employees e ON l.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_positions p ON e.position_id = p.id";

        $whereClauses = [];
        $params = [];

        if (!empty($filters['status'])) {
            $whereClauses[] = "l.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $whereClauses[] = "l.start_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $whereClauses[] = "l.end_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['department_id'])) {
            if (is_array($filters['department_id'])) {
                if (count($filters['department_id']) > 0) {
                    $deptIds = array_map('intval', $filters['department_id']);
                    $inClause = implode(',', $deptIds);
                    $whereClauses[] = "e.department_id IN ($inClause)";
                } else {
                    $whereClauses[] = "1=0";
                }
            } else {
                $whereClauses[] = "e.department_id = :department_id";
                $params[':department_id'] = $filters['department_id'];
            }
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        $sql .= " ORDER BY l.created_at DESC";

        return $this->db->query($sql, $params);
    }

    /**
     * 특정 상태의 휴가 신청 목록을 필터와 함께 조회합니다.
     *
     * @param string $status 조회할 휴가 상태
     * @param array $filters 추가 필터 (e.g., department_id)
     * @return array
     */
    public function findByStatus(string $status, array $filters = []): array
    {
        $filters['status'] = $status;
        return $this->findAll($filters);
    }
}