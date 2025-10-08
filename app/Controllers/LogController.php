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
        log_menu_access($pageTitle);

        return $this->render('pages/logs/index', [
            'pageTitle' => $pageTitle,
            'pageJs' => $pageJs
        ]);
    }

    /**
     * Search logs (API endpoint)
     */
    public function search(): void
    {
        $this->requireAuth('log_admin');
        
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $this->json([
                'success' => false,
                'message' => 'Forbidden'
            ], 403);
            return;
        }

        try {
            $filters = [
                'start_date' => $_GET['start_date'] ?? '',
                'end_date' => $_GET['end_date'] ?? '',
                'user_name' => $_GET['user_name'] ?? '',
                'action' => $_GET['action'] ?? '',
            ];
            
            $logs = $this->logService->searchLogs($filters);
            
            $this->json([
                'success' => true,
                'data' => $logs
            ]);
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all logs (API endpoint)
     */
    public function clear(): void
    {
        $this->requireAuth('log_admin');
        
        try {
            $result = $this->logService->clearLogs();
            
            if ($result) {
                $this->json([
                    'success' => true,
                    'message' => '로그가 성공적으로 비워졌습니다.'
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'message' => '로그 비우기에 실패했습니다.'
                ], 500);
            }
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}