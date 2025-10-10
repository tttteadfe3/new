<?php

namespace App\Middleware;

use App\Core\View;
use App\Services\AuthService;

class PermissionMiddleware extends BaseMiddleware
{
    public function handle($permission = null): void
    {
        if (! (new AuthService())->check($permission)) {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Forbidden'], 403);
            } else {
                http_response_code(403);
                View::render('errors/403', ['message' => 'Access denied. Insufficient permissions.']);
            }
            exit();
        }
    }
}