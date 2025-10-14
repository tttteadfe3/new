<?php

namespace App\Controllers\Web;

use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class DashboardController extends BaseController
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
     * Display the dashboard page
     */
    public function index(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/dashboard.js");

        $pageTitle = "마이페이지";
        $this->activityLogger->logMenuAccess($pageTitle);

        echo $this->render('pages/dashboard/index', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }
}
