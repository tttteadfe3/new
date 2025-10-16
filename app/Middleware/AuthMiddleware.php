<?php

namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware extends BaseMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle($value = null): void
    {
        if (! $this->authService->isLoggedIn()) {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Unauthorized'], 401);
            } else {
                $this->redirect('/login');
            }
            exit();
        }
        $this->authService->checkAccess();
    }
}
