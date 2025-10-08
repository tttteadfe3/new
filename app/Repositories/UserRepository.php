<?php
// app/Repositories/UserRepository.php
namespace App\Repositories;

use App\Core\Database;

class UserRepository {
    public static function findById(int $id) {
        $sql = "SELECT * FROM sys_users WHERE id = :id";
        return Database::fetchOne($sql, [':id' => $id]);
    }

    public static function findByKakaoId(string $kakaoId) {
        $sql = "SELECT * FROM sys_users WHERE kakao_id = :kakao_id";
        return Database::fetchOne($sql, [':kakao_id' => $kakaoId]);
    }

    public static function create(array $data): string {
        $sql = "INSERT INTO sys_users (kakao_id, nickname, email, profile_image_url, status)
                VALUES (:kakao_id, :nickname, :email, :p_img, 'pending')";
        Database::execute($sql, [
            ':kakao_id' => $data['id'],
            ':nickname' => $data['properties']['nickname'],
            ':email' => $data['kakao_account']['email'] ?? 'email-unavailable-'.uniqid().'@example.com',
            ':p_img' => $data['properties']['profile_image'] ?? null,
        ]);
        return Database::lastInsertId();
    }
    
    public static function update(int $userId, array $data): bool {
        $sql = "UPDATE sys_users SET nickname = :nickname, email = :email, profile_image_url = :p_img WHERE id = :id";
        return Database::execute($sql, [
            ':nickname' => $data['properties']['nickname'],
            ':email' => $data['kakao_account']['email'] ?? 'email-unavailable-'.uniqid().'@example.com',
            ':p_img' => $data['properties']['profile_image'] ?? null,
            ':id' => $userId
        ]);
    }
    
    public static function updateUserStatus(int $userId, string $status): bool {
        $sql = "UPDATE sys_users SET status = :status WHERE id = :id";
        return Database::execute($sql, [':status' => $status, ':id' => $userId]);
    }

    public static function countAll(): int {
        return (int) Database::fetchOne("SELECT COUNT(*) as count FROM sys_users")['count'];
    }

    public static function countByStatus(string $status): int {
        $sql = "SELECT COUNT(*) as count FROM sys_users WHERE status = :status";
        return (int) Database::fetchOne($sql, [':status' => $status])['count'];
    }

    public static function getPermissions(int $userId): array {
        $sql = "SELECT DISTINCT p.`key` FROM sys_user_roles ur
                JOIN sys_role_permissions rp ON ur.role_id = rp.role_id
                JOIN sys_permissions p ON rp.permission_id = p.id
                WHERE ur.user_id = :user_id";
        return Database::query($sql, [':user_id' => $userId]);
    }
    
    public static function getAllWithRoles(array $filters = []): array {
        $baseSql = "SELECT 
                    u.id, u.nickname, u.email, u.status, u.employee_id,
                    GROUP_CONCAT(DISTINCT r.name SEPARATOR ', ') as roles,
                    e.name as employee_name
                FROM sys_users u
                LEFT JOIN sys_user_roles ur ON u.id = ur.user_id
                LEFT JOIN sys_roles r ON ur.role_id = r.id
                LEFT JOIN hr_employees e ON u.employee_id = e.id";

        $whereClauses = [];
        $params = [];

        if (!empty($filters['status'])) {
            $whereClauses[] = "u.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['nickname'])) {
            $whereClauses[] = "u.nickname LIKE :nickname";
            $params[':nickname'] = '%' . $filters['nickname'] . '%';
        }

        if (!empty($filters['staff'])) {
            if ($filters['staff'] === 'linked') {
                $whereClauses[] = "u.employee_id IS NOT NULL";
            } elseif ($filters['staff'] === 'unlinked') {
                $whereClauses[] = "u.employee_id IS NULL";
            }
        }

        if (!empty($filters['role_id'])) {
            $whereClauses[] = "u.id IN (SELECT user_id FROM sys_user_roles WHERE role_id = :role_id)";
            $params[':role_id'] = $filters['role_id'];
        }

        $sql = $baseSql;
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $sql .= " GROUP BY u.id, e.name ORDER BY u.created_at DESC";

        return Database::query($sql, $params);
    }

