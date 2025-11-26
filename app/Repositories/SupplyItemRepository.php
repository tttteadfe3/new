<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\SupplyItem;
use App\Services\DataScopeService;

class SupplyItemRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    /**
     * 모든 품목을 조회합니다.
     */
    public function findAll(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT si.*, sc.category_name
                      FROM supply_items si
                      LEFT JOIN supply_categories sc ON si.category_id = sc.id",
            'params' => [],
            'where' => []
        ];

        // supply_items 테이블은 전사 공통 데이터로 간주되므로 별도의 데이터 스코프를 적용하지 않습니다.

        if (!empty($filters['category_id'])) {
            $queryParts['where'][] = "si.category_id = :category_id";
            $queryParts['params'][':category_id'] = $filters['category_id'];
        }

        if (isset($filters['is_active'])) {
            $queryParts['where'][] = "si.is_active = :is_active";
            $queryParts['params'][':is_active'] = (int)$filters['is_active'];
        }

        if (!empty($filters['search'])) {
            $queryParts['where'][] = "si.item_name LIKE :search";
            $queryParts['params'][':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY si.item_name ASC";

        return $this->db->fetchAll($queryParts['sql'], $queryParts['params']);
    }

    /**
     * ID로 품목을 조회합니다.
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT si.*, sc.category_name 
                FROM supply_items si
                LEFT JOIN supply_categories sc ON si.category_id = sc.id
                WHERE si.id = :id";
        $result = $this->db->fetchOne($sql, [':id' => $id]);
        return $result ?: null;
    }



    /**
     * 활성 품목만 조회합니다.
     */
    public function findActiveItems(): array
    {
        $sql = "SELECT si.*, sc.category_name 
                FROM supply_items si
                LEFT JOIN supply_categories sc ON si.category_id = sc.id
                WHERE si.is_active = 1
                ORDER BY si.item_name ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * 분류별 품목을 조회합니다.
     */
    public function findByCategoryId(int $categoryId): array
    {
        $sql = "SELECT * FROM supply_items WHERE category_id = :category_id ORDER BY item_name ASC";
        return $this->db->fetchAll($sql, [':category_id' => $categoryId]);
    }



    /**
     * 품목을 생성합니다.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO supply_items (item_name, category_id, unit, description, is_active, created_at, updated_at)
                VALUES (:item_name, :category_id, :unit, :description, :is_active, NOW(), NOW())";

        $params = [
            ':item_name' => $data['item_name'],
            ':category_id' => $data['category_id'],
            ':unit' => $data['unit'] ?? '개',
            ':description' => $data['description'] ?? null,
            ':is_active' => $data['is_active'] ?? 1
        ];

        $this->db->execute($sql, $params);
        return (int)$this->db->lastInsertId();
    }

    /**
     * 품목을 수정합니다.
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE supply_items SET " . implode(', ', $fields) . " WHERE id = :id";

        return $this->db->execute($sql, $params);
    }

    /**
     * 품목을 삭제합니다.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM supply_items WHERE id = :id";
        return $this->db->execute($sql, [':id' => $id]);
    }

    /**
     * 품목에 연관된 계획이 있는지 확인
     */
    public function hasAssociatedPlans(int $itemId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM supply_plans WHERE item_id = :item_id";
        $result = $this->db->fetchOne($sql, [':item_id' => $itemId]);
        return $result['count'] > 0;
    }

    /**
     * 품목에 연관된 구매가 있는지 확인
     */
    public function hasAssociatedPurchases(int $itemId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM supply_purchases WHERE item_id = :item_id";
        $result = $this->db->fetchOne($sql, [':item_id' => $itemId]);
        return $result['count'] > 0;
    }

    /**
     * 품목에 연관된 지급이 있는지 확인
     */
    public function hasAssociatedDistributions(int $itemId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM supply_distributions WHERE item_id = :item_id";
        $result = $this->db->fetchOne($sql, [':item_id' => $itemId]);
        return $result['count'] > 0;
    }


}
