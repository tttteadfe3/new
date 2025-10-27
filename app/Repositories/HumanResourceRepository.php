<?php

namespace App\Repositories;

use App\Core\Database;

class HumanResourceRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * 직원의 부서 또는 직급을 업데이트합니다.
     *
     * @param int $employeeId
     * @param array $data ['department_id' => ?, 'position_id' => ?]
     * @return bool
     */
    public function updateEmployeeAssignment(int $employeeId, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $setClauses = [];
        $params = [':id' => $employeeId];

        if (isset($data['department_id'])) {
            $setClauses[] = 'department_id = :department_id';
            $params[':department_id'] = $data['department_id'];
        }

        if (isset($data['position_id'])) {
            $setClauses[] = 'position_id = :position_id';
            $params[':position_id'] = $data['position_id'];
        }

        if (empty($setClauses)) {
            return false;
        }

        $sql = "UPDATE hr_employees SET " . implode(', ', $setClauses) . " WHERE id = :id";

        return $this->db->execute($sql, $params) > 0;
    }
}
