<?php
// app/Repositories/EmployeeRepository.php
namespace App\Repositories;

use App\Core\Database;

class EmployeeRepository {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * 직원의 고유 ID(PK)로 직원 정보를 조회합니다.
     * @param int $id 직원의 id
     * @return mixed
     */
    public function findById(int $id) {
        $sql = "SELECT e.*, d.name as department_name, p.name as position_name
                FROM hr_employees e
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_positions p ON e.position_id = p.id
                WHERE e.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    /**
     * Finds active employees who are not linked to any user account.
     * @return array
     */
    public function findUnlinked(): array
    {
        $sql = "SELECT e.*
                FROM hr_employees e
                LEFT JOIN sys_users u ON e.id = u.employee_id
                WHERE u.employee_id IS NULL AND e.termination_date IS NULL
                ORDER BY e.name ASC";
        return $this->db->query($sql);
    }

    /**
     * 사용자의 고유 ID(user_id)에 연결된 직원 정보를 조회합니다.
     * @param int $userId 사용자의 id
     * @return mixed
     */
    public function findByUserId(int $userId) {
        $sql = "SELECT e.*, d.name as department_name, p.name as position_name
                FROM hr_employees e
                JOIN sys_users u ON e.id = u.employee_id 
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_positions p ON e.position_id = p.id
                WHERE u.id = :user_id";
        return $this->db->fetchOne($sql, [':user_id' => $userId]);
    }

    public function findByDepartmentIds(array $departmentIds): array
    {
        if (empty($departmentIds)) {
            return [];
        }
        $inClause = implode(',', array_map('intval', $departmentIds));
        $sql = "SELECT e.id, e.name
                FROM hr_employees e
                WHERE e.department_id IN ($inClause) AND e.termination_date IS NULL
                ORDER BY e.name ASC";
        return $this->db->query($sql);
    }

    public function findByIds(array $employeeIds): array
    {
        if (empty($employeeIds)) {
            return [];
        }
        $inClause = implode(',', array_map('intval', $employeeIds));
        $sql = "SELECT e.id, e.name
                FROM hr_employees e
                WHERE e.id IN ($inClause)";
        return $this->db->query($sql);
    }

    /**
     * 모든 직원 목록을 조회합니다. 연결된 사용자 닉네임도 함께 가져옵니다.
     * @param array $filters 필터 조건 (예: ['department_id' => 1])
     * @return array
     */
    public function getAll(array $filters = []): array {
        $sql = "SELECT e.*, u.nickname, d.name as department_name, p.name as position_name
                FROM hr_employees e
                LEFT JOIN sys_users u ON e.id = u.employee_id
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_positions p ON e.position_id = p.id";

        $whereClauses = [];
        $params = [];

        if (!empty($filters['department_id'])) {
            if (is_array($filters['department_id'])) {
                if (count($filters['department_id']) > 0) {
                    // Sanitize all IDs to ensure they are integers
                    $deptIds = array_map('intval', $filters['department_id']);
                    $inClause = implode(',', $deptIds);
                    $whereClauses[] = "e.department_id IN ($inClause)";
                } else {
                    // Handle empty array case to return no results
                    $whereClauses[] = "1=0";
                }
            } else {
                // Handle single department ID
                $whereClauses[] = "e.department_id = :department_id";
                $params[':department_id'] = $filters['department_id'];
            }
        }

        if (!empty($filters['position_id'])) {
            $whereClauses[] = "e.position_id = :position_id";
            $params[':position_id'] = $filters['position_id'];
        }

        if (isset($filters['status']) && $filters['status']) {
            if ($filters['status'] === 'active') {
                $whereClauses[] = "e.termination_date IS NULL";
            } elseif ($filters['status'] === 'inactive') {
                $whereClauses[] = "e.termination_date IS NOT NULL";
            }
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $sql .= " ORDER BY
            CASE p.name
                WHEN '대표' THEN 1
                WHEN '부장' THEN 2
                WHEN '과장' THEN 3
                WHEN '팀장' THEN 4
                WHEN '조장' THEN 5
                WHEN '주임' THEN 6
                WHEN '사원' THEN 7
                ELSE 8
            END,
            e.hire_date ASC";

        return $this->db->query($sql, $params);
    }

    /**
     * 모든 활성 직원 (퇴사일이 없는 직원) 목록을 조회합니다.
     * @return array
     */
    public function findAllActive(): array {
        $sql = "SELECT e.*, d.name as department_name, p.name as position_name
                FROM hr_employees e
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_positions p ON e.position_id = p.id
                WHERE e.termination_date IS NULL
                ORDER BY e.name ASC";

        return $this->db->query($sql);
    }

    /**
     * 직원 정보를 저장 (생성/수정)합니다. 신규 생성 시 사번을 자동으로 생성합니다.
     * @param array $data 저장할 데이터
     * @return string|null 저장된 직원의 ID
     */
    public function save(array $data): ?string {
        $id = $data['id'] ?? null;
        
        // 신규 생성일 경우에만 사번 생성
        if (!$id) {
            $hireDate = $data['hire_date'] ?? date('Y-m-d');
            if (empty($hireDate)) $hireDate = date('Y-m-d');
            $prefix = 'WS' . date('ym', strtotime($hireDate));

            $seqSql = "SELECT MAX(CAST(SUBSTRING(employee_number, 7) AS UNSIGNED)) as last_num FROM hr_employees WHERE employee_number LIKE :prefix";
            $result = $this->db->fetchOne($seqSql, [':prefix' => $prefix . '%']);
            $nextSeq = ($result['last_num'] ?? 0) + 1;
            $data['employee_number'] = $prefix . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
        }

        if ($id) { // Update
            $sql = "UPDATE hr_employees SET name=:name, employee_number=:employee_number, hire_date=:hire_date, phone_number=:phone_number, address=:address, emergency_contact_name=:emergency_contact_name, emergency_contact_relation=:emergency_contact_relation, clothing_top_size=:clothing_top_size, clothing_bottom_size=:clothing_bottom_size, shoe_size=:shoe_size, department_id=:department_id, position_id=:position_id WHERE id=:id";
        } else { // Insert
            $sql = "INSERT INTO hr_employees (name, employee_number, hire_date, phone_number, address, emergency_contact_name, emergency_contact_relation, clothing_top_size, clothing_bottom_size, shoe_size, department_id, position_id) VALUES (:name, :employee_number, :hire_date, :phone_number, :address, :emergency_contact_name, :emergency_contact_relation, :clothing_top_size, :clothing_bottom_size, :shoe_size, :department_id, :position_id)";
        }
        
        $this->db->execute($sql, [
            ':id' => $id,
            ':name' => $data['name'],
            ':employee_number' => $data['employee_number'],
            ':hire_date' => $data['hire_date'] ?: null,
            ':phone_number' => $data['phone_number'] ?: null,
            ':address' => $data['address'] ?: null,
            ':emergency_contact_name' => $data['emergency_contact_name'] ?: null,
            ':emergency_contact_relation' => $data['emergency_contact_relation'] ?: null,
            ':clothing_top_size' => $data['clothing_top_size'] ?: null,
            ':clothing_bottom_size' => $data['clothing_bottom_size'] ?: null,
            ':shoe_size' => $data['shoe_size'] ?: null,
            ':department_id' => $data['department_id'] ?: null,
            ':position_id' => $data['position_id'] ?: null,
        ]);

        return $id ?: $this->db->lastInsertId();
    }

    /**
     * 직원 정보를 삭제합니다.
     * @param int $id 삭제할 직원의 id
     * @return bool
     */
    public function delete(int $id): bool {
        return $this->db->execute("DELETE FROM hr_employees WHERE id = :id", [':id' => $id]) > 0;
    }

    /**
     * [사용자용] 프로필 수정 요청을 받아 'pending_profile_data'에 JSON으로 저장합니다.
     */
    public function requestProfileUpdate(int $userId, array $data): bool {
        $sql = "UPDATE hr_employees SET 
                    profile_update_status = 'pending',
                    profile_update_rejection_reason = NULL,
                    pending_profile_data = :pending_data
                WHERE id = (SELECT employee_id FROM sys_users WHERE id = :user_id)";
        
        $pendingData = [
            'phone_number' => $data['phone_number'], 'address' => $data['address'],
            'emergency_contact_name' => $data['emergency_contact_name'], 'emergency_contact_relation' => $data['emergency_contact_relation'],
            'clothing_top_size' => $data['clothing_top_size'], 'clothing_bottom_size' => $data['clothing_bottom_size'],
            'shoe_size' => $data['shoe_size'],
        ];

        return $this->db->execute($sql, [
            ':user_id' => $userId,
            ':pending_data' => json_encode($pendingData, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * [관리자용] 프로필 변경을 최종 승인하고 실제 데이터를 업데이트합니다.
     */
    public function applyProfileUpdate(int $employeeId, array $newData): bool {
        $sql = "UPDATE hr_employees SET
                    phone_number = :phone_number, address = :address,
                    emergency_contact_name = :emergency_contact_name, emergency_contact_relation = :emergency_contact_relation,
                    clothing_top_size = :clothing_top_size, clothing_bottom_size = :clothing_bottom_size,
                    shoe_size = :shoe_size,
                    profile_update_status = 'none',
                    pending_profile_data = NULL,
                    profile_update_rejection_reason = NULL
                WHERE id = :id";
        
        return $this->db->execute($sql, [
            ':id' => $employeeId,
            ':phone_number' => $newData['phone_number'], ':address' => $newData['address'],
            ':emergency_contact_name' => $newData['emergency_contact_name'], ':emergency_contact_relation' => $newData['emergency_contact_relation'],
            ':clothing_top_size' => $newData['clothing_top_size'], ':clothing_bottom_size' => $newData['clothing_bottom_size'],
            ':shoe_size' => $newData['shoe_size']
        ]);
    }

    /**
     * [관리자용] 프로필 수정을 반려 처리합니다.
     */
    public function rejectProfileUpdate(int $employeeId, string $reason): bool {
        $sql = "UPDATE hr_employees SET 
                    profile_update_status = 'rejected',
                    profile_update_rejection_reason = :reason,
                    pending_profile_data = NULL
                WHERE id = :id";
        return $this->db->execute($sql, [':id' => $employeeId, ':reason' => $reason]) > 0;
    }

    /**
     * [관리자용] 프로필 변경 요청을 승인 처리합니다. (상태만 변경)
     */
    public function approveProfileUpdateStatus(int $employeeId): bool {
        $sql = "UPDATE hr_employees SET profile_update_status = 'none' WHERE id = :id";
        return $this->db->execute($sql, [':id' => $employeeId]) > 0;
    }
}