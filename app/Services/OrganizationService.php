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
     * @return array
     */
    public function getOrganizationChartData(): array
    {
        $flatData = $this->departmentRepository->findAllWithEmployees();
        if (empty($flatData)) {
            return [];
        }

        // 현재 사용자가 볼 수 있는 부서 ID 목록을 가져옵니다.
        $visibleDeptIds = $this->getVisibleDepartmentIdsForCurrentUser();

        // visibleDeptIds가 null이 아니면(즉, 전체 보기 권한이 없는 경우) 데이터를 필터링합니다.
        if ($visibleDeptIds !== null) {
            $flatData = array_filter($flatData, function ($row) use ($visibleDeptIds) {
                return in_array($row['id'], $visibleDeptIds);
            });
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

    /**
     * @return array
     */
    public function getManagableDepartments(): array
    {
        $permittedDeptIds = $this->_getPermittedDepartmentIds();

        $allDepartments = $this->departmentRepository->getAll();
        $departmentMap = [];
        foreach ($allDepartments as $dept) {
            $departmentMap[$dept->id] = $dept;
        }

        if ($permittedDeptIds === null) {
            $visibleDepartments = $departmentMap;
        } else {
            if (empty($permittedDeptIds)) {
                return [];
            }
            $visibleDepartments = [];
            foreach ($permittedDeptIds as $permittedDeptId) {
                $this->findSubtreeRecursive($permittedDeptId, $departmentMap, $visibleDepartments);
            }
        }

        $result = [];
        foreach ($visibleDepartments as $dept) {
            $deptArray = (array)$dept;
            $deptArray['name'] = $this->getHierarchicalName($dept->id, $departmentMap);
            $result[] = $deptArray;
        }

        return $result;
    }

    /**
     * @param int $deptId
     * @param array $map
     * @param array $visible
     * @return void
     */
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

    /**
     * @param int $deptId
     * @param array $map
     * @param string $separator
     * @return string
     */
    private function getHierarchicalName(int $deptId, array &$map, string $separator = '->'): string
    {
        if (!isset($map[$deptId])) return '';

        $path = [];
        $current = $map[$deptId];
        $depth = 0;
        $maxDepth = 10; // 무한 루프 방지

        while ($current && $depth < $maxDepth) {
            array_unshift($path, $current->name);
            $current = $current->parent_id ? ($map[$current->parent_id] ?? null) : null;
            $depth++;
        }

        if ($depth >= $maxDepth) {
            // 깊은 중첩이나 순환 참조의 경우를 우아하게 처리
            array_unshift($path, '...');
        }

        return implode($separator, $path);
    }

    /**
     * @return array|null
     */
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

    /**
     * @return array|null
     */
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

    /**
     * @return array
     */
    public function getAllDepartments(): array
    {
        return $this->departmentRepository->getAll();
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function createDepartment(array $data): string
    {
        $this->departmentRepository->beginTransaction();
        try {
            $viewerEmployeeIds = $data['viewer_employee_ids'] ?? [];
            unset($data['viewer_employee_ids']);
            $viewerDepartmentIds = $data['viewer_department_ids'] ?? [];
            unset($data['viewer_department_ids']);
            unset($data['path']);

            $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            $data['parent_id'] = $parentId;

            $newDeptId = $this->departmentRepository->create($data);

            $path = $this->calculateDepartmentPath($parentId, $newDeptId);
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

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * @throws \Exception
     */
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

            $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            $data['parent_id'] = $parentId;

            $newPath = $this->calculateDepartmentPath($parentId, $id);
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

    /**
     * @param int|null $parentId
     * @param int $currentId
     * @return string
     * @throws \Exception
     */
    private function calculateDepartmentPath(?int $parentId, int $currentId): string
    {
        if ($parentId === null) {
            return "/{$currentId}/";
        }
        $parent = $this->departmentRepository->findById($parentId);
        if (!$parent) {
            throw new \Exception("ID가 {$parentId}인 상위 부서를 찾을 수 없습니다.");
        }
        return rtrim($parent->path ?? '', '/') . "/{$currentId}/";
    }

    /**
     * @param int $parentId
     * @param string $parentPath
     * @return void
     */
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

    /**
     * @param int $id
     * @return bool
     */
    public function deleteDepartment(int $id): bool
    {
        return $this->departmentRepository->delete($id);
    }

    /**
     * @param int $departmentId
     * @return array
     */
    public function getDepartmentViewPermissionIds(int $departmentId): array
    {
        return $this->departmentRepository->findDepartmentViewPermissionIds($departmentId);
    }

    /**
     * @param int $departmentId
     * @return array
     */
    public function getEligibleViewerEmployees(int $departmentId): array
    {
        $ancestorIds = $this->departmentRepository->findAncestorIds($departmentId);
        if (empty($ancestorIds)) {
            return [];
        }
        return $this->employeeRepository->findByDepartmentIds($ancestorIds);
    }

    /**
     * @return array
     */
    public function getFormattedDepartmentListWithHierarchy(): array
    {
        $allDepartments = $this->departmentRepository->findAllWithViewers();
        if (empty($allDepartments)) {
            return [];
        }

        // 맵 및 트리 구조 빌드
        $departmentMap = [];
        $tree = [];
        foreach ($allDepartments as $dept) {
            $departmentMap[$dept['id']] = $dept;
        }

        foreach ($departmentMap as $id => &$dept) {
            if (!empty($dept['parent_id']) && isset($departmentMap[$dept['parent_id']])) {
                $departmentMap[$dept['parent_id']]['children'][] = &$dept;
            } else {
                $tree[] = &$dept;
            }
        }
        unset($dept); // 참조 해제

        // 계층적 이름으로 트리 평탄화
        $formattedList = [];
        $this->flattenTree($tree, $formattedList);

        return $formattedList;
    }

    /**
     * @param array $nodes
     * @param array $formattedList
     * @param string $parentPath
     * @return void
     */
    private function flattenTree(array $nodes, array &$formattedList, string $parentPath = ''): void
    {
        foreach ($nodes as $node) {
            // 계층적 이름 구성
            $currentPath = $parentPath . (empty($parentPath) ? '' : ' > ') . $node['simple_name'];

            // 수정할 노드의 복사본 생성
            $formattedNode = $node;
            $formattedNode['name'] = $currentPath;

            // 목록에 추가하기 전에 자식 제거
            unset($formattedNode['children']);
            $formattedList[] = $formattedNode;

            // 자식이 있는 경우 재귀
            if (!empty($node['children'])) {
                $this->flattenTree($node['children'], $formattedList, $currentPath);
            }
        }
    }
}
