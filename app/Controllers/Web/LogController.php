<?php

namespace App\Controllers\Web;

use App\Services\LogService;
use App\Core\JsonResponse;
use Exception;

class LogController extends BaseController
{
    private LogService $logService;

    public function __construct()
    {
        parent::__construct();
        $this->logService = new LogService();
    }

    /**
     * Display the log viewer page
     */
    public function index(): void
    {
        $pageTitle = "사용 로그 뷰어";

        // Load BaseApp and dependencies
        \App\Core\View::addJs(BASE_ASSETS_URL . '/assets/js/services/api-service.js');
        \App\Core\View::addJs(BASE_ASSETS_URL . '/assets/js/components/base-app.js');

        \App\Core\View::addJs(BASE_ASSETS_URL . '/assets/js/pages/log-viewer-app.js');
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);

        echo $this->render('pages/logs/index', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

}