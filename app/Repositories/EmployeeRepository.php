<?php
// app/Repositories/EmployeeRepository.php
namespace App\Repositories;

use App\Core\Database;
use App\Services\DataScopeService;

class EmployeeRepository {
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService) {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
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
     * 사용자 계정에 연결되지 않은 활성 직원을 찾습니다.
     * @return array
     */
    public function findUnlinked(?int $departmentId = null): array
    {
        $params = [];
        $sql = "SELECT e.*, p.level
                FROM hr_employees e
                LEFT JOIN sys_users u ON e.id = u.employee_id
                LEFT JOIN hr_positions p ON e.position_id = p.id
                WHERE u.employee_id IS NULL AND e.termination_date IS NULL";

        if ($departmentId) {
            $sql .= " AND e.department_id = :department_id";
            $params[':department_id'] = $departmentId;
        }

        $sql .= " ORDER BY p.level ASC, e.hire_date ASC";
        return $this->db->query($sql, $params);
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

    /**
     * @param array $departmentIds
     * @return array
     */
    public function findByDepartmentIds(array $departmentIds): array
    {
        if (empty($departmentIds)) {
            return [];
        }
        $inClause = implode(',', array_map('intval', $departmentIds));
        $sql = "SELECT e.id, e.name
                FROM hr_employees e
                JOIN hr_positions p ON e.position_id = p.id
                WHERE e.department_id IN ($inClause) AND e.termination_date IS NULL
                ORDER BY p.level ASC, e.hire_date ASC";
        return $this->db->query($sql);
    }

    /**
     * @param array $employeeIds
     * @return array
     */
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
     * @param array $filters 필터 조건
     * @return array
     */
    public function getAll(array $filters = []): array {
        $queryParts = [
            'sql' => "SELECT e.*, u.nickname, d.name as department_name, p.name as position_name
                      FROM hr_employees e
                      LEFT JOIN sys_users u ON e.id = u.employee_id
                      LEFT JOIN hr_departments d ON e.department_id = d.id
                      LEFT JOIN hr_positions p ON e.position_id = p.id",
            'params' => [],
            'where' => []
        ];

        // 데이터 스코프 적용
        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        if (!empty($filters['department_id'])) {
            $queryParts['where'][] = "e.department_id = :department_id";
            $queryParts['params'][':department_id'] = $filters['department_id'];
        }

        if (!empty($filters['position_id'])) {
            $queryParts['where'][] = "e.position_id = :position_id";
            $queryParts['params'][':position_id'] = $filters['position_id'];
        }

        if (isset($filters['status']) && $filters['status']) {
            if ($filters['status'] === '재직중') {
                $queryParts['where'][] = "e.termination_date IS NULL";
            } elseif ($filters['status'] === '퇴사') {
                $queryParts['where'][] = "e.termination_date IS NOT NULL";
            }
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY p.level ASC, e.hire_date ASC";

        return $this->db->query($queryParts['sql'], $queryParts['params']);
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
                ORDER BY p.level ASC, e.hire_date ASC";

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

        if ($id) { // 업데이트
            $sql = "UPDATE hr_employees SET phone_number=:phone_number, address=:address, emergency_contact_name=:emergency_contact_name, emergency_contact_relation=:emergency_contact_relation, clothing_top_size=:clothing_top_size, clothing_bottom_size=:clothing_bottom_size, shoe_size=:shoe_size WHERE id=:id";
            $params = [
                ':id' => $id,
                ':phone_number' => $data['phone_number'] ?? null,
                ':address' => $data['address'] ?? null,
                ':emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                ':emergency_contact_relation' => $data['emergency_contact_relation'] ?? null,
                ':clothing_top_size' => $data['clothing_top_size'] ?? null,
                ':clothing_bottom_size' => $data['clothing_bottom_size'] ?? null,
                ':shoe_size' => $data['shoe_size'] ?? null,
            ];
        } else { // 삽입
            $sql = "INSERT INTO hr_employees (name, employee_number, hire_date, phone_number, address, emergency_contact_name, emergency_contact_relation, clothing_top_size, clothing_bottom_size, shoe_size, department_id, position_id) VALUES (:name, :employee_number, :hire_date, :phone_number, :address, :emergency_contact_name, :emergency_contact_relation, :clothing_top_size, :clothing_bottom_size, :shoe_size, :department_id, :position_id)";
            $params = [
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
            ];
        }
        
        $this->db->execute($sql, $params);

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
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function requestProfileUpdate(int $userId, array $data): bool {
        $sql = "UPDATE hr_employees SET 
                    profile_update_status = '대기',
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
     * @param int $employeeId
     * @param array $newData
     * @return bool
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
     * @param int $employeeId
     * @param string $reason
     * @return bool
     */
    public function rejectProfileUpdate(int $employeeId, string $reason): bool {
        $sql = "UPDATE hr_employees SET 
                    profile_update_status = '반려',
                    profile_update_rejection_reason = :reason,
                    pending_profile_data = NULL
                WHERE id = :id";
        return $this->db->execute($sql, [':id' => $employeeId, ':reason' => $reason]) > 0;
    }

    /**
     * [관리자용] 프로필 변경 요청을 승인 처리합니다. (상태만 변경)
     * @param int $employeeId
     * @return bool
     */
    public function approveProfileUpdateStatus(int $employeeId): bool {
        $sql = "UPDATE hr_employees SET profile_update_status = 'none' WHERE id = :id";
        return $this->db->execute($sql, [':id' => $employeeId]) > 0;
    }

    /**
     * 직원의 퇴사일을 설정하고 관련 사용자 계정을 비활성화합니다.
     * @param int $employeeId
     * @param string $terminationDate
     * @return bool
     */
    public function setTerminationDate(int $employeeId, string $terminationDate): bool
    {
        $this->db->beginTransaction();

        try {
            // 1. 퇴사일 설정
            $sqlEmployee = "UPDATE hr_employees SET termination_date = :termination_date WHERE id = :id";
            $this->db->execute($sqlEmployee, [
                ':termination_date' => $terminationDate,
                ':id' => $employeeId
            ]);

            // 2. employee_id에 연결된 user_id를 먼저 조회합니다.
            $user = $this->db->fetchOne("SELECT id FROM sys_users WHERE employee_id = :employee_id", [':employee_id' => $employeeId]);

            // 3. 연결된 사용자가 있는 경우에만 후속 조치를 처리합니다.
            if ($user && $user['id']) {
                $userId = $user['id'];

                // 3-1. 사용자 계정 비활성화
                $sqlUser = "UPDATE sys_users SET status = 'inactive' WHERE id = :user_id";
                $this->db->execute($sqlUser, [':user_id' => $userId]);

                // 3-2. 사용자의 모든 역할(권한) 제거
                $sqlDeleteRoles = "DELETE FROM sys_user_roles WHERE user_id = :user_id";
                $this->db->execute($sqlDeleteRoles, [':user_id' => $userId]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            // TODO: Log error
            return false;
        }
    }
}
