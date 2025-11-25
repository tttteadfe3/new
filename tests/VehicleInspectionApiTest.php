<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\Api\VehicleInspectionApiController;
use App\Services\VehicleInspectionService;
use App\Models\VehicleInspection;
use App\Core\Request;
use App\Core\JsonResponse;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use Exception;

class VehicleInspectionApiTest extends TestCase
{
    private $inspectionService;
    private $request;
    private $jsonResponse;
    private $controller;

    protected function setUp(): void
    {
        $this->inspectionService = $this->createMock(VehicleInspectionService::class);
        $this->request = $this->createMock(Request::class);
        $this->jsonResponse = $this->createMock(JsonResponse::class);

        // Mock other dependencies that are not directly used in the test
        $authService = $this->createMock(AuthService::class);
        $viewDataService = $this->createMock(ViewDataService::class);
        $activityLogger = $this->createMock(ActivityLogger::class);
        $employeeRepository = $this->createMock(EmployeeRepository::class);

        $this->controller = new VehicleInspectionApiController(
            $this->request,
            $authService,
            $viewDataService,
            $activityLogger,
            $employeeRepository,
            $this->jsonResponse,
            $this->inspectionService
        );
    }

    public function testShowReturnsInspectionWhenFound()
    {
        $inspectionData = new VehicleInspection();
        $inspectionData->id = 1;
        $inspectionData->vehicle_id = 1;
        $inspectionData->inspection_date = '2023-10-01';

        $this->inspectionService->method('getInspectionById')
            ->with(1)
            ->willReturn($inspectionData);

        $this->jsonResponse->expects($this->once())
            ->method('send')
            ->with($this->callback(function($data) {
                return $data['status'] === 'success' && $data['data']->id === 1;
            }));

        $this->controller->show(1);
    }

    public function testShowReturnsNotFoundWhenInspectionNotFound()
    {
        $this->inspectionService->method('getInspectionById')
            ->with(999)
            ->willReturn(null);

        $this->jsonResponse->expects($this->once())
            ->method('send')
            ->with($this->callback(function($data) {
                return $data['status'] === 'error' && $data['message'] === '검사 내역을 찾을 수 없습니다.';
            }), 404);

        $this->controller->show(999);
    }
}
