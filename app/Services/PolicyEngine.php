<?php

namespace App\Services;

use App\Core\Database;
use App\Core\SessionManager;

/**
 * Policy-Based Access Control (PBAC) 엔진
 * 
 * 리소스 타입별 권한 정책을 평가하여 사용자가 조회 가능한 데이터 범위를 결정합니다.
 */
class PolicyEngine
{
    private Database $db;
    private SessionManager $sessionManager;
    private DepartmentHierarchyService $deptService;

    public function __construct(
        Database $db,
        SessionManager $sessionManager,
        DepartmentHierarchyService $deptService
    ) {
        $this->db = $db;
        $this->sessionManager = $sessionManager;
        $this->deptService = $deptService;
    }

    /**
     * 사용자가 특정 리소스 타입에 대해 조회 가능한 부서 ID 반환
     * 
     * @param int $userId 사용자 ID
     * @param string $resourceType 리소스 타입 (employee, vehicle, leave, supply 등)
     * @param string $action 액션 (view, create, update, delete 등)
     * @return array|null 조회 가능한 부서 ID 배열, null이면 전체 조회 가능
     */
    public function getScopeIds(int $userId, string $resourceType, string $action = 'view'): ?array
    {
        $user = $this->getUserWithEmployee($userId);
        
        if (!$user) {
            return [];
        }

        // 전체 관리 권한 체크 (employee.manage 등)
        if ($this->hasGlobalPermission($user, $resourceType, $action)) {
            return null;  // 전체 조회 가능
        }

        // 사용자에게 적용되는 정책 목록 조회
        $policies = $this->getPoliciesForUser($userId, $resourceType, $action);
        
        if (empty($policies)) {
            return [];  // 적용 가능한 정책 없음
        }

        $allDeptIds = [];
        
        foreach ($policies as $policy) {
            $deptIds = $this->evaluatePolicy($policy, $user);
            $allDeptIds = array_merge($allDeptIds, $deptIds);
        }

        return empty($allDeptIds) ? [] : array_values(array_unique($allDeptIds));
    }

    /**
     * 사용자 정보 조회 (직원 정보 포함)
     */
    private function getUserWithEmployee(int $userId): ?array
    {
        $user = $this->sessionManager->get('user');
        
        if (!$user || $user['id'] !== $userId) {
            // 세션에 없으면 DB에서 조회
            $sql = "SELECT u.*, e.* 
                    FROM sys_users u
                    LEFT JOIN hr_employees e ON u.employee_id = e.id
                    WHERE u.id = :user_id";
            $user = $this->db->fetchOne($sql, [':user_id' => $userId]);
        }

        return $user;
    }

    /**
     * 전체 관리 권한 체크
     */
    private function hasGlobalPermission(array $user, string $resourceType, string $action): bool
    {
        $permissions = $user['permissions'] ?? [];
        
        // employee.manage, leave.manage 등의 권한이 있으면 전체 조회 가능
        $globalPermissionKey = "{$resourceType}.manage";
        
        return in_array($globalPermissionKey, $permissions);
    }

    /**
     * 사용자에게 적용되는 정책 목록 조회
     */
    private function getPoliciesForUser(int $userId, string $resourceType, string $action): array
    {
        $sql = "
            SELECT DISTINCT 
                p.id,
                p.name,
                p.scope_type,
                p.scope_config,
                p.priority
            FROM permission_policies p
            WHERE p.is_active = 1
              AND p.resource_type_id = (SELECT id FROM permission_resource_types WHERE name = :resource_type)
              AND p.action_id = (SELECT id FROM permission_actions WHERE name = :action)
              AND (
                -- 역할을 통한 정책
                EXISTS (
                    SELECT 1 FROM role_policies rp
                    JOIN sys_user_roles ur ON rp.role_id = ur.role_id
                    WHERE ur.user_id = :user_id_1 AND rp.policy_id = p.id
                )
                OR
                -- 직접 부여된 정책 (만료되지 않은 것만)
                EXISTS (
                    SELECT 1 FROM user_policies up
                    WHERE up.user_id = :user_id_2 
                      AND up.policy_id = p.id
                      AND (up.expires_at IS NULL OR up.expires_at > NOW())
                )
              )
            ORDER BY p.priority DESC, p.id ASC
        ";

        return $this->db->query($sql, [
            ':user_id_1' => $userId,
            ':user_id_2' => $userId,
            ':resource_type' => $resourceType,
            ':action' => $action
        ]);
    }

