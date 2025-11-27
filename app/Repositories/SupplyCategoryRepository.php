<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\SupplyCategory;

class SupplyCategoryRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * 모든 분류를 조회합니다.
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM supply_categories ORDER BY level ASC, display_order ASC, category_name ASC";
        $results = $this->db->fetchAll($sql);
        return array_map(fn($row) => SupplyCategory::make($row), $results);
    }

    /**
     * ID로 분류를 조회합니다.
     */
    public function findById(int $id): ?SupplyCategory
    {
        $sql = "SELECT * FROM supply_categories WHERE id = :id";
        $row = $this->db->fetchOne($sql, [':id' => $id]);
        return $row ? SupplyCategory::make($row) : null;
    }

    /**
     * 레벨별 분류를 조회합니다.
     */
    public function findByLevel(int $level): array
    {
        $sql = "SELECT * FROM supply_categories WHERE level = :level ORDER BY display_order ASC, category_name ASC";
        $results = $this->db->fetchAll($sql, [':level' => $level]);
        return array_map(fn($row) => SupplyCategory::make($row), $results);
    }

    /**
     * 활성 분류만 조회합니다.
     */
    public function findActiveCategories(): array
    {
        $sql = "SELECT * FROM supply_categories WHERE is_active = 1 ORDER BY level ASC, display_order ASC, category_name ASC";
        $results = $this->db->fetchAll($sql);
        return array_map(fn($row) => SupplyCategory::make($row), $results);
    }

    /**
     * 상위 분류의 하위 분류들을 조회합니다.
     */
    public function findByParentId(int $parentId): array
    {
        $sql = "SELECT * FROM supply_categories WHERE parent_id = :parent_id ORDER BY display_order ASC, category_name ASC";
        $results = $this->db->fetchAll($sql, [':parent_id' => $parentId]);
        return array_map(fn($row) => SupplyCategory::make($row), $results);
    }

    /**
     * 분류를 생성합니다.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO supply_categories (parent_id, category_name, level, is_active, display_order) 
                VALUES (:parent_id, :category_name, :level, :is_active, :display_order)";
        
        $params = [
            ':parent_id' => $data['parent_id'] ?? null,
            ':category_name' => $data['category_name'],
            ':level' => $data['level'],
            ':is_active' => $data['is_active'] ?? 1,
            ':display_order' => $data['display_order'] ?? 0
        ];

        $this->db->execute($sql, $params);
        return (int) $this->db->lastInsertId();
    }

    /**
     * 분류를 수정합니다.
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['category_name', 'is_active', 'display_order'];
        $updateFields = [];
        $params = [':id' => $id];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $sql = "UPDATE supply_categories SET " . implode(', ', $updateFields) . " WHERE id = :id";

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * 분류를 삭제합니다.
     */
    public function delete(int $id): bool
    {
        if ($this->hasAssociatedItems($id) || $this->hasChildren($id)) {
            return false;
        }

        $sql = "DELETE FROM supply_categories WHERE id = :id";
        return $this->db->execute($sql, [':id' => $id]) > 0;
    }

    /**
     * 분류에 연관된 품목이 있는지 확인합니다.
     */
    public function hasAssociatedItems(int $categoryId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM supply_items WHERE category_id = :category_id";
        $result = $this->db->fetchOne($sql, [':category_id' => $categoryId]);
        return $result['count'] > 0;
    }

    /**
     * 하위 분류가 있는지 확인합니다.
     */
    public function hasChildren(int $categoryId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM supply_categories WHERE parent_id = :id";
        $result = $this->db->fetchOne($sql, [':id' => $categoryId]);
        return $result['count'] > 0;
    }



    /**
     * 계층적 분류 구조를 조회합니다.
     */
    public function findHierarchical(): array
    {
        $sql = "SELECT c1.*, c2.category_name as parent_name
                FROM supply_categories c1
                LEFT JOIN supply_categories c2 ON c1.parent_id = c2.id
                ORDER BY c1.level ASC, c1.display_order ASC, c1.category_name ASC";
        
        return $this->db->fetchAll($sql);
    }
}
