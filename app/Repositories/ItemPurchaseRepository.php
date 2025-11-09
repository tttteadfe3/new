<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\ItemPurchase;
use App\Services\DataScopeService;
use Exception;

class ItemPurchaseRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    /**
     * ID로 구매 내역을 찾습니다.
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM im_item_purchases WHERE id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    /**
     * 필터 조건에 따라 구매 내역 목록을 조회합니다.
     * @param array $filters
     * @return array
     */
    public function findAll(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT
                        ip.*,
                        i.name as item_name,
                        ic.name as category_name,
                        creator.name as creator_name,
                        stocker.name as stocker_name
                      FROM im_item_purchases ip
                      JOIN im_items i ON ip.item_id = i.id
                      JOIN im_item_categories ic ON i.category_id = ic.id
                      LEFT JOIN hr_employees creator ON ip.created_by = creator.id
                      LEFT JOIN hr_employees stocker ON ip.stocked_by = stocker.id",
            'params' => [],
            'where' => []
        ];

        if (!empty($filters['year'])) {
            $queryParts['where'][] = "YEAR(ip.purchase_date) = :year";
            $queryParts['params'][':year'] = $filters['year'];
        }

        if (isset($filters['is_stocked']) && $filters['is_stocked'] !== '') {
            $queryParts['where'][] = "ip.is_stocked = :is_stocked";
            $queryParts['params'][':is_stocked'] = $filters['is_stocked'];
        }

        $queryParts = $this->dataScopeService->applyItemPurchaseScope($queryParts, 'ip');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY ip.purchase_date DESC, ip.id DESC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 새로운 구매 내역을 생성합니다.
     * @param array $data
     * @return string
     */
    public function create(array $data): string
    {
        $sql = "INSERT INTO im_item_purchases (item_id, plan_id, purchase_date, quantity, unit_price, supplier, created_by)
                VALUES (:item_id, :plan_id, :purchase_date, :quantity, :unit_price, :supplier, :created_by)";
        $params = [
            ':item_id' => $data['item_id'],
            ':plan_id' => $data['plan_id'] ?? null,
            ':purchase_date' => $data['purchase_date'],
            ':quantity' => $data['quantity'],
            ':unit_price' => $data['unit_price'],
            ':supplier' => $data['supplier'] ?? null,
            ':created_by' => $data['created_by'],
        ];
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    /**
     * 구매 내역을 업데이트합니다.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE im_item_purchases
                SET item_id = :item_id, plan_id = :plan_id, purchase_date = :purchase_date, quantity = :quantity,
                    unit_price = :unit_price, supplier = :supplier
                WHERE id = :id AND is_stocked = 0"; // 입고되지 않은 내역만 수정 가능
        $params = [
            ':id' => $id,
            ':item_id' => $data['item_id'],
            ':plan_id' => $data['plan_id'] ?? null,
            ':purchase_date' => $data['purchase_date'],
            ':quantity' => $data['quantity'],
            ':unit_price' => $data['unit_price'],
            ':supplier' => $data['supplier'] ?? null,
        ];
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * 구매 내역을 삭제합니다.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // 입고되지 않은 내역만 삭제 가능
        return $this->db->execute("DELETE FROM im_item_purchases WHERE id = :id AND is_stocked = 0", [':id' => $id]) > 0;
    }

    /**
     * 구매 내역을 입고 처리하고 재고를 업데이트합니다. (트랜잭션)
     * @param int $purchaseId
     * @param int $stockerId
     * @return bool
     */
    public function processStockIn(int $purchaseId, int $stockerId): bool
    {
        $this->db->beginTransaction();
        try {
            // 1. 구매 내역 조회 및 잠금
            $purchase = $this->db->fetchOne("SELECT * FROM im_item_purchases WHERE id = :id FOR UPDATE", [':id' => $purchaseId]);

            if (!$purchase || $purchase['is_stocked'] == 1) {
                $this->db->rollBack();
                return false; // 이미 처리되었거나 존재하지 않음
            }

            // 2. 재고 업데이트
            $updateStockSql = "UPDATE im_items SET stock = stock + :quantity WHERE id = :item_id";
            $this->db->execute($updateStockSql, [
                ':quantity' => $purchase['quantity'],
                ':item_id' => $purchase['item_id']
            ]);

            // 3. 구매 내역 입고 처리
            $updatePurchaseSql = "UPDATE im_item_purchases
                                  SET is_stocked = 1, stocked_by = :stocked_by, stocked_at = NOW()
                                  WHERE id = :id";
            $this->db->execute($updatePurchaseSql, [
                ':stocked_by' => $stockerId,
                ':id' => $purchaseId
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            // Optional: log the exception
            error_log("Stock-in processing failed: " . $e->getMessage());
            return false;
        }
    }
}
