<?php

namespace App\Controllers\Api {
    function header($string, $replace = true, $http_response_code = null) {
        // Do nothing
    }
}

namespace Tests {
    use PHPUnit\Framework\TestCase;
    use App\Controllers\Api\VehicleApiController;
    use App\Services\VehicleService;
    use App\Services\AuthService;
    use App\Core\Request;
    use App\Core\JsonResponse;
    use App\Services\ViewDataService;
    use App\Services\ActivityLogger;
    use App\Repositories\EmployeeRepository;

    class VehicleManagementTest extends TestCase
    {
        private $vehicleService;

        private $authService;
        private $request;
        private $controller;

        protected function setUp(): void
        {
            // Mock AJAX request
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = '/api/vehicles';

            $this->vehicleService = $this->createMock(VehicleService::class);
            $this->authService = $this->createMock(AuthService::class);
            $this->request = $this->createMock(Request::class);
            
            // Mock other dependencies
            $viewDataService = $this->createMock(ViewDataService::class);
            $activityLogger = $this->createMock(ActivityLogger::class);
            $employeeRepository = $this->createMock(EmployeeRepository::class);
            $jsonResponse = $this->createMock(JsonResponse::class);

            $this->controller = new VehicleApiController(
                $this->request,
                $this->authService,
                $viewDataService,
                $activityLogger,
                $employeeRepository,
                $jsonResponse,
                $this->vehicleService
            );
        }

        public function testIndexForDepartmentManager()
        {
            // Arrange
            $user = ['employee_id' => 123];
            $this->authService->method('user')->willReturn($user);
            
            $this->request->method('input')->willReturnMap([
                ['department_id', null],
                ['status_code', null],
                ['search', null]
            ]);

            // Expect
            $this->vehicleService->expects($this->once())
                ->method('getAllVehicles')
                ->with($this->callback(function($filters) {
                    // Controller no longer adds scope IDs, just passes request filters
                    return !isset($filters['department_id']);
                }));

            // Act
            $this->controller->index();
        }

        public function testIndexForDriver()
        {
            // Arrange
            $user = ['employee_id' => 456];
            $this->authService->method('user')->willReturn($user);
            
            $this->request->method('input')->willReturnMap([
                ['department_id', null],
                ['status_code', null],
                ['search', null]
            ]);

            // Expect
            $this->vehicleService->expects($this->once())
                ->method('getAllVehicles')
                ->with($this->callback(function($filters) {
                     // Controller no longer adds scope IDs, just passes request filters
                    return !isset($filters['department_id']);
                }));

            // Act
            $this->controller->index();
        }

        public function testIndexForAdmin()
        {
            // Arrange
            $user = ['employee_id' => 999];
            $this->authService->method('user')->willReturn($user);
            
            $this->request->method('input')->willReturnMap([
                ['department_id', null],
                ['status_code', null],
                ['search', null]
            ]);

            // Expect
            $this->vehicleService->expects($this->once())
                ->method('getAllVehicles')
                ->with($this->callback(function($filters) {
                    // Admin should NOT have visible_department_ids set
                    return !isset($filters['visible_department_ids']);
                }));

            // Act
            $this->controller->index();
        }
    }
}
