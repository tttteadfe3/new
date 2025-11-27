<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\SupplyPlan;

class SupplyPlanRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * 모든 계획을 조회합니다.
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM supply_plans ORDER BY year DESC, created_at DESC";
        return $this->db->fetchAllAs(SupplyPlan::class, $sql);
    }

    /**
     * ID로 계획을 조회합니다.
     */
    public function findById(int $id): ?SupplyPlan
    {
        $sql = "SELECT * FROM supply_plans WHERE id = :id";
        $result = $this->db->fetchOneAs(SupplyPlan::class, $sql, [':id' => $id]);
        return $result ?: null;
    }

    /**
     * 연도별 계획을 조회합니다.
     */
    public function findByYear(int $year): array
    {
        $sql = "SELECT * FROM supply_plans WHERE year = :year ORDER BY created_at DESC";
        return $this->db->fetchAllAs(SupplyPlan::class, $sql, [':year' => $year]);
    }

    /**
     * 연도와 분류별 계획을 조회합니다.
     */
    public function findByYearAndCategory(int $year, int $categoryId): array
    {
        $sql = "SELECT sp.* FROM supply_plans sp
                JOIN supply_items si ON sp.item_id = si.id
                WHERE sp.year = :year AND si.category_id = :category_id
                ORDER BY sp.created_at DESC";
        
        return $this->db->fetchAllAs(SupplyPlan::class, $sql, [
            ':year' => $year,
            ':category_id' => $categoryId
        ]);
    }

    /**
     * 연도와 품목별 계획을 조회합니다.
     */
    public function findByYearAndItem(int $year, int $itemId): ?SupplyPlan
    {
        $sql = "SELECT * FROM supply_plans WHERE year = :year AND item_id = :item_id";
        $result = $this->db->fetchOneAs(SupplyPlan::class, $sql, [
            ':year' => $year,
            ':item_id' => $itemId
        ]);
        return $result ?: null;
    }

    /**
     * 계획을 생성합니다.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO supply_plans (year, item_id, planned_quantity, unit_price, notes, created_by) 
                VALUES (:year, :item_id, :planned_quantity, :unit_price, :notes, :created_by)";
        
        $params = [
            ':year' => $data['year'],
            ':item_id' => $data['item_id'],
            ':planned_quantity' => $data['planned_quantity'],
            ':unit_price' => $data['unit_price'],
            ':notes' => $data['notes'] ?? null,
            ':created_by' => $data['created_by']
        ];

        $this->db->execute($sql, $params);
        return (int) $this->db->lastInsertId();
    }

    /**
     * 계획을 수정합니다.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE supply_plans 
                SET planned_quantity = :planned_quantity, unit_price = :unit_price, notes = :notes 
                WHERE id = :id";
        
        $params = [
            ':id' => $id,
            ':planned_quantity' => $data['planned_quantity'],
            ':unit_price' => $data['unit_price'],
            ':notes' => $data['notes'] ?? null
        ];

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * 계획을 삭제합니다.
     */
    public function delete(int $id): bool
    {
        // 연관된 데이터가 있는지 확인
        if ($this->hasAssociatedPurchases($id) || $this->hasAssociatedDistributions($id)) {
            return false;
        }

        $sql = "DELETE FROM supply_plans WHERE id = :id";
        return $this->db->execute($sql, [':id' => $id]) > 0;
    }

    /**
     * 대량 계획을 생성합니다.
     */
    public function createBulk(array $plans): bool
    {
        if (empty($plans)) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $sql = "INSERT INTO supply_plans (year, item_id, planned_quantity, unit_price, notes, created_by) 
                    VALUES (:year, :item_id, :planned_quantity, :unit_price, :notes, :created_by)";

            foreach ($plans as $plan) {
                $params = [
                    ':year' => $plan['year'],
                    ':item_id' => $plan['item_id'],
                    ':planned_quantity' => $plan['planned_quantity'],
                    ':unit_price' => $plan['unit_price'],
                    ':notes' => $plan['notes'] ?? null,
                    ':created_by' => $plan['created_by']
                ];

                $this->db->execute($sql, $params);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * 계획에 연관된 구매가 있는지 확인합니다.
     */
    public function hasAssociatedPurchases(int $planId): bool
    {
        // 계획의 품목과 연도를 기준으로 구매가 있는지 확인
        $sql = "SELECT COUNT(*) as count FROM supply_purchases sp
                JOIN supply_plans spl ON sp.item_id = spl.item_id
                WHERE spl.id = :plan_id AND YEAR(sp.purchase_date) = spl.year";
        
        $result = $this->db->fetchOne($sql, [':plan_id' => $planId]);
        return $result['count'] > 0;
    }

    /**
     * 계획에 연관된 지급이 있는지 확인합니다.
     */
    public function hasAssociatedDistributions(int $planId): bool
    {
        // 계획의 품목과 연도를 기준으로 지급이 있는지 확인
        $sql = "SELECT COUNT(*) as count FROM supply_distributions sd
                JOIN supply_plans spl ON sd.item_id = spl.item_id
                WHERE spl.id = :plan_id AND YEAR(sd.distribution_date) = spl.year";
        
        $result = $this->db->fetchOne($sql, [':plan_id' => $planId]);
        return $result['count'] > 0;
    }

    /**
     * 연도별 예산 요약을 조회합니다.
     */
    public function getBudgetSummaryByYear(int $year): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_items,
                    SUM(planned_quantity) as total_quantity,
                    SUM(planned_quantity * unit_price) as total_budget,
                    AVG(unit_price) as avg_unit_price
                FROM supply_plans 
                WHERE year = :year";
        
        return $this->db->fetchOne($sql, [':year' => $year]);
    }

    /**
     * 품목 정보와 함께 계획을 조회합니다.
     */
    public function findWithItems(int $year): array
    {
        $queryParts = [
            'sql' => "SELECT sp.*, si.item_name, si.item_code, si.unit, sc.category_name
                      FROM supply_plans sp
                      JOIN supply_items si ON sp.item_id = si.id
                      LEFT JOIN supply_categories sc ON si.category_id = sc.id",
            'params' => [':year' => $year],
            'where' => ["sp.year = :year"]
        ];
        
        // supply_plans 테이블은 현재 HR 관련 정보와 직접 조인되지 않아 부서/직원 스코프 적용이 어렵습니다.
        // 향후 권한 정책이 확장되면 이 부분에 스코프 적용이 필요할 수 있습니다.

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY sc.category_name ASC, si.item_name ASC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 중복 계획이 있는지 확인합니다.
     */
    public function isDuplicatePlan(int $year, int $itemId, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM supply_plans WHERE year = :year AND item_id = :item_id";
        $params = [':year' => $year, ':item_id' => $itemId];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] > 0;
    }
}