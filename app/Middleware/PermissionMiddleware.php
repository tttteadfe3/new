<?php

namespace App\Middleware;

use App\Core\View;
use App\Services\AuthService;
use App\Services\ViewDataService;

class PermissionMiddleware extends BaseMiddleware
{
    private AuthService $authService;
    private ViewDataService $viewDataService;

    public function __construct(AuthService $authService, ViewDataService $viewDataService)
    {
        $this->authService = $authService;
        $this->viewDataService = $viewDataService;
    }

    public function handle($permission = null): void
    {
        if (! $this->authService->check($permission)) {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Forbidden'], 403);
            } else {
                http_response_code(403);
                // 일관된 사용자 경험을 제공하기 위해
                // 기본 애플리케이션 레이아웃 내에서 403 오류 페이지를 렌더링합니다.
                // 메뉴가 있는 레이아웃이 올바르게 렌더링되도록 하려면
                // 공통 뷰 데이터를 제공해야 합니다.
                $commonData = $this->viewDataService->getCommonData();
                $viewData = array_merge($commonData, [
                    'pageTitle' => '접근 권한 없음',
                    'message' => '이 페이지에 접근할 수 있는 권한이 없습니다.'
                ]);
                echo \App\Core\View::getInstance()->render('errors/403', $viewData, 'layouts/basic');
            }
            exit();
        }
    }
}
