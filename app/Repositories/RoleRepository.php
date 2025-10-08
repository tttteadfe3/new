<?php
// app/Repositories/RoleRepository.php
namespace App\Repositories;

use App\Core\Database;

class RoleRepository {
    public static function getAllRoles() {
        return Database::query("SELECT * FROM sys_roles ORDER BY name");
    }

    public static function getAllPermissions() {
        return Database::query("SELECT * FROM sys_permissions ORDER BY `key`");
    }

    public static function getRolePermissions(int $roleId): array {
        $sql = "SELECT p.id, p.`key` FROM sys_role_permissions rp
                JOIN sys_permissions p ON rp.permission_id = p.id
                WHERE rp.role_id = :role_id";
        return Database::query($sql, [':role_id' => $roleId]);
    }

    public static function updateRolePermissions(int $roleId, array $permissionIds): void {
        Database::beginTransaction();
        try {
            // 기존 권한 삭제
            $sql_delete = "DELETE FROM sys_role_permissions WHERE role_id = :role_id";
            Database::execute($sql_delete, [':role_id' => $roleId]);

            // 새 권한 추가
            if (!empty($permissionIds)) {
                // Using positional parameters for simplicity
                $sql_insert = "INSERT INTO sys_role_permissions (role_id, permission_id) VALUES ";
                $params = [];
                $placeholders = [];
                
                foreach ($permissionIds as $pid) {
                    $placeholders[] = "(?, ?)";
                    $params[] = $roleId;
                    $params[] = (int) $pid; // Ensure permission ID is also an integer
                }
                
                $sql_insert .= implode(', ', $placeholders);
                
                // Debug output (remove in production)
                error_log("SQL: " . $sql_insert);
                error_log("Params: " . print_r($params, true));
                
                Database::execute($sql_insert, $params);
            }
            Database::commit();
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e; // 예외를 다시 던져서 중앙 처리기에서 처리하도록 함
        }
    }

    public static function findById(int $roleId) {
        return Database::fetchOne("SELECT * FROM sys_roles WHERE id = :id", [':id' => $roleId]);
    }

    public static function create(string $name, string $description): string {
        $sql = "INSERT INTO sys_roles (name, description) VALUES (:name, :description)";
        Database::execute($sql, [':name' => $name, ':description' => $description]);
        return Database::lastInsertId();
    }
    
    public static function update(int $roleId, string $name, string $description): bool {
        $sql = "UPDATE sys_roles SET name = :name, description = :description WHERE id = :id";
        return Database::execute($sql, [':id' => $roleId, ':name' => $name, ':description' => $description]);
    }

    public static function isUserAssigned(int $roleId): bool {
        $sql = "SELECT 1 FROM sys_user_roles WHERE role_id = :role_id LIMIT 1";
        return (bool) Database::fetchOne($sql, [':role_id' => $roleId]);
    }

    public static function delete(int $roleId): bool {
        // 사용자가 할당된 역할은 삭제할 수 없도록 방어
        if (self::isUserAssigned($roleId)) {
            return false;
        }
        return Database::execute("DELETE FROM sys_roles WHERE id = :id", [':id' => $roleId]);
    }
    /**
     * 모든 역할 목록을 사용자 수(user_count)와 함께 가져옵니다.
     */
    public static function getAllRolesWithUserCount(): array {
        $sql = "SELECT r.*, COUNT(ur.user_id) as user_count
                FROM sys_roles r
                LEFT JOIN sys_user_roles ur ON r.id = ur.role_id
                GROUP BY r.id
                ORDER BY r.name";
        return Database::query($sql);
    }

    /**
     * 특정 역할에 할당된 사용자 목록을 가져옵니다.
     */
    public static function getUsersAssignedToRole(int $roleId): array {
        $sql = "SELECT u.id, u.nickname FROM sys_user_roles ur
                JOIN sys_users u ON ur.user_id = u.id
                WHERE ur.role_id = :role_id
                ORDER BY u.nickname ASC";
        return Database::query($sql, [':role_id' => $roleId]);
    }
}