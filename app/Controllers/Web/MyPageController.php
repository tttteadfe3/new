<?php

namespace App\Controllers\Web;

use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class MyPageController extends BaseController
{
    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
    }

    /**
     * 대시보드 페이지를 표시합니다
     */
    public function index(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/my-page.js");

        echo $this->render('pages/my-page/index', [], 'layouts/app');
    }
}
