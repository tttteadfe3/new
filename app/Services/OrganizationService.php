<?php

namespace App\Services;

use App\Repositories\DepartmentRepository;
use App\Repositories\EmployeeRepository;

class OrganizationService
{
    private DepartmentRepository $departmentRepository;
    private AuthService $authService;
    private EmployeeRepository $employeeRepository;

    public function __construct(
        DepartmentRepository $departmentRepository,
        AuthService $authService,
        \App\Repositories\EmployeeRepository $employeeRepository
    ) {
        $this->departmentRepository = $departmentRepository;
        $this->authService = $authService;
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * 조직도 데이터를 계층 구조로 가공하여 반환합니다.
     * @return array
     */
    public function getOrganizationChartData(): array
    {
        $flatData = $this->departmentRepository->findAllWithEmployees();

        if (empty($flatData)) {
            return [];
        }

        $departments = [];
        foreach ($flatData as $row) {
            $deptId = $row['id'];
            if (!isset($departments[$deptId])) {
                $departments[$deptId] = [
                    'id' => $deptId,
                    'name' => $row['name'],
                    'parent_id' => $row['parent_id'],
                    'manager_name' => $row['manager_name'],
                    'children' => [],
                    'employees' => []
                ];
            }
            if ($row['employee_id']) {
                 $departments[$deptId]['employees'][] = [
                    'id' => $row['employee_id'],
                    'name' => $row['employee_name'],
                    'position' => $row['position_name']
                ];
            }
        }

        $tree = [];
        foreach ($departments as $id => &$dept) {
            if ($dept['parent_id'] && isset($departments[$dept['parent_id']])) {
                $departments[$dept['parent_id']]['children'][] = &$dept;
            } else {
                $tree[] = &$dept;
            }
        }
        unset($dept);

        return $tree;
    }

    public function getManagableDepartments(): array
    {
        $user = $this->authService->user();
        if (!$user || !$user['employee_id']) {
            return $this->departmentRepository->getAll();
        }

        // Check if user has global permission to see all departments
        if ($this->authService->check('department.manage_all')) { // Assuming a permission key
            return $this->departmentRepository->getAll();
        }

        $managedDeptIds = $this->departmentRepository->findManagedDepartmentIdsByEmployee($user['employee_id']);
        if (empty($managedDeptIds)) {
            // If not a manager of any department, maybe just show their own?
            $employee = $this->employeeRepository->findById($user['employee_id']);
            return $employee ? [$this->departmentRepository->findById($employee['department_id'])] : [];
        }

        $allDepartments = $this->departmentRepository->getAll();
        $departmentMap = [];
        foreach ($allDepartments as $dept) {
            $departmentMap[$dept->id] = $dept;
        }

        $visibleDepartments = [];
        foreach ($managedDeptIds as $managedDeptId) {
            $this->findSubtreeRecursive($managedDeptId, $departmentMap, $visibleDepartments);
        }

        // Format names hierarchically
        foreach ($visibleDepartments as &$dept) {
            $dept->name = $this->getHierarchicalName($dept->id, $departmentMap);
        }

        return array_values($visibleDepartments);
    }

    private function findSubtreeRecursive(int $deptId, array &$map, array &$visible)
    {
        if (!isset($map[$deptId]) || isset($visible[$deptId])) {
            return;
        }
        $visible[$deptId] = $map[$deptId];

        foreach ($map as $child) {
            if ($child->parent_id == $deptId) {
                $this->findSubtreeRecursive($child->id, $map, $visible);
            }
        }
    }

    private function getHierarchicalName(int $deptId, array &$map, string $separator = '->'): string
    {
        if (!isset($map[$deptId])) {
            return '';
        }

        $path = [];
        $current = $map[$deptId];
        while ($current) {
            array_unshift($path, $current->name);
            $current = $current->parent_id ? ($map[$current->parent_id] ?? null) : null;
        }
        return implode($separator, $path);
    }

    /**
     * 현재 사용자가 볼 수 있는 부서 ID 목록을 가져옵니다.
     * 이 메소드는 데이터 조회 권한의 중심 역할을 합니다.
     *
     * @return array|null 사용자가 모든 부서를 볼 수 있는 전역 권한을 가질 경우 null을 반환하고,
     *                    그렇지 않으면 볼 수 있는 부서 ID의 배열을 반환합니다.
     */
    public function getVisibleDepartmentIdsForCurrentUser(): ?array
    {
        $user = $this->authService->user();
        if (!$user) {
            return []; // 로그인하지 않은 사용자
        }

        // 규칙 1: 전역 "모든 부서 보기" 권한 확인
        if ($this->authService->check('department.view_all')) {
            return null; // null은 '제한 없음'을 의미
        }

        if (empty($user['employee_id'])) {
            return []; // 직원이 아닌 사용자는 어떤 부서도 볼 수 없음
        }

        $employee = $this->employeeRepository->findById($user['employee_id']);
        if (!$employee || empty($employee['department_id'])) {
            return []; // 소속 부서가 없는 직원
        }

        // 규칙 1.5: 소속 부서의 "전체 보기" 권한 확인 (기존 기능 복원)
        $department = $this->departmentRepository->findById($employee['department_id']);
        if ($department && $department->can_view_all_employees) {
            return null; // null은 '제한 없음'을 의미
        }

        // 규칙 2: 부서 관리자 권한 확인
        $managedDeptIds = $this->departmentRepository->findManagedDepartmentIdsByEmployee($user['employee_id']);

        if (!empty($managedDeptIds)) {
            // 관리자일 경우, 관리하는 모든 부서와 그 하위 부서들을 모두 가져옵니다.
            $allVisibleIds = [];
            foreach ($managedDeptIds as $deptId) {
                $subtreeIds = $this->departmentRepository->findSubtreeIds($deptId);
                $allVisibleIds = array_merge($allVisibleIds, $subtreeIds);
            }
            // 자신의 부서도 추가 (관리부서와 소속부서가 다를 경우를 대비)
            $allVisibleIds[] = $employee['department_id'];
            return array_unique($allVisibleIds);
        }

        // 규칙 3: 일반 사용자일 경우
        // 자신의 소속 부서만 볼 수 있습니다.
        return [$employee['department_id']];
    }

    /**
     * 현재 사용자가 볼 수 있는 부서의 전체 객체 목록을 반환합니다.
     * 내부적으로 getVisibleDepartmentIdsForCurrentUser를 사용하여 권한을 확인합니다.
     * @return Department[]
     */
    public function getVisibleDepartmentsForCurrentUser(): array
    {
        $visibleIds = $this->getVisibleDepartmentIdsForCurrentUser();

        $allDepartments = $this->departmentRepository->getAll();

        if ($visibleIds === null) {
            return $allDepartments; // 모든 부서 반환
        }

        if (empty($visibleIds)) {
            return [];
        }

        // 볼 수 있는 ID에 해당하는 부서 객체만 필터링하여 반환
        return array_filter($allDepartments, function ($department) use ($visibleIds) {
            return in_array($department->id, $visibleIds);
        });
    }

    // ===================================================
    // CRUD Methods restored for OrganizationApiController
    // ===================================================

    public function getAllDepartments(): array
    {
        return $this->departmentRepository->getAll();
    }

    public function createDepartment(array $data): string
    {
        $this->departmentRepository->beginTransaction();
        try {
            $managerId = $data['manager_id'] ?? null;
            unset($data['manager_id']); // Ensure it's not passed to the create method

            $newDeptId = $this->departmentRepository->create($data);

            if ($managerId) {
                $this->departmentRepository->replaceManagers($newDeptId, [$managerId]);
            }

            $this->departmentRepository->commit();
            return $newDeptId;
        } catch (\Exception $e) {
            $this->departmentRepository->rollBack();
            throw $e;
        }
    }

    public function updateDepartment(int $id, array $data): bool
    {
        $this->departmentRepository->beginTransaction();
        try {
            $managerId = $data['manager_id'] ?? null;
            unset($data['manager_id']);

            $result = $this->departmentRepository->update($id, $data);

            // Replace managers - if managerId is empty/null, it will remove all managers
            $this->departmentRepository->replaceManagers($id, $managerId ? [$managerId] : []);

            $this->departmentRepository->commit();
            return $result;
        } catch (\Exception $e) {
            $this->departmentRepository->rollBack();
            throw $e;
        }
    }

    public function deleteDepartment(int $id): bool
    {
        return $this->departmentRepository->delete($id);
    }
}
