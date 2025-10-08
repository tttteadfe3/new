<?php
// app/Repositories/DepartmentRepository.php
namespace App\Repositories;

use App\Core\Database;

class DepartmentRepository {
    public static function getAll() {
        return Database::query("SELECT * FROM hr_departments ORDER BY name");
    }

    public static function findById(int $id) {
        return Database::fetchOne("SELECT * FROM hr_departments WHERE id = :id", [':id' => $id]);
    }

    public static function create(string $name): string {
        $sql = "INSERT INTO hr_departments (name) VALUES (:name)";
        Database::execute($sql, [':name' => $name]);
        return Database::lastInsertId();
    }

    public static function update(int $id, string $name): bool {
        $sql = "UPDATE hr_departments SET name = :name WHERE id = :id";
        return Database::execute($sql, [':id' => $id, ':name' => $name]);
    }

    public static function isEmployeeAssigned(int $id): bool {
        $sql = "SELECT 1 FROM hr_employees WHERE department_id = :id LIMIT 1";
        return (bool) Database::fetchOne($sql, [':id' => $id]);
    }

    public static function delete(int $id): bool {
        if (self::isEmployeeAssigned($id)) {
            return false;
        }
        return Database::execute("DELETE FROM hr_departments WHERE id = :id", [':id' => $id]);
    }
}
