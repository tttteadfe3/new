<?php

namespace App\Controllers\Api;

use App\Services\VehicleService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use Exception;

class VehicleApiController extends BaseApiController
{
    private VehicleService $vehicleService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        VehicleService $vehicleService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->vehicleService = $vehicleService;
    }

    public function index(): void
    {
        try {
            $filters = [
                'department_id' => $this->request->input('department_id'),
                'status_code' => $this->request->input('status_code'),
                'search' => $this->request->input('search')
            ];

            // 빈 값 제거
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            // DataScope는 Repository에서 자동 적용됨
            $vehicles = $this->vehicleService->getAllVehicles($filters);
            $this->apiSuccess($vehicles);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function show(int $id): void
    {
        try {
            $vehicle = $this->vehicleService->getVehicleById($id);
            if (!$vehicle) {
                $this->apiNotFound('차량 정보를 찾을 수 없습니다.');
                return;
            }
            $this->apiSuccess($vehicle);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            $id = $this->vehicleService->registerVehicle($data);
            $this->apiSuccess(['id' => $id, 'message' => '차량이 등록되었습니다.'], 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function update(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            $success = $this->vehicleService->updateVehicle($id, $data);
            if ($success) {
                $this->apiSuccess(['message' => '차량 정보가 수정되었습니다.']);
            } else {
                $this->apiError('차량 정보 수정에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function destroy(int $id): void
    {
        try {
            $success = $this->vehicleService->deleteVehicle($id);
            if ($success) {
                $this->apiSuccess(['message' => '차량이 삭제되었습니다.']);
            } else {
                $this->apiError('차량 삭제에 실패했습니다.');
            }
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
