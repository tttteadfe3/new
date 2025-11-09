<?php

namespace App\Repositories;

use App\Core\Database;
use App\Services\DataScopeService;

class ItemStatisticRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    /**
     * 연도별 예산 대비 집행률 통계를 조회합니다.
     * @param int $year
     * @return array
     */
    public function getBudgetExecutionStats(int $year): array
    {
        $queryParts = [
            'sql' => "
                SELECT
                    COALESCE(SUM(ip.budget), 0) as total_budget,
                    (SELECT COALESCE(SUM(ipu.quantity * ipu.unit_price), 0)
                     FROM im_item_purchases ipu
                     WHERE YEAR(ipu.purchase_date) = :year) as total_executed
                FROM im_item_plans ip",
            'params' => [':year' => $year],
            'where' => ["ip.year = :year"]
        ];

        $queryParts = $this->dataScopeService->applyItemPlanScope($queryParts, 'ip');

        // This is tricky because the main query is an aggregation.
        // For simplicity, we assume if a user can see any plan, they see the aggregate.
        // A more granular approach might require subqueries.
        if (count($queryParts['where']) > 1) { // more than just the year filter
             $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        } else {
            $queryParts['sql'] .= " WHERE ip.year = :year";
        }

        return $this->db->fetchOne($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 현재 재고 현황을 품목별로 조회합니다.
     * @return array
     */
    public function getCurrentStockStatus(): array
    {
        $queryParts = [
            'sql' => "SELECT i.name as item_name, ic.name as category_name, i.stock
                      FROM im_items i
                      JOIN im_item_categories ic ON i.category_id = ic.id",
            'params' => [],
            'where' => []
        ];

        $queryParts = $this->dataScopeService->applyItemScope($queryParts, 'i');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY i.stock DESC, ic.name, i.name LIMIT 10";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 연도별, 품목별 지급 현황을 조회합니다.
     * @param int $year
     * @return array
     */
    public function getItemGiveStatsByYear(int $year): array
    {
        $queryParts = [
            'sql' => "SELECT
                        i.name as item_name,
                        ic.name as category_name,
                        SUM(ig.quantity) as total_quantity
                      FROM im_item_gives ig
                      JOIN im_items i ON ig.item_id = i.id
                      JOIN im_item_categories ic ON i.category_id = ic.id",
            'params' => [':year' => $year],
            'where' => ["YEAR(ig.give_date) = :year"]
        ];

        $queryParts = $this->dataScopeService->applyItemGiveScope($queryParts, 'ig');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " GROUP BY i.id, i.name, ic.name ORDER BY total_quantity DESC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 연도별, 부서별 지급 현황을 조회합니다.
     * @param int $year
     * @return array
     */
    public function getDepartmentGiveStatsByYear(int $year): array
    {
        $queryParts = [
            'sql' => "SELECT
                        d.name as department_name,
                        i.name as item_name,
                        SUM(ig.quantity) as total_quantity
                      FROM im_item_gives ig
                      JOIN hr_departments d ON ig.department_id = d.id
                      JOIN im_items i ON ig.item_id = i.id",
            'params' => [':year' => $year],
            'where' => ["YEAR(ig.give_date) = :year", "ig.department_id IS NOT NULL"]
        ];

        $queryParts = $this->dataScopeService->applyItemGiveScope($queryParts, 'ig');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " GROUP BY d.id, d.name, i.name ORDER BY d.name, total_quantity DESC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }
}
