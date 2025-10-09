<?php

namespace App\Controllers\Api;

use App\Services\UserService;
use Exception;
use InvalidArgumentException;

/**
 * Handles all API requests related to user management.
 * This controller now exclusively uses the UserService to interact with the model layer.
 */
class UserApiController extends BaseApiController
{
    private UserService $userService;

    public function __construct()
    {
        parent::__construct();
        $this->userService = new UserService();
    }

    /**
     * Routes user API requests based on an 'action' parameter.
     */
    public function index(): void
    {
        $this->requireAuth('user_admin');
        
        $action = $this->request->input('action');
        
        try {
            switch ($action) {
                case 'list':
                    $this->getUserList();
                    break;
                case 'get_one':
                    $this->getUser();
                    break;
                case 'get_all_roles':
                    $this->getAllRoles();
                    break;
                case 'save':
                    $this->saveUser();
                    break;
                case 'get_unlinked_employees':
                    $this->getUnlinkedEmployees();
                    break;
                case 'link_employee':
                    $this->linkEmployee();
                    break;
                case 'unlink_employee':
                    $this->unlinkEmployee();
                    break;
                default:
                    $this->apiError('Invalid action specified.', 'INVALID_ACTION', 400);
            }
        } catch (Exception $e) {
            $this->apiError('An unexpected error occurred: ' . $e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    private function getUserList(): void
    {
        $filters = [
            'status' => $this->request->input('status'),
            'nickname' => $this->request->input('nickname'),
            'staff' => $this->request->input('staff'),
            'role_id' => $this->request->input('role_id') ? (int)$this->request->input('role_id') : null,
        ];
        
        $users = $this->userService->getAllUsers(array_filter($filters));
        $this->apiSuccess($users);
    }

    private function getUser(): void
    {
        $userId = (int)$this->request->input('user_id', 0);
        if (!$userId) {
            $this->apiError('User ID is required', 'INVALID_INPUT', 400);
            return;
        }
        
        $user = $this->userService->getUser($userId);
        
        if ($user) {
            $user['assigned_roles'] = $this->userService->getUserRoles($userId);
            $this->apiSuccess($user);
        } else {
            $this->apiNotFound('User not found');
        }
    }

    private function getAllRoles(): void
    {
        $roles = $this->userService->getAllRoles();
        $this->apiSuccess($roles);
    }

    private function saveUser(): void
    {
        $input = $this->getJsonInput();
        $userId = (int)($input['user_id'] ?? 0);
        if (!$userId) {
            $this->apiError('User ID is required', 'INVALID_INPUT', 400);
            return;
        }
        
        try {
            if ($this->userService->updateUser($userId, $input)) {
                $timestamp_file = ROOT_PATH . '/storage/permissions_last_updated.txt';
                file_put_contents($timestamp_file, time());
                
                $this->apiSuccess(null, '사용자 정보가 저장되었습니다.');
            } else {
                $this->apiError('저장 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
            }
        } catch (InvalidArgumentException $e) {
            $this->apiError($e->getMessage(), 'INVALID_INPUT', 400);
        }
    }

    private function getUnlinkedEmployees(): void
    {
        $departmentId = $this->request->input('department_id') ? (int)$this->request->input('department_id') : null;
        $employees = $this->userService->getUnlinkedEmployees($departmentId);
        $this->apiSuccess($employees);
    }

    private function linkEmployee(): void
    {
        $input = $this->getJsonInput();
        $userId = (int)($input['user_id'] ?? 0);
        $employeeId = (int)($input['employee_id'] ?? 0);
        
        if (!$userId || !$employeeId) {
            $this->apiError('User ID and Employee ID are required', 'INVALID_INPUT', 400);
            return;
        }
        
        if ($this->userService->linkEmployee($userId, $employeeId)) {
            $this->apiSuccess(null, '직원이 성공적으로 연결되었습니다.');
        } else {
            $this->apiError('연결에 실패했습니다. 이미 다른 사용자와 연결된 직원일 수 있습니다.', 'OPERATION_FAILED', 500);
        }
    }

    private function unlinkEmployee(): void
    {
        $input = $this->getJsonInput();
        $userId = (int)($input['user_id'] ?? 0);
        if (!$userId) {
            $this->apiError('User ID is required', 'INVALID_INPUT', 400);
            return;
        }
        
        if ($this->userService->unlinkEmployee($userId)) {
            $this->apiSuccess(null, '직원 연결이 해제되었습니다.');
        } else {
            $this->apiError('연결 해제에 실패했습니다.', 'OPERATION_FAILED', 500);
        }
    }
}