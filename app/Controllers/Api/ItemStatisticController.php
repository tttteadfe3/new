<?php

namespace App\Controllers\Api;

use App\Services\ItemStatisticService;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use App\Repositories\LogRepository;

class ItemStatisticController extends BaseApiController
{
    private ItemStatisticService $itemStatisticService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        ItemStatisticService $itemStatisticService,
        LogRepository $logRepository
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->itemStatisticService = $itemStatisticService;
        $this->logRepository = $logRepository;
    }

    /**
     * 통계 대시보드 데이터를 가져옵니다.
     */
    public function dashboard(): void
    {
        try {
            $year = $this->request->input('year');
            if (!$year) {
                $this->apiBadRequest('year는 필수입니다.');
                return;
            }

            $stats = $this->itemStatisticService->getDashboardStats((int)$year);
            $this->apiSuccess($stats);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
