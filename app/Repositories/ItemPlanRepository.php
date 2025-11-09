<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\ItemPlan;
use App\Services\DataScopeService;

class ItemPlanRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    /**
     * ID로 계획을 찾습니다.
     * @param int $id
     * @return ItemPlan|null
     */
    public function findById(int $id): ?ItemPlan
    {
        $sql = "SELECT * FROM im_item_plans WHERE id = :id";
        return $this->db->fetchOneAs(ItemPlan::class, $sql, [':id' => $id]);
    }

    /**
     * 특정 연도의 모든 계획을 가져옵니다.
     * @param int $year
     * @return array
     */
    public function findByYear(int $year): array
    {
        $queryParts = [
            'sql' => "SELECT
                        ip.*,
                        i.name as item_name,
                        ic.id as category_id,
                        ic.name as category_name,
                        e.name as creator_name
                      FROM im_item_plans ip
                      JOIN im_items i ON ip.item_id = i.id
                      JOIN im_item_categories ic ON i.category_id = ic.id
                      LEFT JOIN hr_employees e ON ip.created_by = e.id
                      WHERE ip.year = :year",
            'params' => [':year' => $year],
            'where' => []
        ];

        $queryParts = $this->dataScopeService->applyItemPlanScope($queryParts, 'ip');

        // The base query already has a WHERE clause for the year.
        // We only need to add the data scope conditions.
        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " AND " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY ic.name, i.name";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 새로운 계획을 생성합니다.
     * @param array $data
     * @return string
     */
    public function create(array $data): string
    {
        $sql = "INSERT INTO im_item_plans (year, item_id, unit_price, quantity, note, created_by)
                VALUES (:year, :item_id, :unit_price, :quantity, :note, :created_by)";
        $params = [
            ':year' => $data['year'],
            ':item_id' => $data['item_id'],
            ':unit_price' => $data['unit_price'],
            ':quantity' => $data['quantity'],
            ':note' => $data['note'] ?? null,
            ':created_by' => $data['created_by'],
        ];
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    /**
     * 계획을 업데이트합니다.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE im_item_plans
                SET year = :year, item_id = :item_id, unit_price = :unit_price, quantity = :quantity, note = :note
                WHERE id = :id";
        $params = [
            ':id' => $id,
            ':year' => $data['year'],
            ':item_id' => $data['item_id'],
            ':unit_price' => $data['unit_price'],
            ':quantity' => $data['quantity'],
            ':note' => $data['note'] ?? null,
        ];
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * 계획을 삭제합니다.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "SELECT COUNT(*) as count FROM im_item_purchases WHERE plan_id = :id";
        $count = $this->db->fetchOne($sql, [':id' => $id])['count'];
        if ($count > 0) {
            return false; // Found a dependency, cannot delete
        }

        return $this->db->execute("DELETE FROM im_item_plans WHERE id = :id", [':id' => $id]) > 0;
    }

    /**
     * 여러 계획을 한 번에 생성합니다. (Transaction)
     * @param array $plansData
     * @return int
     * @throws \Exception
     */
    public function createBulk(array $plansData): int
    {
        if (empty($plansData)) {
            return 0;
        }

        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO im_item_plans (year, item_id, unit_price, quantity, note, created_by)
                    VALUES (:year, :item_id, :unit_price, :quantity, :note, :created_by)";

            $rowCount = 0;
            foreach ($plansData as $data) {
                $params = [
                    ':year' => $data['year'],
                    ':item_id' => $data['item_id'],
                    ':unit_price' => $data['unit_price'],
                    ':quantity' => $data['quantity'],
                    ':note' => $data['note'] ?? null,
                    ':created_by' => $data['created_by'],
                ];
                $this->db->execute($sql, $params);
                $rowCount++;
            }

            $this->db->commit();
            return $rowCount;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
