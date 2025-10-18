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
     * Display the holiday administration page.
     */
    public function index(): void
    {
        // Set page-specific CSS and JS
        \App\Core\View::getInstance()->addCss(BASE_ASSETS_URL . '/assets/libs/flatpickr/flatpickr.min.css');
        \App\Core\View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/libs/flatpickr/flatpickr.min.js');

        // Load BaseApp and dependencies

        \App\Core\View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/holiday-admin.js');

        echo $this->render('pages/holidays/index', [], 'layouts/app');
    }

}
