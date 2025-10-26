<?php
// app/Repositories/LogRepository.php
namespace App\Repositories;

use App\Core\Database;

class LogRepository {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * @param array $logData
     * @return bool
     */
    public function insert(array $logData): bool {
        // SQL 오류를 피하기 위해 모든 키가 있는지 확인합니다.
        $defaults = [
            'user_id' => null,
            'user_name' => null,
            'action' => null,
            'details' => '', // 제공되지 않은 경우 기본값은 빈 문자열
            'ip_address' => null
        ];
        $params = array_merge($defaults, $logData);

        $sql = "INSERT INTO sys_activity_logs (user_id, user_name, action, details, ip_address)
                VALUES (:user_id, :user_name, :action, :details, :ip_address)";
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * @param array $filters
     * @param int $limit
     * @return array
     */
    public function search(array $filters = [], int $limit = 50): array {
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

        return $this->db->query($sql, $params);
    }

    /**
     * @return bool
     */
    public function truncate(): bool {
        $sql = "TRUNCATE TABLE sys_activity_logs";
        return $this->db->execute($sql) > 0;
    }
}
