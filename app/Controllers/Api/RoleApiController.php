<?php

namespace App\Controllers\Api;

use App\Repositories\RoleRepository;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;

class RoleApiController extends BaseApiController
{
    private RoleRepository $roleRepository;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        RoleRepository $roleRepository
    ) {
        parent::__construct(
            $request,
            $authService,
            $viewDataService,
            $activityLogger,
            $employeeRepository,
            $jsonResponse
        );
        $this->roleRepository = $roleRepository;
    }

    /**
     * 사용자 수를 포함한 모든 역할을 가져옵니다.
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
     * 권한 및 할당된 사용자를 포함한 역할 세부 정보를 가져옵니다.
     * GET /api/roles/{id}
     */
    public function show(int $id): void
    {
        try {
            $role = $this->roleRepository->findById($id);
            if (!$role) {
                $this->apiNotFound('역할을 찾을 수 없습니다');
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
     * 새 역할을 만듭니다.
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
     * 기존 역할을 업데이트합니다.
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
     * 역할을 삭제합니다.
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
     * 역할에 대한 권한을 업데이트합니다.
     * PUT /api/roles/{id}/permissions
     */
    public function updatePermissions(int $id): void
    {
        try {
            $input = $this->getJsonInput();
            $permissionIds = $input['permissions'] ?? [];

            $this->roleRepository->updateRolePermissions($id, $permissionIds);

            // 권한 캐시 무효화
            $timestamp_file = ROOT_PATH . '/storage/permissions_last_updated.txt';
            file_put_contents($timestamp_file, time());

            $this->apiSuccess(null, '권한이 저장되었습니다.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
