<?php

namespace App\Controllers\Api;

use App\Services\VehicleMaintenanceService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use Exception;

class VehicleMaintenanceApiController extends BaseApiController
{
    private VehicleMaintenanceService $maintenanceService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        VehicleMaintenanceService $maintenanceService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->maintenanceService = $maintenanceService;
    }

    // Breakdowns
    public function indexBreakdowns(): void
    {
        try {
            $filters = [
                'vehicle_id' => $this->request->input('vehicle_id'),
                'status' => $this->request->input('status')
            ];

            // 빈 값 제거
            $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');
            
            // DataScope는 Repository에서 자동 적용됨
            $breakdowns = $this->maintenanceService->getBreakdowns($filters);
            $this->apiSuccess($breakdowns);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function showBreakdown(int $id): void
    {
        try {
            $breakdown = $this->maintenanceService->getBreakdown($id);
            if (!$breakdown) {
                $this->apiNotFound('고장 내역을 찾을 수 없습니다.');
                return;
            }
            $this->apiSuccess($breakdown);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function storeBreakdown(): void
    {
        try {
            $data = $this->request->all();
            // Auto-assign reporter if not provided? For now assume frontend sends it or we use logged in user's employee id
            // $data['driver_employee_id'] = ...
            
            $id = $this->maintenanceService->reportBreakdown($data);
            $this->apiSuccess(['id' => $id, 'message' => '고장 신고가 접수되었습니다.'], 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // Repairs
    public function storeRepair(): void
    {
        try {
            $data = $this->request->all();
            $id = $this->maintenanceService->registerRepair($data);
            $this->apiSuccess(['id' => $id, 'message' => '수리 내역이 등록되었습니다.'], 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // Self Maintenance
    public function indexSelfMaintenances(): void
    {
        try {
            $filters = [
                'vehicle_id' => $this->request->input('vehicle_id')
            ];

            // 빈 값 제거
            $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');
            
            // DataScope는 Repository에서 자동 적용됨
            $maintenances = $this->maintenanceService->getSelfMaintenances($filters);
            $this->apiSuccess($maintenances);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function storeSelfMaintenance(): void
    {
        try {
            $data = $this->request->all();
            $id = $this->maintenanceService->recordSelfMaintenance($data);
            $this->apiSuccess(['id' => $id, 'message' => '자체 정비 내역이 등록되었습니다.'], 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // Workflow Actions
    public function decideBreakdown(int $id): void
    {
        try {
            $type = $this->request->input('type'); // INTERNAL or EXTERNAL
            // Validation...
            $this->maintenanceService->decideRepairType($id, $type);
            $this->apiSuccess(['message' => '수리 방법이 결정되었습니다.']);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function completeBreakdown(int $id): void
    {
        try {
            $repairData = $this->request->all();
            $this->maintenanceService->completeRepair($id, $repairData);
            $this->apiSuccess(['message' => '수리가 완료 처리되었습니다.']);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function confirmBreakdown(int $id): void
    {
        try {
            $this->maintenanceService->confirmRepair($id);
            $this->apiSuccess(['message' => '수리가 최종 승인되었습니다.']);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function confirmSelfMaintenance(int $id): void
    {
        try {
            $this->maintenanceService->confirmSelfMaintenance($id);
            $this->apiSuccess(['message' => '자체 정비가 승인되었습니다.']);
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
