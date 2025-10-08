<?php

namespace App\Controllers;

use App\Core\AuthManager;
use App\Core\View;
use App\Core\Request;
use App\Models\Permission;

abstract class BaseController
{
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    /**
     * Require authentication and optionally check for specific permission.
     * 
     * @deprecated Use middleware instead: 'auth' or 'permission:permission_name'
     * @param string|null $permission The permission to check for
     * @throws \Exception If user is not authenticated or lacks permission
     */
    protected function requireAuth(string $permission = null): void
    {
        if (!AuthManager::isLoggedIn()) {
            $this->redirect('/login');
            exit;
        }

        if ($permission !== null) {
            $user = AuthManager::user();
            $userRole = $user['role'] ?? 'guest';
            
            if (!Permission::hasPermission($userRole, $permission)) {
                $this->json([
                    'success' => false,
                    'message' => 'Access denied. Insufficient permissions.',
                    'errors' => ['permission' => 'You do not have permission to access this resource.']
                ], 403);
                exit;
            }
        }
    }

    /**
     * Render a view with data.
     * 
     * @param string $view The view file to render
     * @param array $data Data to pass to the view
     * @param string|null $layout The layout to use for rendering
     * @return string The rendered view content
     */
    protected function render(string $view, array $data = [], ?string $layout = null): string
    {
        return View::render($view, $data, $layout);
    }

    /**
     * Return a JSON response.
     * 
     * @param array $data The data to return
     * @param int $status HTTP status code
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        
        // Ensure consistent JSON response format
        $response = [
            'success' => $status >= 200 && $status < 300,
            'data' => $data['data'] ?? null,
            'message' => $data['message'] ?? '',
            'errors' => $data['errors'] ?? []
        ];
        
        // If data is passed directly without the standard format, use it as data
        if (!isset($data['success']) && !isset($data['message']) && !isset($data['errors'])) {
            $response['data'] = $data;
        } else {
            // Merge with provided structure
            $response = array_merge($response, $data);
        }
        
        echo json_encode($response);
    }

    /**
     * Redirect to a URL.
     * 
     * @param string $url The URL to redirect to
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Get the current authenticated user.
     * 
     * @return array|null The user data or null if not authenticated
     */
    protected function user(): ?array
    {
        return AuthManager::user();
    }

    /**
     * Check if the current user is authenticated.
     * 
     * @return bool True if authenticated, false otherwise
     */
    protected function isAuthenticated(): bool
    {
        return AuthManager::isLoggedIn();
    }
}