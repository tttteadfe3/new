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
                    'viewer_employee_names' => $row['viewer_employee_names'],
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

        $permittedDeptIds = $this->departmentRepository->findDepartmentIdsWithEmployeeViewPermission($user['employee_id']);
        if (empty($permittedDeptIds)) {
            // If not a viewer of any department, maybe just show their own?
            $employee = $this->employeeRepository->findById($user['employee_id']);
            return $employee ? [$this->departmentRepository->findById($employee['department_id'])] : [];
        }

        $allDepartments = $this->departmentRepository->getAll();
        $departmentMap = [];
        foreach ($allDepartments as $dept) {
            $departmentMap[$dept['id']] = $dept;
        }

        $visibleDepartments = [];
        foreach ($permittedDeptIds as $permittedDeptId) {
            $this->findSubtreeRecursive($permittedDeptId, $departmentMap, $visibleDepartments);
        }

        // Format names hierarchically
        foreach ($visibleDepartments as &$dept) {
            $dept['name'] = $this->getHierarchicalName($dept['id'], $departmentMap);
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
            if ($child['parent_id'] == $deptId) {
                $this->findSubtreeRecursive($child['id'], $map, $visible);
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
            array_unshift($path, $current['name']);
            $current = $current['parent_id'] ? ($map[$current['parent_id']] ?? null) : null;
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

        // 3. Determine visibility based on view permissions.
        $permittedDeptIds = $this->departmentRepository->findDepartmentIdsWithEmployeeViewPermission($user['employee_id']);
        $allVisibleIds = [];

        if (empty($permittedDeptIds)) {
            // Not a viewer, can only see their own department.
            if ($employee && $employee['department_id']) {
                $allVisibleIds[] = $employee['department_id'];
            }
        } else {
            // Is a viewer, get all sub-departments of all permitted departments.
            foreach ($permittedDeptIds as $deptId) {
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
            $viewerEmployeeIds = $data['viewer_employee_ids'] ?? [];
            unset($data['viewer_employee_ids']);
            $viewerDepartmentIds = $data['viewer_department_ids'] ?? [];
            unset($data['viewer_department_ids']);

            // Path is intentionally omitted here
            unset($data['path']);

            $newDeptId = $this->departmentRepository->create($data);

            // Now calculate and update the path
            $path = $this->calculateDepartmentPath($data['parent_id'] ?? null, $newDeptId);
            $this->departmentRepository->update($newDeptId, ['path' => $path]);

            if (!empty($viewerEmployeeIds)) {
                $this->departmentRepository->replaceEmployeeViewPermissions($newDeptId, $viewerEmployeeIds);
            }
            if (!empty($viewerDepartmentIds)) {
                $this->departmentRepository->replaceDepartmentViewPermissions($newDeptId, $viewerDepartmentIds);
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
            $viewerEmployeeIds = $data['viewer_employee_ids'] ?? [];
            unset($data['viewer_employee_ids']);
            $viewerDepartmentIds = $data['viewer_department_ids'] ?? [];
            unset($data['viewer_department_ids']);

            $oldDepartment = $this->departmentRepository->findById($id);
            $oldPath = $oldDepartment->path;

            $newPath = $this->calculateDepartmentPath($data['parent_id'] ?? null, $id);
            $data['path'] = $newPath;

            $result = $this->departmentRepository->update($id, $data);

            if ($oldPath !== $newPath) {
                $this->updateSubtreePaths($id, $newPath);
            }

            $this->departmentRepository->replaceEmployeeViewPermissions($id, $viewerEmployeeIds);
            $this->departmentRepository->replaceDepartmentViewPermissions($id, $viewerDepartmentIds);

            $this->departmentRepository->commit();
            return $result;
        } catch (\Exception $e) {
            $this->departmentRepository->rollBack();
            throw $e;
        }
    }

    private function calculateDepartmentPath(?int $parentId, int $currentId): string
    {
        if ($parentId === null) {
            return "/{$currentId}/";
        }
        $parent = $this->departmentRepository->findById($parentId);
        if (!$parent) {
            throw new \Exception("Parent department with ID {$parentId} not found.");
        }
        return rtrim($parent->path, '/') . "/{$currentId}/";
    }

    private function updateSubtreePaths(int $parentId, string $parentPath)
    {
        $children = $this->departmentRepository->findByParentId($parentId);
        foreach ($children as $child) {
            $childId = $child['id'];
            $newPath = rtrim($parentPath, '/') . "/{$childId}/";
            $this->departmentRepository->update($childId, ['path' => $newPath]);
            $this->updateSubtreePaths($childId, $newPath);
        }
    }

    public function deleteDepartment(int $id): bool
    {
        return $this->departmentRepository->delete($id);
    }

    public function getDepartmentViewPermissionIds(int $departmentId): array
    {
        return $this->departmentRepository->findDepartmentViewPermissionIds($departmentId);
    }

    /**
     * Gets all departments and formats their names contextually for display in lists.
     * @return array
     */
    public function getFormattedDepartmentListForAll(): array
    {
        $allDepartments = $this->departmentRepository->findAllWithViewers();
        if (empty($allDepartments)) {
            return [];
        }

        $departmentMap = [];
        foreach ($allDepartments as $dept) {
            $departmentMap[$dept['id']] = $dept;
        }

        return $this->formatDepartmentList($allDepartments, $departmentMap);
    }

    private function formatDepartmentList(array $departments, array $departmentMap): array
    {
        // Identify true root departments (those without a parent in the full map)
        $rootDeptIds = [];
        foreach ($departmentMap as $dept) {
            if ($dept['parent_id'] === null) {
                $rootDeptIds[] = $dept['id'];
            }
        }
        $rootDeptIdsSet = array_flip($rootDeptIds);

        // Format names based on the display rule
        $formattedDepartments = [];
        foreach ($departments as $dept) {
            $formattedDept = $dept; // Work with a copy of the array
            $parentId = $formattedDept['parent_id'];

            // Display simple name if it's a root or a direct child of a root
            if (isset($rootDeptIdsSet[$formattedDept['id']]) || ($parentId !== null && isset($rootDeptIdsSet[$parentId]))) {
                // Name is already simple
            }
            // For all other descendants, display as "ParentName(ChildName)"
            else if ($parentId !== null && isset($departmentMap[$parentId])) {
                $parentName = $departmentMap[$parentId]['name'];

                // Clean the child name by removing a "(Parent)" suffix if it already exists in the database.
                $cleanedChildName = preg_replace('/ \(' . preg_quote($parentName, '/') . '\)$/', '', $formattedDept['name']);

                // Check if the parent name itself is already formatted. If so, use the simple name.
                $simpleParentName = preg_replace('/ \(.*\)$/', '', $parentName);

                $formattedDept['name'] = "{$simpleParentName} ({$cleanedChildName})";
            }

            $formattedDepartments[] = $formattedDept;
        }

        return $formattedDepartments;
    }
}
