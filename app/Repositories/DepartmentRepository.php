<?php
namespace App\Repositories;

use App\Core\Database;
use App\Models\Department;

class DepartmentRepository {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * @return Department[]
     */
    public function getAll(): array {
        $sql = "
            SELECT d.*
            FROM hr_departments d
            ORDER BY d.name
        ";
        return $this->db->fetchAll($sql);
    }

    public function findById(int $id): ?Department {
        $sql = "
            SELECT d.*
            FROM hr_departments d
            WHERE d.id = :id
        ";
        $result = $this->db->fetchOneAs(Department::class, $sql, [':id' => $id]);
        return $result ?: null;
    }

    public function findByParentId(int $parentId): array
    {
        $sql = "SELECT * FROM hr_departments WHERE parent_id = :parent_id";
        return $this->db->fetchAll($sql, [':parent_id' => $parentId]);
    }

    public function findDepartmentViewPermissionIds(int $departmentId): array
    {
        $sql = "SELECT permitted_department_id FROM hr_department_view_permissions WHERE department_id = :department_id";
        $results = $this->db->query($sql, [':department_id' => $departmentId]);
        return array_column($results, 'permitted_department_id');
    }

    public function findDepartmentIdsWithEmployeeViewPermission(int $employeeId): array
    {
        $sql = "SELECT department_id FROM hr_department_managers WHERE employee_id = :employee_id";
        $results = $this->db->query($sql, [':employee_id' => $employeeId]);
        return array_column($results, 'department_id');
    }

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

    public function findAllWithEmployees(): array {
        $sql = "
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
                hr_positions p ON e.position_id = p.id
            ORDER BY
                d.parent_id ASC, d.name ASC, e.name ASC
        ";
        return $this->db->query($sql);
    }

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

    public function create(array $data): string {
        $sql = "INSERT INTO hr_departments (name, parent_id, path, can_view_all_employees) VALUES (:name, :parent_id, :path, :can_view_all_employees)";
        $params = [
            ':name' => $data['name'],
            ':parent_id' => !empty($data['parent_id']) ? $data['parent_id'] : null,
            ':path' => $data['path'] ?? null,
            ':can_view_all_employees' => isset($data['can_view_all_employees']) && $data['can_view_all_employees'] ? 1 : 0
        ];
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE hr_departments SET name = :name, parent_id = :parent_id, path = :path, can_view_all_employees = :can_view_all_employees WHERE id = :id";
        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':parent_id' => !empty($data['parent_id']) ? $data['parent_id'] : null,
            ':path' => $data['path'] ?? null,
            ':can_view_all_employees' => isset($data['can_view_all_employees']) && $data['can_view_all_employees'] ? 1 : 0
        ];
        return $this->db->execute($sql, $params) > 0;
    }

    public function isEmployeeAssigned(int $id): bool {
        $sql = "SELECT 1 FROM hr_employees WHERE department_id = :id LIMIT 1";
        return (bool) $this->db->fetchOne($sql, [':id' => $id]);
    }

    public function delete(int $id): bool {
        if ($this->isEmployeeAssigned($id)) {
            return false;
        }
        return $this->db->execute("DELETE FROM hr_departments WHERE id = :id", [':id' => $id]) > 0;
    }

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

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    public function rollBack(): void
    {
        $this->db->rollBack();
    }
}
