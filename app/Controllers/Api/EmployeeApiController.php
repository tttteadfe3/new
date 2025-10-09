<?php

namespace App\Controllers\Api;

use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
use App\Services\EmployeeService;
use App\Repositories\EmployeeChangeLogRepository;
use Exception;
use InvalidArgumentException;

class EmployeeApiController extends BaseApiController
{
    private EmployeeService $employeeService;

    public function __construct()
    {
        parent::__construct();
        $this->employeeService = new EmployeeService();
    }

    /**
     * Handle all employee API requests based on action parameter.
     */
    public function index(): void
    {
        $this->requireAuth('employee_admin');
        
        $action = $this->request->input('action');
        
        try {
            switch ($action) {
                case 'get_initial_data':
                    $this->getInitialData();
                    break;
                case 'list':
                    $this->getEmployeeList();
                    break;
                case 'get_one':
                    $this->getEmployee();
                    break;
                case 'get_change_history':
                    $this->getChangeHistory();
                    break;
                case 'save':
                    $this->saveEmployee();
                    break;
                case 'delete':
                    $this->deleteEmployee();
                    break;
                case 'approve_update':
                    $this->approveUpdate();
                    break;
                case 'reject_update':
                    $this->rejectUpdate();
                    break;
                default:
                    $this->apiError('Invalid action specified.', 'INVALID_ACTION', 400);
            }
        } catch (Exception $e) {
            $this->apiError('An unexpected error occurred: ' . $e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    private function getInitialData(): void
    {
        $filters = ['status' => $this->request->input('status')];
        
        $employees = $this->employeeService->getAllEmployees(array_filter($filters));
        $departments = DepartmentRepository::getAll();
        $positions = PositionRepository::getAll();
        
        $this->apiSuccess([
            'employees' => $employees,
            'departments' => $departments,
            'positions' => $positions
        ]);
    }

    private function getEmployeeList(): void
    {
        $filters = [
            'department_id' => $this->request->input('department_id'),
            'position_id' => $this->request->input('position_id'),
            'status' => $this->request->input('status')
        ];
        
        $employees = $this->employeeService->getAllEmployees(array_filter($filters));
        $this->apiSuccess($employees);
    }

    private function getEmployee(): void
    {
        $employeeId = (int)$this->request->input('id', 0);
        if (!$employeeId) {
            $this->apiError('Employee ID is required', 'INVALID_INPUT', 400);
            return;
        }
        
        $employee = $this->employeeService->getEmployee($employeeId);
        if ($employee) {
            $this->apiSuccess($employee);
        } else {
            $this->apiNotFound('Employee not found');
        }
    }

    private function getChangeHistory(): void
    {
        $employeeId = (int)$this->request->input('id', 0);
        if (!$employeeId) {
            $this->apiError('Employee ID is required', 'INVALID_INPUT', 400);
            return;
        }
        
        $history = EmployeeChangeLogRepository::findByEmployeeId($employeeId);
        $this->apiSuccess($history);
    }

    private function saveEmployee(): void
    {
        $input = $this->getJsonInput();
        if (empty($input)) {
            $this->apiError('Employee data is required', 'INVALID_INPUT', 400);
            return;
        }
        
        try {
            $employeeId = $input['id'] ?? null;
            if ($employeeId) {
                $result = $this->employeeService->updateEmployee((int)$employeeId, $input);
            } else {
                $result = $this->employeeService->createEmployee($input);
            }
            $this->apiSuccess(['new_id' => $result], '직원 정보가 저장되었습니다.');
        } catch (InvalidArgumentException $e) {
            $this->apiError($e->getMessage(), 'INVALID_INPUT', 400);
        } catch (Exception $e) {
            $this->apiError('저장 중 오류가 발생했습니다: ' . $e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    private function deleteEmployee(): void
    {
        $input = $this->getJsonInput();
        $employeeId = (int)($input['id'] ?? 0);
        if (!$employeeId) {
            $this->apiError('Employee ID is required', 'INVALID_INPUT', 400);
            return;
        }
        
        try {
            if ($this->employeeService->deleteEmployee($employeeId)) {
                $this->apiSuccess(null, '직원 정보가 삭제되었습니다.');
            } else {
                $this->apiError('삭제 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
            }
        } catch (InvalidArgumentException $e) {
            $this->apiNotFound($e->getMessage());
        }
    }

    private function approveUpdate(): void
    {
        $input = $this->getJsonInput();
        $employeeId = (int)($input['id'] ?? 0);
        if (!$employeeId) {
            $this->apiError('Employee ID is required', 'INVALID_INPUT', 400);
            return;
        }
        
        if ($this->employeeService->approveProfileUpdate($employeeId)) {
            $this->apiSuccess(null, '프로필 변경사항이 승인되었습니다.');
        } else {
            $this->apiError('프로필 변경 승인에 실패했거나, 처리할 요청이 없습니다.', 'SERVER_ERROR', 500);
        }
    }

    private function rejectUpdate(): void
    {
        $input = $this->getJsonInput();
        $employeeId = (int)($input['id'] ?? 0);
        $reason = trim($input['reason'] ?? '');
        
        if (!$employeeId) {
            $this->apiError('Employee ID is required', 'INVALID_INPUT', 400);
            return;
        }
        if (empty($reason)) {
            $this->apiError('반려 사유를 반드시 입력해야 합니다.', 'INVALID_INPUT', 400);
            return;
        }
        
        if ($this->employeeService->rejectProfileUpdate($employeeId, $reason)) {
            $this->apiSuccess(null, '프로필 변경 요청을 반려 처리했습니다.');
        } else {
            $this->apiError('반려 처리에 실패했습니다.', 'SERVER_ERROR', 500);
        }
    }
}