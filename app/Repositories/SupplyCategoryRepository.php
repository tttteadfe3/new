<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\SupplyCategory;
use App\Services\DataScopeService;

class SupplyCategoryRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    /**
     * 모든 분류를 조회합니다.
     */
    public function findAll(): array
    {
        $queryParts = [
            'sql' => "SELECT * FROM supply_categories",
            'params' => [],
            'where' => []
        ];

        // supply_categories 테이블은 전사 공통 데이터로 간주되므로 별도의 데이터 스코프를 적용하지 않습니다.

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY level ASC, display_order ASC, category_name ASC";

        return $this->db->fetchAllAs(SupplyCategory::class, $queryParts['sql'], $queryParts['params']);
    }

    /**
     * ID로 분류를 조회합니다.
     */
    public function findById(int $id): ?SupplyCategory
    {
        $sql = "SELECT * FROM supply_categories WHERE id = :id";
        $result = $this->db->fetchOneAs(SupplyCategory::class, $sql, [':id' => $id]);
        return $result ?: null;
    }

    /**
     * 레벨별 분류를 조회합니다.
     */
    public function findByLevel(int $level): array
    {
        $sql = "SELECT * FROM supply_categories WHERE level = :level ORDER BY display_order ASC, category_name ASC";
        return $this->db->fetchAllAs(SupplyCategory::class, $sql, [':level' => $level]);
    }

    /**
     * 활성 분류만 조회합니다.
     */
    public function findActiveCategories(): array
    {
        $sql = "SELECT * FROM supply_categories WHERE is_active = 1 ORDER BY level ASC, display_order ASC, category_name ASC";
        return $this->db->fetchAllAs(SupplyCategory::class, $sql);
    }

    /**
     * 상위 분류의 하위 분류들을 조회합니다.
     */
    public function findByParentId(int $parentId): array
    {
        $sql = "SELECT * FROM supply_categories WHERE parent_id = :parent_id ORDER BY display_order ASC, category_name ASC";
        return $this->db->fetchAllAs(SupplyCategory::class, $sql, [':parent_id' => $parentId]);
    }

    /**
     * 분류를 생성합니다.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO supply_categories (parent_id, category_code, category_name, level, is_active, display_order) 
                VALUES (:parent_id, :category_code, :category_name, :level, :is_active, :display_order)";
        
        $params = [
            ':parent_id' => $data['parent_id'] ?? null,
            ':category_code' => $data['category_code'],
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
        // 동적으로 업데이트할 필드 구성
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
            return false; // 업데이트할 필드가 없음
        }
        
        $sql = "UPDATE supply_categories 
                SET " . implode(', ', $updateFields) . " 
                WHERE id = :id";

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * 분류를 삭제합니다.
     */
    public function delete(int $id): bool
    {
        // 연관된 데이터가 있는지 확인
        if ($this->hasAssociatedItems($id)) {
            return false;
        }

        // 하위 분류가 있는지 확인
        $childrenSql = "SELECT COUNT(*) as count FROM supply_categories WHERE parent_id = :id";
        $childrenResult = $this->db->fetchOne($childrenSql, [':id' => $id]);
        if ($childrenResult['count'] > 0) {
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
     * 분류 코드가 중복되는지 확인합니다.
     */
    public function isDuplicateCategoryCode(string $categoryCode, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM supply_categories WHERE category_code = :category_code";
        $params = [':category_code' => $categoryCode];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * 계층적 분류 구조를 조회합니다.
     */
    public function findHierarchical(): array
    {
        $queryParts = [
            'sql' => "SELECT c1.*, c2.category_name as parent_name
                      FROM supply_categories c1
                      LEFT JOIN supply_categories c2 ON c1.parent_id = c2.id",
            'params' => [],
            'where' => []
        ];

        // supply_categories 테이블은 전사 공통 데이터로 간주되므로 별도의 데이터 스코프를 적용하지 않습니다.

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY c1.level ASC, c1.display_order ASC, c1.category_name ASC";
        
        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }
}