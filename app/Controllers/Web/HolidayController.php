<?php

namespace App\Controllers\Web;

use App\Services\HolidayService;
use App\Core\View;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;

class HolidayController extends BaseController
{
    private HolidayService $holidayService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        HolidayService $holidayService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->holidayService = $holidayService;
    }

    /**
     * 휴일 관리 페이지를 표시합니다.
     */
    public function index(): void
    {
        // 페이지별 CSS 및 JS 설정
        \App\Core\View::getInstance()->addCss(BASE_ASSETS_URL . '/assets/libs/flatpickr/flatpickr.min.css');
        \App\Core\View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/flatpickr/flatpickr.min.js');

        // BaseApp 및 종속성 로드
        \App\Core\View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/holiday-admin.js');

        echo $this->render('pages/holidays/index', [], 'layouts/app');
    }

}
