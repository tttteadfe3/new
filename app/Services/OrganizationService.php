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
        $visibleDepartments = $this->getVisibleDepartmentIdsForCurrentUser();

        // if null, user can see all departments
        if ($visibleDepartments === null) {
            return $this->getFormattedDepartmentListForAll();
        }

        if (empty($visibleDepartments)) {
            return [];
        }

        $allDepartments = $this->departmentRepository->findAllWithManagers();
        $departmentMap = [];
        foreach ($allDepartments as $dept) {
            $departmentMap[$dept->id] = $dept;
        }

        $visibleDepartmentObjects = array_intersect_key($departmentMap, array_flip($visibleDepartments));

        return $this->formatDepartmentList($visibleDepartmentObjects, $departmentMap);
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
     * Get the list of department IDs that the current user is allowed to view.
     * Returns null if the user has global "view all" permissions.
     * Returns an array of department IDs otherwise.
     */
    public function getVisibleDepartmentIdsForCurrentUser(): ?array
    {
        // 1. Check for global permissions first.
        $user = $this->authService->user();
        if (!$user || $this->authService->check('employee.view_all')) { // A new global permission
            return null;
        }

        if (empty($user['employee_id'])) {
            return []; // Not an employee, can't see anyone.
        }

        // 2. Check for department-level "view all" permission.
        $employee = $this->employeeRepository->findById($user['employee_id']);
        if ($employee && !empty($employee['department_id'])) {
            $department = $this->departmentRepository->findById($employee['department_id']);
            if ($department && $department->can_view_all_employees) {
                return null;
            }
        }

        // 3. Determine visibility based on manager status.
        $managedDeptIds = $this->departmentRepository->findManagedDepartmentIdsByEmployee($user['employee_id']);
        $allVisibleIds = [];

        if (empty($managedDeptIds)) {
            // Not a manager, can only see their own department.
            if ($employee && $employee['department_id']) {
                $allVisibleIds[] = $employee['department_id'];
            }
        } else {
            // Is a manager, get all sub-departments of all managed departments.
            foreach ($managedDeptIds as $deptId) {
                $subtreeIds = $this->departmentRepository->findSubtreeIds($deptId);
                $allVisibleIds = array_merge($allVisibleIds, $subtreeIds);
            }
        }

        return array_unique($allVisibleIds);
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
            $managerIds = $data['manager_ids'] ?? [];
            unset($data['manager_ids']); // Ensure it's not passed to the create method

            $newDeptId = $this->departmentRepository->create($data);

            if (!empty($managerIds)) {
                $this->departmentRepository->replaceManagers($newDeptId, $managerIds);
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
            // Expect an array of manager IDs.
            $managerIds = $data['manager_ids'] ?? [];
            unset($data['manager_ids']);

            $result = $this->departmentRepository->update($id, $data);

            // Replace managers. If managerIds is empty, it will remove all managers.
            $this->departmentRepository->replaceManagers($id, $managerIds);

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

    /**
     * Gets all departments and formats their names contextually for display in lists.
     * @return array
     */
    public function getFormattedDepartmentListForAll(): array
    {
        $allDepartments = $this->departmentRepository->findAllWithManagers();
        if (empty($allDepartments)) {
            return [];
        }

        $departmentMap = [];
        foreach ($allDepartments as $dept) {
            $departmentMap[$dept->id] = $dept;
        }

        return $this->formatDepartmentList($allDepartments, $departmentMap);
    }

    private function formatDepartmentList(array $departments, array $departmentMap): array
    {
        // Identify true root departments (those without a parent in the full map)
        $rootDeptIds = [];
        foreach ($departmentMap as $dept) {
            if ($dept->parent_id === null) {
                $rootDeptIds[] = $dept->id;
            }
        }
        $rootDeptIdsSet = array_flip($rootDeptIds);

        // Format names based on the display rule
        $formattedDepartments = [];
        foreach ($departments as $dept) {
            $formattedDept = clone $dept; // Clone to avoid modifying original data
            $parentId = $formattedDept->parent_id;

            // Display simple name if it's a root or a direct child of a root
            if (isset($rootDeptIdsSet[$formattedDept->id]) || ($parentId !== null && isset($rootDeptIdsSet[$parentId]))) {
                // Name is already simple
            }
            // For all other descendants, display as "ParentName(ChildName)"
            else if ($parentId !== null && isset($departmentMap[$parentId])) {
                $parentName = $departmentMap[$parentId]->name;
                // To prevent overly long names like "Grandparent(Parent(Child))",
                // we check if the parent name is already formatted.
                if (strpos($parentName, '(') === false) {
                     $formattedDept->name = "{$parentName} ({$formattedDept->name})";
                }
            }

            $formattedDepartments[] = $formattedDept;
        }

        return $formattedDepartments;
    }
}
