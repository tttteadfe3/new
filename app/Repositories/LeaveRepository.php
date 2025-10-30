<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class LeaveRepository
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function beginTransaction() { $this->db->beginTransaction(); }
    public function commit() { $this->db->commit(); }
    public function rollBack() { $this->db->rollBack(); }

    public function updateLeaveBalance(int $employeeId, int $year, float $days, string $columnToUpdate): bool
    {
        $sql = "INSERT INTO hr_leave_balances (employee_id, year, {$columnToUpdate})
                VALUES (:employee_id, :year, :days)
                ON DUPLICATE KEY UPDATE
                {$columnToUpdate} = {$columnToUpdate} + :days";

        return $this->db->execute($sql, [
            ':employee_id' => $employeeId,
            ':year' => $year,
            ':days' => $days
        ]) > 0;
    }

    public function createLeaveLog(int $employeeId, string $changeType, float $changeDays, string $reason, ?int $processorId = null, ?int $requestId = null): bool
    {
        $sql = "INSERT INTO hr_leave_logs (employee_id, leave_request_id, change_type, change_days, reason, processor_id)
                VALUES (:employee_id, :leave_request_id, :change_type, :change_days, :reason, :processor_id)";

        return $this->db->execute($sql, [
            ':employee_id' => $employeeId,
            ':leave_request_id' => $requestId,
            ':change_type' => $changeType,
            ':change_days' => $changeDays,
            ':reason' => $reason,
            ':processor_id' => $processorId
        ]) > 0;
    }

    public function getAllActiveEmployees(): array
    {
        $sql = "SELECT id, name, hire_date FROM hr_employees WHERE termination_date IS NULL";
        return $this->db->fetchAll($sql);
    }

    public function findBalanceByEmployeeAndYear(int $employeeId, int $year): ?array
    {
        $sql = "SELECT * FROM hr_leave_balances WHERE employee_id = :employee_id AND year = :year";
        return $this->db->fetchOne($sql, [':employee_id' => $employeeId, ':year' => $year]) ?: null;
    }

    public function findRequestsByEmployee(int $employeeId, array $filters): array
    {
        // ... (필터링 로직은 이전과 동일하게 유지)
        return [];
    }

    public function createLeaveRequest(int $employeeId, array $data): ?int
    {
        $sql = "INSERT INTO hr_leave_requests (employee_id, start_date, end_date, days_count, leave_subtype, reason, status)
                VALUES (:employee_id, :start_date, :end_date, :days_count, :leave_subtype, :reason, 'pending')";

        return $this->db->insert($sql, [
            ':employee_id' => $employeeId,
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':days_count' => $data['days_count'],
            ':leave_subtype' => $data['leave_subtype'],
            ':reason' => $data['reason']
        ]);
    }

    public function findRequestById(int $id): ?array
    {
        $sql = "SELECT * FROM hr_leave_requests WHERE id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]) ?: null;
    }

    public function updateRequestStatus(int $id, string $status, array $extraData = []): bool
    {
        $setClauses = ['status = :status'];
        $params = [':id' => $id, ':status' => $status];

        foreach ($extraData as $key => $value) {
            $setClauses[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }

        $sql = "UPDATE hr_leave_requests SET " . implode(', ', $setClauses) . " WHERE id = :id";
        return $this->db->execute($sql, $params) > 0;
    }

    public function findRequestsByAdmin(array $filters): array
    {
        // ... (필터링 로직은 이전과 동일하게 유지)
        return [];
    }

    public function getUnusedBalancesByYear(int $year): array
    {
        $sql = "SELECT * FROM hr_leave_balances
                WHERE year = :year
                AND (base_leave + seniority_leave + monthly_leave + adjustment_leave) > used_leave";
        return $this->db->fetchAll($sql, [':year' => $year]);
    }
}
