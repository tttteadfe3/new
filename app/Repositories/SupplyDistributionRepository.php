<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\SupplyDistribution;

class SupplyDistributionRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * 모든 지급을 조회합니다.
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM supply_distributions ORDER BY distribution_date DESC, created_at DESC";
        return $this->db->fetchAllAs(SupplyDistribution::class, $sql);
    }

    /**
     * ID로 지급을 조회합니다.
     */
    public function findById(int $id): ?SupplyDistribution
    {
        $sql = "SELECT * FROM supply_distributions WHERE id = :id";
        $result = $this->db->fetchOneAs(SupplyDistribution::class, $sql, [':id' => $id]);
        return $result ?: null;
    }

    /**
     * 직원별 지급을 조회합니다.
     */
    public function findByEmployee(int $employeeId): array
    {
        $sql = "SELECT * FROM supply_distributions WHERE employee_id = :employee_id ORDER BY distribution_date DESC";
        return $this->db->fetchAllAs(SupplyDistribution::class, $sql, [':employee_id' => $employeeId]);
    }

    /**
     * 부서별 지급을 조회합니다.
     */
    public function findByDepartment(int $departmentId): array
    {
        $sql = "SELECT * FROM supply_distributions WHERE department_id = :department_id ORDER BY distribution_date DESC";
        return $this->db->fetchAllAs(SupplyDistribution::class, $sql, [':department_id' => $departmentId]);
    }

    /**
     * 품목별 지급을 조회합니다.
     */
    public function findByItemId(int $itemId): array
    {
        $sql = "SELECT * FROM supply_distributions WHERE item_id = :item_id ORDER BY distribution_date DESC";
        return $this->db->fetchAllAs(SupplyDistribution::class, $sql, [':item_id' => $itemId]);
    }

    /**
     * 날짜 범위별 지급을 조회합니다.
     */
    public function findByDateRange(string $startDate, string $endDate): array
    {
        $sql = "SELECT * FROM supply_distributions 
                WHERE distribution_date BETWEEN :start_date AND :end_date 
                ORDER BY distribution_date DESC";
        
        return $this->db->fetchAllAs(SupplyDistribution::class, $sql, [
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
    }

    /**
     * 취소되지 않은 지급을 조회합니다.
     */
    public function findActive(): array
    {
        $sql = "SELECT * FROM supply_distributions WHERE is_cancelled = 0 ORDER BY distribution_date DESC";
        return $this->db->fetchAllAs(SupplyDistribution::class, $sql);
    }

    /**
     * 취소된 지급을 조회합니다.
     */
    public function findCancelled(): array
    {
        $sql = "SELECT * FROM supply_distributions WHERE is_cancelled = 1 ORDER BY cancelled_at DESC";
        return $this->db->fetchAllAs(SupplyDistribution::class, $sql);
    }

    /**
     * 지급을 생성합니다.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO supply_distributions (item_id, employee_id, department_id, distribution_date, quantity, notes, distributed_by) 
                VALUES (:item_id, :employee_id, :department_id, :distribution_date, :quantity, :notes, :distributed_by)";
        
        $params = [
            ':item_id' => $data['item_id'],
            ':employee_id' => $data['employee_id'],
            ':department_id' => $data['department_id'],
            ':distribution_date' => $data['distribution_date'],
            ':quantity' => $data['quantity'],
            ':notes' => $data['notes'] ?? null,
            ':distributed_by' => $data['distributed_by']
        ];

        $this->db->execute($sql, $params);
        return (int) $this->db->lastInsertId();
    }

    /**
     * 지급을 수정합니다.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE supply_distributions 
                SET distribution_date = :distribution_date, quantity = :quantity, notes = :notes 
                WHERE id = :id AND is_cancelled = 0";
        
        $params = [
            ':id' => $id,
            ':distribution_date' => $data['distribution_date'],
            ':quantity' => $data['quantity'],
            ':notes' => $data['notes'] ?? null
        ];

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * 지급을 삭제합니다.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM supply_distributions WHERE id = :id";
        return $this->db->execute($sql, [':id' => $id]) > 0;
    }

    /**
     * 지급을 취소합니다.
     */
    public function cancel(int $id, int $cancelledBy, string $reason): bool
    {
        $sql = "UPDATE supply_distributions 
                SET is_cancelled = 1, cancelled_at = NOW(), cancelled_by = :cancelled_by, cancel_reason = :cancel_reason 
                WHERE id = :id";
        
        return $this->db->execute($sql, [
            ':id' => $id,
            ':cancelled_by' => $cancelledBy,
            ':cancel_reason' => $reason
        ]) > 0;
    }

    /**
     * 지급 통계를 조회합니다.
     */
    public function getDistributionStats(array $filters): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_distributions,
                    SUM(quantity) as total_quantity,
                    COUNT(DISTINCT employee_id) as unique_employees,
                    COUNT(DISTINCT department_id) as unique_departments
                FROM supply_distributions 
                WHERE is_cancelled = 0";
        
        $params = [];

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $sql .= " AND distribution_date BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $filters['start_date'];
            $params[':end_date'] = $filters['end_date'];
        }

        if (!empty($filters['item_id'])) {
            $sql .= " AND item_id = :item_id";
            $params[':item_id'] = $filters['item_id'];
        }

        if (!empty($filters['department_id'])) {
            $sql .= " AND department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * 품목별 총 지급량을 조회합니다.
     */
    public function getTotalDistributedQuantity(int $itemId): int
    {
        $sql = "SELECT COALESCE(SUM(quantity), 0) as total FROM supply_distributions WHERE item_id = :item_id AND is_cancelled = 0";
        $result = $this->db->fetchOne($sql, [':item_id' => $itemId]);
        return (int) $result['total'];
    }

    /**
     * 부서별 지급 현황을 조회합니다.
     */
    public function getDepartmentDistributionStats(int $departmentId, int $year): array
    {
        $sql = "SELECT 
                    si.item_name,
                    SUM(sd.quantity) as total_quantity,
                    COUNT(sd.id) as distribution_count,
                    COUNT(DISTINCT sd.employee_id) as employee_count
                FROM supply_distributions sd
                JOIN supply_items si ON sd.item_id = si.id
                WHERE sd.department_id = :department_id 
                AND YEAR(sd.distribution_date) = :year 
                AND sd.is_cancelled = 0
                GROUP BY sd.item_id, si.item_name
                ORDER BY total_quantity DESC";
        
        return $this->db->query($sql, [
            ':department_id' => $departmentId,
            ':year' => $year
        ]);
    }

    /**
     * 직원별 지급 현황을 조회합니다.
     */
    public function getEmployeeDistributionStats(int $employeeId, int $year): array
    {
        $sql = "SELECT 
                    si.item_name,
                    SUM(sd.quantity) as total_quantity,
                    COUNT(sd.id) as distribution_count,
                    MAX(sd.distribution_date) as last_distribution_date
                FROM supply_distributions sd
                JOIN supply_items si ON sd.item_id = si.id
                WHERE sd.employee_id = :employee_id 
                AND YEAR(sd.distribution_date) = :year 
                AND sd.is_cancelled = 0
                GROUP BY sd.item_id, si.item_name
                ORDER BY last_distribution_date DESC";
        
        return $this->db->query($sql, [
            ':employee_id' => $employeeId,
            ':year' => $year
        ]);
    }

    /**
     * 품목, 직원, 부서 정보와 함께 지급을 조회합니다.
     */
    public function findWithRelations(): array
    {
        $sql = "SELECT sd.*, si.item_name, si.item_code, he.name as employee_name, hd.name as department_name
                FROM supply_distributions sd
                JOIN supply_items si ON sd.item_id = si.id
                JOIN hr_employees he ON sd.employee_id = he.id
                JOIN hr_departments hd ON sd.department_id = hd.id
                ORDER BY sd.distribution_date DESC, sd.created_at DESC";
        
        return $this->db->query($sql);
    }

    /**
     * 연도별 지급 통계를 조회합니다.
     */
    public function getDistributionStatsByYear(int $year): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_distributions,
                    SUM(quantity) as total_quantity,
                    COUNT(DISTINCT employee_id) as unique_employees,
                    COUNT(DISTINCT department_id) as unique_departments,
                    COUNT(DISTINCT item_id) as unique_items
                FROM supply_distributions 
                WHERE YEAR(distribution_date) = :year AND is_cancelled = 0";
        
        return $this->db->fetchOne($sql, [':year' => $year]);
    }
}