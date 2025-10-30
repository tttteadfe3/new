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

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    public function rollBack(): void
    {
        $this->db->rollBack();
    }

    public function updateLeaveBalance(int $employeeId, int $year, float $days, string $columnToUpdate): bool
    {
        // First, check if a balance record for that year exists.
        $sql = "SELECT id FROM hr_leave_balances WHERE employee_id = :employee_id AND year = :year";
        $exists = $this->db->fetch($sql, [':employee_id' => $employeeId, ':year' => $year]);

        if ($exists) {
            // If it exists, update it.
            $sql = "UPDATE hr_leave_balances SET {$columnToUpdate} = {$columnToUpdate} + :days WHERE employee_id = :employee_id AND year = :year";
        } else {
            // If not, create it.
            $sql = "INSERT INTO hr_leave_balances (employee_id, year, {$columnToUpdate}) VALUES (:employee_id, :year, :days)";
        }

        return $this->db->execute($sql, [':days' => $days, ':employee_id' => $employeeId, ':year' => $year]);
    }

    public function createLeaveLog(int $employeeId, string $changeType, float $changeDays, string $reason, ?int $processorId = null, ?int $requestId = null): bool
    {
        $sql = "INSERT INTO hr_leave_logs (employee_id, change_type, change_days, reason, processor_id, request_id, created_at) VALUES (:employee_id, :change_type, :change_days, :reason, :processor_id, :request_id, NOW())";
        $params = [
            ':employee_id' => $employeeId,
            ':change_type' => $changeType,
            ':change_days' => $changeDays,
            ':reason' => $reason,
            ':processor_id' => $processorId,
            ':request_id' => $requestId
        ];
        return $this->db->execute($sql, $params);
    }

    public function getAllActiveEmployees(): array
    {
        $sql = "SELECT id, hire_date FROM hr_employees WHERE status = 'active'";
        return $this->db->fetchAll($sql);
    }

    public function findBalanceByEmployeeAndYear(int $employeeId, int $year): ?array
    {
        $sql = "SELECT * FROM hr_leave_balances WHERE employee_id = :employee_id AND year = :year";
        return $this->db->fetch($sql, [':employee_id' => $employeeId, ':year' => $year]) ?: null;
    }

    public function findRequestsByEmployee(int $employeeId, array $filters): array
    {
        $params = [':employee_id' => $employeeId];
        $whereClauses = ['employee_id = :employee_id'];

        if (!empty($filters['year'])) {
            $whereClauses[] = 'YEAR(start_date) = :year';
            $params[':year'] = $filters['year'];
        }
        if (!empty($filters['status'])) {
            $whereClauses[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        $sql = "SELECT r.*, approver.name as approver_name
                FROM hr_leave_requests r
                LEFT JOIN hr_employees approver ON r.approver_id = approver.id
                WHERE " . implode(' AND ', $whereClauses) . " ORDER BY r.start_date DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function createLeaveRequest(int $employeeId, array $data): ?int
    {
        $sql = "INSERT INTO hr_leave_requests (employee_id, leave_type, leave_subtype, start_date, end_date, days_count, reason, status, created_at) VALUES (:employee_id, :leave_type, :leave_subtype, :start_date, :end_date, :days_count, :reason, 'pending', NOW())";
        $params = [
            ':employee_id' => $employeeId,
            ':leave_type' => $data['leave_type'],
            ':leave_subtype' => $data['leave_subtype'],
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':days_count' => $data['days_count'],
            ':reason' => $data['reason'] ?? null
        ];
        if ($this->db->execute($sql, $params)) {
            return $this->db->lastInsertId();
        }
        return null;
    }

    public function findRequestById(int $id): ?array
    {
        $sql = "SELECT * FROM hr_leave_requests WHERE id = :id";
        return $this->db->fetch($sql, [':id' => $id]) ?: null;
    }

    public function updateRequestStatus(int $id, string $status, array $extraData = []): bool
    {
        $setClauses = ['status = :status', 'updated_at = NOW()'];
        $params = [':id' => $id, ':status' => $status];

        if (array_key_exists('approver_id', $extraData)) {
            $setClauses[] = 'approver_id = :approver_id';
            $params[':approver_id'] = $extraData['approver_id'];
        }
        if (array_key_exists('rejection_reason', $extraData)) {
            $setClauses[] = 'rejection_reason = :rejection_reason';
            $params[':rejection_reason'] = $extraData['rejection_reason'];
        }
        if (array_key_exists('cancellation_reason', $extraData)) {
            $setClauses[] = 'cancellation_reason = :cancellation_reason';
            $params[':cancellation_reason'] = $extraData['cancellation_reason'];
        }

        $sql = "UPDATE hr_leave_requests SET " . implode(', ', $setClauses) . " WHERE id = :id";
        return $this->db->execute($sql, $params);
    }

    public function findRequestsByAdmin(array $filters): array
    {
        $params = [];
        $whereClauses = [];

        if (!empty($filters['year'])) {
            $whereClauses[] = 'YEAR(r.start_date) = ?';
            $params[] = $filters['year'];
        }
        if (!empty($filters['status'])) {
            $whereClauses[] = 'r.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['department_ids'])) {
            $inClause = implode(',', array_fill(0, count($filters['department_ids']), '?'));
            $whereClauses[] = "e.department_id IN ({$inClause})";
            $params = array_merge($params, $filters['department_ids']);
        }

        $sql = "SELECT r.*, e.name as employee_name, d.name as department_name, approver.name as approver_name
                FROM hr_leave_requests r
                JOIN hr_employees e ON r.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_employees approver ON r.approver_id = approver.id"
                . (empty($whereClauses) ? '' : ' WHERE ' . implode(' AND ', $whereClauses))
                . " ORDER BY r.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getBalancesByAdmin(array $filters): array
    {
        $params = [];
        $whereClauses = [];

        if (!empty($filters['year'])) {
            $whereClauses[] = 'b.year = ?';
            $params[] = $filters['year'];
        }
        if (!empty($filters['department_ids'])) {
            $inClause = implode(',', array_fill(0, count($filters['department_ids']), '?'));
            $whereClauses[] = "e.department_id IN ({$inClause})";
            $params = array_merge($params, $filters['department_ids']);
        }

        $sql = "SELECT
                    e.id,
                    e.name as employee_name,
                    d.name as department_name,
                    b.*
                FROM hr_employees e
                LEFT JOIN hr_leave_balances b ON e.id = b.employee_id
                LEFT JOIN hr_departments d ON e.department_id = d.id "
                . (empty($whereClauses) ? '' : ' WHERE ' . implode(' AND ', $whereClauses))
                . " ORDER BY e.name ASC";

        return $this->db->fetchAll($sql, $params);
    }


    public function getUnusedBalancesByYear(int $year): array
    {
        $sql = "SELECT * FROM hr_leave_balances WHERE year = :year";
        return $this->db->fetchAll($sql, [':year' => $year]);
    }
}
