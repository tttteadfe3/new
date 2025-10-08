<?php

namespace App\Controllers;

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
    public function index(): string
    {
        $this->requireAuth('log_admin');
        
        $pageTitle = "사용 로그 뷰어";
        $pageJs = [
            BASE_ASSETS_URL . '/assets/js/pages/log_viewer.js'
        ];
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);

        return $this->render('pages/logs/index', [
            'pageTitle' => $pageTitle,
            'pageJs' => $pageJs
        ]);
    }

}