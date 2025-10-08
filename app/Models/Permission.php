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
            FROM sys_role_permissions rp
            JOIN sys_roles r ON rp.role_id = r.id
            JOIN sys_permissions p ON rp.permission_id = p.id
            WHERE r.name = ? AND p.key = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$role, $permission]);

        return (int)$stmt->fetchColumn() > 0;
    }
}