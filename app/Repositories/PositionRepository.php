<?php
// app/Repositories/PositionRepository.php
namespace App\Repositories;

use App\Core\Database;

class PositionRepository {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * @return array
     */
    public function getAll() {
        return $this->db->query("SELECT * FROM hr_positions ORDER BY level ASC, name ASC");
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function findById(int $id) {
        return $this->db->fetchOne("SELECT * FROM hr_positions WHERE id = :id", [':id' => $id]);
    }

    /**
     * @param string $name
     * @param int $level
     * @return string
     */
    public function create(string $name, int $level): string {
        $sql = "INSERT INTO hr_positions (name, level) VALUES (:name, :level)";
        $this->db->execute($sql, [':name' => $name, ':level' => $level]);
        return $this->db->lastInsertId();
    }

    /**
     * @param int $id
     * @param string $name
     * @param int $level
     * @return bool
     */
    public function update(int $id, string $name, int $level): bool {
        // 먼저 직책이 존재하는지 확인합니다.
        if (!$this->findById($id)) {
            return false;
        }

        // 존재하면 업데이트를 수행합니다.
        // 0개의 행이 영향을 받더라도 (즉, 데이터가 동일하더라도) 작업은 성공합니다.
        $sql = "UPDATE hr_positions SET name = :name, level = :level WHERE id = :id";
        $this->db->execute($sql, [':id' => $id, ':name' => $name, ':level' => $level]);
        return true;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function isEmployeeAssigned(int $id): bool {
        $sql = "SELECT 1 FROM hr_employees WHERE position_id = :id LIMIT 1";
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
        return $this->db->execute("DELETE FROM hr_positions WHERE id = :id", [':id' => $id]) > 0;
    }
}
