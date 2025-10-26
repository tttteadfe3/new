<?php

namespace App\Controllers\Api;

use App\Services\LogService;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;

class LogApiController extends BaseApiController
{
    private LogService $logService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        LogService $logService
    ) {
        parent::__construct(
            $request,
            $authService,
            $viewDataService,
            $activityLogger,
            $employeeRepository,
            $jsonResponse
        );
        $this->logService = $logService;
    }

    /**
     * 로그를 검색하고 검색합니다.
     * GET /api/logs에 해당합니다.
     */
    public function index(): void
    {
        
        try {
            $filters = [
                'start_date' => $this->request->input('start_date', ''),
                'end_date' => $this->request->input('end_date', ''),
                'user_name' => $this->request->input('user_name', ''),
                'action' => $this->request->input('action', ''),
            ];
            
            $logs = $this->logService->searchLogs(array_filter($filters));
            $this->apiSuccess($logs);
        } catch (Exception $e) {
            $this->apiError('로그 검색 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 모든 로그를 지웁니다.
     * DELETE /api/logs에 해당합니다.
     */
    public function destroy(): void
    {
        
        try {
            $this->logService->clearLogs();
            $this->apiSuccess(null, '로그가 성공적으로 비워졌습니다.');
        } catch (Exception $e) {
            $this->apiError('로그를 비우는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }
}
