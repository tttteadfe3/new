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
            SELECT d.*, m.employee_id as manager_id
            FROM hr_departments d
            LEFT JOIN hr_department_managers m ON d.id = m.department_id
            ORDER BY d.name
        ";
        return $this->db->fetchAllAs(Department::class, $sql);
    }

    public function findById(int $id): ?Department {
        $sql = "
            SELECT d.*, m.employee_id as manager_id
            FROM hr_departments d
            LEFT JOIN hr_department_managers m ON d.id = m.department_id
            WHERE d.id = :id
        ";
        $result = $this->db->fetchOneAs(Department::class, $sql, [':id' => $id]);
        return $result ?: null;
    }

    public function findManagedDepartmentIdsByEmployee(int $employeeId): array
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

    public function findAllWithEmployees(): array {
        $sql = "
            SELECT
                d.id, d.name, d.parent_id,
                e.id as employee_id,
                e.name as employee_name,
                p.name as position_name,
                (SELECT GROUP_CONCAT(m.name SEPARATOR ', ') FROM hr_department_managers dm JOIN hr_employees m ON dm.employee_id = m.id WHERE dm.department_id = d.id) as manager_name
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

    public function create(array $data): string {
        $sql = "INSERT INTO hr_departments (name, parent_id, can_view_all_leaves) VALUES (:name, :parent_id, :can_view_all_leaves)";
        $params = [
            ':name' => $data['name'],
            ':parent_id' => !empty($data['parent_id']) ? $data['parent_id'] : null,
            ':can_view_all_leaves' => isset($data['can_view_all_leaves']) && $data['can_view_all_leaves'] ? 1 : 0
        ];
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE hr_departments SET name = :name, parent_id = :parent_id, can_view_all_leaves = :can_view_all_leaves WHERE id = :id";
        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':parent_id' => !empty($data['parent_id']) ? $data['parent_id'] : null,
            ':can_view_all_leaves' => isset($data['can_view_all_leaves']) && $data['can_view_all_leaves'] ? 1 : 0
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

    public function replaceManagers(int $departmentId, array $managerIds): void
    {
        $this->db->execute("DELETE FROM hr_department_managers WHERE department_id = :department_id", [':department_id' => $departmentId]);

        if (empty($managerIds)) {
            return;
        }

        $sql = "INSERT INTO hr_department_managers (department_id, employee_id) VALUES ";
        $params = [];
        $placeholders = [];
        foreach ($managerIds as $i => $managerId) {
            $placeholders[] = "(:department_id_{$i}, :manager_id_{$i})";
            $params[":department_id_{$i}"] = $departmentId;
            $params[":manager_id_{$i}"] = $managerId;
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
