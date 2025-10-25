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
        $permittedDeptIds = $this->_getPermittedDepartmentIds();

        if ($permittedDeptIds === null) {
            $allDepartments = $this->departmentRepository->getAll();
            $departmentMap = [];
            foreach ($allDepartments as $dept) {
                $departmentMap[$dept['id']] = (array)$dept;
            }
            $visibleDepartments = $departmentMap;
        } else {
            if (empty($permittedDeptIds)) {
                return [];
            }
            $allDepartments = $this->departmentRepository->getAll();
            $departmentMap = [];
            foreach ($allDepartments as $dept) {
                $departmentMap[$dept['id']] = (array)$dept;
            }
            $visibleDepartments = [];
            foreach ($permittedDeptIds as $permittedDeptId) {
                $this->findSubtreeRecursive($permittedDeptId, $departmentMap, $visibleDepartments);
            }
        }

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
        if (!isset($map[$deptId])) return '';

        $path = [];
        $current = $map[$deptId];
        while ($current) {
            array_unshift($path, $current['name']);
            $current = $current['parent_id'] ? ($map[$current['parent_id']] ?? null) : null;
        }
        return implode($separator, $path);
    }

    private function _getPermittedDepartmentIds(): ?array
    {
        $user = $this->authService->user();
        if (!$user || $this->authService->check('employee.view_all')) {
            return null;
        }

        if (empty($user['employee_id'])) {
            return [];
        }

        $employee = $this->employeeRepository->findById($user['employee_id']);

        $permittedDeptIds = [];
        if ($employee && $employee['department_id']) {
            $permittedDeptIds[] = $employee['department_id'];
        }

        $employeePermitted = $this->departmentRepository->findDepartmentIdsWithEmployeeViewPermission($user['employee_id']);
        $permittedDeptIds = array_merge($permittedDeptIds, $employeePermitted);

        if ($employee && $employee['department_id']) {
            $departmentPermitted = $this->departmentRepository->findVisibleDepartmentIdsForGivenDepartment($employee['department_id']);
            $permittedDeptIds = array_merge($permittedDeptIds, $departmentPermitted);
        }

        return array_unique($permittedDeptIds);
    }

    public function getVisibleDepartmentIdsForCurrentUser(): ?array
    {
        $permittedDeptIds = $this->_getPermittedDepartmentIds();
        if ($permittedDeptIds === null) {
            return null;
        }
        if (empty($permittedDeptIds)) {
            return [];
        }

        $allVisibleIds = [];
        foreach ($permittedDeptIds as $deptId) {
            $subtreeIds = $this->departmentRepository->findSubtreeIds($deptId);
            $allVisibleIds = array_merge($allVisibleIds, $subtreeIds);
        }

        return array_unique($allVisibleIds);
    }

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
            unset($data['path']);

            $newDeptId = $this->departmentRepository->create($data);

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
        $rootDeptIds = [];
        foreach ($departmentMap as $dept) {
            if ($dept['parent_id'] === null) {
                $rootDeptIds[] = $dept['id'];
            }
        }
        $rootDeptIdsSet = array_flip($rootDeptIds);

        $formattedDepartments = [];
        foreach ($departments as $dept) {
            $formattedDept = $dept;
            $parentId = $formattedDept['parent_id'];

            if (isset($rootDeptIdsSet[$formattedDept['id']]) || ($parentId !== null && isset($rootDeptIdsSet[$parentId]))) {
                // Name is already simple
            } else if ($parentId !== null && isset($departmentMap[$parentId])) {
                $parentName = $departmentMap[$parentId]['name'];
                $cleanedChildName = preg_replace('/ \(' . preg_quote($parentName, '/') . '\)$/', '', $formattedDept['name']);
                $simpleParentName = preg_replace('/ \(.*\)$/', '', $parentName);
                $formattedDept['name'] = "{$simpleParentName} ({$cleanedChildName})";
            }
            $formattedDepartments[] = $formattedDept;
        }

        return $formattedDepartments;
    }
}
