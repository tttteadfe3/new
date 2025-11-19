<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\SupplyStock;
use App\Services\DataScopeService;

class SupplyStockRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    /**
     * 모든 재고를 조회합니다.
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM supply_stocks ORDER BY last_updated DESC";
        return $this->db->fetchAllAs(SupplyStock::class, $sql);
    }

    /**
     * ID로 재고를 조회합니다.
     */
    public function findById(int $id): ?SupplyStock
    {
        $sql = "SELECT * FROM supply_stocks WHERE id = :id";
        $result = $this->db->fetchOneAs(SupplyStock::class, $sql, [':id' => $id]);
        return $result ?: null;
    }

    /**
     * 품목별 재고를 조회합니다.
     */
    public function findByItemId(int $itemId): ?SupplyStock
    {
        $sql = "SELECT * FROM supply_stocks WHERE item_id = :item_id";
        $result = $this->db->fetchOneAs(SupplyStock::class, $sql, [':item_id' => $itemId]);
        return $result ?: null;
    }

    /**
     * 재고 부족 품목을 조회합니다.
     */
    public function findLowStockItems(int $threshold = 10): array
    {
        $sql = "SELECT ss.*, si.item_name, si.item_code
                FROM supply_stocks ss
                JOIN supply_items si ON ss.item_id = si.id
                WHERE ss.current_stock <= :threshold
                ORDER BY ss.current_stock ASC";
        
        return $this->db->query($sql, [':threshold' => $threshold]);
    }

    /**
     * 재고가 있는 품목을 조회합니다.
     */
    public function findItemsWithStock(): array
    {
        $sql = "SELECT ss.*, si.item_name, si.item_code, si.unit
                FROM supply_stocks ss
                JOIN supply_items si ON ss.item_id = si.id
                WHERE ss.current_stock > 0
                ORDER BY si.item_name ASC";
        
        return $this->db->query($sql);
    }

    /**
     * 재고를 생성합니다.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO supply_stocks (item_id, total_purchased, total_distributed) 
                VALUES (:item_id, :total_purchased, :total_distributed)";
        
        $params = [
            ':item_id' => $data['item_id'],
            ':total_purchased' => $data['total_purchased'] ?? 0,
            ':total_distributed' => $data['total_distributed'] ?? 0
        ];

        $this->db->execute($sql, $params);
        return (int) $this->db->lastInsertId();
    }

    /**
     * 재고를 수정합니다.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE supply_stocks 
                SET total_purchased = :total_purchased, total_distributed = :total_distributed 
                WHERE id = :id";
        
        $params = [
            ':id' => $id,
            ':total_purchased' => $data['total_purchased'],
            ':total_distributed' => $data['total_distributed']
        ];

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * 재고를 삭제합니다.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM supply_stocks WHERE id = :id";
        return $this->db->execute($sql, [':id' => $id]) > 0;
    }

    /**
     * 재고를 업데이트합니다.
     */
    public function updateStock(int $itemId, int $quantity, string $type): bool
    {
        // 재고 레코드가 없으면 생성
        $this->initializeStock($itemId);

        switch ($type) {
            case 'purchase':
                $sql = "UPDATE supply_stocks 
                        SET total_purchased = total_purchased + :quantity 
                        WHERE item_id = :item_id";
                break;
            case 'distribution':
                $sql = "UPDATE supply_stocks 
                        SET total_distributed = total_distributed + :quantity 
                        WHERE item_id = :item_id";
                break;
            case 'cancel_distribution':
                $sql = "UPDATE supply_stocks 
                        SET total_distributed = GREATEST(0, total_distributed - :quantity) 
                        WHERE item_id = :item_id";
                break;
            default:
                return false;
        }

        return $this->db->execute($sql, [
            ':item_id' => $itemId,
            ':quantity' => $quantity
        ]) > 0;
    }

    /**
     * 품목의 현재 재고를 조회합니다.
     */
    public function getCurrentStock(int $itemId): int
    {
        $sql = "SELECT current_stock FROM supply_stocks WHERE item_id = :item_id";
        $result = $this->db->fetchOne($sql, [':item_id' => $itemId]);
        return $result ? (int) $result['current_stock'] : 0;
    }

    /**
     * 재고 이력을 조회합니다.
     */
    public function getStockHistory(int $itemId): array
    {
        // 구매 이력
        $purchasesSql = "SELECT 'purchase' as type, purchase_date as date, quantity, 
                         CONCAT('구매 - ', supplier) as description
                         FROM supply_purchases 
                         WHERE item_id = :item_id AND is_received = 1
                         ORDER BY purchase_date DESC";

        // 지급 이력
        $distributionsSql = "SELECT 'distribution' as type, distribution_date as date, quantity, 
                            CONCAT('지급 - ', he.name, ' (', hd.name, ')') as description
                            FROM supply_distributions sd
                            JOIN hr_employees he ON sd.employee_id = he.id
                            JOIN hr_departments hd ON sd.department_id = hd.id
                            WHERE sd.item_id = :item_id AND sd.is_cancelled = 0
                            ORDER BY distribution_date DESC";

        $purchases = $this->db->query($purchasesSql, [':item_id' => $itemId]);
        $distributions = $this->db->query($distributionsSql, [':item_id' => $itemId]);

        // 두 배열을 합치고 날짜순으로 정렬
        $history = array_merge($purchases, $distributions);
        usort($history, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $history;
    }

    /**
     * 재고 요약 정보를 조회합니다.
     */
    public function getStockSummary(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_items,
                    SUM(total_purchased) as total_purchased,
                    SUM(total_distributed) as total_distributed,
                    SUM(current_stock) as total_current_stock,
                    COUNT(CASE WHEN current_stock = 0 THEN 1 END) as out_of_stock_items,
                    COUNT(CASE WHEN current_stock <= 10 THEN 1 END) as low_stock_items
                FROM supply_stocks";
        
        return $this->db->fetchOne($sql);
    }

    /**
     * 품목 정보와 함께 재고를 조회합니다.
     */
    public function findWithItems(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT ss.*, si.item_name, si.item_code, si.unit, sc.category_name
                      FROM supply_stocks ss
                      JOIN supply_items si ON ss.item_id = si.id
                      LEFT JOIN supply_categories sc ON si.category_id = sc.id",
            'params' => [],
            'where' => []
        ];

        // supply_stocks 테이블은 전사 재고 현황을 나타내므로 별도의 데이터 스코프를 적용하지 않습니다.
        
        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY sc.category_name ASC, si.item_name ASC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 재고가 없는 품목을 조회합니다.
     */
    public function findOutOfStockItems(): array
    {
        $sql = "SELECT ss.*, si.item_name, si.item_code
                FROM supply_stocks ss
                JOIN supply_items si ON ss.item_id = si.id
                WHERE ss.current_stock = 0
                ORDER BY si.item_name ASC";
        
        return $this->db->query($sql);
    }

    /**
     * 재고 목록을 필터링하여 조회합니다.
     */
    public function getStockList(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT ss.id, si.item_code, si.item_name, COALESCE(sc.category_name, '미분류') as category_name, si.unit, ss.current_stock
                      FROM supply_stocks ss
                      JOIN supply_items si ON ss.item_id = si.id
                      LEFT JOIN supply_categories sc ON si.category_id = sc.id",
            'params' => [],
            'where' => []
        ];

        if (!empty($filters['category_id'])) {
            $queryParts['where'][] = "si.category_id = :category_id";
            $queryParts['params'][':category_id'] = $filters['category_id'];
        }

        if (!empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'in_stock':
                    $queryParts['where'][] = "ss.current_stock > 0";
                    break;
                case 'out_of_stock':
                    $queryParts['where'][] = "ss.current_stock = 0";
                    break;
            }
        }

        if (!empty($filters['search'])) {
            $queryParts['where'][] = "(si.item_name LIKE :search OR si.item_code LIKE :search)";
            $queryParts['params'][':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY sc.category_name ASC, si.item_name ASC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 품목별 재고 초기화를 합니다.
     */
    public function initializeStock(int $itemId): bool
    {
        // 이미 재고 레코드가 있는지 확인
        $existingSql = "SELECT id FROM supply_stocks WHERE item_id = :item_id";
        $existing = $this->db->fetchOne($existingSql, [':item_id' => $itemId]);

        if ($existing) {
            return true; // 이미 존재함
        }

        // 새 재고 레코드 생성
        $sql = "INSERT INTO supply_stocks (item_id, total_purchased, total_distributed) 
                VALUES (:item_id, 0, 0)";
        
        return $this->db->execute($sql, [':item_id' => $itemId]) > 0;
    }
}