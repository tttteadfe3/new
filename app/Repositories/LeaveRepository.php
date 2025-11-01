<?php

namespace App\Repositories;

use App\Core\Database;

class LeaveRepository
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getLogsByEmployeeId(int $employeeId): array
    {
        return $this->db->query("SELECT * FROM hr_leave_logs WHERE employee_id = ?", [$employeeId]);
    }

    public function createLog(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO hr_leave_logs (employee_id, leave_request_id, leave_type, transaction_type, amount, reason, actor_employee_id)
             VALUES (:employee_id, :leave_request_id, :leave_type, :transaction_type, :amount, :reason, :actor_employee_id)"
        );
        $data = array_merge(['leave_request_id' => null, 'actor_employee_id' => null], $data);
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function createRequest(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO hr_leave_requests (employee_id, leave_type, request_unit, start_date, end_date, days_count, reason, status)
             VALUES (:employee_id, :leave_type, :request_unit, :start_date, :end_date, :days_count, :reason, :status)"
        );
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function findRequestById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM hr_leave_requests WHERE id = ?", [$id]);
    }

    public function updateRequestStatus(int $id, string $status, int $approverId = null): bool
    {
        $sql = "UPDATE hr_leave_requests SET status = :status, approver_employee_id = :approver_id WHERE id = :id";
        return $this->db->execute($sql, [':status' => $status, ':approver_id' => $approverId, ':id' => $id]);
    }

    public function updateRequestStatusAndReason(int $id, string $status, int $approverId, string $reason): bool
    {
        $sql = "UPDATE hr_leave_requests SET status = :status, approver_employee_id = :approver_id, rejection_reason = :reason WHERE id = :id";
        return $this->db->execute($sql, [':status' => $status, ':approver_id' => $approverId, ':reason' => $reason, ':id' => $id]);
    }

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

    public function beginTransaction() { $this->db->beginTransaction(); }
    public function commit() { $this->db->commit(); }
    public function rollBack() { $this->db->rollBack(); }
}
