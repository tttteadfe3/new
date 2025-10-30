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

    // ... (beginTransaction, commit, rollBack, etc. - unchanged)
    public function beginTransaction(){$this->db->beginTransaction();}
    public function commit(){$this->db->commit();}
    public function rollBack(){$this->db->rollBack();}
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

        $sql = "SELECT * FROM hr_leave_requests WHERE " . implode(' AND ', $whereClauses) . " ORDER BY start_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createLeaveRequest(int $employeeId, array $data): ?int
    {
        // ... (unchanged)
        return 1;
    }

    public function findRequestById(int $id): ?array
    {
        // ... (unchanged)
        return null;
    }

    public function updateRequestStatus(int $id, string $status, array $extraData = []): bool
    {
        // ... (unchanged)
        return true;
    }

    public function findRequestsByAdmin(array $filters): array
    {
        $params = [];
        $whereClauses = [];

        if (!empty($filters['year'])) {
            $whereClauses[] = 'YEAR(r.start_date) = :year';
            $params[':year'] = $filters['year'];
        }
        if (!empty($filters['status'])) {
            $whereClauses[] = 'r.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['department_ids']) && is_array($filters['department_ids'])) {
            $inClause = implode(',', array_fill(0, count($filters['department_ids']), '?'));
            $whereClauses[] = "e.department_id IN ({$inClause})";
            $params = array_merge($params, $filters['department_ids']);
        }

        $sql = "SELECT r.*, e.name as employee_name, d.name as department_name
                FROM hr_leave_requests r
                JOIN hr_employees e ON r.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id"
                . (empty($whereClauses) ? '' : ' WHERE ' . implode(' AND ', $whereClauses))
                . " ORDER BY r.start_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnusedBalancesByYear(int $year): array
    {
        // ... (unchanged)
        return [];
    }
}
