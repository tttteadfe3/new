<?php

namespace App\Controllers\Api;

use App\Services\EmployeeService;
use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
use App\Services\LeaveManagementService; // 추가
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;

class EmployeeApiController extends BaseApiController
{
    private EmployeeService $employeeService;
    private DepartmentRepository $departmentRepository;
    private PositionRepository $positionRepository;
    private LeaveManagementService $leaveManagementService; // 추가
    private \App\Services\DataScopeService $dataScopeService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        EmployeeService $employeeService,
        DepartmentRepository $departmentRepository,
        PositionRepository $positionRepository,
        LeaveManagementService $leaveManagementService, // 추가
        \App\Services\DataScopeService $dataScopeService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->employeeService = $employeeService;
        $this->departmentRepository = $departmentRepository;
        $this->positionRepository = $positionRepository;
        $this->leaveManagementService = $leaveManagementService; // 추가
        $this->dataScopeService = $dataScopeService;
    }

    // ... (other methods are unchanged)

    public function store(): void
    {
        if (!$this->authService->check('employee.create')) {
            $this->apiForbidden('직원을 생성할 권한이 없습니다.');
            return;
        }
        try {
            $data = $this->getJsonInput();
            $newEmployeeId = $this->employeeService->createEmployee($data);

            if ($newEmployeeId) {
                // 직원이 성공적으로 생성된 후, 초기 연차를 부여합니다.
                $this->leaveManagementService->grantInitialLeaveForNewEmployee($newEmployeeId, $this->authService->getCurrentEmployeeId());

                $this->apiSuccess(['id' => $newEmployeeId], '직원이 성공적으로 생성되었고 초기 연차가 부여되었습니다.');
            } else {
                $this->apiError('직원 생성에 실패했습니다.');
            }
        } catch (\InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ... (other methods are unchanged)
}
