<?php
// app/Repositories/RoleRepository.php
namespace App\Repositories;

use App\Core\Database;

class RoleRepository {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getAllRoles() {
        return $this->db->query("SELECT * FROM sys_roles ORDER BY name");
    }

    public function getAllPermissions() {
        return $this->db->query("SELECT * FROM sys_permissions ORDER BY `key`");
    }

    public function getRolePermissions(int $roleId): array {
        $sql = "SELECT p.id, p.`key` FROM sys_role_permissions rp
                JOIN sys_permissions p ON rp.permission_id = p.id
                WHERE rp.role_id = :role_id";
        return $this->db->query($sql, [':role_id' => $roleId]);
    }

    public function updateRolePermissions(int $roleId, array $permissionIds): void {
        $this->db->beginTransaction();
        try {
            // 기존 권한 삭제
            $sql_delete = "DELETE FROM sys_role_permissions WHERE role_id = :role_id";
            $this->db->execute($sql_delete, [':role_id' => $roleId]);

            // 새 권한 추가
            if (!empty($permissionIds)) {
                $sql_insert = "INSERT INTO sys_role_permissions (role_id, permission_id) VALUES ";
                $params = [];
                $placeholders = [];
                
                foreach ($permissionIds as $pid) {
                    $placeholders[] = "(?, ?)";
                    $params[] = $roleId;
                    $params[] = (int) $pid;
                }
                
                $sql_insert .= implode(', ', $placeholders);
                $this->db->execute($sql_insert, $params);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function findById(int $roleId) {
        return $this->db->fetchOne("SELECT * FROM sys_roles WHERE id = :id", [':id' => $roleId]);
    }

    public function create(string $name, string $description): string {
        $sql = "INSERT INTO sys_roles (name, description) VALUES (:name, :description)";
        $this->db->execute($sql, [':name' => $name, ':description' => $description]);
        return $this->db->lastInsertId();
    }
    
    public function update(int $roleId, string $name, string $description): bool {
        $sql = "UPDATE sys_roles SET name = :name, description = :description WHERE id = :id";
        return $this->db->execute($sql, [':id' => $roleId, ':name' => $name, ':description' => $description]) > 0;
    }

    public function isUserAssigned(int $roleId): bool {
        $sql = "SELECT 1 FROM sys_user_roles WHERE role_id = :role_id LIMIT 1";
        return (bool) $this->db->fetchOne($sql, [':role_id' => $roleId]);
    }

    public function delete(int $roleId): bool {
        if ($this->isUserAssigned($roleId)) {
            return false;
        }
        return $this->db->execute("DELETE FROM sys_roles WHERE id = :id", [':id' => $roleId]) > 0;
    }

    public function getAllRolesWithUserCount(): array {
        $sql = "SELECT r.*, COUNT(ur.user_id) as user_count
                FROM sys_roles r
                LEFT JOIN sys_user_roles ur ON r.id = ur.role_id
                GROUP BY r.id
                ORDER BY r.name";
        return $this->db->query($sql);
    }

    public function getUsersAssignedToRole(int $roleId): array {
        $sql = "SELECT u.id, u.nickname FROM sys_user_roles ur
                JOIN sys_users u ON ur.user_id = u.id
                WHERE ur.role_id = :role_id
                ORDER BY u.nickname ASC";
        return $this->db->query($sql, [':role_id' => $roleId]);
    }

    public function getUserRoles(int $userId): array {
        $sql = "SELECT r.name FROM sys_user_roles ur
                JOIN sys_roles r ON ur.role_id = r.id
                WHERE ur.user_id = :user_id";

        $roles = $this->db->query($sql, [':user_id' => $userId]);
        return array_column($roles, 'name');
    }
}
