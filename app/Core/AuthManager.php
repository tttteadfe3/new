<?php

namespace App\Core;

class AuthManager
{
    private const USER_SESSION_KEY = 'user';

    /**
     * Log in a user by storing their information in the session.
     */
    public static function login(array $user): void
    {
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