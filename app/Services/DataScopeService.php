<?php
// app/Services/DataScopeService.php
namespace App\Services;

use App\Core\SessionManager;
use App\Core\Database;

/**
 * 데이터 조회 범위를 결정하는 중앙 집중형 서비스입니다.
 * 사용자의 권한에 따라 볼 수 있는 부서 ID 목록을 계산하고,
 * 이를 SQL 쿼리에 적용하는 로직을 담당합니다.
 */
class DataScopeService
{
    private SessionManager $sessionManager;
    private Database $db;

    public function __construct(SessionManager $sessionManager, Database $db)
    {
        $this->sessionManager = $sessionManager;
        $this->db = $db;
    }

    /**
     * 현재 로그인한 사용자가 조회할 수 있는 모든 부서 ID의 배열을 반환합니다.
     * 이 메서드는 다른 리포지토리에 의존하지 않고 직접 DB를 조회하여 순환 의존성을 방지합니다.
     * @return int[]|null 조회 가능한 부서 ID 배열, 또는 전체 조회가 가능한 경우 null.
     */
    public function getVisibleDepartmentIdsForCurrentUser(): ?array
    {
        $user = $this->sessionManager->get('user');
        error_log("DataScopeService: user from session: " . json_encode($user));
        
        if (!$user) {
            error_log("DataScopeService: No user in session, returning empty array");
            return []; // 로그인하지 않은 경우 빈 배열 반환
        }

        $permissions = $user['permissions'] ?? [];
        error_log("DataScopeService: user permissions: " . json_encode($permissions));
        
        if (in_array('employee.manage', $permissions)) {
            error_log("DataScopeService: User has employee.manage permission, returning null (full access)");
            return null; // null은 '전체 조회'를 의미
        }

        $employee = $user['employee'] ?? null;
        if (!$employee) {
            return []; // 직원 정보가 없는 경우 빈 배열 반환
        }

        $permittedDeptIds = [];

        // 2. 개별 직원에게 할당된 부서 조회 권한 (hr_department_managers)
        $managedDeptIds = $this->findDepartmentIdsWithEmployeeViewPermission($employee['id']);
        foreach ($managedDeptIds as $deptId) {
            $permittedDeptIds = array_merge($permittedDeptIds, $this->findSubtreeIds($deptId));
        }

        // 3. 사용자의 소속 부서에 부여된 부서 간 조회 권한 (hr_department_view_permissions)
        if (!empty($employee['department_id'])) {
            $viewableDeptIds = $this->findDepartmentViewPermissionIds($employee['department_id']);
            foreach ($viewableDeptIds as $deptId) {
                $permittedDeptIds = array_merge($permittedDeptIds, $this->findSubtreeIds($deptId));
            }
        }

        return array_values(array_unique($permittedDeptIds));
    }

    // ... (apply...Scope methods remain the same) ...

    /**
     * 직원 테이블에 대한 데이터 스코프를 적용합니다.
     * @param array $queryParts
     * @param string $employeeTableAlias
     * @return array
     */
    public function applyEmployeeScope(array $queryParts, string $employeeTableAlias = 'e'): array
    {
        $visibleDepartmentIds = $this->getVisibleDepartmentIdsForCurrentUser();

        if ($visibleDepartmentIds === null) {
            return $queryParts;
        }

        if (empty($visibleDepartmentIds)) {
            $queryParts['where'][] = "1=0";
        } else {
            $inClause = implode(',', array_map('intval', $visibleDepartmentIds));
            $queryParts['where'][] = "{$employeeTableAlias}.department_id IN ($inClause)";
        }

        return $queryParts;
    }

    /**
     * 차량 테이블에 대한 데이터 스코프를 적용합니다.
     * 운전자는 본인이 운전자로 지정된 차량을 항상 조회할 수 있습니다.
     * @param array $queryParts
     * @param string $vehicleTableAlias
     * @return array
     */
    public function applyVehicleScope(array $queryParts, string $vehicleTableAlias = 'v'): array
    {
        $visibleDepartmentIds = $this->getVisibleDepartmentIdsForCurrentUser();

        if ($visibleDepartmentIds === null) {
            return $queryParts;
        }

        $user = $this->sessionManager->get('user');
        $employeeId = $user['employee_id'] ?? null;

        $conditions = [];

        // 부서 기반 조회 권한
        if (!empty($visibleDepartmentIds)) {
            $inClause = implode(',', array_map('intval', $visibleDepartmentIds));
            $conditions[] = "{$vehicleTableAlias}.department_id IN ($inClause)";
        }

        // 운전자 본인 차량은 항상 조회 가능
        if ($employeeId) {
            $conditions[] = "{$vehicleTableAlias}.driver_employee_id = " . intval($employeeId);
        }

        if (empty($conditions)) {
            $queryParts['where'][] = "1=0";
        } else {
            $queryParts['where'][] = "(" . implode(" OR ", $conditions) . ")";
        }

        return $queryParts;
    }

