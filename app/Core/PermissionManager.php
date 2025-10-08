<?php

namespace App\Core;

use App\Models\Permission;

class PermissionManager
{
    protected ?array $user;

    public function __construct(?array $user)
    {
        $this->user = $user;
    }

    /**
     * Check if the user can perform a given action.
     */
    public function can(string $permission): bool
    {
        if (!$this->user) {
            return false;
        }

        // Admins can do anything.
        if ($this->user['role'] === 'admin') {
            return true;
        }

        // Check for specific permission for the user's role.
        return Permission::hasPermission($this->user['role'], $permission);
    }
}