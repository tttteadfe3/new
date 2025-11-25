<?php

namespace App\Controllers\Api;

use App\Services\VehicleInspectionService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use Exception;

class VehicleInspectionApiController extends BaseApiController
{
    private VehicleInspectionService $inspectionService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        VehicleInspectionService $inspectionService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->inspectionService = $inspectionService;
    }

    public function index(): void
    {
        try {
            $filters = [
                'vehicle_id' => $this->request->input('vehicle_id'),
                'upcoming_expiry' => $this->request->input('upcoming_expiry') // days
            ];

            // Filter by department if applicable
            $user = $this->authService->user();
            if ($user && $user['employee_id']) {
                $employee = $this->employeeRepository->findById($user['employee_id']);
                if ($employee && $employee['department_id']) {
                    $filters['department_id'] = $employee['department_id'];
                }
            }

            $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');
            
            $inspections = $this->inspectionService->getInspections($filters);
            $this->apiSuccess($inspections);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function store(): void
    {
        try {
            $data = $this->request->all();
            $id = $this->inspectionService->registerInspection($data);
            $this->apiSuccess(['id' => $id, 'message' => '검사 내역이 등록되었습니다.'], 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function show(int $id): void
    {
        try {
            $inspection = $this->inspectionService->getInspectionById($id);
            if (!$inspection) {
                $this->apiNotFound('검사 내역을 찾을 수 없습니다.');
                return;
            }
            $this->apiSuccess($inspection);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    protected function handleException(Exception $e): void
    {
        if ($e instanceof \InvalidArgumentException) {
            $this->apiBadRequest($e->getMessage());
        } else {
            $this->apiError('서버 오류가 발생했습니다: ' . $e->getMessage());
        }
    }
}
