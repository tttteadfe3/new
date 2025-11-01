<?php
// app/Services/DataScopeService.php
namespace App\Services;

use App\Repositories\DepartmentRepository;

/**
 * 데이터 조회 범위를 결정하는 중앙 집중형 서비스입니다.
 * 사용자의 권한에 따라 볼 수 있는 부서 ID 목록을 계산하고,
 * 이를 SQL 쿼리에 적용하는 로직을 담당합니다.
 */
class DataScopeService
{
    private AuthService $authService;
    private DepartmentRepository $departmentRepo;
    private \App\Repositories\EmployeeRepository $employeeRepo;

    public function __construct(
        AuthService $authService,
        DepartmentRepository $departmentRepo,
        \App\Repositories\EmployeeRepository $employeeRepo
    ) {
        $this->authService = $authService;
        $this->departmentRepo = $departmentRepo;
        $this->employeeRepo = $employeeRepo;
    }

    /**
     * 현재 로그인한 사용자가 조회할 수 있는 모든 부서 ID의 배열을 반환합니다.
     *
     * - 'employee.manage'와 같은 전체 조회 권한이 있는 경우 모든 부서를 조회할 수 있도록 null을 반환합니다.
     * - 그렇지 않은 경우, 다음을 기반으로 권한이 부여된 부서 목록을 계산합니다:
     *   1. hr_department_managers: 직접 관리하도록 지정된 부서.
     *   2. hr_department_view_permissions: 소속 부서가 조회 권한을 가진 다른 부서.
     *   3. 각 권한에 대해 하위 부서를 포함하여 모든 ID를 재귀적으로 조회합니다.
     *
     * @return int[]|null 조회 가능한 부서 ID 배열, 또는 전체 조회가 가능한 경우 null.
     */
    public function getVisibleDepartmentIdsForCurrentUser(): ?array
    {
        // 1. 전체 조회 권한 확인
        if ($this->authService->check('employee.manage')) {
            return null; // null은 '전체 조회'를 의미
        }

        $user = $this->authService->user();
        if (!$user) {
            return []; // 로그인하지 않은 경우 빈 배열 반환
        }
        $employee = $user['employee'] ?? null;
        if (!$employee) {
            return []; // 직원 정보가 없는 경우 빈 배열 반환
        }

        $permittedDeptIds = [];

        // 2. 개별 직원에게 할당된 부서 조회 권한 (hr_department_managers)
        $managedDeptIds = $this->departmentRepo->findDepartmentIdsWithEmployeeViewPermission($employee['id']);
        foreach ($managedDeptIds as $deptId) {
            $permittedDeptIds = array_merge($permittedDeptIds, $this->departmentRepo->findSubtreeIds($deptId));
        }

        // 3. 사용자의 소속 부서에 부여된 부서 간 조회 권한 (hr_department_view_permissions)
        if (!empty($employee['department_id'])) {
            $viewableDeptIds = $this->departmentRepo->findDepartmentViewPermissionIds($employee['department_id']);
            foreach ($viewableDeptIds as $deptId) {
                $permittedDeptIds = array_merge($permittedDeptIds, $this->departmentRepo->findSubtreeIds($deptId));
            }
        }

        return array_values(array_unique($permittedDeptIds));
    }

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
     *
     * @param int $targetEmployeeId 대상 직원의 ID
     * @return bool 관리할 수 있으면 true, 그렇지 않으면 false
     */
    public function canManageEmployee(int $targetEmployeeId): bool
    {
        // 1. 전체 관리 권한 확인
        if ($this->authService->check('employee.manage')) {
            return true;
        }

        $currentUser = $this->authService->user();
        $currentEmployeeId = $currentUser['employee_id'] ?? null;

        // 2. 자기 자신은 항상 관리 가능
        if ($currentEmployeeId === $targetEmployeeId) {
            return true;
        }

        // 3. 대상 직원의 정보 조회
        $targetEmployee = $this->employeeRepo->findById($targetEmployeeId);
        if (!$targetEmployee || empty($targetEmployee['department_id'])) {
            return false; // 대상 직원이 없거나 부서에 소속되지 않은 경우
        }

        // 4. 현재 사용자가 볼 수 있는 부서 목록 가져오기
        $visibleDeptIds = $this->getVisibleDepartmentIdsForCurrentUser();

        if ($visibleDeptIds === null) {
            return true; // 전체 조회 권한이 있으면 관리 가능
        }

        // 5. 대상 직원의 부서가 보이는 부서 목록에 포함되는지 확인
        return in_array($targetEmployee['department_id'], $visibleDeptIds);
    }
}
