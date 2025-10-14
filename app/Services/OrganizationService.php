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
     * 특정 부서의 부서장을 업데이트합니다.
     */
    public function updateDepartmentManager(int $departmentId, ?int $managerId): bool
    {
        return $this->departmentRepository->updateManager($departmentId, $managerId);
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
        return $this->departmentRepository->create($data);
    }

    public function updateDepartment(int $id, array $data): bool
    {
        return $this->departmentRepository->update($id, $data);
    }

    public function deleteDepartment(int $id): bool
    {
        return $this->departmentRepository->delete($id);
    }
}
