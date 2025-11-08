<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\ItemCategory;
use App\Services\DataScopeService;

class ItemCategoryRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    /**
     * ID로 분류를 찾습니다.
     * @param int $id
     * @return ItemCategory|null
     */
    public function findById(int $id): ?ItemCategory
    {
        $sql = "SELECT * FROM im_item_categories WHERE id = :id";
        return $this->db->fetchOneAs(ItemCategory::class, $sql, [':id' => $id]);
    }

    /**
     * 모든 분류를 계층 구조로 가져옵니다.
     * @return array
     */
    public function getAllAsHierarchy(): array
    {
        $queryParts = [
            'sql' => "SELECT ic.* FROM im_item_categories ic",
            'params' => [],
            'where' => []
        ];

        $queryParts = $this->dataScopeService->applyItemCategoryScope($queryParts, 'ic');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY ic.parent_id, ic.name";

        $allCategories = $this->db->query($queryParts['sql'], $queryParts['params']);

        $hierarchy = [];
        $childrenOf = [];

        foreach ($allCategories as $category) {
            $childrenOf[$category['parent_id']][] = $category;
        }

        $buildHierarchy = function ($parentId) use (&$buildHierarchy, $childrenOf) {
            $result = [];
            if (isset($childrenOf[$parentId])) {
                foreach ($childrenOf[$parentId] as $category) {
                    $category['children'] = $buildHierarchy($category['id']);
                    $result[] = $category;
                }
            }
            return $result;
        };

        return $buildHierarchy(null);
    }

    /**
     * 새로운 분류를 생성합니다.
     * @param array $data
     * @return string
     */
    public function create(array $data): string
    {
        $sql = "INSERT INTO im_item_categories (parent_id, name, is_active) VALUES (:parent_id, :name, :is_active)";
        $params = [
            ':parent_id' => $data['parent_id'] ?: null,
            ':name' => $data['name'],
            ':is_active' => $data['is_active'] ?? 1,
        ];
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    /**
     * 기존 분류를 업데이트합니다.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE im_item_categories SET parent_id = :parent_id, name = :name, is_active = :is_active WHERE id = :id";
        $params = [
            ':id' => $id,
            ':parent_id' => $data['parent_id'] ?: null,
            ':name' => $data['name'],
            ':is_active' => $data['is_active'] ?? 1,
        ];
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * 분류를 삭제합니다.
     * 하위 분류나 연결된 품목이 있으면 삭제할 수 없습니다.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // 하위 분류 확인
        $sql = "SELECT COUNT(*) as count FROM im_item_categories WHERE parent_id = :id";
        $childCount = $this->db->fetchOne($sql, [':id' => $id])['count'];
        if ($childCount > 0) {
            return false;
        }

        // 연결된 품목 확인
        $sql = "SELECT COUNT(*) as count FROM im_items WHERE category_id = :id";
        $itemCount = $this->db->fetchOne($sql, [':id' => $id])['count'];
        if ($itemCount > 0) {
            return false;
        }

        return $this->db->execute("DELETE FROM im_item_categories WHERE id = :id", [':id' => $id]) > 0;
    }
}
