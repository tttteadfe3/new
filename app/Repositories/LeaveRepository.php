<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * 연차 관련 데이터베이스 작업을 처리하는 리포지토리
 */
class LeaveRepository
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * 특정 직원의 모든 연차/월차 로그를 조회한다.
     *
     * @param int $employeeId
     * @return array
     */
    public function getLogsByEmployeeId(int $employeeId): array
    {
        return $this->db->query("SELECT * FROM hr_leave_logs WHERE employee_id = ?", [$employeeId]);
    }

    /**
     * 연차/월차 로그를 생성한다.
     *
     * @param array $data
     * @return int 생성된 로그의 ID
     */
    public function createLog(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO hr_leave_logs (employee_id, leave_request_id, leave_type, transaction_type, amount, reason, actor_employee_id)
             VALUES (:employee_id, :leave_request_id, :leave_type, :transaction_type, :amount, :reason, :actor_employee_id)"
        );
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    /**
     * 휴가 신청 데이터를 생성한다.
     *
     * @param array $data
     * @return int 생성된 신청서의 ID
     */
    public function createRequest(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO hr_leave_requests (employee_id, leave_type, request_unit, start_date, end_date, days_count, reason, status)
             VALUES (:employee_id, :leave_type, :request_unit, :start_date, :end_date, :days_count, :reason, :status)"
        );
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    /**
     * ID로 휴가 신청 데이터를 조회한다.
     */
    public function findRequestById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM hr_leave_requests WHERE id = ?", [$id]);
    }

    /**
     * 휴가 신청 데이터의 상태를 변경한다.
     */
    public function updateRequestStatus(int $id, string $status, int $approverId = null): bool
    {
        $sql = "UPDATE hr_leave_requests SET status = :status, approver_employee_id = :approver_id WHERE id = :id";
        return $this->db->execute($sql, [':status' => $status, ':approver_id' => $approverId, ':id' => $id]);
    }

    /**
     * 휴가 신청 데이터의 상태와 반려 사유를 함께 변경한다.
     */
    public function updateRequestStatusAndReason(int $id, string $status, int $approverId, string $reason): bool
    {
        $sql = "UPDATE hr_leave_requests SET status = :status, approver_employee_id = :approver_id, rejection_reason = :reason WHERE id = :id";
        return $this->db->execute($sql, [
            ':status' => $status,
            ':approver_id' => $approverId,
            ':reason' => $reason,
            ':id' => $id
        ]);
    }

    // DB 트랜잭션 시작
    public function beginTransaction() { $this->db->beginTransaction(); }
    // DB 트랜잭션 커밋
    public function commit() { $this->db->commit(); }
    // DB 트랜잭션 롤백
    public function rollBack() { $this->db->rollBack(); }

    /**
     * 다양한 조건으로 휴가 신청 목록을 조회한다. (관리자용)
     */
    public function findRequestsByFilters(array $filters): array
    {
        $sql = "SELECT r.*, e.name as employee_name, d.name as department_name
                FROM hr_leave_requests r
                JOIN hr_employees e ON r.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id";

        $whereClauses = [];
        $params = [];

        if (!empty($filters['status'])) {
            $whereClauses[] = "r.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['department_ids'])) {
            $inClause = implode(',', array_map('intval', $filters['department_ids']));
            $whereClauses[] = "e.department_id IN ($inClause)";
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $sql .= " ORDER BY r.created_at DESC";

        return $this->db->query($sql, $params);
    }
}
