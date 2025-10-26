<?php
// app/Repositories/EmployeeChangeLogRepository.php
namespace App\Repositories;
use App\Core\Database;

class EmployeeChangeLogRepository {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }
    /**
     * 직원 정보 변경 로그를 기록합니다.
     * @param int $employeeId
     * @param int $changerEmployeeId
     * @param string $fieldName
     * @param string|null $oldValue
     * @param string|null $newValue
     * @return bool
     */
    public function insert(int $employeeId, int $changerEmployeeId, string $fieldName, ?string $oldValue, ?string $newValue): bool {
        $sql = "INSERT INTO hr_employee_change_logs (employee_id, changer_employee_id, field_name, old_value, new_value)
                VALUES (:employee_id, :changer_employee_id, :field_name, :old_value, :new_value)";
        
        return $this->db->execute($sql, [
            ':employee_id' => $employeeId,
            ':changer_employee_id' => $changerEmployeeId,
            ':field_name' => $fieldName,
            ':old_value' => $oldValue,
            ':new_value' => $newValue
        ]);
    }

    /**
     * 특정 직원의 모든 변경 이력을 조회합니다.
     * @param int $employeeId
     * @return array
     */
    public function findByEmployeeId(int $employeeId): array {
        $sql = "SELECT l.*, e.name as changer_name
                FROM hr_employee_change_logs l
                LEFT JOIN hr_employees e ON l.changer_employee_id = e.id
                WHERE l.employee_id = :employee_id 
                ORDER BY l.changed_at DESC";
        return $this->db->query($sql, [':employee_id' => $employeeId]);
    }
}
