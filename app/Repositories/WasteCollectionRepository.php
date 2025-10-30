<?php
namespace App\Repositories;

use App\Core\Database;

class WasteCollectionRepository {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * @param array $data
     * @return int|null
     */
    public function createCollection(array $data): ?int {
        $query = 'INSERT INTO `waste_collections`
                    (`latitude`, `longitude`, `address`, `photo_path`, `issue_date`,
                     `discharge_number`, `submitter_name`, `submitter_phone`, `fee`, `status`, `type`, `geocoding_status`, `created_at`, `created_by`)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)';

        $params = [
            $data['latitude'],
            $data['longitude'],
            $data['address'],
            $data['photo_path'],
            $data['issue_date'],
            $data['discharge_number'] ?? null,
            $data['submitter_name'] ?? null,
            $data['submitter_phone'] ?? null,
            $data['fee'] ?? 0,
            '미처리',
            $data['type'], // 'field' or 'online'
            $data['geocoding_status'] ?? '성공',
            $data['created_by']
        ];

        $newId = $this->db->insert($query, $params);
        return $newId > 0 ? $newId : null;
    }

    /**
     * @param int $collectionId
     * @param string $itemName
     * @param int $quantity
     * @return bool
     */
    public function createCollectionItem(int $collectionId, string $itemName, int $quantity): bool {
        $query = 'INSERT INTO `waste_collection_items` (`collection_id`, `item_name`, `quantity`) VALUES (?, ?, ?)';
        return $this->db->execute($query, [$collectionId, $itemName, $quantity]) > 0;
    }

    /**
     * @param int $collectionId
     * @return bool
     */
    public function deleteItemsByCollectionId(int $collectionId): bool {
        $query = "DELETE FROM `waste_collection_items` WHERE `collection_id` = ?";
        return $this->db->execute($query, [$collectionId]) !== false;
    }

    /**
     * @return array
     */
    public function findAllWithItems(): array {
        $query = "
            SELECT
                wc.*,
                IFNULL((
                    SELECT CONCAT('[', GROUP_CONCAT(JSON_OBJECT('name', wci.item_name, 'quantity', wci.quantity)), ']')
                    FROM waste_collection_items wci
                    WHERE wci.collection_id = wc.id
                ), '[]') AS items
            FROM `waste_collections` wc
            WHERE wc.status = '미처리'
            ORDER BY wc.created_at DESC
        ";
        return $this->db->fetchAll($query);
    }

    /**
     * @param array $filters
     * @return array
     */
    public function findAllForAdmin(array $filters): array {
        $baseQuery = "
            SELECT
                wc.*,
                (SELECT COUNT(*) FROM waste_collection_items wci WHERE wci.collection_id = wc.id) as item_count,
                IFNULL((
                    SELECT CONCAT('[', GROUP_CONCAT(JSON_OBJECT('name', wci.item_name, 'quantity', wci.quantity)), ']')
                    FROM waste_collection_items wci
                    WHERE wci.collection_id = wc.id
                ), '[]') AS items
            FROM `waste_collections` wc
        ";
        $whereClauses = ["wc.type = 'online'"];
        $params = [];

        if (!empty($filters['searchDischargeNumber'])) {
            $whereClauses[] = "wc.discharge_number LIKE ?";
            $params[] = '%' . $filters['searchDischargeNumber'] . '%';
        }
        if (!empty($filters['searchName'])) {
            $whereClauses[] = "wc.submitter_name LIKE ?";
            $params[] = '%' . $filters['searchName'] . '%';
        }
        if (!empty($filters['searchPhone'])) {
            $whereClauses[] = "wc.submitter_phone LIKE ?";
            $params[] = '%' . $filters['searchPhone'] . '%';
        }
        if (!empty($filters['searchStatus'])) {
            $whereClauses[] = "wc.status = ?";
            $params[] = $filters['searchStatus'];
        }

        if (count($whereClauses) > 0) {
            $baseQuery .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $baseQuery .= " ORDER BY wc.created_at DESC";

        return $this->db->fetchAll($baseQuery, $params);
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array {
        $query = "
            SELECT
                wc.*,
                IFNULL((
                    SELECT CONCAT('[', GROUP_CONCAT(JSON_OBJECT('name', wci.item_name, 'quantity', wci.quantity)), ']')
                    FROM waste_collection_items wci
                    WHERE wci.collection_id = wc.id
                ), '[]') AS items
            FROM `waste_collections` wc
            WHERE wc.id = ?
        ";
        return $this->db->fetchOne($query, [$id]);
    }

    /**
     * @param string $address
     * @param int $employeeId
     * @return bool
     */
    public function processByAddress(string $address, int $employeeId): bool {
        $query = "UPDATE `waste_collections` SET `status` = '처리완료', `completed_at` = NOW(), `completed_by` = ? WHERE `address` = ? AND `status` = '미처리'";
        return $this->db->execute($query, [$employeeId, $address]) > 0;
    }

    /**
     * @param int $id
     * @param int $employeeId
     * @return bool
     */
    public function processById(int $id, int $employeeId): bool {
        $query = "UPDATE `waste_collections` SET `status` = '처리완료', `completed_at` = NOW(), `completed_by` = ? WHERE `id` = ? AND `status` = '미처리'";
        return $this->db->execute($query, [$employeeId, $id]) > 0;
    }

    /**
     * @param int $id
     * @param string $memo
     * @param int $employeeId
     * @return bool
     */
    public function updateAdminMemo(int $id, string $memo, int $employeeId): bool {
        $query = "UPDATE `waste_collections` SET `admin_memo` = ?, `completed_at` = NOW(), `completed_by` = ? WHERE `id` = ?";
        return $this->db->execute($query, [$memo, $employeeId, $id]) > 0;
    }

    /**
     * @return bool
     */
    public function clearOnlineSubmissions(): bool {
        $query = "DELETE FROM `waste_collections` WHERE `type` = 'online'";
        return $this->db->execute($query) !== false;
    }

    /**
     * @param string $dischargeNumber
     * @return array|null
     */
    public function findByDischargeNumber(string $dischargeNumber): ?array {
        $query = "SELECT * FROM `waste_collections` WHERE `discharge_number` = ? LIMIT 1";
        $result = $this->db->fetchOne($query, [$dischargeNumber]);
        return $result === false ? null : $result;
    }
}
