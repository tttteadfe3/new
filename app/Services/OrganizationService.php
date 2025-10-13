<?php

namespace App\Services;

use App\Repositories\DepartmentRepository;

class OrganizationService
{
    private DepartmentRepository $departmentRepository;

    public function __construct(DepartmentRepository $departmentRepository)
    {
        $this->departmentRepository = $departmentRepository;
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

        // 1단계: 부서를 초기화하고 직원들을 각 부서에 할당합니다.
        foreach ($flatData as $row) {
            $deptId = $row['id'];
            if (!isset($departments[$deptId])) {
                $departments[$deptId] = [
                    'id' => $deptId,
                    'name' => $row['name'],
                    'parent_id' => $row['parent_id'],
                    'manager_id' => $row['manager_id'],
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

        // 2단계: 부모-자식 관계를 설정하여 트리 구조를 만듭니다.
        $tree = [];
        foreach ($departments as $id => &$dept) {
            if ($dept['parent_id'] && isset($departments[$dept['parent_id']])) {
                // 자식 노드를 부모의 'children' 배열에 참조로 추가합니다.
                $departments[$dept['parent_id']]['children'][] = &$dept;
            } else {
                // 부모가 없는 최상위 노드를 트리의 루트로 추가합니다.
                $tree[] = &$dept;
            }
        }
        unset($dept); // 마지막 요소에 대한 참조를 해제합니다.

        return $tree;
    }

    /**
     * 특정 부서의 부서장을 업데이트합니다.
     * @param int $departmentId
     * @param int|null $managerId
     * @return bool
     */
    public function updateDepartmentManager(int $departmentId, ?int $managerId): bool
    {
        return $this->departmentRepository->updateManager($departmentId, $managerId);
    }
}
