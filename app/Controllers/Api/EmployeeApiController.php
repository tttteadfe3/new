<?php

namespace App\Controllers\Api;

use App\Repositories\EmployeeRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
use App\Services\EmployeeManager;
use App\Repositories\EmployeeChangeLogRepository;

class EmployeeApiController extends BaseApiController
{
    private EmployeeManager $employeeManager;

    public function __construct()
    {
        parent::__construct();
        $this->employeeManager = new EmployeeManager();
    }

    /**
     * Handle all employee API requests based on action parameter
     */
    public function index(): void
    {
        $this->requireAuth('employee_admin');
        
        $action = $this->getAction();
        
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
                    $this->apiBadRequest('Invalid action');
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get initial data for employee management page
     */
    private function getInitialData(): void
    {
        $filters = [
            'status' => $_GET['status'] ?? null
        ];
        
        $employees = EmployeeRepository::getAll(array_filter($filters));
        $departments = DepartmentRepository::getAll();
        $positions = PositionRepository::getAll();
        
        $this->apiSuccess([
            'employees' => $employees,
            'departments' => $departments,
            'positions' => $positions
        ]);
    }

    /**
     * Get filtered employee list
     */
    private function getEmployeeList(): void
    {
        $filters = [
            'department_id' => $_GET['department_id'] ?? null,
            'position_id' => $_GET['position_id'] ?? null,
            'status' => $_GET['status'] ?? null
        ];
        
        $employees = EmployeeRepository::getAll(array_filter($filters));
        $this->apiSuccess($employees);
    }

    /**
     * Get single employee by ID
     */
    private function getEmployee(): void
    {
        $employeeId = (int)($_GET['id'] ?? 0);
        
        if (!$employeeId) {
            $this->apiBadRequest('Employee ID is required');
            return;
        }
        
        $employee = EmployeeRepository::findById($employeeId);
        
        if ($employee) {
            $this->apiSuccess($employee);
        } else {
            $this->apiNotFound('Employee not found');
        }
    }

    /**
     * Get employee change history
     */
    private function getChangeHistory(): void
    {
        $employeeId = (int)($_GET['id'] ?? 0);
        
        if (!$employeeId) {
            $this->apiBadRequest('Employee ID is required');
            return;
        }
        
        $history = EmployeeChangeLogRepository::findByEmployeeId($employeeId);
        $this->apiSuccess($history);
    }

    /**
     * Save employee (create or update)
     */
    private function saveEmployee(): void
    {
        $input = $this->getJsonInput();
        
        if (empty($input)) {
            $this->apiBadRequest('Employee data is required');
            return;
        }
        
        $result = $this->employeeManager->save($input);
        
        if ($result) {
            $this->apiSuccess(['new_id' => $result], '직원 정보가 저장되었습니다.');
        } else {
            $this->apiError('저장 중 오류가 발생했습니다.');
        }
    }

    /**
     * Delete employee
     */
    private function deleteEmployee(): void
    {
        $input = $this->getJsonInput();
        $employeeId = (int)($input['id'] ?? 0);
        
        if (!$employeeId) {
            $this->apiBadRequest('Employee ID is required');
            return;
        }
        
        if ($this->employeeManager->remove($employeeId)) {
            $this->apiSuccess(null, '직원 정보가 삭제되었습니다.');
        } else {
            $this->apiError('삭제 중 오류가 발생했습니다.');
        }
    }

    /**
     * Approve employee profile update
     */
    private function approveUpdate(): void
    {
        $input = $this->getJsonInput();
        $employeeId = (int)($input['id'] ?? 0);
        
        if (!$employeeId) {
            $this->apiBadRequest('Employee ID is required');
            return;
        }
        
        if ($this->employeeManager->approveProfileUpdate($employeeId)) {
            $this->apiSuccess(null, '프로필 변경사항이 승인되었습니다.');
        } else {
            $this->apiError('프로필 변경 승인에 실패했거나, 처리할 요청이 없습니다.');
        }
    }

    /**
     * Reject employee profile update
     */
    private function rejectUpdate(): void
    {
        $input = $this->getJsonInput();
        $employeeId = (int)($input['id'] ?? 0);
        $reason = trim($input['reason'] ?? '');
        
        if (!$employeeId) {
            $this->apiBadRequest('Employee ID is required');
            return;
        }
        
        if (empty($reason)) {
            $this->apiBadRequest('반려 사유를 반드시 입력해야 합니다.');
            return;
        }
        
        if (EmployeeRepository::rejectProfileUpdate($employeeId, $reason)) {
            $this->apiSuccess(null, '프로필 변경 요청을 반려 처리했습니다.');
        } else {
            $this->apiError('반려 처리에 실패했습니다.');
        }
    }
}