<?php

namespace App\Middleware;

use App\Core\AuthManager;

class PermissionMiddleware extends BaseMiddleware
{
    /**
     * Handle permission check.
     * Assumes user is already authenticated (should be used after AuthMiddleware).
     * 
     * @param string $permission The required permission name
     */
    public function handle($permission = null): void
    {
        if (!$permission) {
            throw new \InvalidArgumentException('Permission parameter is required for PermissionMiddleware');
        }

        $user = AuthManager::user();
        if (!$user) {
            if ($this->isApiRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'errors' => ['auth' => 'Authentication required.']
                ], 401);
            } else {
                $this->redirect('/login');
            }
            return;
        }

        $userPermissions = $user['permissions'] ?? [];
        
        if (!in_array($permission, $userPermissions)) {
            if ($this->isApiRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'errors' => ['permission' => 'You do not have permission to access this resource.']
                ], 403);
            } else {
                $this->htmlError(403, 'Forbidden', 'You do not have permission to access this resource.');
            }
        }
    }
}