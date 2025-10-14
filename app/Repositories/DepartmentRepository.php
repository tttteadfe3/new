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
        return $this->db->fetchAllAs(Department::class, "SELECT * FROM hr_departments ORDER BY name");
    }

    public function findById(int $id): ?Department {
        $result = $this->db->fetchOneAs(Department::class, "SELECT * FROM hr_departments WHERE id = :id", [':id' => $id]);
        return $result ?: null;
    }

    public function findAllWithEmployees(): array {
        $sql = "
            SELECT
                d.id, d.name, d.parent_id, d.manager_id,
                e.id as employee_id,
                e.name as employee_name,
                p.name as position_name,
                manager.name as manager_name
            FROM
                hr_departments d
            LEFT JOIN
                hr_employees e ON d.id = e.department_id AND e.termination_date IS NULL
            LEFT JOIN
                hr_positions p ON e.position_id = p.id
            LEFT JOIN
                hr_employees manager ON d.manager_id = manager.id AND manager.termination_date IS NULL
            ORDER BY
                d.parent_id ASC, d.name ASC, e.name ASC
        ";
        return $this->db->query($sql);
    }

    public function create(array $data): string {
        $sql = "INSERT INTO hr_departments (name, parent_id, manager_id, can_view_all_leaves) VALUES (:name, :parent_id, :manager_id, :can_view_all_leaves)";
        $params = [
            ':name' => $data['name'],
            ':parent_id' => !empty($data['parent_id']) ? $data['parent_id'] : null,
            ':manager_id' => !empty($data['manager_id']) ? $data['manager_id'] : null,
            ':can_view_all_leaves' => isset($data['can_view_all_leaves']) && $data['can_view_all_leaves'] ? 1 : 0
        ];
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE hr_departments SET name = :name, parent_id = :parent_id, manager_id = :manager_id, can_view_all_leaves = :can_view_all_leaves WHERE id = :id";
        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':parent_id' => !empty($data['parent_id']) ? $data['parent_id'] : null,
            ':manager_id' => !empty($data['manager_id']) ? $data['manager_id'] : null,
            ':can_view_all_leaves' => isset($data['can_view_all_leaves']) && $data['can_view_all_leaves'] ? 1 : 0
        ];
        return $this->db->execute($sql, $params) > 0;
    }

    public function updateManager(int $departmentId, ?int $managerId): bool {
        $sql = "UPDATE hr_departments SET manager_id = :manager_id WHERE id = :id";
        return $this->db->execute($sql, [':id' => $departmentId, ':manager_id' => $managerId]) > 0;
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
}
