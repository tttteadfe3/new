<?php

namespace App\Controllers\Api;

use App\Services\UserService;
use Exception;
use InvalidArgumentException;

class UserApiController extends BaseApiController
{
    private UserService $userService;

    public function __construct()
    {
        parent::__construct();
        $this->userService = new UserService();
    }

    /**
     * Get a list of users based on filters.
     * GET /api/users
     */
    public function index(): void
    {
        try {
            $filters = [
                'status' => $this->request->input('status'),
                'nickname' => $this->request->input('nickname'),
                'staff' => $this->request->input('staff'),
                'role_id' => $this->request->input('role_id') ? (int)$this->request->input('role_id') : null,
            ];

            $users = $this->userService->getAllUsers(array_filter($filters));
            $this->apiSuccess($users);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get a single user's details.
     * GET /api/users/{id}
     */
    public function show(int $id): void
    {
        try {
            $user = $this->userService->getUser($id);
            if ($user) {
                $user['assigned_roles'] = $this->userService->getRoleIdsForUser($id);
                $this->apiSuccess($user);
            } else {
                $this->apiNotFound('User not found');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Update a user's roles and status.
     * PUT /api/users/{id}
     */
    public function update(int $id): void
    {
        try {
            $input = $this->getJsonInput();
            if ($this->userService->updateUser($id, $input)) {
                // Invalidate permissions cache
                $timestamp_file = ROOT_PATH . '/storage/permissions_last_updated.txt';
                file_put_contents($timestamp_file, time());
                
                $this->apiSuccess(null, '사용자 정보가 저장되었습니다.');
            } else {
                $this->apiError('저장 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
            }
        } catch (InvalidArgumentException $e) {
            $this->apiError($e->getMessage(), 'INVALID_INPUT', 400);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Link a user to an employee.
     * POST /api/users/{id}/link
     */
    public function linkEmployee(int $id): void
    {
        try {
            $input = $this->getJsonInput();
            $employeeId = (int)($input['employee_id'] ?? 0);

            if (!$employeeId) {
                $this->apiBadRequest('Employee ID is required');
            }

            if ($this->userService->linkEmployee($id, $employeeId)) {
                $this->apiSuccess(null, '직원이 성공적으로 연결되었습니다.');
            } else {
                $this->apiError('연결에 실패했습니다. 이미 다른 사용자와 연결된 직원일 수 있습니다.', 'OPERATION_FAILED', 409); // 409 Conflict
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Unlink a user from an employee.
     * POST /api/users/{id}/unlink
     */
    public function unlinkEmployee(int $id): void
    {
        try {
            if ($this->userService->unlinkEmployee($id)) {
                $this->apiSuccess(null, '직원 연결이 해제되었습니다.');
            } else {
                $this->apiError('연결 해제에 실패했습니다.', 'OPERATION_FAILED', 500);
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}