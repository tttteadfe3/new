<?php

namespace App\Controllers\Api;

use App\Services\UserService;
use Exception;

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
     * Note: This is a non-RESTful pattern. A future refactoring could
     * map these actions to distinct controller methods and routes.
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
                    $this->error('Invalid action specified.', [], 400);
            }
        } catch (Exception $e) {
            $this->error('An unexpected error occurred: ' . $e->getMessage(), [], 500);
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
        $this->success($users);
    }

    private function getUser(): void
    {
        $userId = (int)$this->request->input('user_id', 0);
        if (!$userId) {
            $this->error('User ID is required', [], 400);
            return;
        }
        
        $user = $this->userService->getUser($userId);
        
        if ($user) {
            // The service layer could be enhanced to include this in the getUser response
            $user['assigned_roles'] = $this->userService->getUserRoles($userId);
            $this->success($user);
        } else {
            $this->notFound('User not found');
        }
    }

    private function getAllRoles(): void
    {
        $roles = $this->userService->getAllRoles();
        $this->success($roles);
    }

    private function saveUser(): void
    {
        $input = $this->getJsonInput();
        $userId = (int)($input['user_id'] ?? 0);
        if (!$userId) {
            $this->error('User ID is required', [], 400);
            return;
        }
        
        try {
            if ($this->userService->updateUser($userId, $input)) {
                $this->success(null, '사용자 정보가 저장되었습니다.');
            } else {
                $this->error('저장 중 오류가 발생했습니다.', [], 500);
            }
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage(), [], 400);
        }
    }

    private function getUnlinkedEmployees(): void
    {
        $departmentId = $this->request->input('department_id') ? (int)$this->request->input('department_id') : null;
        $employees = $this->userService->getUnlinkedEmployees($departmentId);
        $this->success($employees);
    }

    private function linkEmployee(): void
    {
        $input = $this->getJsonInput();
        $userId = (int)($input['user_id'] ?? 0);
        $employeeId = (int)($input['employee_id'] ?? 0);
        
        if (!$userId || !$employeeId) {
            $this->error('User ID and Employee ID are required', [], 400);
            return;
        }
        
        if ($this->userService->linkEmployee($userId, $employeeId)) {
            $this->success(null, '직원이 성공적으로 연결되었습니다.');
        } else {
            $this->error('연결에 실패했습니다. 이미 다른 사용자와 연결된 직원일 수 있습니다.', [], 500);
        }
    }

    private function unlinkEmployee(): void
    {
        $input = $this->getJsonInput();
        $userId = (int)($input['user_id'] ?? 0);
        if (!$userId) {
            $this->error('User ID is required', [], 400);
            return;
        }
        
        // This method was missing from the service, assuming it should be added.
        if ($this->userService->unlinkEmployee($userId)) {
            $this->success(null, '직원 연결이 해제되었습니다.');
        } else {
            $this->error('연결 해제에 실패했습니다.', [], 500);
        }
    }

    private function getJsonInput(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}