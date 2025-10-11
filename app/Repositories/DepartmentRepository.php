<?php
// app/Repositories/DepartmentRepository.php
namespace App\Repositories;

use App\Core\Database;

class DepartmentRepository {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }
    public function getAll() {
        return $this->db->query("SELECT * FROM hr_departments ORDER BY name");
    }

    public function findById(int $id) {
        return $this->db->fetchOne("SELECT * FROM hr_departments WHERE id = :id", [':id' => $id]);
    }

    public function create(string $name): string {
        $sql = "INSERT INTO hr_departments (name) VALUES (:name)";
        $this->db->execute($sql, [':name' => $name]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, string $name): bool {
        $sql = "UPDATE hr_departments SET name = :name WHERE id = :id";
        return $this->db->execute($sql, [':id' => $id, ':name' => $name]) > 0;
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
