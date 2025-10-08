<?php

namespace App\Models;

use App\Core\DB;
use PDO;

class Permission
{
    /**
     * Check if a role has a specific permission.
     */
    public static function hasPermission(string $role, string $permission): bool
    {
        $pdo = DB::getInstance();

        $sql = "
            SELECT COUNT(*)
            FROM role_permissions rp
            JOIN roles r ON rp.role_id = r.id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE r.name = ? AND p.name = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$role, $permission]);

        return (int)$stmt->fetchColumn() > 0;
    }
}