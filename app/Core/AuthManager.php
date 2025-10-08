<?php

namespace App\Core;

use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

class AuthManager
{
    private const USER_SESSION_KEY = 'user';

    /**
     * Log in a user by storing their information in the session.
     * This now includes fetching and storing their roles and permissions.
     */
    public static function login(array $user): void
    {
        // Fetch all roles and permissions for the user
        $user['roles'] = RoleRepository::getUserRoles($user['id']);
        $permissions = UserRepository::getPermissions($user['id']);
        // Ensure permissions are a flat array of strings for easy checking
        $user['permissions'] = array_column($permissions, 'key');

        $_SESSION[self::USER_SESSION_KEY] = $user;
    }

    /**
     * Log out the current user.
     */
    public static function logout(): void
    {
        unset($_SESSION[self::USER_SESSION_KEY]);
        session_destroy();
    }

    /**
     * Check if a user is currently logged in.
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION[self::USER_SESSION_KEY]);
    }

    /**
     * Get the currently authenticated user.
     */
    public static function user(): ?array
    {
        return $_SESSION[self::USER_SESSION_KEY] ?? null;
    }
}