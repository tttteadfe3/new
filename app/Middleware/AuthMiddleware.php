<?php

namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware extends BaseMiddleware
{
    public function handle($value = null): void
    {
        if (! (new AuthService())->isLoggedIn()) {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Unauthorized'], 401);
            } else {
                $this->redirect('/login');
            }
            exit();
        }
    }
}