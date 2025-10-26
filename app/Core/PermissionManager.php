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
     * 사용자가 주어진 작업을 수행할 수 있는지 확인합니다.
     */
    public function can(string $permission): bool
    {
        if (!$this->user) {
            return false;
        }

        // 관리자는 모든 것을 할 수 있습니다.
        if ($this->user['role'] === 'admin') {
            return true;
        }

        // 사용자의 역할에 대한 특정 권한을 확인합니다.
        return Permission::hasPermission($this->user['role'], $permission);
    }
}
