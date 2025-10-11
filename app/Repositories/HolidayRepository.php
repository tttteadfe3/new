<?php
// app/Repositories/HolidayRepository.php
namespace App\Repositories;

use App\Core\Database;

class HolidayRepository {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }
    public function getAll() {
        $sql = "SELECT h.*, d.name as department_name
                FROM hr_holidays h
                LEFT JOIN hr_departments d ON h.department_id = d.id
                ORDER BY h.date DESC";
        return $this->db->query($sql);
    }

    public function findById(int $id) {
        return $this->db->fetchOne("SELECT * FROM hr_holidays WHERE id = :id", [':id' => $id]);
    }

    public function create(array $data): string {
        $sql = "INSERT INTO hr_holidays (name, date, type, department_id, deduct_leave)
                VALUES (:name, :date, :type, :department_id, :deduct_leave)";

        $params = [
            ':name' => $data['name'],
            ':date' => $data['date'],
            ':type' => $data['type'],
            ':department_id' => $data['department_id'] ?: null,
            ':deduct_leave' => $data['deduct_leave']
        ];

        $this->db->execute($sql, $params) > 0;
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE hr_holidays SET
                    name = :name,
                    date = :date,
                    type = :type,
                    department_id = :department_id,
                    deduct_leave = :deduct_leave
                WHERE id = :id";

        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':date' => $data['date'],
            ':type' => $data['type'],
            ':department_id' => $data['department_id'] ?: null,
            ':deduct_leave' => $data['deduct_leave']
        ];

        return $this->db->execute($sql, $params) > 0;
    }

    public function delete(int $id): bool {
        return $this->db->execute("DELETE FROM hr_holidays WHERE id = :id", [':id' => $id]) > 0;
    }

    /**
     * 특정 기간과 부서에 해당하는 휴일/근무일 정보를 조회합니다.
     * @param string $startDate 시작일 (Y-m-d)
     * @param string $endDate 종료일 (Y-m-d)
     * @param int|null $departmentId 부서 ID (null이면 전체 부서용 설정만 조회)
     * @return array
     */
    public function findForDateRange(string $startDate, string $endDate, ?int $departmentId): array {
        // 부서별 설정과 전체 공통 설정을 모두 가져옵니다.
        // department_id IS NULL: 전체 공통
        // department_id = :departmentId: 해당 직원의 부서
        $sql = "SELECT * FROM hr_holidays
                WHERE date BETWEEN :start_date AND :end_date
                AND (department_id = :department_id OR department_id IS NULL)";

        $params = [
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':department_id' => $departmentId
        ];

        return $this->db->query($sql, $params);
    }
}