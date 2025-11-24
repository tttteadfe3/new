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

    /**
     * 모든 소모품 조회
     */
    public function findAll(array $filters = []): array
    {
        $sql = "
            SELECT 
                vc.*,
                CASE 
                    WHEN vc.current_stock <= vc.minimum_stock THEN 1
                    ELSE 0
                END as is_low_stock
            FROM vehicle_consumables vc
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['category'])) {
            $sql .= " AND vc.category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (vc.name LIKE :search OR vc.part_number LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $sql .= " AND vc.current_stock <= vc.minimum_stock";
        }
        
        $sql .= " ORDER BY vc.name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * ID로 소모품 조회
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT vc.*
            FROM vehicle_consumables vc
            WHERE vc.id = :id
        ";
        
        $result = $this->db->fetchOne($sql, [':id' => $id]);
        return $result ?: null;
    }

    /**
     * 소모품 등록
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO vehicle_consumables (
                name, category, part_number, unit, unit_price,
                current_stock, minimum_stock, location, note
            ) VALUES (
                :name, :category, :part_number, :unit, :unit_price,
                :current_stock, :minimum_stock, :location, :note
            )
        ";
        
        return $this->db->insert($sql, [
            ':name' => $data['name'],
            ':category' => $data['category'] ?? null,
            ':part_number' => $data['part_number'] ?? null,
            ':unit' => $data['unit'] ?? '개',
            ':unit_price' => $data['unit_price'] ?? 0,
            ':current_stock' => $data['current_stock'] ?? 0,
            ':minimum_stock' => $data['minimum_stock'] ?? 0,
            ':location' => $data['location'] ?? null,
            ':note' => $data['note'] ?? null
        ]);
    }

    /**
     * 소모품 수정
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'name', 'category', 'part_number', 'unit', 'unit_price',
            'current_stock', 'minimum_stock', 'location', 'note'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE vehicle_consumables SET " . implode(', ', $fields) . " WHERE id = :id";
        
        return (bool) $this->db->execute($sql, $params);
    }

    /**
     * 소모품 삭제
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM vehicle_consumables WHERE id = :id";
        return (bool) $this->db->execute($sql, [':id' => $id]);
    }

    /**
     * 재고 조정
     */
    public function adjustStock(int $id, int $quantity): bool
    {
        $sql = "
            UPDATE vehicle_consumables 
            SET current_stock = current_stock + :quantity
            WHERE id = :id
        ";
        
        return (bool) $this->db->execute($sql, [
            ':id' => $id,
            ':quantity' => $quantity
        ]);
    }

    /**
     * 카테고리 목록 조회
     */
    public function getCategories(): array
    {
        $sql = "
            SELECT DISTINCT category
            FROM vehicle_consumables
            WHERE category IS NOT NULL
            ORDER BY category ASC
        ";
        
        $results = $this->db->fetchAll($sql);
        return array_column($results, 'category');
    }

    /**
     * 사용 이력 조회
     */
    public function getUsageHistory(int $consumableId, int $limit = 50): array
    {
        $sql = "
            SELECT 
                vcu.*,
                v.vehicle_number,
                e.name as used_by_name,
                vw.work_item
            FROM vehicle_consumable_usage vcu
            LEFT JOIN vehicles v ON vcu.vehicle_id = v.id
            LEFT JOIN hr_employees e ON vcu.used_by = e.id
            LEFT JOIN vehicle_works vw ON vcu.work_id = vw.id
            WHERE vcu.consumable_id = :consumable_id
            ORDER BY vcu.used_at DESC
            LIMIT :limit
        ";
        
        return $this->db->fetchAll($sql, [
            ':consumable_id' => $consumableId,
            ':limit' => $limit
        ]);
    }

    /**
     * 입고 이력 조회
     */
    public function getStockInHistory(int $consumableId, int $limit = 50): array
    {
        $sql = "
            SELECT 
                vcsi.*,
                e.name as registered_by_name
            FROM vehicle_consumable_stock_in vcsi
            LEFT JOIN hr_employees e ON vcsi.registered_by = e.id
            WHERE vcsi.consumable_id = :consumable_id
            ORDER BY vcsi.purchase_date DESC, vcsi.created_at DESC
            LIMIT :limit
        ";
        
        return $this->db->fetchAll($sql, [
            ':consumable_id' => $consumableId,
            ':limit' => $limit
        ]);
    }

    /**
     * 사용 기록 등록
     */
    public function recordUsage(array $data): int
    {
        $sql = "
            INSERT INTO vehicle_consumable_usage (
                consumable_id, work_id, vehicle_id, quantity,
                used_by, used_at, note
            ) VALUES (
                :consumable_id, :work_id, :vehicle_id, :quantity,
                :used_by, :used_at, :note
            )
        ";
        
        return $this->db->insert($sql, [
            ':consumable_id' => $data['consumable_id'],
            ':work_id' => $data['work_id'] ?? null,
            ':vehicle_id' => $data['vehicle_id'] ?? null,
            ':quantity' => $data['quantity'],
            ':used_by' => $data['used_by'] ?? null,
            ':used_at' => $data['used_at'] ?? date('Y-m-d H:i:s'),
            ':note' => $data['note'] ?? null
        ]);
    }

    /**
     * 입고 기록 등록
     */
    public function recordStockIn(array $data): int
    {
        $sql = "
            INSERT INTO vehicle_consumable_stock_in (
                consumable_id, quantity, unit_price, supplier,
                purchase_date, registered_by, note
            ) VALUES (
                :consumable_id, :quantity, :unit_price, :supplier,
                :purchase_date, :registered_by, :note
            )
        ";
        
        return $this->db->insert($sql, [
            ':consumable_id' => $data['consumable_id'],
            ':quantity' => $data['quantity'],
            ':unit_price' => $data['unit_price'] ?? null,
            ':supplier' => $data['supplier'] ?? null,
            ':purchase_date' => $data['purchase_date'] ?? date('Y-m-d'),
            ':registered_by' => $data['registered_by'] ?? null,
            ':note' => $data['note'] ?? null
        ]);
    }
}
