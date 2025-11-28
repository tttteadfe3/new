<?php
namespace App\Repositories;

use App\Core\Database;
use App\Models\Department;
use App\Services\PolicyEngine;
use App\Core\SessionManager;

class DepartmentRepository {
    private Database $db;
    private PolicyEngine $policyEngine;
    private SessionManager $sessionManager;

    public function __construct(Database $db, PolicyEngine $policyEngine, SessionManager $sessionManager) {
        $this->db = $db;
        $this->policyEngine = $policyEngine;
        $this->sessionManager = $sessionManager;
    }

    /**
     * @return Department[]
     */
    public function getAll(): array {
        $queryParts = [
            'sql' => "SELECT d.* FROM hr_departments d",
            'params' => [],
            'where' => []
        ];

        // 데이터 스코프 적용 (PolicyEngine 사용)
        $user = $this->sessionManager->get('user');
        if ($user) {
            $scopeIds = $this->policyEngine->getScopeIds($user['id'], 'department', 'view');
            
            if ($scopeIds === null) {
                // 전체 조회 가능
            } elseif (empty($scopeIds)) {
                $queryParts['where'][] = "1=0";
            } else {
                $inClause = implode(',', array_map('intval', $scopeIds));
                $queryParts['where'][] = "d.id IN ($inClause)";
            }
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY d.name";

        $results = $this->db->fetchAllAs(Department::class, $queryParts['sql'], $queryParts['params']);

        return $results;
    }

    /**
     * 스코프 제한 없이 모든 부서를 조회합니다.
     * @return Department[]
     */
    public function getAllUnscoped(): array {
        $sql = "SELECT d.* FROM hr_departments d ORDER BY d.name";
        return $this->db->fetchAllAs(Department::class, $sql);
    }

    /**
     * 배열 형태로 모든 부서를 조회합니다.
     * @return array
     */
    public function getAllAsArray(): array {
        $queryParts = [
            'sql' => "SELECT d.id, d.name, d.parent_id FROM hr_departments d",
            'params' => [],
            'where' => []
        ];

        // 데이터 스코프 적용 (PolicyEngine 사용)
        $user = $this->sessionManager->get('user');
        if ($user) {
            $scopeIds = $this->policyEngine->getScopeIds($user['id'], 'department', 'view');
            
            if ($scopeIds === null) {
                // 전체 조회 가능
            } elseif (empty($scopeIds)) {
                $queryParts['where'][] = "1=0";
            } else {
                $inClause = implode(',', array_map('intval', $scopeIds));
                $queryParts['where'][] = "d.id IN ($inClause)";
            }
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY d.name";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * @param int $id
     * @return Department|null
     */
    public function findById(int $id): ?Department {
        $sql = "
            SELECT d.*
            FROM hr_departments d
            WHERE d.id = :id
        ";
        $result = $this->db->fetchOneAs(Department::class, $sql, [':id' => $id]);
        return $result ?: null;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $inClause = implode(',', array_map('intval', $ids));
        $sql = "SELECT * FROM hr_departments WHERE id IN ($inClause)";
        return $this->db->fetchAllAs(Department::class, $sql);
    }

    /**
     * @param int $parentId
     * @return array
     */
    public function findByParentId(int $parentId): array
    {
        $sql = "SELECT * FROM hr_departments WHERE parent_id = :parent_id";
        return $this->db->fetchAll($sql, [':parent_id' => $parentId]);
    }

    /**
     * @param int $departmentId
     * @return array
     */
    public function findDepartmentViewPermissionIds(int $departmentId): array
    {
        $sql = "SELECT permitted_department_id FROM hr_department_view_permissions WHERE department_id = :department_id";
        $results = $this->db->query($sql, [':department_id' => $departmentId]);
        return array_column($results, 'permitted_department_id');
    }

    /**
     * @param int $permittedDepartmentId
     * @return array
     */
    public function findVisibleDepartmentIdsForGivenDepartment(int $permittedDepartmentId): array
    {
        $sql = "SELECT department_id FROM hr_department_view_permissions WHERE permitted_department_id = :permitted_department_id";
        $results = $this->db->query($sql, [':permitted_department_id' => $permittedDepartmentId]);
        return array_column($results, 'department_id');
    }

    /**
     * @param int $employeeId
     * @return array
     */
    public function findDepartmentIdsWithEmployeeViewPermission(int $employeeId): array
    {
        $sql = "SELECT department_id FROM hr_department_managers WHERE employee_id = :employee_id";
        $results = $this->db->query($sql, [':employee_id' => $employeeId]);
        return array_column($results, 'department_id');
    }

    /**
     * @param int $departmentId
     * @return array
     */
    public function findSubtreeIds(int $departmentId): array
    {
        $sql = "
            WITH RECURSIVE DepartmentHierarchy AS (
                SELECT id FROM hr_departments WHERE id = :department_id
                UNION ALL
                SELECT d.id FROM hr_departments d
                INNER JOIN DepartmentHierarchy dh ON d.parent_id = dh.id
            )
            SELECT id FROM DepartmentHierarchy
        ";
        $results = $this->db->query($sql, [':department_id' => $departmentId]);
        return array_column($results, 'id');
    }

    /**
     * @param int $departmentId
     * @return array
     */
    public function findAncestorIds(int $departmentId): array
    {
        $sql = "
            WITH RECURSIVE DepartmentAncestors AS (
                SELECT id, parent_id FROM hr_departments WHERE id = :department_id
                UNION ALL
                SELECT d.id, d.parent_id FROM hr_departments d
                INNER JOIN DepartmentAncestors da ON d.id = da.parent_id
            )
            SELECT id FROM DepartmentAncestors
        ";
        $results = $this->db->query($sql, [':department_id' => $departmentId]);
        return array_column($results, 'id');
    }

    /**
     * @return array
     */
    public function findAllWithEmployees(): array {
        $queryParts = [
            'sql' => "
                SELECT
                    d.id, d.name, d.parent_id,
                    e.id as employee_id,
                    e.name as employee_name,
                    p.name as position_name,
                    (SELECT GROUP_CONCAT(m.name SEPARATOR ', ') FROM hr_department_managers dm JOIN hr_employees m ON dm.employee_id = m.id WHERE dm.department_id = d.id) as viewer_employee_names,
                    (SELECT GROUP_CONCAT(dm.employee_id SEPARATOR ',') FROM hr_department_managers dm WHERE dm.department_id = d.id) as viewer_employee_ids
                FROM
                hr_departments d
                LEFT JOIN
                    hr_employees e ON d.id = e.department_id AND e.termination_date IS NULL
                LEFT JOIN
                    hr_positions p ON e.position_id = p.id",
            'params' => [],
            'where' => []
        ];

        // 데이터 스코프 적용 (PolicyEngine 사용)
        $user = $this->sessionManager->get('user');
        if ($user) {
            $scopeIds = $this->policyEngine->getScopeIds($user['id'], 'department', 'view');
            
            if ($scopeIds === null) {
                // 전체 조회 가능
            } elseif (empty($scopeIds)) {
                $queryParts['where'][] = "1=0";
            } else {
                $inClause = implode(',', array_map('intval', $scopeIds));
                $queryParts['where'][] = "d.id IN ($inClause)";
            }
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY d.parent_id ASC, d.name ASC, e.name ASC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    /**
     * @return array
     */
    public function findAllWithViewers(): array
    {
        $sql = "
            SELECT
                d.*,
                d.name as simple_name,
                GROUP_CONCAT(m.name SEPARATOR ', ') as viewer_employee_names,
                GROUP_CONCAT(dm.employee_id SEPARATOR ',') as viewer_employee_ids
            FROM hr_departments d
            LEFT JOIN hr_department_managers dm ON d.id = dm.department_id
            LEFT JOIN hr_employees m ON dm.employee_id = m.id
            GROUP BY d.id
            ORDER BY d.name
        ";
        return $this->db->fetchAll($sql);
    }

    /**
     * @param array $data
     * @return string
     */
    public function create(array $data): string {
        $sql = "INSERT INTO hr_departments (name, parent_id, path) VALUES (:name, :parent_id, :path)";
        $params = [
            ':name' => $data['name'],
            ':parent_id' => !empty($data['parent_id']) ? $data['parent_id'] : null,
            ':path' => $data['path'] ?? null
        ];
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool {
        $sql = "UPDATE hr_departments SET name = :name, parent_id = :parent_id, path = :path WHERE id = :id";
        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':parent_id' => !empty($data['parent_id']) ? $data['parent_id'] : null,
            ':path' => $data['path'] ?? null
        ];
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function isEmployeeAssigned(int $id): bool {
        $sql = "SELECT 1 FROM hr_employees WHERE department_id = :id LIMIT 1";
        return (bool) $this->db->fetchOne($sql, [':id' => $id]);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool {
        if ($this->isEmployeeAssigned($id)) {
            return false;
        }
        return $this->db->execute("DELETE FROM hr_departments WHERE id = :id", [':id' => $id]) > 0;
    }

    /**
     * @param int $departmentId
     * @param array $employeeIds
     * @return void
     */
    public function replaceEmployeeViewPermissions(int $departmentId, array $employeeIds): void
    {
        $this->db->execute("DELETE FROM hr_department_managers WHERE department_id = :department_id", [':department_id' => $departmentId]);

        if (empty($employeeIds)) {
            return;
        }

        $sql = "INSERT INTO hr_department_managers (department_id, employee_id) VALUES ";
        $params = [];
        $placeholders = [];
        foreach ($employeeIds as $i => $employeeId) {
            $placeholders[] = "(:department_id_{$i}, :employee_id_{$i})";
            $params[":department_id_{$i}"] = $departmentId;
            $params[":employee_id_{$i}"] = $employeeId;
        }
        $sql .= implode(', ', $placeholders);

        $this->db->execute($sql, $params);
    }

    /**
     * @param int $departmentId
     * @param array $permittedDepartmentIds
     * @return void
     */
    public function replaceDepartmentViewPermissions(int $departmentId, array $permittedDepartmentIds): void
    {
        $this->db->execute("DELETE FROM hr_department_view_permissions WHERE department_id = :department_id", [':department_id' => $departmentId]);

        if (empty($permittedDepartmentIds)) {
            return;
        }

        $sql = "INSERT INTO hr_department_view_permissions (department_id, permitted_department_id) VALUES ";
        $params = [];
        $placeholders = [];
        foreach ($permittedDepartmentIds as $i => $permittedDepartmentId) {
            $placeholders[] = "(:department_id_{$i}, :permitted_department_id_{$i})";
            $params[":department_id_{$i}"] = $departmentId;
            $params[":permitted_department_id_{$i}"] = $permittedDepartmentId;
        }
        $sql .= implode(', ', $placeholders);

        $this->db->execute($sql, $params);
    }

    /**
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * @return void
     */
    public function commit(): void
    {
        $this->db->commit();
    }

    /**
     * @return void
     */
    public function rollBack(): void
    {
        $this->db->rollBack();
    }
}