    /**
     * 부서 테이블 자체에 대한 데이터 스코프를 적용합니다.
     * @param array $queryParts
     * @param string $departmentTableAlias
     * @return array
     */
    public function applyDepartmentScope(array $queryParts, string $departmentTableAlias = 'd'): array
    {
        $visibleDepartmentIds = $this->getVisibleDepartmentIdsForCurrentUser();

        if ($visibleDepartmentIds === null) {
            return $queryParts;
        }

        if (empty($visibleDepartmentIds)) {
            $queryParts['where'][] = "1=0";
        } else {
            $inClause = implode(',', array_map('intval', $visibleDepartmentIds));
            $queryParts['where'][] = "{$departmentTableAlias}.id IN ($inClause)";
        }

        return $queryParts;
    }

    /**
     * 휴일 테이블에 대한 데이터 스코프를 적용합니다. (전체 휴일 포함)
     * @param array $queryParts
     * @param string $holidayTableAlias
     * @return array
     */
    public function applyHolidayScope(array $queryParts, string $holidayTableAlias = 'h'): array
    {
        $visibleDepartmentIds = $this->getVisibleDepartmentIdsForCurrentUser();

        if ($visibleDepartmentIds === null) {
            return $queryParts;
        }

        if (empty($visibleDepartmentIds)) {
            // 조회 가능한 부서가 없으면 전체 휴일만 조회
            $queryParts['where'][] = "{$holidayTableAlias}.department_id IS NULL";
        } else {
            $inClause = implode(',', array_map('intval', $visibleDepartmentIds));
            $queryParts['where'][] = "({$holidayTableAlias}.department_id IS NULL OR {$holidayTableAlias}.department_id IN ($inClause))";
        }

        return $queryParts;
    }

    /**
     * 사용자 테이블 조회 시 데이터 스코프를 적용합니다. (직원 정보가 없는 사용자 포함)
     * @param array $queryParts
     * @param string $userTableAlias
     * @param string $employeeTableAlias
     * @return array
     */
    public function applyUserScope(array $queryParts, string $userTableAlias = 'u', string $employeeTableAlias = 'e'): array
    {
        $visibleDepartmentIds = $this->getVisibleDepartmentIdsForCurrentUser();

        if ($visibleDepartmentIds === null) {
            return $queryParts;
        }

        if (empty($visibleDepartmentIds)) {
            $queryParts['where'][] = "{$userTableAlias}.employee_id IS NULL";
        } else {
            $inClause = implode(',', array_map('intval', $visibleDepartmentIds));
            $queryParts['where'][] = "({$employeeTableAlias}.department_id IN ($inClause) OR {$userTableAlias}.employee_id IS NULL)";
        }

        return $queryParts;
    }


    /**
     * 현재 사용자가 대상 직원을 관리할 수 있는지 확인합니다.
     * @param int $targetEmployeeId 대상 직원의 ID
     * @return bool 관리할 수 있으면 true, 그렇지 않으면 false
     */
    public function canManageEmployee(int $targetEmployeeId): bool
    {
        $user = $this->sessionManager->get('user');
        if (!$user) return false;

        $permissions = $user['permissions'] ?? [];
        if (in_array('employee.manage', $permissions)) {
            return true;
        }

        $currentEmployeeId = $user['employee_id'] ?? null;
        if ($currentEmployeeId === $targetEmployeeId) {
            return true;
        }

        $targetEmployee = $this->findEmployeeById($targetEmployeeId);
        if (!$targetEmployee || empty($targetEmployee['department_id'])) {
            return false;
        }

        $visibleDeptIds = $this->getVisibleDepartmentIdsForCurrentUser();
        if ($visibleDeptIds === null) {
            return true;
        }

        return in_array($targetEmployee['department_id'], $visibleDeptIds);
    }

    // =================================================================
    // Private helper methods to query DB directly, avoiding circular deps
    // =================================================================

    private function findEmployeeById(int $id) {
        $sql = "SELECT e.* FROM hr_employees e WHERE e.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    private function findDepartmentIdsWithEmployeeViewPermission(int $employeeId): array
    {
        $sql = "SELECT department_id FROM hr_department_managers WHERE employee_id = :employee_id";
        $results = $this->db->query($sql, [':employee_id' => $employeeId]);
        return array_column($results, 'department_id');
    }

    private function findDepartmentViewPermissionIds(int $departmentId): array
    {
        $sql = "SELECT permitted_department_id FROM hr_department_view_permissions WHERE department_id = :department_id";
        $results = $this->db->query($sql, [':department_id' => $departmentId]);
        return array_column($results, 'permitted_department_id');
    }

    private function findSubtreeIds(int $departmentId): array
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
}