    public static function getRoleIdsForUser(int $userId): array {
        $sql = "SELECT role_id FROM sys_user_roles WHERE user_id = :user_id";
        $results = Database::query($sql, [':user_id' => $userId]);
        return array_column($results, 'role_id');
    }

    public static function updateUserRoles(int $userId, array $roleIds): void {
        Database::beginTransaction();
        try {
            // 기존 역할 모두 삭제
            Database::execute("DELETE FROM sys_user_roles WHERE user_id = :user_id", [':user_id' => $userId]);

            // 새 역할 추가
            if (!empty($roleIds)) {
                $sql = "INSERT INTO sys_user_roles (user_id, role_id) VALUES ";
                $placeholders = [];
                $params = [':user_id' => $userId];
                foreach ($roleIds as $index => $roleId) {
                    $key = ":role_id" . $index;
                    $placeholders[] = "(:user_id, {$key})";
                    $params[$key] = $roleId;
                }
                $sql .= implode(', ', $placeholders);
                Database::execute($sql, $params);
            }
            Database::commit();
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * 직원 정보와 아직 연결되지 않은 '활성' 사용자 목록을 가져옵니다.
     */
    public static function findUsersWithoutEmployeeRecord(): array {
        $sql = "SELECT u.id, u.nickname FROM sys_users u
                LEFT JOIN hr_employees e ON u.id = e.user_id
                WHERE e.user_id IS NULL AND u.status = 'active'
                ORDER BY u.nickname";
        return Database::query($sql);
    }

    /**
     * 사용자 계정과 아직 연결되지 않은 직원 목록을 가져옵니다.
     * @param int|null $departmentId 부서 ID로 필터링
     */
    public static function getUnlinkedEmployees(int $departmentId = null): array {
        $params = [];
        // employees.id가 users.employee_id에 존재하지 않고, 퇴사일이 없는 직원만 선택
        $sql = "SELECT e.id, e.name, e.employee_number FROM hr_employees e
                WHERE NOT EXISTS (SELECT 1 FROM sys_users u WHERE u.employee_id = e.id)
                AND e.termination_date IS NULL";
        
        if ($departmentId) {
            $sql .= " AND e.department_id = :department_id";
            $params[':department_id'] = $departmentId;
        }
        
        $sql .= " ORDER BY e.name";
        return Database::query($sql, $params);
    }

    /**
     * 특정 사용자에게 직원을 연결(매핑)합니다.
     */
    public static function linkEmployee(int $userId, int $employeeId): bool {
        $sql = "UPDATE sys_users SET employee_id = :employee_id WHERE id = :user_id";
        return Database::execute($sql, [':employee_id' => $employeeId, ':user_id' => $userId]);
    }
    
    /**
     * 특정 사용자의 직원 연결을 해제합니다.
     * (Called when user status is changed to non-active)
     */
    public static function unlinkEmployee(int $userId): bool {
        $sql = "UPDATE sys_users SET employee_id = NULL WHERE id = :user_id";
        return Database::execute($sql, [':user_id' => $userId]);
    }

    /**
     * 모든 사용자 목록 조회
     */
    public static function getAll(): array {
        return self::getAllWithRoles();
    }

    /**
     * 사용자 삭제
     */
    public static function delete(int $id): bool {
        Database::beginTransaction();
        try {
            // 먼저 사용자 역할 삭제
            Database::execute("DELETE FROM sys_user_roles WHERE user_id = :user_id", [':user_id' => $id]);
            
            // 사용자 삭제
            $result = Database::execute("DELETE FROM sys_users WHERE id = :id", [':id' => $id]);
            
            Database::commit();
            return $result;
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * 사용자 역할 목록 조회
     */
    public static function getUserRoles(int $userId): array {
        $sql = "SELECT r.id, r.name FROM sys_user_roles ur
                JOIN sys_roles r ON ur.role_id = r.id
                WHERE ur.user_id = :user_id
                ORDER BY r.name";
        return Database::query($sql, [':user_id' => $userId]);
    }

    /**
     * 사용자 상태 토글 (활성/비활성)
     */
    public static function toggleStatus(int $userId): bool {
        $sql = "UPDATE sys_users SET status = CASE 
                    WHEN status = 'active' THEN 'inactive' 
                    ELSE 'active' 
                END 
                WHERE id = :id";
        return Database::execute($sql, [':id' => $userId]);
    }

}