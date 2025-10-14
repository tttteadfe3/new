<?php

namespace App\Controllers\Api;

use App\Services\EmployeeService;
use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
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

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        EmployeeService $employeeService,
        DepartmentRepository $departmentRepository,
        PositionRepository $positionRepository
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->employeeService = $employeeService;
        $this->departmentRepository = $departmentRepository;
        $this->positionRepository = $positionRepository;
    }

    public function getInitialData(): void
    {
        try {
            $data = [
                'departments' => $this->departmentRepository->getAll(),
                'positions' => $this->positionRepository->getAll(),
            ];
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function index(): void
    {
        try {
            $filters = $this->request->all();
            $employees = $this->employeeService->getAllEmployees($filters);
            $this->apiSuccess($employees);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function show(int $id): void
    {
        if (!$this->authService->canManageEmployee($id)) {
            $this->apiForbidden('해당 직원의 정보를 조회할 권한이 없습니다.');
            return;
        }
        try {
            $employee = $this->employeeService->getEmployee($id);
            if ($employee) {
                $this->apiSuccess($employee);
            } else {
                $this->apiNotFound('직원을 찾을 수 없습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function update(int $id): void
    {
        if (!$this->authService->canManageEmployee($id)) {
            $this->apiForbidden('해당 직원의 정보를 수정할 권한이 없습니다.');
            return;
        }
        try {
            $data = $this->getJsonInput();
            $this->employeeService->updateEmployee($id, $data);
            $this->apiSuccess(null, '직원 정보가 성공적으로 수정되었습니다.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function destroy(int $id): void
    {
        if (!$this->authService->canManageEmployee($id)) {
            $this->apiForbidden('해당 직원을 삭제할 권한이 없습니다.');
            return;
        }
        try {
            if ($this->employeeService->deleteEmployee($id)) {
                $this->apiSuccess(null, '직원이 성공적으로 삭제되었습니다.');
            } else {
                $this->apiError('직원 삭제에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ... (rest of the original methods are assumed to be here)
}
