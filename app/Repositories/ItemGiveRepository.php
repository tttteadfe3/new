<?php

namespace App\Repositories;

use App\Core\Database;
use App\Services\DataScopeService;
use Exception;

class ItemGiveRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    /**
     * 필터 조건에 따라 지급 내역 목록을 조회합니다.
     * @param array $filters
     * @return array
     */
    public function findAll(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT
                        ig.*,
                        i.name as item_name,
                        ic.name as category_name,
                        d.name as department_name,
                        e.name as employee_name,
                        creator.name as creator_name
                      FROM im_item_gives ig
                      JOIN im_items i ON ig.item_id = i.id
                      JOIN im_item_categories ic ON i.category_id = ic.id
                      LEFT JOIN hr_departments d ON ig.department_id = d.id
                      LEFT JOIN hr_employees e ON ig.employee_id = e.id
                      LEFT JOIN hr_employees creator ON ig.created_by = creator.id",
            'params' => [],
            'where' => []
        ];

        if (!empty($filters['start_date'])) {
            $queryParts['where'][] = "ig.give_date >= :start_date";
            $queryParts['params'][':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $queryParts['where'][] = "ig.give_date <= :end_date";
            $queryParts['params'][':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['department_id'])) {
            $queryParts['where'][] = "ig.department_id = :department_id";
            $queryParts['params'][':department_id'] = $filters['department_id'];
        }

        // TODO: DataScope 적용 필요 (지급 내역 레벨)
        // $queryParts = $this->dataScopeService->applyItemGiveScope($queryParts, 'ig');

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY ig.give_date DESC, ig.id DESC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 신규 지급 내역을 생성하고 재고를 차감합니다. (트랜잭션)
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function create(array $data): string
    {
        $this->db->beginTransaction();
        try {
            // 1. 재고 확인 및 잠금
            $item = $this->db->fetchOne("SELECT * FROM im_items WHERE id = :id FOR UPDATE", [':id' => $data['item_id']]);

            if (!$item || $item['stock'] < $data['quantity']) {
                $this->db->rollBack();
                throw new Exception('재고가 부족합니다.');
            }

            // 2. 재고 차감
            $this->db->execute("UPDATE im_items SET stock = stock - :quantity WHERE id = :item_id", [
                ':quantity' => $data['quantity'],
                ':item_id' => $data['item_id']
            ]);

            // 3. 지급 내역 등록
            $sql = "INSERT INTO im_item_gives (item_id, give_date, department_id, employee_id, quantity, note, created_by)
                    VALUES (:item_id, :give_date, :department_id, :employee_id, :quantity, :note, :created_by)";
            $params = [
                ':item_id' => $data['item_id'],
                ':give_date' => $data['give_date'],
                ':department_id' => $data['department_id'] ?? null,
                ':employee_id' => $data['employee_id'] ?? null,
                ':quantity' => $data['quantity'],
                ':note' => $data['note'] ?? null,
                ':created_by' => $data['created_by'],
            ];
            $this->db->execute($sql, $params);
            $lastId = $this->db->lastInsertId();

            $this->db->commit();
            return $lastId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e; // Re-throw the exception
        }
    }

    /**
     * 지급 내역을 삭제(취소)하고 재고를 복원합니다. (트랜잭션)
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $this->db->beginTransaction();
        try {
            // 1. 지급 내역 조회 및 잠금
            $give = $this->db->fetchOne("SELECT * FROM im_item_gives WHERE id = :id FOR UPDATE", [':id' => $id]);

            if (!$give) {
                $this->db->rollBack();
                return false; // Or throw exception
            }

            // 2. 재고 복원
            $this->db->execute("UPDATE im_items SET stock = stock + :quantity WHERE id = :item_id", [
                ':quantity' => $give['quantity'],
                ':item_id' => $give['item_id']
            ]);

            // 3. 지급 내역 삭제
            $this->db->execute("DELETE FROM im_item_gives WHERE id = :id", [':id' => $id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e; // Re-throw the exception
        }
    }

    /**
     * 재고가 있는 모든 품목 목록을 조회합니다.
     * @return array
     */
    public function getAvailableItems(): array
    {
        // TODO: DataScope 적용 필요 (품목 레벨)
        $sql = "SELECT i.id, i.name, i.stock, ic.name as category_name
                FROM im_items i
                JOIN im_item_categories ic ON i.category_id = ic.id
                WHERE i.stock > 0
                ORDER BY ic.name, i.name";
        return $this->db->query($sql);
    }
}
