<?php

namespace App\Controllers\Api;

use App\Repositories\RoleRepository;

class RoleApiController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handle all role API requests based on action parameter
     */
    public function index(): void
    {
        $this->requireAuth('role_admin');
        
        $action = $this->getAction();
        
        try {
            switch ($action) {
                case 'list_roles':
                    $this->listRoles();
                    break;
                case 'get_details':
                    $this->getRoleDetails();
                    break;
                case 'save_permissions':
                    $this->savePermissions();
                    break;
                case 'save_role':
                    $this->saveRole();
                    break;
                case 'delete_role':
                    $this->deleteRole();
                    break;
                default:
                    $this->apiBadRequest('Invalid action');
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get all roles with user count
     */
    private function listRoles(): void
    {
        $roles = RoleRepository::getAllRolesWithUserCount();
        $this->apiSuccess($roles);
    }

    /**
     * Get role details including permissions and assigned users
     */
    private function getRoleDetails(): void
    {
        $roleId = (int)($_GET['role_id'] ?? 0);
        
        if (!$roleId) {
            $this->apiBadRequest('Role ID is required');
            return;
        }
        
        $role = RoleRepository::findById($roleId);
        $allPermissions = RoleRepository::getAllPermissions();
        $assignedPermissions = array_column(RoleRepository::getRolePermissions($roleId), 'id');
        $assignedUsers = RoleRepository::getUsersAssignedToRole($roleId);
        
        $this->apiSuccess([
            'role' => $role,
            'all_permissions' => $allPermissions,
            'assigned_permission_ids' => $assignedPermissions,
            'assigned_users' => $assignedUsers
        ]);
    }

    /**
     * Save role permissions
     */
    private function savePermissions(): void
    {
        $input = $this->getJsonInput();
        $roleId = (int)($input['role_id'] ?? 0);
        $permissionIds = $input['permissions'] ?? [];
        
        if (!$roleId) {
            $this->apiBadRequest('Role ID is required');
            return;
        }
        
        RoleRepository::updateRolePermissions($roleId, $permissionIds);
        
        // 글로벌 권한 변경 타임스탬프 업데이트
        $timestamp_file = ROOT_PATH . '/storage/permissions_last_updated.txt';
        file_put_contents($timestamp_file, time());
        
        $this->apiSuccess(null, '권한이 저장되었습니다.');
    }

    /**
     * Save role (create or update)
     */
    private function saveRole(): void
    {
        $input = $this->getJsonInput();
        $roleId = (int)($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        
        if (empty($name)) {
            $this->apiBadRequest('역할 이름은 필수입니다.');
            return;
        }
        
        if ($roleId > 0) { // 수정
            RoleRepository::update($roleId, $name, $description);
            $this->apiSuccess(null, '역할 정보가 수정되었습니다.');
        } else { // 생성
            $newRoleId = RoleRepository::create($name, $description);
            $this->apiSuccess(['new_role_id' => $newRoleId], '새 역할이 생성되었습니다.');
        }
    }

    /**
     * Delete a role
     */
    private function deleteRole(): void
    {
        $input = $this->getJsonInput();
        $roleId = (int)($input['id'] ?? 0);
        
        if (!$roleId) {
            $this->apiBadRequest('Role ID is required');
            return;
        }
        
        if (RoleRepository::delete($roleId)) {
            $this->apiSuccess(null, '역할이 삭제되었습니다.');
        } else {
            $this->apiError('사용자가 할당된 역할은 삭제할 수 없습니다.');
        }
    }
}