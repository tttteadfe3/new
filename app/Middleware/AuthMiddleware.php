<?php

namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware extends BaseMiddleware
{
    /**
     * Handle authentication check.
     * Redirects to login if user is not authenticated.
     */
    public function handle($parameter = null): void
    {
        $authService = new AuthService();
        if (!$authService->isLoggedIn()) {
            if ($this->isApiRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Authentication required',
                    'errors' => ['auth' => 'You must be logged in to access this resource.']
                ], 401);
            } else {
                $this->redirect('/login');
            }
        }
    }
}