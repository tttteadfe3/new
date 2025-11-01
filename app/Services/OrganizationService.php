<?php

namespace App\Services;

use App\Repositories\DepartmentRepository;
use App\Repositories\EmployeeRepository;

class OrganizationService
{
    private DepartmentRepository $departmentRepository;
    private DataScopeService $dataScopeService;
    private EmployeeRepository $employeeRepository;

    public function __construct(
        DepartmentRepository $departmentRepository,
        DataScopeService $dataScopeService,
        \App\Repositories\EmployeeRepository $employeeRepository
    ) {
        $this->departmentRepository = $departmentRepository;
        $this->dataScopeService = $dataScopeService;
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
            $viewerDepartmentIds = $data['viewer_department_ids'] ?? [];

            $departmentData = [
                'name' => trim($data['name'] ?? ''),
                'parent_id' => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                'path' => null
            ];

            if (empty($departmentData['name'])) {
                 throw new \InvalidArgumentException('Department name is required.');
            }

            $parentId = $departmentData['parent_id'];
            $newDeptId = $this->departmentRepository->create($departmentData);
            $newDeptIdInt = (int)$newDeptId;

            $path = $this->calculateDepartmentPath($parentId, $newDeptIdInt);

            // 경로 업데이트 시 name과 parent_id를 유지해야 함
            $updateData = [
                'name' => $departmentData['name'],
                'parent_id' => $departmentData['parent_id'],
                'path' => $path,
            ];
            $this->departmentRepository->update($newDeptIdInt, $updateData);

            if (!empty($viewerEmployeeIds)) {
                $this->departmentRepository->replaceEmployeeViewPermissions($newDeptIdInt, $viewerEmployeeIds);
            }
            if (!empty($viewerDepartmentIds)) {
                $this->departmentRepository->replaceDepartmentViewPermissions($newDeptIdInt, $viewerDepartmentIds);
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
            if (!$oldDepartment) {
                throw new \Exception("ID가 {$id}인 부서를 찾을 수 없습니다.");
            }
            $oldPath = $oldDepartment->path;

            // name이 전달되지 않은 경우, 기존 이름 사용
            if (!isset($data['name'])) {
                $data['name'] = $oldDepartment->name;
            }

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

            // path를 업데이트할 때 name 필드도 함께 전달하여 null이 되지 않도록 방지
            $updateData = [
                'path' => $newPath,
                'name' => $child['name'], // 기존 이름을 그대로 유지
                'parent_id' => $child['parent_id'] // parent_id도 유지
            ];
            $this->departmentRepository->update($childId, $updateData);

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
            // 'name' 필드는 원본 이름을 유지하고, 계층적 이름은 새 필드에 저장
            $formattedNode['name'] = $node['simple_name'];
            $formattedNode['hierarchical_name'] = $currentPath;

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
