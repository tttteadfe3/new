<?php

namespace App\Services;

use App\Core\SessionManager;
use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use App\Repositories\LogRepository;
use Exception;

/**
 * Provides a unified, instance-based service for all authentication,
 * authorization, and session management tasks.
 * This class merges the responsibilities of the old Core\AuthManager and Services\AuthManager.
 */
class AuthService {

    /**
     * Get the currently authenticated user from the session.
     */
    public function user(): ?array
    {
        return SessionManager::get('user');
    }

    /**
     * Check if a user is currently logged in.
     */
    public function isLoggedIn(): bool
    {
        return SessionManager::has('user');
    }

    /**
     * Establishes a user session after successful login.
     * Merges logic from Core\AuthManager and the old establishSession.
     */
    public function login(array $user) {
        if ($user['status'] === 'blocked') {
            throw new Exception("Blocked accounts cannot log in.");
        }

        // Fetch and enrich user data with roles and permissions for the session
        $user['roles'] = RoleRepository::getUserRoles($user['id']);
        $permissions = UserRepository::getPermissions($user['id']);
        $user['permissions'] = array_column($permissions, 'key');

        SessionManager::set('user', $user);

        // Cache the timestamp
        SessionManager::set('permissions_cached_at', time());

        LogRepository::insert([
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
            $latestUser = UserRepository::findById($user['id']);
            $nickname = $latestUser['nickname'] ?? $user['nickname'];

            LogRepository::insert([
                ':user_id' => $user['id'],
                ':user_name' => $nickname,
                ':action' => 'Logout',
                ':details' => null,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        SessionManager::destroy();
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
        $user_permissions_cached_at = SessionManager::get('permissions_cached_at', 0);

        // If cache is old, refresh it by re-logging in the session data
        if ($user_permissions_cached_at < $global_permissions_last_updated) {
            $this->login($this->user());
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

        $currentUser = UserRepository::findById($user['id']);

        if (!$currentUser || !isset($currentUser['status'])) {
            $this->logout();
        }

        return $currentUser['status'];
    }
}