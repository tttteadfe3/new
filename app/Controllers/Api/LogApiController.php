<?php

namespace App\Controllers\Api;

use App\Repositories\LogRepository;
use Exception;

class LogApiController extends BaseApiController
{
    /**
     * Search and retrieve logs.
     * Corresponds to GET /api/logs
     */
    public function index(): void
    {
        $this->requireAuth('log_admin');
        
        try {
            $filters = [
                'start_date' => $this->request->input('start_date', ''),
                'end_date' => $this->request->input('end_date', ''),
                'user_name' => $this->request->input('user_name', ''),
                'action' => $this->request->input('action', ''),
            ];
            
            $logs = LogRepository::search(array_filter($filters));
            $this->apiSuccess($logs);
        } catch (Exception $e) {
            $this->apiError('로그 검색 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Clear all logs.
     * Corresponds to DELETE /api/logs
     */
    public function destroy(): void
    {
        $this->requireAuth('log_admin');
        
        try {
            LogRepository::truncate();
            $this->apiSuccess(null, '로그가 성공적으로 비워졌습니다.');
        } catch (Exception $e) {
            $this->apiError('로그를 비우는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }
}