<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;

abstract class BaseController
{
    protected Request $request;
    protected AuthService $authService;

    public function __construct()
    {
        $this->request = new Request();
        $this->authService = new AuthService();
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
        if (!$this->authService->isLoggedIn()) {
            $this->redirect('/login');
            exit;
        }

        if ($permission !== null) {
            // Use the new centralized permission checker
            if (!$this->authService->check($permission)) {
                View::render('errors/403', ['message' => 'Access denied. Insufficient permissions.']);
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
        // Prepare common data for all views that use a layout
        $commonData = [];
        if ($layout !== null && $this->isAuthenticated()) {
            // Decouple controller from data-fetching logic by using the dedicated service.
            $commonData = ViewDataService::getCommonData();
        }

        // Merge controller-specific data with common data
        $viewData = array_merge($data, $commonData);

        return View::render($view, $viewData, $layout);
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
        return $this->authService->user();
    }

    /**
     * Check if the current user is authenticated.
     * 
     * @return bool True if authenticated, false otherwise
     */
    protected function isAuthenticated(): bool
    {
        return $this->authService->isLoggedIn();
    }
}