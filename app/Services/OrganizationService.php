<?php

namespace App\Services;

use App\Repositories\DepartmentRepository;

class OrganizationService
{
    private DepartmentRepository $departmentRepository;
    private AuthService $authService;
    private EmployeeRepository $employeeRepository;

    public function __construct(
        DepartmentRepository $departmentRepository,
        AuthService $authService,
        EmployeeRepository $employeeRepository
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
