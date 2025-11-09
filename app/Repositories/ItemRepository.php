<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Item;
use App\Services\DataScopeService;

class ItemRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    /**
     * ID로 품목을 찾습니다.
     * @param int $id
     * @return Item|null
     */
    public function findById(int $id): ?Item
    {
        $sql = "SELECT * FROM im_items WHERE id = :id";
        return $this->db->fetchOneAs(Item::class, $sql, [':id' => $id]);
    }

    /**
     * 특정 분류에 속한 모든 품목을 가져옵니다.
     * @param int $categoryId
     * @return array
     */
    public function findByCategoryId(int $categoryId): array
    {
        $queryParts = [
            'sql' => "SELECT i.*, ic.name as category_name
                      FROM im_items i
                      JOIN im_item_categories ic ON i.category_id = ic.id",
            'params' => [],
            'where' => ['i.category_id = :category_id']
        ];
        $queryParts['params'][':category_id'] = $categoryId;

        $queryParts = $this->dataScopeService->applyItemScope($queryParts, 'i');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY i.name";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 새로운 품목을 생성합니다.
     * @param array $data
     * @return string
     */
    public function create(array $data): string
    {
        $sql = "INSERT INTO im_items (category_id, name, stock, note) VALUES (:category_id, :name, :stock, :note)";
        $params = [
            ':category_id' => $data['category_id'],
            ':name' => $data['name'],
            ':stock' => $data['stock'] ?? 0,
            ':note' => $data['note'] ?? null,
        ];
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    /**
     * 품목 정보를 업데이트합니다.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE im_items SET category_id = :category_id, name = :name, stock = :stock, note = :note WHERE id = :id";
        $params = [
            ':id' => $id,
            ':category_id' => $data['category_id'],
            ':name' => $data['name'],
            ':stock' => $data['stock'],
            ':note' => $data['note'],
        ];
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * 품목 재고를 업데이트합니다. (증감)
     * @param int $id
     * @param int $quantityChange
     * @return bool
     */
    public function updateStock(int $id, int $quantityChange): bool
    {
        $sql = "UPDATE im_items SET stock = stock + :quantityChange WHERE id = :id";
        return $this->db->execute($sql, [':id' => $id, ':quantityChange' => $quantityChange]) > 0;
    }

    /**
     * 품목을 삭제합니다.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $tablesToCheck = ['im_item_plans', 'im_item_purchases', 'im_item_gives'];
        foreach ($tablesToCheck as $table) {
            $sql = "SELECT COUNT(*) as count FROM {$table} WHERE item_id = :id";
            $count = $this->db->fetchOne($sql, [':id' => $id])['count'];
            if ($count > 0) {
                return false; // Found a dependency, cannot delete
            }
        }

        return $this->db->execute("DELETE FROM im_items WHERE id = :id", [':id' => $id]) > 0;
    }
}
