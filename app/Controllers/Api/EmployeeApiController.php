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
     * Get a list of all employees based on filters.
     */
    public function index(): void
    {
        try {
            $filters = [
                'department_id' => $this->request->input('department_id'),
                'position_id' => $this->request->input('position_id'),
                'status' => $this->request->input('status')
            ];

            $employees = $this->employeeService->getAllEmployees(array_filter($filters));
            $this->apiSuccess($employees);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get initial data needed for the employee management UI.
     */
    public function getInitialData(): void
    {
        try {
            $departments = $this->departmentRepository->getAll();
            $positions = $this->positionRepository->getAll();

            $this->apiSuccess([
                'departments' => $departments,
                'positions' => $positions
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get a single employee by their ID.
     */
    public function show(int $id): void
    {
        try {
            $employee = $this->employeeService->getEmployee($id);
            if ($employee) {
                $this->apiSuccess($employee);
            } else {
                $this->apiNotFound('Employee not found');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Create a new employee.
     */
    public function store(): void
    {
        try {
            $input = $this->getJsonInput();
            if (empty($input)) {
                $this->apiBadRequest('Employee data is required');
                return;
            }
            $result = $this->employeeService->createEmployee($input);
            $this->apiSuccess(['new_id' => $result], '직원 정보가 생성되었습니다.', 201);
        } catch (InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Update an existing employee.
     */
    public function update(int $id): void
    {
        try {
            $input = $this->getJsonInput();
            if (empty($input)) {
                $this->apiBadRequest('Employee data is required');
                return;
            }
            $result = $this->employeeService->updateEmployee($id, $input);
            $this->apiSuccess(['id' => $result], '직원 정보가 저장되었습니다.');
        } catch (InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Delete an employee.
     */
    public function destroy(int $id): void
    {
        try {
            if ($this->employeeService->deleteEmployee($id)) {
                $this->apiSuccess(null, '직원 정보가 삭제되었습니다.');
            } else {
                $this->apiError('삭제 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
            }
        } catch (InvalidArgumentException $e) {
            $this->apiNotFound($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get the change history for a specific employee.
     */
    public function getChangeHistory(int $id): void
    {
        try {
            $history = $this->employeeChangeLogRepository->findByEmployeeId($id);
            $this->apiSuccess($history);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get employees not linked to any user account.
     */
    public function unlinked(): void
    {
        try {
            $unlinkedEmployees = $this->employeeService->getUnlinkedEmployees();
            $this->apiSuccess($unlinkedEmployees);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Approve a profile update request.
     */
    public function approveUpdate(int $id): void
    {
        if (empty($id)) {
            $this->apiBadRequest('Employee ID is required.');
            return;
        }
        
        try {
            if ($this->employeeService->approveProfileUpdate($id)) {
                $this->apiSuccess(['message' => '프로필 변경사항이 승인되었습니다.']);
            } else {
                $this->apiError('프로필 변경 승인에 실패했거나, 처리할 요청이 없습니다.', 'UPDATE_FAILED', 500);
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Reject a profile update request.
     */
    public function rejectUpdate(int $id): void
    {
        if (empty($id)) {
            $this->apiBadRequest('Employee ID is required.');
            return;
        }

        $input = $this->getJsonInput();
        $reason = trim($input['reason'] ?? '');
        
        if (empty($reason)) {
            $this->apiBadRequest('반려 사유를 반드시 입력해야 합니다.');
            return;
        }
        
        try {
            if ($this->employeeService->rejectProfileUpdate($id, $reason)) {
                $this->apiSuccess(['message' => '프로필 변경 요청을 반려 처리했습니다.']);
            } else {
                $this->apiError('반려 처리에 실패했습니다.', 'UPDATE_FAILED', 500);
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}