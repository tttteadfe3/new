<?php

namespace App\Controllers\Api;

use App\Repositories\LogRepository;

class LogApiController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Search and retrieve logs
     */
    public function index(): void
    {
        $this->requireAuth('log_admin');
        
        try {
            $filters = [
                'start_date' => $_GET['start_date'] ?? '',
                'end_date' => $_GET['end_date'] ?? '',
                'user_name' => $_GET['user_name'] ?? '',
                'action' => $_GET['action'] ?? '',
            ];
            
            $logs = LogRepository::search($filters);
            $this->apiSuccess($logs);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Clear all logs
     */
    public function clear(): void
    {
        $this->requireAuth('log_admin');
        
        try {
            LogRepository::truncate();
            $this->apiSuccess(null, '로그가 성공적으로 비워졌습니다.');
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
}