<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\SupplyPurchase;
use App\Services\DataScopeService;

class SupplyPurchaseRepository 
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    /**
     * 모든 구매를 조회합니다.
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM supply_purchases ORDER BY purchase_date DESC, created_at DESC";
        return $this->db->fetchAllAs(SupplyPurchase::class, $sql);
    }

    /**
     * ID로 구매를 조회합니다.
     */
    public function findById(int $id): ?SupplyPurchase
    {
        $sql = "SELECT * FROM supply_purchases WHERE id = :id";
        $result = $this->db->fetchOneAs(SupplyPurchase::class, $sql, [':id' => $id]);
        return $result ?: null;
    }

    /**
     * 품목별 구매를 조회합니다.
     */
    public function findByItemId(int $itemId): array
    {
        $sql = "SELECT * FROM supply_purchases WHERE item_id = :item_id ORDER BY purchase_date DESC";
        return $this->db->fetchAllAs(SupplyPurchase::class, $sql, [':item_id' => $itemId]);
    }

    /**
     * 날짜 범위별 구매를 조회합니다.
     */
    public function findByDateRange(string $startDate, string $endDate): array
    {
        $sql = "SELECT * FROM supply_purchases 
                WHERE purchase_date BETWEEN :start_date AND :end_date 
                ORDER BY purchase_date DESC";
        
        return $this->db->fetchAllAs(SupplyPurchase::class, $sql, [
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
    }

    /**
     * 입고 상태별 구매를 조회합니다.
     */
    public function findByReceivedStatus(bool $isReceived): array
    {
        $sql = "SELECT * FROM supply_purchases WHERE is_received = :is_received ORDER BY purchase_date DESC";
        return $this->db->fetchAllAs(SupplyPurchase::class, $sql, [':is_received' => $isReceived ? 1 : 0]);
    }

    /**
     * 공급업체별 구매를 조회합니다.
     */
    public function findBySupplier(string $supplier): array
    {
        $sql = "SELECT * FROM supply_purchases WHERE supplier LIKE :supplier ORDER BY purchase_date DESC";
        return $this->db->fetchAllAs(SupplyPurchase::class, $sql, [':supplier' => "%{$supplier}%"]);
    }

    /**
     * 구매를 생성합니다.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO supply_purchases (item_id, purchase_date, quantity, unit_price, supplier, is_received, received_date, notes, created_by) 
                VALUES (:item_id, :purchase_date, :quantity, :unit_price, :supplier, :is_received, :received_date, :notes, :created_by)";
        
        $params = [
            ':item_id' => $data['item_id'],
            ':purchase_date' => $data['purchase_date'],
            ':quantity' => $data['quantity'],
            ':unit_price' => $data['unit_price'],
            ':supplier' => $data['supplier'] ?? null,
            ':is_received' => $data['is_received'] ?? 0,
            ':received_date' => $data['received_date'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':created_by' => $data['created_by']
        ];

        $this->db->execute($sql, $params);
        return (int) $this->db->lastInsertId();
    }

    /**
     * 구매를 수정합니다.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE supply_purchases 
                SET purchase_date = :purchase_date, quantity = :quantity, unit_price = :unit_price, 
                    supplier = :supplier, notes = :notes 
                WHERE id = :id";
        
        $params = [
            ':id' => $id,
            ':purchase_date' => $data['purchase_date'],
            ':quantity' => $data['quantity'],
            ':unit_price' => $data['unit_price'],
            ':supplier' => $data['supplier'] ?? null,
            ':notes' => $data['notes'] ?? null
        ];

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * 구매를 삭제합니다.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM supply_purchases WHERE id = :id";
        return $this->db->execute($sql, [':id' => $id]) > 0;
    }

    /**
     * 입고 처리를 합니다.
     */
    public function markAsReceived(int $id, string $receivedDate): bool
    {
        $sql = "UPDATE supply_purchases 
                SET is_received = 1, received_date = :received_date 
                WHERE id = :id";
        
        return $this->db->execute($sql, [
            ':id' => $id,
            ':received_date' => $receivedDate
        ]) > 0;
    }

    /**
     * 품목별 총 구매량을 조회합니다.
     */
    public function getTotalPurchasedQuantity(int $itemId): int
    {
        $sql = "SELECT COALESCE(SUM(quantity), 0) as total FROM supply_purchases WHERE item_id = :item_id AND is_received = 1";
        $result = $this->db->fetchOne($sql, [':item_id' => $itemId]);
        return (int) $result['total'];
    }

    /**
     * 품목별 총 구매 금액을 조회합니다.
     */
    public function getTotalPurchasedAmount(int $itemId): float
    {
        $sql = "SELECT COALESCE(SUM(quantity * unit_price), 0) as total FROM supply_purchases WHERE item_id = :item_id";
        $result = $this->db->fetchOne($sql, [':item_id' => $itemId]);
        return (float) $result['total'];
    }

    /**
     * 연도별 구매 통계를 조회합니다.
     */
    public function getPurchaseStatsByYear(int $year): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_purchases,
                    SUM(quantity) as total_quantity,
                    SUM(quantity * unit_price) as total_amount,
                    COUNT(CASE WHEN is_received = 1 THEN 1 END) as received_purchases,
                    COUNT(CASE WHEN is_received = 0 THEN 1 END) as pending_purchases
                FROM supply_purchases 
                WHERE YEAR(purchase_date) = :year";
        
        return $this->db->fetchOne($sql, [':year' => $year]);
    }

    /**
     * 품목 정보와 함께 구매를 조회합니다.
     */
    public function findWithItems(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT sp.*, si.item_name, si.item_code, si.unit, sc.category_name
                      FROM supply_purchases sp
                      JOIN supply_items si ON sp.item_id = si.id
                      LEFT JOIN supply_categories sc ON si.category_id = sc.id",
            'params' => [],
            'where' => []
        ];

        // TODO: 향후 지급품 관리 권한 정책이 구체화되면 여기에 데이터 스코프를 적용해야 합니다.
        // 예를 들어, 특정 부서의 관리자는 해당 부서원이 등록한 구매 내역만 조회할 수 있어야 합니다.
        // $queryParts = $this->dataScopeService->applySomeScope($queryParts, 'sp');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }
        
        $queryParts['sql'] .= " ORDER BY sp.purchase_date DESC, sp.created_at DESC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 미입고 구매 목록을 조회합니다.
     */
    public function findPendingReceived(): array
    {
        $sql = "SELECT sp.*, si.item_name, si.item_code
                FROM supply_purchases sp
                JOIN supply_items si ON sp.item_id = si.id
                WHERE sp.is_received = 0
                ORDER BY sp.purchase_date ASC";
        
        return $this->db->query($sql);
    }
}