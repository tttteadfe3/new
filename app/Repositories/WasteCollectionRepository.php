<?php
namespace App\Repositories;

use App\Core\Database;

class WasteCollectionRepository {

    public static function createCollection(array $data): ?int {
        $query = 'INSERT INTO `waste_collections`
                    (`latitude`, `longitude`, `address`, `photo_path`, `user_id`, `employee_id`, `issue_date`,
                     `discharge_number`, `submitter_name`, `submitter_phone`, `fee`, `status`, `type`, `geocoding_status`, `created_at`)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';

        $params = [
            $data['latitude'],
            $data['longitude'],
            $data['address'],
            $data['photo_path'],
            $data['user_id'],
            $data['employee_id'],
            $data['issue_date'],
            $data['discharge_number'] ?? null,
            $data['submitter_name'] ?? null,
            $data['submitter_phone'] ?? null,
            $data['fee'] ?? 0,
            'unprocessed',
            $data['type'], // 'field' or 'online'
            $data['geocoding_status'] ?? 'success'
        ];

        $newId = Database::insert($query, $params);
        return $newId > 0 ? $newId : null;
    }

    public static function createCollectionItem(int $collectionId, string $itemName, int $quantity): bool {
        $query = 'INSERT INTO `waste_collection_items` (`collection_id`, `item_name`, `quantity`) VALUES (?, ?, ?)';
        return Database::execute($query, [$collectionId, $itemName, $quantity]) > 0;
    }

    public static function deleteItemsByCollectionId(int $collectionId): bool {
        $query = "DELETE FROM `waste_collection_items` WHERE `collection_id` = ?";
        return Database::execute($query, [$collectionId]) !== false;
    }

    public static function findAllWithItems(): array {
        $query = "
            SELECT
                wc.*,
                COALESCE(
                    (SELECT JSON_ARRAYAGG(JSON_OBJECT('name', wci.item_name, 'quantity', wci.quantity))
                     FROM waste_collection_items wci
                     WHERE wci.collection_id = wc.id),
                    JSON_ARRAY()
                ) as items
            FROM `waste_collections` wc
            WHERE wc.status = 'unprocessed'
            ORDER BY wc.created_at DESC
        ";
        return Database::fetchAll($query);
    }

    public static function findAllForAdmin(array $filters): array {
        $baseQuery = "
            SELECT
                wc.*,
                (SELECT COUNT(*) FROM waste_collection_items wci WHERE wci.collection_id = wc.id) as item_count,
                COALESCE(
                    (SELECT JSON_ARRAYAGG(JSON_OBJECT('name', wci.item_name, 'quantity', wci.quantity))
                     FROM waste_collection_items wci
                     WHERE wci.collection_id = wc.id),
                    JSON_ARRAY()
                ) as items
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

        $baseQuery .= " WHERE " . implode(' AND ', $whereClauses);
        $baseQuery .= " ORDER BY wc.created_at DESC";

        return Database::fetchAll($baseQuery, $params);
    }

    public static function findById(int $id): ?array {
        $query = "
            SELECT
                wc.*,
                COALESCE(
                    (SELECT JSON_ARRAYAGG(JSON_OBJECT('name', wci.item_name, 'quantity', wci.quantity))
                     FROM waste_collection_items wci
                     WHERE wci.collection_id = wc.id),
                    JSON_ARRAY()
                ) as items
            FROM `waste_collections` wc
            WHERE wc.id = ?
        ";
        return Database::fetchOne($query, [$id]);
    }

    public static function processByAddress(string $address): bool {
        $query = "UPDATE `waste_collections` SET `status` = 'processed' WHERE `address` = ? AND `status` = 'unprocessed'";
        return Database::execute($query, [$address]) > 0;
    }

    public static function processById(int $id): bool {
        $query = "UPDATE `waste_collections` SET `status` = 'processed' WHERE `id` = ? AND `status` = 'unprocessed'";
        return Database::execute($query, [$id]) > 0;
    }

    public static function updateAdminMemo(int $id, string $memo): bool {
        $query = "UPDATE `waste_collections` SET `admin_memo` = ? WHERE `id` = ?";
        return Database::execute($query, [$memo, $id]) > 0;
    }

    public static function clearOnlineSubmissions(): bool {
        $query = "DELETE FROM `waste_collections` WHERE `type` = 'online'";
        return Database::execute($query) !== false;
    }

    public static function findByDischargeNumber(string $dischargeNumber): ?array {
        $query = "SELECT * FROM `waste_collections` WHERE `discharge_number` = ? LIMIT 1";
        $result = Database::fetchOne($query, [$dischargeNumber]);
        return $result === false ? null : $result;
    }
}