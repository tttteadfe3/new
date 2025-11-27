<?php

namespace App\Services;

use App\Core\Database;

/**
 * 부서 계층 구조 관리 유틸리티 서비스
 * 
 * 부서의 상/하위 관계, 관리자 정보 등을 조회하는 순수 유틸리티 클래스
 */
class DepartmentHierarchyService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * 부서의 모든 하위 부서 ID 조회 (재귀적, 자기 자신 포함)
     * 
     * @param int $departmentId 부서 ID
     * @return array 하위 부서 ID 배열 (자기 자신 포함)
     */
    public function getSubtreeIds(int $departmentId): array
    {
        $sql = "
            WITH RECURSIVE DepartmentHierarchy AS (
                SELECT id FROM hr_departments WHERE id = :department_id
                UNION ALL
                SELECT d.id 
                FROM hr_departments d
                INNER JOIN DepartmentHierarchy dh ON d.parent_id = dh.id
            )
            SELECT id FROM DepartmentHierarchy
        ";
        
        $results = $this->db->query($sql, [':department_id' => $departmentId]);
        return array_column($results, 'id');
    }

    /**
     * 부서의 모든 상위 부서 ID 조회 (재귀적, 자기 자신 제외)
     * 
     * @param int $departmentId 부서 ID
     * @return array 상위 부서 ID 배열
     */
    public function getAncestorIds(int $departmentId): array
    {
        $sql = "
            WITH RECURSIVE DepartmentAncestors AS (
                SELECT id, parent_id FROM hr_departments WHERE id = :department_id
                UNION ALL
                SELECT d.id, d.parent_id 
                FROM hr_departments d
                INNER JOIN DepartmentAncestors da ON d.id = da.parent_id
            )
            SELECT id FROM DepartmentAncestors WHERE id != :department_id
        ";
        
        $results = $this->db->query($sql, [':department_id' => $departmentId]);
        return array_column($results, 'id');
    }

    /**
     * 직원이 관리하는 부서 ID 목록 조회
     * 
     * @param int $employeeId 직원 ID
     * @return array 관리 부서 ID 배열
     */
    public function getManagedDepartmentIds(int $employeeId): array
    {
        $sql = "SELECT department_id FROM hr_department_managers WHERE employee_id = :employee_id";
        $results = $this->db->query($sql, [':employee_id' => $employeeId]);
        return array_column($results, 'department_id');
    }

    /**
     * 부서 정보 조회
     * 
     * @param int $departmentId 부서 ID
     * @return array|null 부서 정보 배열 또는 null
     */
    public function getDepartment(int $departmentId): ?array
    {
        $sql = "SELECT * FROM hr_departments WHERE id = :id";
        return $this->db->fetchOne($sql, [':id' => $departmentId]);
    }

    /**
     * 여러 부서의 모든 하위 부서 ID 조회 (통합)
     * 
     * @param array $departmentIds 부서 ID 배열
     * @return array 모든 하위 부서 ID 배열 (중복 제거)
     */
    public function getMultipleSubtreeIds(array $departmentIds): array
    {
        if (empty($departmentIds)) {
            return [];
        }

        $allIds = [];
        foreach ($departmentIds as $deptId) {
            $subtreeIds = $this->getSubtreeIds($deptId);
            $allIds = array_merge($allIds, $subtreeIds);
        }

        return array_values(array_unique($allIds));
    }

    /**
     * 부서의 직속 상위 부서 ID 조회
     * 
     * @param int $departmentId 부서 ID
     * @return int|null 상위 부서 ID 또는 null (최상위 부서인 경우)
     */
    public function getParentId(int $departmentId): ?int
    {
        $dept = $this->getDepartment($departmentId);
        return $dept['parent_id'] ?? null;
    }

    /**
     * 같은 레벨의 형제 부서 ID 조회 (자기 자신 포함)
     * 
     * @param int $departmentId 부서 ID
     * @return array 형제 부서 ID 배열
     */
    public function getSiblingIds(int $departmentId): array
    {
        $dept = $this->getDepartment($departmentId);
        
        if (!$dept) {
            return [];
        }

        $sql = "SELECT id FROM hr_departments WHERE parent_id ";
        
        if ($dept['parent_id']) {
            $sql .= "= :parent_id";
            $results = $this->db->query($sql, [':parent_id' => $dept['parent_id']]);
        } else {
            // 최상위 부서인 경우
            $sql .= "IS NULL";
            $results = $this->db->query($sql, []);
        }

        return array_column($results, 'id');
    }
}
