<?php

namespace App\Controllers\Web;

use App\Services\LogService;
use App\Core\JsonResponse;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\View;

class LogController extends BaseController
{
    private LogService $logService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        LogService $logService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->logService = $logService;
    }

    /**
     * Display the log viewer page
     */
    public function index(): void
    {
        $pageTitle = "사용 로그 뷰어";

        // Load BaseApp and dependencies

        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/log-viewer.js');
        $this->activityLogger->logMenuAccess($pageTitle);

        echo $this->render('pages/logs/index', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

}
