<?php

namespace App\Controllers\Web;

use App\Core\View;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;

abstract class BaseController
{
    protected Request $request;
    protected AuthService $authService;
    protected ViewDataService $viewDataService;
    protected ActivityLogger $activityLogger;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger
    ) {
        $this->request = $request;
        $this->authService = $authService;
        $this->viewDataService = $viewDataService;
        $this->activityLogger = $activityLogger;
    }

    /**
     * 데이터를 사용하여 뷰를 렌더링합니다.
     * 
     * @param string $view 렌더링할 뷰 파일
     * @param array $data 뷰에 전달할 데이터
     * @param string|null $layout 렌더링에 사용할 레이아웃
     * @return string 렌더링된 뷰 콘텐츠
     */
    protected function render(string $view, array $data = [], ?string $layout = null): string
    {
        // 레이아웃을 사용하는 모든 뷰에 대한 공통 데이터 준비
        $commonData = [];
        if ($layout !== null && $this->isAuthenticated()) {
            // 전용 서비스의 인스턴스를 사용합니다.
            $commonData = $this->viewDataService->getCommonData();
        }

        // 컨트롤러별 데이터를 공통 데이터와 병합
        $viewData = array_merge($data, $commonData);

        return \App\Core\View::getInstance()->render($view, $viewData, $layout);
    }

    /**
     * URL로 리디렉션합니다.
     * 
     * @param string $url 리디렉션할 URL
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * 현재 인증된 사용자를 가져옵니다.
     * 
     * @return array|null 인증되지 않은 경우 사용자 데이터 또는 null
     */
    protected function user(): ?array
    {
        return $this->authService->user();
    }

    /**
     * 현재 사용자가 인증되었는지 확인합니다.
     * 
     * @return bool 인증된 경우 true, 그렇지 않은 경우 false
     */
    protected function isAuthenticated(): bool
    {
        return $this->authService->isLoggedIn();
    }
}
