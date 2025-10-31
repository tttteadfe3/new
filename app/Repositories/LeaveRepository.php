<?php

namespace App\Repositories;

use App\Core\Database;
use Exception;
use PDO;

class LeaveRepository
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    //======================================================================
    // Transaction Helpers
    //======================================================================

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

    //======================================================================
    // Core Balance and Log Operations
    //======================================================================

    public function updateLeaveBalance(int $employeeId, int $year, float $days, string $columnToUpdate): bool
    {
        $sql = "SELECT id FROM hr_leave_balances WHERE employee_id = :employee_id AND year = :year";
        $exists = $this->db->fetchOne($sql, [':employee_id' => $employeeId, ':year' => $year]);

        if ($exists) {
            $sql = "UPDATE hr_leave_balances SET {$columnToUpdate} = {$columnToUpdate} + :days, updated_at = NOW() WHERE employee_id = :employee_id AND year = :year";
        } else {
            $sql = "INSERT INTO hr_leave_balances (employee_id, year, {$columnToUpdate}) VALUES (:employee_id, :year, :days)";
        }

        return $this->db->execute($sql, [':days' => $days, ':employee_id' => $employeeId, ':year' => $year]);
    }

    public function createLeaveLog(int $employeeId, string $changeType, float $changeDays, string $reason, ?int $processorId = null, ?int $requestId = null): bool
    {
        $sql = "INSERT INTO hr_leave_logs (employee_id, change_type, change_days, reason, processor_id, leave_request_id, processed_at) VALUES (:employee_id, :change_type, :change_days, :reason, :processor_id, :request_id, NOW())";
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

    //======================================================================
    // Transactional Business Logic Methods
    //======================================================================

    public function grantLeave(int $employeeId, int $year, float $days, string $column, string $logType, string $reason, ?int $processorId): bool
    {
        $this->beginTransaction();
        try {
            $this->updateLeaveBalance($employeeId, $year, $days, $column);
            $this->createLeaveLog($employeeId, $logType, $days, $reason, $processorId);
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function processLeaveUsage(int $requestId, int $approverId): bool
    {
        $this->beginTransaction();
        try {
            $request = $this->findRequestById($requestId);
            if (!$request) throw new Exception("Leave request not found.");

            $year = (int)date('Y', strtotime($request['start_date']));
            $daysToDeduct = -abs($request['days_count']);

            $this->updateLeaveBalance($request['employee_id'], $year, $daysToDeduct, 'used_leave');
            $this->createLeaveLog($request['employee_id'], 'use', $daysToDeduct, "연차 사용 승인", $approverId, $requestId);
            $this->updateRequestStatus($requestId, 'approved', ['approver_id' => $approverId]);

            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function processLeaveCancellation(int $requestId, int $approverId): bool
    {
        $this->beginTransaction();
        try {
            $request = $this->findRequestById($requestId);
            if (!$request) throw new Exception("Leave request not found.");

            $year = (int)date('Y', strtotime($request['start_date']));
            $daysToRestore = abs($request['days_count']);

            $this->updateLeaveBalance($request['employee_id'], $year, $daysToRestore * -1, 'used_leave'); // Restore used_leave
            $this->createLeaveLog($request['employee_id'], 'cancel_use', $daysToRestore, "연차 사용 취소 승인", $approverId, $requestId);
            $this->updateRequestStatus($requestId, 'cancelled', ['approver_id' => $approverId]);

            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function expireLeave(int $employeeId, int $year, float $daysToExpire, int $adminId): bool
    {
        $this->beginTransaction();
        try {
            $daysToExpireNegative = -abs($daysToExpire);
            // Expiring leave should reduce the total entitlement, so we adjust a main bucket like 'base_leave'
            $this->updateLeaveBalance($employeeId, $year, $daysToExpireNegative, 'base_leave');
            $this->createLeaveLog($employeeId, 'expire', $daysToExpireNegative, "{$year}년 미사용 연차 소멸", $adminId);
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function applyAdjustment(int $employeeId, int $year, float $days, string $column, string $logType, string $reason, int $adminId): bool
    {
        $this->beginTransaction();
        try {
            $this->updateLeaveBalance($employeeId, $year, $days, $column);
            $this->updateLeaveBalance($employeeId, $year, $days, 'adjustment_leave');
            $this->createLeaveLog($employeeId, $logType, $days, $reason, $adminId);
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }


    //======================================================================
    // Data Retrieval & Read Operations
    //======================================================================

    public function findBalanceForEmployee(int $employeeId, int $year): ?array
    {
        $sql = "SELECT *, (base_leave + seniority_leave + monthly_leave + adjustment_leave - used_leave) as remaining_leave FROM hr_leave_balances WHERE employee_id = :employee_id AND year = :year";
        return $this->db->fetchOne($sql, [':employee_id' => $employeeId, ':year' => $year]) ?: null;
    }

    public function getRemainingBalancesForYear(int $year): array
    {
        $sql = "SELECT employee_id, (base_leave + seniority_leave + monthly_leave + adjustment_leave - used_leave) as remaining_leave FROM hr_leave_balances WHERE year = :year HAVING remaining_leave > 0";
        return $this->db->fetchAll($sql, [':year' => $year]);
    }

    public function getAllActiveEmployees(): array
    {
        $sql = "SELECT id, hire_date FROM hr_employees WHERE termination_date IS NULL";
        return $this->db->fetchAll($sql);
    }

    public function getAllActiveEmployeesWithDetails(?int $departmentId = null): array
    {
        $sql = "SELECT e.id, e.name, e.hire_date, d.name as department_name
                FROM hr_employees e
                LEFT JOIN hr_departments d ON e.department_id = d.id
                WHERE e.termination_date IS NULL";

        $params = [];
        if ($departmentId) {
            $sql .= " AND e.department_id = ?";
            $params[] = $departmentId;
        }
        return $this->db->fetchAll($sql, $params);
    }

    public function findRequestById(int $id): ?array
    {
        $sql = "SELECT * FROM hr_leave_requests WHERE id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]) ?: null;
    }

    public function findRequestsByEmployee(int $employeeId, array $filters): array
    {
        $params = [':employee_id' => $employeeId];
        $whereClauses = ['r.employee_id = :employee_id'];

        if (!empty($filters['year'])) {
            $whereClauses[] = 'YEAR(r.start_date) = :year';
            $params[':year'] = $filters['year'];
        }
        if (!empty($filters['status'])) {
            $whereClauses[] = 'r.status = :status';
            $params[':status'] = $filters['status'];
        }

        $sql = "SELECT r.*, a.name as approver_name
                FROM hr_leave_requests r
                LEFT JOIN hr_employees a ON r.approver_id = a.id
                WHERE " . implode(' AND ', $whereClauses) . " ORDER BY r.start_date DESC";
        return $this->db->fetchAll($sql, $params);
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

        $sql = "SELECT r.*, e.name as employee_name, d.name as department_name, a.name as approver_name
                FROM hr_leave_requests r
                JOIN hr_employees e ON r.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_employees a ON r.approver_id = a.id"
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
         if (!empty($filters['employee_id'])) {
            $whereClauses[] = 'e.id = ?';
            $params[] = $filters['employee_id'];
        }
        if (!empty($filters['department_ids'])) {
            $inClause = implode(',', array_fill(0, count($filters['department_ids']), '?'));
            $whereClauses[] = "e.department_id IN ({$inClause})";
            $params = array_merge($params, $filters['department_ids']);
        }
        if (isset($filters['status']) && $filters['status'] === 'active') {
             $whereClauses[] = 'e.termination_date IS NULL';
        }

        $sql = "SELECT e.id, e.name as employee_name, d.name as department_name, b.*,
                       (b.base_leave + b.seniority_leave + b.monthly_leave + b.adjustment_leave) as total_leave,
                       (b.base_leave + b.seniority_leave + b.monthly_leave + b.adjustment_leave - b.used_leave) as remaining_leave
                FROM hr_employees e
                LEFT JOIN hr_leave_balances b ON e.id = b.employee_id AND b.year = ?
                LEFT JOIN hr_departments d ON e.department_id = d.id "
                . (empty($whereClauses) ? '' : ' WHERE ' . implode(' AND ', $whereClauses))
                . " ORDER BY e.name ASC";

        // Year is needed for the join condition as well
        array_unshift($params, $filters['year'] ?? date('Y'));

        return $this->db->fetchAll($sql, $params);
    }

    //======================================================================
    // Write Operations for Requests
    //======================================================================

    public function createLeaveRequest(int $employeeId, array $data): ?int
    {
        $sql = "INSERT INTO hr_leave_requests (employee_id, leave_subtype, start_date, end_date, days_count, reason, status, created_at) VALUES (:employee_id, :leave_subtype, :start_date, :end_date, :days_count, :reason, 'pending', NOW())";
        $params = [
            ':employee_id' => $employeeId,
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

    public function updateRequestStatus(int $id, string $status, array $extraData = []): bool
    {
        $setClauses = ['status = :status', 'updated_at = NOW()'];
        $params = [':id' => $id, ':status' => $status];

        if (array_key_exists('approver_id', $extraData) && $extraData['approver_id']) {
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
}
