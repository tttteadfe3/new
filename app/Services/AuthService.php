<?php

namespace App\Services;

use App\Core\Database;
use App\Core\SessionManager;
use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use App\Repositories\LogRepository;
use Exception;

/**
 * Provides a unified, instance-based service for all authentication,
 * authorization, and session management tasks.
 */
class AuthService {
    private SessionManager $sessionManager;
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;
    private LogRepository $logRepository;

    public function __construct() {
        $db = \App\Core\Database::getInstance();
        $this->sessionManager = new SessionManager();
        $this->userRepository = new UserRepository($db);
        $this->roleRepository = new RoleRepository($db);
        $this->logRepository = new LogRepository($db);
    }

    /**
     * Get the currently authenticated user from the session.
     */
    public function user(): ?array
    {
        return $this->sessionManager->get('user');
    }

    /**
     * Check if a user is currently logged in.
     */
    public function isLoggedIn(): bool
    {
        return $this->sessionManager->has('user');
    }

    /**
     * Establishes a user session after successful login.
     */
    public function login(array $user) {
        if ($user['status'] === 'blocked') {
            throw new Exception("Blocked accounts cannot log in.");
        }

        $this->_refreshSessionPermissions($user);

        $this->logRepository->insert([
            ':user_id' => $user['id'],
            ':user_name' => $user['nickname'],
            ':action' => 'Login Success',
            ':details' => null,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

    /**
     * Logs the user out and destroys the session.
     */
    public function logout() {
        if ($this->isLoggedIn()) {
            $user = $this->user();
            $latestUser = $this->userRepository->findById($user['id']);
            $nickname = $latestUser['nickname'] ?? $user['nickname'];

            $this->logRepository->insert([
                ':user_id' => $user['id'],
                ':user_name' => $nickname,
                ':action' => 'Logout',
                ':details' => null,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        $this->sessionManager->destroy();
        header('Location: /login');
        exit();
    }

    /**
     * Checks if the currently logged-in user has a specific permission.
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
     * Checks the user's real-time status and redirects if not active.
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
     * Fetches the user's current status from the database.
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
     * Refreshes the user's roles and permissions in the session.
     */
    private function _refreshSessionPermissions(array $user): void {
        $user['roles'] = $this->roleRepository->getUserRoles($user['id']);
        $permissions = $this->userRepository->getPermissions($user['id']);
        $user['permissions'] = array_column($permissions, 'key');

        $this->sessionManager->set('user', $user);
        $this->sessionManager->set('permissions_cached_at', time());
    }
}
