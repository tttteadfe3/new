<?php

namespace App\Controllers\Web;

use App\Core\View;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;

abstract class BaseController
{
    protected Request $request;
    protected AuthService $authService;
    protected ViewDataService $viewDataService;

    public function __construct()
    {
        $this->request = new Request();
        $this->authService = new AuthService();
        $this->viewDataService = new ViewDataService();
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
            // Use the instance of the dedicated service.
            $commonData = $this->viewDataService->getCommonData();
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
