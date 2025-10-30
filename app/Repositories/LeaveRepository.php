<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class LeaveRepository
{
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db->getConnection();
    }

    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    public function commit()
    {
        $this->db->commit();
    }

    public function rollBack()
    {
        $this->db->rollBack();
    }

    public function updateLeaveBalance(int $employeeId, int $year, float $days, string $columnToUpdate): bool
    {
        $sql = "INSERT INTO hr_leave_balances (employee_id, year, {$columnToUpdate})
                VALUES (:employee_id, :year, :days)
                ON DUPLICATE KEY UPDATE
                {$columnToUpdate} = {$columnToUpdate} + :days";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':employee_id' => $employeeId,
            ':year' => $year,
            ':days' => $days
        ]);
    }

    public function createLeaveLog(int $employeeId, string $changeType, float $changeDays, string $reason, ?int $processorId = null, ?int $requestId = null): bool
    {
        $sql = "INSERT INTO hr_leave_logs (employee_id, leave_request_id, change_type, change_days, reason, processor_id)
                VALUES (:employee_id, :leave_request_id, :change_type, :change_days, :reason, :processor_id)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':employee_id' => $employeeId,
            ':leave_request_id' => $requestId,
            ':change_type' => $changeType,
            ':change_days' => $changeDays,
            ':reason' => $reason,
            ':processor_id' => $processorId
        ]);
    }

    public function getAllActiveEmployees(): array
    {
        $sql = "SELECT id, name, hire_date FROM hr_employees WHERE termination_date IS NULL";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findBalanceByEmployeeAndYear(int $employeeId, int $year): ?array
    {
        $sql = "SELECT * FROM hr_leave_balances WHERE employee_id = :employee_id AND year = :year";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId, ':year' => $year]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findRequestsByEmployee(int $employeeId, array $filters): array
    {
        $sql = "SELECT * FROM hr_leave_requests WHERE employee_id = :employee_id";
        // TODO: Add filters (year, status) to SQL query
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createLeaveRequest(int $employeeId, array $data): ?int
    {
        $sql = "INSERT INTO hr_leave_requests (employee_id, start_date, end_date, days_count, leave_subtype, reason, status)
                VALUES (:employee_id, :start_date, :end_date, :days_count, :leave_subtype, :reason, 'pending')";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':employee_id' => $employeeId,
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':days_count' => $data['days_count'],
            ':leave_subtype' => $data['leave_subtype'],
            ':reason' => $data['reason']
        ]);
        return $success ? $this->db->lastInsertId() : null;
    }

    public function findRequestById(int $id): ?array
    {
        $sql = "SELECT * FROM hr_leave_requests WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
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
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function findRequestsByAdmin(array $filters): array
    {
        $sql = "SELECT r.*, e.name as employee_name, d.name as department_name
                FROM hr_leave_requests r
                JOIN hr_employees e ON r.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id";
        // TODO: Add filters (status, department_ids, year) to SQL query
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnusedBalancesByYear(int $year): array
    {
        $sql = "SELECT * FROM hr_leave_balances
                WHERE year = :year
                AND (base_leave + seniority_leave + monthly_leave + adjustment_leave) > used_leave";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':year' => $year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
