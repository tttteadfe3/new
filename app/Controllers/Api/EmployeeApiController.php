<?php

namespace App\Controllers\Api;

use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
use App\Services\EmployeeService;
use App\Repositories\EmployeeChangeLogRepository;
use Exception;

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
     * This is a non-RESTful approach that we are refactoring to be more robust.
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
                    $this->error('Invalid action specified.', [], 400);
            }
        } catch (Exception $e) {
            $this->error('An unexpected error occurred: ' . $e->getMessage(), [], 500);
        }
    }

    private function getInitialData(): void
    {
        $filters = ['status' => $this->request->input('status')];
        
        $employees = $this->employeeService->getAllEmployees(array_filter($filters));
        $departments = DepartmentRepository::getAll();
        $positions = PositionRepository::getAll();
        
        $this->success([
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
        $this->success($employees);
    }

    private function getEmployee(): void
    {
        $employeeId = (int)$this->request->input('id', 0);
        if (!$employeeId) {
            $this->error('Employee ID is required', [], 400);
            return;
        }
        
        $employee = $this->employeeService->getEmployee($employeeId);
        if ($employee) {
            $this->success($employee);
        } else {
            $this->notFound('Employee not found');
        }
    }

    private function getChangeHistory(): void
    {
        $employeeId = (int)$this->request->input('id', 0);
        if (!$employeeId) {
            $this->error('Employee ID is required', [], 400);
            return;
        }
        
        $history = EmployeeChangeLogRepository::findByEmployeeId($employeeId);
        $this->success($history);
    }

    private function saveEmployee(): void
    {
        $input = $this->getJsonInput();
        if (empty($input)) {
            $this->error('Employee data is required', [], 400);
            return;
        }
        
        try {
            $employeeId = $input['id'] ?? null;
            if ($employeeId) {
                $result = $this->employeeService->updateEmployee((int)$employeeId, $input);
            } else {
                $result = $this->employeeService->createEmployee($input);
            }
            $this->success(['new_id' => $result], '직원 정보가 저장되었습니다.');
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage(), [], 400);
        } catch (Exception $e) {
            $this->error('저장 중 오류가 발생했습니다: ' . $e->getMessage(), [], 500);
        }
    }

    private function deleteEmployee(): void
    {
        $input = $this->getJsonInput();
        $employeeId = (int)($input['id'] ?? 0);
        if (!$employeeId) {
            $this->error('Employee ID is required', [], 400);
            return;
        }
        
        try {
            if ($this->employeeService->deleteEmployee($employeeId)) {
                $this->success(null, '직원 정보가 삭제되었습니다.');
            } else {
                $this->error('삭제 중 오류가 발생했습니다.', [], 500);
            }
        } catch (InvalidArgumentException $e) {
            $this->notFound($e->getMessage());
        }
    }

    private function approveUpdate(): void
    {
        $input = $this->getJsonInput();
        $employeeId = (int)($input['id'] ?? 0);
        if (!$employeeId) {
            $this->error('Employee ID is required', [], 400);
            return;
        }
        
        if ($this->employeeService->approveProfileUpdate($employeeId)) {
            $this->success(null, '프로필 변경사항이 승인되었습니다.');
        } else {
            $this->error('프로필 변경 승인에 실패했거나, 처리할 요청이 없습니다.', [], 500);
        }
    }

    private function rejectUpdate(): void
    {
        $input = $this->getJsonInput();
        $employeeId = (int)($input['id'] ?? 0);
        $reason = trim($input['reason'] ?? '');
        
        if (!$employeeId) {
            $this->error('Employee ID is required', [], 400);
            return;
        }
        if (empty($reason)) {
            $this->error('반려 사유를 반드시 입력해야 합니다.', [], 400);
            return;
        }
        
        if ($this->employeeService->rejectProfileUpdate($employeeId, $reason)) {
            $this->success(null, '프로필 변경 요청을 반려 처리했습니다.');
        } else {
            $this->error('반려 처리에 실패했습니다.', [], 500);
        }
    }

    private function getJsonInput(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}