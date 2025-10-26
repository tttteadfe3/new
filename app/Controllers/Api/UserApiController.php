<?php

namespace App\Controllers\Api;

use App\Services\UserService;
use Exception;
use InvalidArgumentException;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;

class UserApiController extends BaseApiController
{
    private UserService $userService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        UserService $userService
    ) {
        parent::__construct(
            $request,
            $authService,
            $viewDataService,
            $activityLogger,
            $employeeRepository,
            $jsonResponse
        );
        $this->userService = $userService;
    }

    /**
     * 필터를 기반으로 사용자 목록을 가져옵니다.
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
     * 단일 사용자의 세부 정보를 가져옵니다.
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
                $this->apiNotFound('사용자를 찾을 수 없습니다');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 사용자의 역할 및 상태를 업데이트합니다.
     * PUT /api/users/{id}
     */
    public function update(int $id): void
    {
        try {
            $input = $this->getJsonInput();
            if ($this->userService->updateUser($id, $input)) {
                // 권한 캐시 무효화
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
     * 사용자를 직원에게 연결합니다.
     * POST /api/users/{id}/link
     */
    public function linkEmployee(int $id): void
    {
        try {
            $input = $this->getJsonInput();
            $employeeId = (int)($input['employee_id'] ?? 0);

            if (!$employeeId) {
                $this->apiBadRequest('직원 ID가 필요합니다');
            }

            if ($this->userService->linkEmployee($id, $employeeId)) {
                $this->apiSuccess(null, '직원이 성공적으로 연결되었습니다.');
            } else {
                $this->apiError('연결에 실패했습니다. 이미 다른 사용자와 연결된 직원일 수 있습니다.', 'OPERATION_FAILED', 409); // 409 충돌
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 직원에서 사용자 연결을 해제합니다.
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
