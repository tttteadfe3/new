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
                // Render the 403 error page within the main application layout
                // to provide a consistent user experience.
                // To ensure the layout renders correctly with menus,
                // we need to provide the common view data.
                $commonData = \App\Services\ViewDataService::getCommonData();
                $viewData = array_merge($commonData, [
                    'pageTitle' => '접근 권한 없음',
                    'message' => '이 페이지에 접근할 수 있는 권한이 없습니다.'
                ]);
                echo View::render('errors/403', $viewData, 'layouts/basic');
            }
            exit();
        }
    }
}