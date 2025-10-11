<?php

namespace App\Controllers\Api;

use App\Repositories\RoleRepository;
use Exception;

class RoleApiController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all roles with user count.
     * GET /api/roles
     */
    public function index(): void
    {
        try {
            $roles = $this->roleRepository->getAllRolesWithUserCount();
            $this->apiSuccess($roles);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get role details including permissions and assigned users.
     * GET /api/roles/{id}
     */
    public function show(int $id): void
    {
        try {
            $role = $this->roleRepository->findById($id);
            if (!$role) {
                $this->apiNotFound('Role not found');
            }

            $allPermissions = $this->roleRepository->getAllPermissions();
            $assignedPermissions = array_column($this->roleRepository->getRolePermissions($id), 'id');
            $assignedUsers = $this->roleRepository->getUsersAssignedToRole($id);

            $this->apiSuccess([
                'role' => $role,
                'all_permissions' => $allPermissions,
                'assigned_permission_ids' => $assignedPermissions,
                'assigned_users' => $assignedUsers
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Create a new role.
     * POST /api/roles
     */
    public function store(): void
    {
        try {
            $input = $this->getJsonInput();
            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');

            if (empty($name)) {
                $this->apiBadRequest('역할 이름은 필수입니다.');
            }

            $newRoleId = $this->roleRepository->create($name, $description);
            $this->apiSuccess(['id' => $newRoleId], '새 역할이 생성되었습니다.');

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Update an existing role.
     * PUT /api/roles/{id}
     */
    public function update(int $id): void
    {
        try {
            $input = $this->getJsonInput();
            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');

            if (empty($name)) {
                $this->apiBadRequest('역할 이름은 필수입니다.');
            }

            $this->roleRepository->update($id, $name, $description);
            $this->apiSuccess(null, '역할 정보가 수정되었습니다.');

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Delete a role.
     * DELETE /api/roles/{id}
     */
    public function destroy(int $id): void
    {
        try {
            if ($this->roleRepository->delete($id)) {
                $this->apiSuccess(null, '역할이 삭제되었습니다.');
            } else {
                $this->apiError('사용자가 할당된 역할은 삭제할 수 없습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Update permissions for a role.
     * PUT /api/roles/{id}/permissions
     */
    public function updatePermissions(int $id): void
    {
        try {
            $input = $this->getJsonInput();
            $permissionIds = $input['permissions'] ?? [];

            $this->roleRepository->updateRolePermissions($id, $permissionIds);

            // Invalidate permissions cache
            $timestamp_file = ROOT_PATH . '/storage/permissions_last_updated.txt';
            file_put_contents($timestamp_file, time());

            $this->apiSuccess(null, '권한이 저장되었습니다.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}