    /**
     * 정책 평가 - 정책에 따른 부서 ID 목록 반환
     */
    private function evaluatePolicy(array $policy, array $user): array
    {
        $employee = $user['employee'] ?? null;
        
        if (!$employee || !$employee['department_id']) {
            return [];
        }

        switch ($policy['scope_type']) {
            case 'own':
                // 본인 부서만
                return [$employee['department_id']];

            case 'department':
                // 소속 부서만
                return [$employee['department_id']];
            
            case 'global':
                // 전체 접근 (null 반환)
                return []; // Will be handled specially by caller

            case 'managed_departments':
                // 관리하는 부서 + 하위 부서
                return $this->evaluateManagedDepartments($employee['id']);

            case 'parent_department_tree':
                // 상위 부서의 전체 트리 (같은 과 전체)
                return $this->evaluateParentDepartmentTree($employee['department_id'], $policy);

            case 'custom':
                // 커스텀 스코프
                return $this->evaluateCustomScope($policy['scope_config'], $employee);

            default:
                return [];
        }
    }

    /**
     * 관리 부서 스코프 평가
     */
    private function evaluateManagedDepartments(int $employeeId): array
    {
        $managedDeptIds = $this->deptService->getManagedDepartmentIds($employeeId);
        
        if (empty($managedDeptIds)) {
            return [];
        }

        // 관리 부서 + 각 부서의 하위 부서 모두 포함
        return $this->deptService->getMultipleSubtreeIds($managedDeptIds);
    }

    /**
     * 상위 부서 트리 스코프 평가 (같은 과 전체 연차 조회)
     */
    private function evaluateParentDepartmentTree(int $departmentId, array $policy): array
    {
        $dept = $this->deptService->getDepartment($departmentId);
        
        if (!$dept || !$dept['parent_id']) {
            // 상위 부서가 없으면 본인 부서만
            return [$departmentId];
        }

        // 상위 부서의 모든 하위 부서 반환 (자기 자신 포함)
        return $this->deptService->getSubtreeIds($dept['parent_id']);
    }

    /**
     * 커스텀 스코프 평가
     */
    private function evaluateCustomScope(?string $scopeConfigJson, array $employee): array
    {
        if (!$scopeConfigJson) {
            return [];
        }

        $config = json_decode($scopeConfigJson, true);
        if (!$config) {
            return [];
        }

        $scope = $config['scope'] ?? '';
        $departmentId = $employee['department_id'];

        switch ($scope) {
            case 'parent_department_tree':
                // 상위 부서의 전체 트리
                $dept = $this->deptService->getDepartment($departmentId);
                if ($dept && $dept['parent_id']) {
                    return $this->deptService->getSubtreeIds($dept['parent_id']);
                }
                return [$departmentId];

            case 'peer_departments':
                // 형제 부서들 (같은 상위 부서를 가진 부서들)
                return $this->deptService->getSiblingIds($departmentId);

            case 'ancestors':
                // 상위 부서들만
                return $this->deptService->getAncestorIds($departmentId);

            default:
                return [];
        }
    }

    /**
     * 특정 리소스에 대한 권한 체크 (boolean)
     * 
     * @param int $userId 사용자 ID
     * @param string $resourceType 리소스 타입
     * @param string $action 액션
     * @param int|null $resourceDepartmentId 리소스의 부서 ID (체크용)
     * @return bool 권한 있으면 true
     */
    public function can(int $userId, string $resourceType, string $action, ?int $resourceDepartmentId = null): bool
    {
        $scopeIds = $this->getScopeIds($userId, $resourceType, $action);

        // 전체 권한
        if ($scopeIds === null) {
            return true;
        }

        // 부서 ID 체크가 필요한 경우
        if ($resourceDepartmentId !== null) {
            return in_array($resourceDepartmentId, $scopeIds);
        }

        // 최소한 하나의 부서라도 있으면 권한 있음
        return !empty($scopeIds);
    }
}
