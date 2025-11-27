<?php
// app/Repositories/UserRepository.php
namespace App\Repositories;

use App\Core\Database;

use App\Services\PolicyEngine;
use App\Core\SessionManager;

class UserRepository {
    private Database $db;
    private PolicyEngine $policyEngine;

    private SessionManager $sessionManager;

    public function __construct(Database $db, PolicyEngine $policyEngine, SessionManager $sessionManager) {
        $this->db = $db;
        $this->policyEngine = $policyEngine;

        $this->sessionManager = $sessionManager;
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function findById(int $id) {
        $sql = "SELECT * FROM sys_users WHERE id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    /**
     * @param string $kakaoId
     * @return mixed
     */
    public function findByKakaoId(string $kakaoId) {
        $sql = "SELECT * FROM sys_users WHERE kakao_id = :kakao_id";
        return $this->db->fetchOne($sql, [':kakao_id' => $kakaoId]);
    }

    /**
     * @param array $data
     * @return string
     */
    public function create(array $data): string {
        $sql = "INSERT INTO sys_users (kakao_id, nickname, email, profile_image_url, status)
                VALUES (:kakao_id, :nickname, :email, :p_img, '대기')";
        $this->db->execute($sql, [
            ':kakao_id' => $data['id'],
            ':nickname' => $data['properties']['nickname'],
            ':email' => $data['kakao_account']['email'] ?? 'email-unavailable-'.uniqid().'@example.com',
            ':p_img' => $data['properties']['profile_image'] ?? null,
        ]);
        return $this->db->lastInsertId();
    }
    
    /**
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function update(int $userId, array $data): bool {
        $sql = "UPDATE sys_users SET nickname = :nickname, email = :email, profile_image_url = :p_img WHERE id = :id";
        return $this->db->execute($sql, [
            ':nickname' => $data['properties']['nickname'],
            ':email' => $data['kakao_account']['email'] ?? 'email-unavailable-'.uniqid().'@example.com',
            ':p_img' => $data['properties']['profile_image'] ?? null,
            ':id' => $userId
        ]) > 0;
    }
    
    /**
     * @param int $userId
     * @param string $status
     * @return bool
     */
    public function updateUserStatus(int $userId, string $status): bool {
        $sql = "UPDATE sys_users SET status = :status WHERE id = :id";
        return $this->db->execute($sql, [':status' => $status, ':id' => $userId]) > 0;
    }

    /**
     * @return int
     */
    public function countAll(): int {
        return (int) $this->db->fetchOne("SELECT COUNT(*) as count FROM sys_users")['count'];
    }

    /**
     * @param string $status
     * @return int
     */
    public function countByStatus(string $status): int {
        $sql = "SELECT COUNT(*) as count FROM sys_users WHERE status = :status";
        return (int) $this->db->fetchOne($sql, [':status' => $status])['count'];
    }

    /**
     * @param int $userId
     * @return array
     */
    public function getPermissions(int $userId): array {
        $sql = "SELECT DISTINCT p.`key` FROM sys_user_roles ur
                JOIN sys_role_permissions rp ON ur.role_id = rp.role_id
                JOIN sys_permissions p ON rp.permission_id = p.id
                WHERE ur.user_id = :user_id";
        return $this->db->query($sql, [':user_id' => $userId]);
    }
    
    /**
     * @param array $filters
     * @return array
     */
    public function getAllWithRoles(array $filters = []): array {
        $queryParts = [
            'sql' => "SELECT
                        u.id, u.nickname, u.email, u.status, u.employee_id,
                        GROUP_CONCAT(DISTINCT r.name SEPARATOR ', ') as roles,
                        e.name as employee_name
                    FROM sys_users u
                    LEFT JOIN sys_user_roles ur ON u.id = ur.user_id
                    LEFT JOIN sys_roles r ON ur.role_id = r.id
                    LEFT JOIN hr_employees e ON u.employee_id = e.id",
            'params' => [],
            'where' => []
        ];

        // 데이터 스코프 적용 (PolicyEngine 사용)
        $user = $this->sessionManager->get('user');
        if ($user) {
            $scopeIds = $this->policyEngine->getScopeIds($user['id'], 'user', 'view');
            
            if ($scopeIds === null) {
                // 전체 조회 가능
            } elseif (empty($scopeIds)) {
                $queryParts['where'][] = "u.employee_id IS NULL"; // 권한 없으면 연결되지 않은 사용자만? 혹은 아예 조회 불가? 기존 로직 참고
            } else {
                $inClause = implode(',', array_map('intval', $scopeIds));
                $queryParts['where'][] = "(e.department_id IN ($inClause) OR u.employee_id IS NULL)";
            }
        }

        if (!empty($filters['status'])) {
            $queryParts['where'][] = "u.status = :status";
            $queryParts['params'][':status'] = $filters['status'];
        }

        if (!empty($filters['nickname'])) {
            $queryParts['where'][] = "u.nickname LIKE :nickname";
            $queryParts['params'][':nickname'] = '%' . $filters['nickname'] . '%';
        }

        if (!empty($filters['staff'])) {
            if ($filters['staff'] === 'linked') {
                $queryParts['where'][] = "u.employee_id IS NOT NULL";
            } elseif ($filters['staff'] === 'unlinked') {
                $queryParts['where'][] = "u.employee_id IS NULL";
            }
        }

        if (!empty($filters['role_id'])) {
            $queryParts['where'][] = "u.id IN (SELECT user_id FROM sys_user_roles WHERE role_id = :role_id)";
            $queryParts['params'][':role_id'] = $filters['role_id'];
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " GROUP BY u.id, e.name ORDER BY u.created_at DESC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * @param int $userId
     * @return array
     */
    public function getRoleIdsForUser(int $userId): array {
        $sql = "SELECT role_id FROM sys_user_roles WHERE user_id = :user_id";
        $results = $this->db->query($sql, [':user_id' => $userId]);
        return array_column($results, 'role_id');
    }

    /**
     * @param int $userId
     * @param array $roleIds
     * @return void
     * @throws \Exception
     */
    public function updateUserRoles(int $userId, array $roleIds): void {
        $this->db->beginTransaction();
        try {
            // 기존 역할 모두 삭제
            $this->db->execute("DELETE FROM sys_user_roles WHERE user_id = :user_id", [':user_id' => $userId]);

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
                $this->db->execute($sql, $params);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * 직원 정보와 아직 연결되지 않은 '활성' 사용자 목록을 가져옵니다.
     * @return array
     */
    public function findUsersWithoutEmployeeRecord(): array {
        $sql = "SELECT u.id, u.nickname FROM sys_users u
                LEFT JOIN hr_employees e ON u.id = e.user_id
                WHERE e.user_id IS NULL AND u.status = '활성'
                ORDER BY u.nickname";
        return $this->db->query($sql);
    }

    /**
     * 사용자 계정과 아직 연결되지 않은 직원 목록을 가져옵니다.
     * @return array
     */
    public function getUnlinkedEmployees(): array {
        $queryParts = [
            'sql' => "SELECT e.id, e.name, e.employee_number FROM hr_employees e
                      WHERE NOT EXISTS (SELECT 1 FROM sys_users u WHERE u.employee_id = e.id)
                      AND e.termination_date IS NULL",
            'params' => [],
            'where' => []
        ];

        // 데이터 스코프 적용 (PolicyEngine 사용) - getUnlinkedEmployees는 보통 관리자가 사용하므로 스코프 적용 필요
        $user = $this->sessionManager->get('user');
        if ($user) {
            $scopeIds = $this->policyEngine->getScopeIds($user['id'], 'employee', 'view');
            
            if ($scopeIds === null) {
                // 전체 조회 가능
            } elseif (empty($scopeIds)) {
                $queryParts['where'][] = "1=0";
            } else {
                $inClause = implode(',', array_map('intval', $scopeIds));
                $queryParts['where'][] = "e.department_id IN ($inClause)";
            }
        }

        if (!empty($queryParts['where'])) {
            // 이미 WHERE 절이 있으므로 AND로 연결
            $queryParts['sql'] .= " AND " . implode(" AND ", $queryParts['where']);
        }
        
        $queryParts['sql'] .= " ORDER BY e.name";
        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 특정 사용자에게 직원을 연결(매핑)합니다.
     * @param int $userId
     * @param int $employeeId
     * @return bool
     */
    public function linkEmployee(int $userId, int $employeeId): bool {
        $sql = "UPDATE sys_users SET employee_id = :employee_id WHERE id = :user_id";
        return $this->db->execute($sql, [':employee_id' => $employeeId, ':user_id' => $userId]) > 0;
    }
    
    /**
     * 특정 사용자의 직원 연결을 해제합니다.
     * (사용자 상태가 비활성으로 변경될 때 호출됨)
     * @param int $userId
     * @return bool
     */
    public function unlinkEmployee(int $userId): bool {
        $sql = "UPDATE sys_users SET employee_id = NULL WHERE id = :user_id";
        return $this->db->execute($sql, [':user_id' => $userId]) > 0;
    }

    /**
     * 모든 사용자 목록 조회
     * @return array
     */
    public function getAll(): array {
        return $this->getAllWithRoles();
    }

    /**
     * 사용자 삭제
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function delete(int $id): bool {
        $this->db->beginTransaction();
        try {
            // 먼저 사용자 역할 삭제
            $this->db->execute("DELETE FROM sys_user_roles WHERE user_id = :user_id", [':user_id' => $id]);
            
            // 사용자 삭제
            $result = $this->db->execute("DELETE FROM sys_users WHERE id = :id", [':id' => $id]);
            
            $this->db->commit();
            return $result > 0;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * 사용자 역할 목록 조회
     * @param int $userId
     * @return array
     */
    public function getUserRoles(int $userId): array {
        $sql = "SELECT r.id, r.name FROM sys_user_roles ur
                JOIN sys_roles r ON ur.role_id = r.id
                WHERE ur.user_id = :user_id
                ORDER BY r.name";
        return $this->db->query($sql, [':user_id' => $userId]);
    }

    /**
     * 사용자 상태 토글 (활성/비활성)
     * @param int $userId
     * @return bool
     */
    public function toggleStatus(int $userId): bool {
        $sql = "UPDATE sys_users SET status = CASE 
                    WHEN status = '활성' THEN '비활성'
                    ELSE '활성'
                END 
                WHERE id = :id";
        return $this->db->execute($sql, [':id' => $userId]) > 0;
    }

    /**
     * Kakao ID로 사용자를 찾거나 새로 만듭니다.
     * 이전에는 User 모델에 있던 로직을 캡슐화하고
     * 표준화된 데이터베이스 헬퍼를 사용합니다.
     * @param array $kakaoUser
     * @return array
     */
    public function findOrCreateFromKakao(array $kakaoUser): array
    {
        // 1. Kakao ID로 사용자 찾기
        $user = $this->findByKakaoId($kakaoUser['id']);

        if ($user) {
            // 사용자가 존재하면 해당 데이터 반환
            return $user;
        }

        // 2. 사용자가 존재하지 않으면 기존 create 메서드를 사용하여 새로 만들기
        $newUserId = $this->create($kakaoUser);

        // 3. 새로 만든 사용자의 데이터 반환
        return $this->findById((int)$newUserId);
    }
}
