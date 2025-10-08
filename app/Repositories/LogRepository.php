<?php
// app/Repositories/LogRepository.php
namespace App\Repositories;

use App\Core\Database;

class LogRepository {
    public static function insert(array $logData): bool {
        $sql = "INSERT INTO sys_activity_logs (user_id, user_name, action, details, ip_address)
                VALUES (:user_id, :user_name, :action, :details, :ip_address)";
        return Database::execute($sql, $logData);
    }

    public static function search(array $filters = [], int $limit = 50): array {
        $sql = "SELECT * FROM sys_activity_logs";
        $whereClauses = [];
        $params = [];

        if (!empty($filters['start_date'])) {
            $whereClauses[] = "created_at >= :start_date";
            $params[':start_date'] = $filters['start_date'] . ' 00:00:00';
        }
        if (!empty($filters['end_date'])) {
            $whereClauses[] = "created_at <= :end_date";
            $params[':end_date'] = $filters['end_date'] . ' 23:59:59';
        }
        if (!empty($filters['user_name'])) {
            $whereClauses[] = "user_name LIKE :user_name";
            $params[':user_name'] = '%' . $filters['user_name'] . '%';
        }
        if (!empty($filters['action'])) {
            $whereClauses[] = "action LIKE :action";
            $params[':action'] = '%' . $filters['action'] . '%';
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $sql .= " ORDER BY created_at DESC LIMIT " . $limit;

        return Database::query($sql, $params);
    }

    public static function truncate(): bool {
        $sql = "TRUNCATE TABLE sys_activity_logs";
        return Database::execute($sql);
    }
}