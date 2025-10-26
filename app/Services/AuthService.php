<?php

namespace App\Services;

use App\Core\SessionManager;
use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use App\Repositories\LogRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\EmployeeRepository;
use Exception;

class AuthService {
    private SessionManager $sessionManager;
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;
    private LogRepository $logRepository;
    private DepartmentRepository $departmentRepository;
    private EmployeeRepository $employeeRepository;
    private ?array $departmentMap = null;

    public function __construct(
        SessionManager $sessionManager,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        LogRepository $logRepository,
        DepartmentRepository $departmentRepository,
        EmployeeRepository $employeeRepository
    ) {
        $this->sessionManager = $sessionManager;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->logRepository = $logRepository;
        $this->departmentRepository = $departmentRepository;
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * @return SessionManager
     */
    public function getSessionManager(): SessionManager
    {
        return $this->sessionManager;
    }

    /**
     * @return array|null
     */
    public function user(): ?array
    {
        return $this->sessionManager->get('user');
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->sessionManager->has('user');
    }

    /**
     * @param array $user
     * @return void
     * @throws Exception
     */
    public function login(array $user) {
        if ($user['status'] === 'blocked') {
            throw new Exception("차단된 계정은 로그인할 수 없습니다.");
        }

        $this->_refreshSessionPermissions($user);

        $this->logRepository->insert([
            ':user_id' => $user['id'],
            ':user_name' => $user['nickname'],
            ':action' => '로그인 성공',
            ':details' => null,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

    /**
     * @return void
     */
    public function logout() {
        if ($this->isLoggedIn()) {
            $user = $this->user();
            $latestUser = $this->userRepository->findById($user['id']);
            $nickname = $latestUser['nickname'] ?? $user['nickname'];

            $this->logRepository->insert([
                ':user_id' => $user['id'],
                ':user_name' => $nickname,
                ':action' => '로그아웃',
                ':details' => null,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        $this->sessionManager->destroy();
        header('Location: /login');
        exit();
    }

    /**
     * @param string $permission_key
     * @return bool
     */
    public function check(string $permission_key): bool {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $permissions_last_updated_file = ROOT_PATH . '/storage/permissions_last_updated.txt';
        $global_permissions_last_updated = file_exists($permissions_last_updated_file) ? (int)file_get_contents($permissions_last_updated_file) : 0;
        $user_permissions_cached_at = $this->sessionManager->get('permissions_cached_at', 0);

        if ($user_permissions_cached_at < $global_permissions_last_updated) {
            $this->_refreshSessionPermissions($this->user());
        }

        $permissions = $this->user()['permissions'] ?? [];
        return in_array($permission_key, $permissions);
    }

    /**
     * @param int $targetEmployeeId
     * @return bool
     */
    public function canManageEmployee(int $targetEmployeeId): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        if ($this->check('employee.manage_all')) {
            return true;
        }

        $currentUser = $this->user();
        $managerEmployeeId = $currentUser['employee_id'] ?? null;

        if (!$managerEmployeeId) {
            return false;
        }

        if ($managerEmployeeId === $targetEmployeeId) {
            return true;
        }

        $targetEmployee = $this->employeeRepository->findById($targetEmployeeId);
        if (!$targetEmployee || !$targetEmployee['department_id']) {
            return false;
        }

        $managedDeptIds = $this->departmentRepository->findDepartmentIdsWithEmployeeViewPermission($managerEmployeeId);
        if (empty($managedDeptIds)) {
            return false;
        }

        $this->loadDepartmentMap();

        $currentDeptId = $targetEmployee['department_id'];

        while ($currentDeptId) {
            if (in_array($currentDeptId, $managedDeptIds)) {
                return true;
            }

            $department = $this->departmentMap[$currentDeptId] ?? null;
            if (!$department || !$department->parent_id) {
                break;
            }
            $currentDeptId = $department->parent_id;
        }

        return false;
    }

    /**
     * @return void
     */
    private function loadDepartmentMap(): void
    {
        if ($this->departmentMap === null) {
            $allDepartments = $this->departmentRepository->getAll();
            $this->departmentMap = [];
            foreach ($allDepartments as $dept) {
                $this->departmentMap[$dept->id] = $dept;
            }
        }
    }

    /**
     * @return void
     */
    public function checkAccess() {
        $realtime_status = $this->checkStatus();
        if ($realtime_status === 'active') {
            return;
        }

        if (strpos($_SERVER['REQUEST_URI'], '/status') !== false) {
            return;
        }

        switch ($realtime_status) {
            case 'pending':
                header('Location: /status');
                exit();

            case 'blocked':
            default:
                $this->logout();
                break;
        }
    }

    /**
     * @return string
     */
    private function checkStatus(): string {
        if (!$this->isLoggedIn()) {
            $this->logout();
        }

        $user = $this->user();
        if (!$user || !isset($user['id'])) {
            $this->logout();
        }

        $currentUser = $this->userRepository->findById($user['id']);

        if (!$currentUser || !isset($currentUser['status'])) {
            $this->logout();
        }

        return $currentUser['status'];
    }

    /**
     * @param array $user
     * @return void
     */
    private function _refreshSessionPermissions(array $user): void {
        $user['roles'] = $this->roleRepository->getUserRoles($user['id']);
        $permissions = $this->userRepository->getPermissions($user['id']);
        $user['permissions'] = array_column($permissions, 'key');

        $this->sessionManager->set('user', $user);
        $this->sessionManager->set('permissions_cached_at', time());
    }
}
