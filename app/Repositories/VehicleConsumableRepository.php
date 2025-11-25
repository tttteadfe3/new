<?php

namespace App\Repositories;

use App\Core\Database;

class VehicleConsumableRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // ============ 카테고리 관리 ============
    
    /**
     * 모든 카테고리 조회 (트리 구조)
     */
    public function findAllCategories(array $filters = []): array
    {
        $sql = "
            SELECT 
                vc.*,
                COALESCE(
                    (SELECT SUM(quantity) FROM vehicle_consumable_stock WHERE category_id = vc.id),
                    0
                ) - COALESCE(
                    (SELECT SUM(quantity) FROM vehicle_consumable_usage WHERE category_id = vc.id),
                    0
                ) as current_stock
            FROM vehicle_consumables_categories vc
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['parent_id'])) {
            $sql .= " AND vc.parent_id = :parent_id";
            $params[':parent_id'] = $filters['parent_id'];
        }
        
        if (isset($filters['level'])) {
            $sql .= " AND vc.level = :level";
            $params[':level'] = $filters['level'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND vc.name LIKE :search";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY vc.path ASC, vc.sort_order ASC, vc.name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * 카테고리 트리 구조로 조회
     */
    public function getCategoryTree(): array
    {
        $sql = "
            SELECT 
                id,
                name,
                parent_id,
                level,
                path,
                unit,
                sort_order
            FROM vehicle_consumables_categories
            ORDER BY path ASC, sort_order ASC
        ";
        
        $categories = $this->db->fetchAll($sql);
        return $this->buildTree($categories);
    }

    /**
     * 배열을 트리 구조로 변환
     */
    private function buildTree(array $elements, $parentId = null): array
    {
        $branch = [];
        
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        
        return $branch;
    }

    /**
     * ID로 카테고리 조회
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                vc.*,
                COALESCE(
                    (SELECT SUM(quantity) FROM vehicle_consumable_stock WHERE category_id = vc.id),
                    0
                ) - COALESCE(
                    (SELECT SUM(quantity) FROM vehicle_consumable_usage WHERE category_id = vc.id),
                    0
                ) as current_stock,
                (SELECT COUNT(*) FROM vehicle_consumables_categories WHERE parent_id = vc.id) as children_count
            FROM vehicle_consumables_categories vc
            WHERE vc.id = :id
        ";
        
        $result = $this->db->fetchOne($sql, [':id' => $id]);
        return $result ?: null;
    }

    /**
     * 카테고리 등록
     */
    public function create(array $data): int
    {
        // path 계산
        if (!empty($data['parent_id'])) {
            $parent = $this->findById($data['parent_id']);
            $data['level'] = $parent['level'] + 1;
        } else {
            $data['level'] = 1;
        }
        
        $sql = "
            INSERT INTO vehicle_consumables_categories (
                name, parent_id, level, sort_order, unit, note
            ) VALUES (
                :name, :parent_id, :level, :sort_order, :unit, :note
            )
        ";
        
        $id = $this->db->insert($sql, [
            ':name' => $data['name'],
            ':parent_id' => $data['parent_id'] ?? null,
            ':level' => $data['level'],
            ':sort_order' => $data['sort_order'] ?? 0,
            ':unit' => $data['unit'] ?? '',
            ':note' => $data['note'] ?? null
        ]);
        
        // path 업데이트
        $this->updatePath($id);
        
        return $id;
    }

    /**
     * path 필드 업데이트
     */
    private function updatePath(int $id): bool
    {
        $category = $this->findById($id);
        
        if (empty($category['parent_id'])) {
            $path = (string)$id;
        } else {
            $parent = $this->findById($category['parent_id']);
            $path = $parent['path'] . '/' . $id;
        }
        
        $sql = "UPDATE vehicle_consumables_categories SET path = :path WHERE id = :id";
        return (bool)$this->db->execute($sql, [':path' => $path, ':id' => $id]);
    }

    /**
     * 카테고리 수정
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['name', 'parent_id', 'sort_order', 'unit', 'note'];
        $setClauses = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $setClauses[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($setClauses)) {
            return false;
        }
        
        $sql = "
            UPDATE vehicle_consumables_categories 
            SET " . implode(', ', $setClauses) . "
            WHERE id = :id
        ";
        
        $result = (bool)$this->db->execute($sql, $params);
        
        // parent_id가 변경되면 path와 level 재계산
        if (isset($data['parent_id'])) {
            $this->updatePath($id);
        }
        
        return $result;
    }

    /**
     * 카테고리 삭제
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM vehicle_consumables_categories WHERE id = :id";
        return (bool)$this->db->execute($sql, [':id' => $id]);
    }

    /**
     * 자식 카테고리 존재 여부
     */
    public function hasChildren(int $id): bool
    {
        $sql = "SELECT COUNT(*) as count FROM vehicle_consumables_categories WHERE parent_id = :id";
        $result = $this->db->fetchOne($sql, [':id' => $id]);
        return $result && $result['count'] > 0;
    }

    // ============ 입고 관리 ============

    /**
     * 입고 기록
     */
    public function recordStockIn(array $data): int
    {
        $sql = "
            INSERT INTO vehicle_consumable_stock (
                category_id, item_name, quantity, unit_price, 
                purchase_date, registered_by, note
            ) VALUES (
                :category_id, :item_name, :quantity, :unit_price,
                :purchase_date, :registered_by, :note
            )
        ";
        
        return $this->db->insert($sql, [
            ':category_id' => $data['category_id'],
            ':item_name' => $data['item_name'],
            ':quantity' => $data['quantity'],
            ':unit_price' => $data['unit_price'] ?? null,
            ':purchase_date' => $data['purchase_date'] ?? date('Y-m-d'),
            ':registered_by' => $data['registered_by'] ?? null,
            ':note' => $data['note'] ?? null
        ]);
    }

    /**
     * 입고 이력 조회
     */
    public function getStockInHistory(int $categoryId): array
    {
        $sql = "
            SELECT 
                vcs.*,
                e.name as registered_by_name
            FROM vehicle_consumable_stock vcs
            LEFT JOIN employees e ON vcs.registered_by = e.id
            WHERE vcs.category_id = :category_id
            ORDER BY vcs.purchase_date DESC, vcs.created_at DESC
        ";
        
        return $this->db->fetchAll($sql, [':category_id' => $categoryId]);
    }

    // ============ 사용 관리 ============

    /**
     * 사용 기록
     */
    public function recordUsage(array $data): int
    {
        $sql = "
            INSERT INTO vehicle_consumable_usage (
                category_id, item_name, maintenance_id, vehicle_id, 
                quantity, used_by, note
            ) VALUES (
                :category_id, :item_name, :maintenance_id, :vehicle_id,
                :quantity, :used_by, :note
            )
        ";
        
        return $this->db->insert($sql, [
            ':category_id' => $data['category_id'],
            ':item_name' => $data['item_name'] ?? null,
            ':maintenance_id' => $data['maintenance_id'] ?? null,
            ':vehicle_id' => $data['vehicle_id'] ?? null,
            ':quantity' => $data['quantity'],
            ':used_by' => $data['used_by'] ?? null,
            ':note' => $data['note'] ?? null
        ]);
    }

    /**
     * 사용 이력 조회
     */
    public function getUsageHistory(int $categoryId): array
    {
        $sql = "
            SELECT 
                vcu.*,
                v.vehicle_number,
                e.name as used_by_name,
                vm.work_item
            FROM vehicle_consumable_usage vcu
            LEFT JOIN vehicles v ON vcu.vehicle_id = v.id
            LEFT JOIN employees e ON vcu.used_by = e.id
            LEFT JOIN vehicle_maintenance vm ON vcu.maintenance_id = vm.id
            WHERE vcu.category_id = :category_id
            ORDER BY vcu.used_at DESC
        ";
        
        return $this->db->fetchAll($sql, [':category_id' => $categoryId]);
    }

    // ============ 재고 조회 ============

    /**
     * 카테고리별 재고 조회
     */
    public function getStockByCategory(int $categoryId): array
    {
        $sql = "
            SELECT 
                c.id,
                c.name as category_name,
                c.unit,
                COALESCE(SUM(s.quantity), 0) as total_stock_in,
                COALESCE(SUM(u.quantity), 0) as total_usage,
                COALESCE(SUM(s.quantity), 0) - COALESCE(SUM(u.quantity), 0) as current_stock
            FROM vehicle_consumables_categories c
            LEFT JOIN vehicle_consumable_stock s ON c.id = s.category_id
            LEFT JOIN vehicle_consumable_usage u ON c.id = u.category_id
            WHERE c.id = :category_id
            GROUP BY c.id
        ";
        
        $result = $this->db->fetchOne($sql, [':category_id' => $categoryId]);
        return $result ?: [];
    }

    /**
     * 품명별 재고 조회
     */
    public function getStockByItem(int $categoryId): array
    {
        $sql = "
            SELECT 
                c.name as category_name,
                c.unit,
                s.item_name,
                SUM(s.quantity) as stock_in,
                COALESCE((
                    SELECT SUM(quantity)
                    FROM vehicle_consumable_usage
                    WHERE category_id = c.id AND item_name = s.item_name
                ), 0) as used,
                SUM(s.quantity) - COALESCE((
                    SELECT SUM(quantity)
                    FROM vehicle_consumable_usage
                    WHERE category_id = c.id AND item_name = s.item_name
                ), 0) as current_stock
            FROM vehicle_consumable_stock s
            INNER JOIN vehicle_consumables_categories c ON s.category_id = c.id
            WHERE c.id = :category_id
            GROUP BY s.item_name
            ORDER BY s.item_name
        ";
        
        return $this->db->fetchAll($sql, [':category_id' => $categoryId]);
    }

    /**
     * 재고 조정 (입고 테이블에 직접 기록)
     */
    public function adjustStock(int $categoryId, int $quantity, string $itemName = '재고조정'): bool
    {
        $sql = "
            INSERT INTO vehicle_consumable_stock (
                category_id, item_name, quantity, purchase_date, note
            ) VALUES (
                :category_id, :item_name, :quantity, :purchase_date, '재고 조정'
            )
        ";
        
        $this->db->insert($sql, [
            ':category_id' => $categoryId,
            ':item_name' => $itemName,
            ':quantity' => $quantity,
            ':purchase_date' => date('Y-m-d')
        ]);
        
        return true;
    }
}
