<?php

namespace App\Controllers\Api;

use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use App\Services\UserManager;

class UserApiController extends BaseApiController
{
    private UserManager $userManager;

    public function __construct()
    {
        parent::__construct();
        $this->userManager = new UserManager();
    }

    /**
     * Handle all user API requests based on action parameter
     */
    public function index(): void
    {
        $this->requireAuth('user_admin');
        
        $action = $this->getAction();
        
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
                    $this->apiBadRequest('Invalid action');
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get filtered user list
     */
    private function getUserList(): void
    {
        $filters = [
            'status' => $_GET['status'] ?? null,
            'nickname' => $_GET['nickname'] ?? null,
            'staff' => $_GET['staff'] ?? null,
            'role_id' => isset($_GET['role_id']) && $_GET['role_id'] !== '' ? (int)$_GET['role_id'] : null,
        ];
        
        $users = UserRepository::getAllWithRoles(array_filter($filters));
        $this->apiSuccess($users);
    }

    /**
     * Get single user by ID
     */
    private function getUser(): void
    {
        $userId = (int)($_GET['user_id'] ?? 0);
        
        if (!$userId) {
            $this->apiBadRequest('User ID is required');
            return;
        }
        
        $user = UserRepository::findById($userId);
        
        if ($user) {
            $user['assigned_roles'] = UserRepository::getRoleIdsForUser($userId);
            $this->apiSuccess($user);
        } else {
            $this->apiNotFound('User not found');
        }
    }

    /**
     * Get all available roles
     */
    private function getAllRoles(): void
    {
        $roles = RoleRepository::getAllRoles();
        $this->apiSuccess($roles);
    }

    /**
     * Save user data
     */
    private function saveUser(): void
    {
        $input = $this->getJsonInput();
        $userId = (int)($input['user_id'] ?? 0);
        
        if (!$userId) {
            $this->apiBadRequest('User ID is required');
            return;
        }
        
        $data = [
            'status' => $input['status'] ?? 'pending',
            'roles' => $input['roles'] ?? []
        ];
        
        if ($this->userManager->updateUser($userId, $data)) {
            $this->apiSuccess(null, '사용자 정보가 저장되었습니다.');
        } else {
            $this->apiError('저장 중 오류가 발생했습니다.');
        }
    }

    /**
     * Get employees that are not linked to any user
     */
    private function getUnlinkedEmployees(): void
    {
        $departmentId = isset($_GET['department_id']) && !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
        $employees = UserRepository::getUnlinkedEmployees($departmentId);
        $this->apiSuccess($employees);
    }

    /**
     * Link a user to an employee
     */
    private function linkEmployee(): void
    {
        $input = $this->getJsonInput();
        $userId = (int)($input['user_id'] ?? 0);
        $employeeId = (int)($input['employee_id'] ?? 0);
        
        if (!$userId || !$employeeId) {
            $this->apiBadRequest('User ID and Employee ID are required');
            return;
        }
        
        if (UserRepository::linkEmployee($userId, $employeeId)) {
            $this->apiSuccess(null, '직원이 성공적으로 연결되었습니다.');
        } else {
            $this->apiError('연결에 실패했습니다. 이미 다른 사용자와 연결된 직원일 수 있습니다.');
        }
    }

    /**
     * Unlink a user from an employee
     */
    private function unlinkEmployee(): void
    {
        $input = $this->getJsonInput();
        $userId = (int)($input['user_id'] ?? 0);
        
        if (!$userId) {
            $this->apiBadRequest('User ID is required');
            return;
        }
        
        if (UserRepository::unlinkEmployee($userId)) {
            $this->apiSuccess(null, '직원 연결이 해제되었습니다.');
        } else {
            $this->apiError('연결 해제에 실패했습니다.');
        }
    }
}