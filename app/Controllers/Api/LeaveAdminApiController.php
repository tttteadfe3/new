<?php

namespace App\Controllers\Api;

use App\Services\LeaveService;
use App\Repositories\LeaveRepository;
use App\Repositories\EmployeeRepository;
use App\Core\SessionManager;

class LeaveAdminApiController extends BaseApiController
{
    private LeaveService $leaveService;

    public function __construct()
    {
        parent::__construct();
        $this->leaveService = new LeaveService();
    }

    /**
     * Handle all leave admin API requests based on action parameter
     */
    public function index(): void
    {
        $this->requireAuth('leave_admin');
        
        $action = $this->getAction();
        
        try {
            switch ($action) {
                case 'list_entitlements':
                    $this->listEntitlements();
                    break;
                case 'calculate_leaves':
                    $this->calculateLeaves();
                    break;
                case 'save_leaves':
                    $this->saveLeaves();
                    break;
                case 'calculate_and_grant':
                    $this->calculateAndGrant();
                    break;
                case 'list_requests':
                    $this->listRequests();
                    break;
                case 'approve_request':
                    $this->approveRequest();
                    break;
                case 'reject_request':
                    $this->rejectRequest();
                    break;
                case 'approve_cancellation':
                    $this->approveCancellation();
                    break;
                case 'reject_cancellation':
                    $this->rejectCancellation();
                    break;
                case 'get_history':
                    $this->getHistory();
                    break;
                case 'grant_for_all':
                    $this->grantForAll();
                    break;
                case 'manual_adjustment':
                    $this->manualAdjustment();
                    break;
                default:
                    $this->apiBadRequest('Invalid action');
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * List all employee leave entitlements
     */
    private function listEntitlements(): void
    {
        $filters = [
            'year' => $_GET['year'] ?? date('Y'),
            'department_id' => $_GET['department_id'] ?? null
        ];
        
        $data = LeaveRepository::getAllEntitlements($filters);
        
        // 각 직원에 대해 연차 계산 상세 내역을 추가
        foreach ($data as &$row) {
            if (!empty($row['hire_date'])) {
                $row['leave_breakdown'] = $this->leaveService->calculateAnnualLeaveDays($row['hire_date'], $filters['year']);
            } else {
                $row['leave_breakdown'] = null;
            }
        }
        unset($row);
        
        $this->apiSuccess($data);
    }

    /**
     * Calculate leaves for employees
     */
    private function calculateLeaves(): void
    {
        $input = $this->getJsonInput();
        $year = (int)($input['year'] ?? date('Y'));
        $department_id = $input['department_id'] ?? null;
        
        $employeeFilters = ['status' => 'active'];
        if ($department_id) {
            $employeeFilters['department_id'] = $department_id;
        }
        
        $employees = EmployeeRepository::getAll($employeeFilters);
        
        $results = [];
        foreach ($employees as $employee) {
            if (empty($employee['hire_date'])) {
                $employee['leave_data'] = null;
            } else {
                $employee['leave_data'] = $this->leaveService->calculateAnnualLeaveDays($employee['hire_date'], $year);
            }
            $results[] = $employee;
        }
        
        $this->apiSuccess($results);
    }

    /**
     * Save leaves for multiple employees
     */
    private function saveLeaves(): void
    {
        $input = $this->getJsonInput();
        $employees_data = $input['employees'] ?? [];
        $year = (int)($input['year'] ?? date('Y'));
        
        $success_count = 0;
        $failed_count = 0;
        $errors = [];
        
        foreach ($employees_data as $employee) {
            try {
                $this->leaveService->grantCalculatedAnnualLeave((int)$employee['id'], $year);
                $success_count++;
            } catch (\Exception $e) {
                $failed_count++;
                $errors[] = "{$employee['name']}: " . $e->getMessage();
            }
        }
        
        $message = "총 {$success_count}명의 연차 부여를 완료했습니다.";
        if ($failed_count > 0) {
            $this->apiError("{$failed_count}명 실패. 오류: " . implode(', ', $errors));
        } else {
            $this->apiSuccess(null, $message);
        }
    }

    /**
     * Calculate and grant leave for a single employee
     */
    private function calculateAndGrant(): void
    {
        $input = $this->getJsonInput();
        $employeeId = (int)($input['employee_id'] ?? 0);
        $year = (int)($input['year'] ?? date('Y'));
        
        if (!$employeeId) {
            $this->apiBadRequest('Employee ID is required');
            return;
        }
        
        if ($this->leaveService->grantCalculatedAnnualLeave($employeeId, $year)) {
            $this->apiSuccess(null, "연차 계산 및 부여가 완료되었습니다.");
        } else {
            $this->apiError("연차 부여 처리 중 오류가 발생했습니다.");
        }
    }

    /**
     * List leave requests by status
     */
    private function listRequests(): void
    {
        $status = $_GET['status'] ?? 'pending';
        
        if ($status === 'cancellation') {
            $data = LeaveRepository::getAll(['status' => 'cancellation_requested']);
        } else {
            $data = LeaveRepository::getAll(['status' => $status]);
        }
        
        $this->apiSuccess($data);
    }

    /**
     * Approve a leave request
     */
    private function approveRequest(): void
    {
        $input = $this->getJsonInput();
        $leaveId = (int)($input['id'] ?? 0);
        $adminId = SessionManager::get('user')['id'];
        
        if (!$leaveId) {
            $this->apiBadRequest('Leave ID is required');
            return;
        }
        
        [$success, $message] = $this->leaveService->approveRequest($leaveId, $adminId);
        
        if ($success) {
            $this->apiSuccess(null, $message);
        } else {
            $this->apiError($message);
        }
    }

    /**
     * Reject a leave request
     */
    private function rejectRequest(): void
    {
        $input = $this->getJsonInput();
        $leaveId = (int)($input['id'] ?? 0);
        $reason = trim($input['reason'] ?? '');
        $adminId = SessionManager::get('user')['id'];
        
        if (!$leaveId) {
            $this->apiBadRequest('Leave ID is required');
            return;
        }
        
        if (empty($reason)) {
            $this->apiBadRequest('반려 사유를 입력해야 합니다.');
            return;
        }
        
        [$success, $message] = $this->leaveService->rejectRequest($leaveId, $adminId, $reason);
        
        if ($success) {
            $this->apiSuccess(null, $message);
        } else {
            $this->apiError($message);
        }
    }

    /**
     * Approve a leave cancellation request
     */
    private function approveCancellation(): void
    {
        $input = $this->getJsonInput();
        $leaveId = (int)($input['id'] ?? 0);
        $adminId = SessionManager::get('user')['id'];
        
        if (!$leaveId) {
            $this->apiBadRequest('Leave ID is required');
            return;
        }
        
        [$success, $message] = $this->leaveService->approveCancellation($leaveId, $adminId);
        
        if ($success) {
            $this->apiSuccess(null, $message);
        } else {
            $this->apiError($message);
        }
    }

    /**
     * Reject a leave cancellation request
     */
    private function rejectCancellation(): void
    {
        $input = $this->getJsonInput();
        $leaveId = (int)($input['id'] ?? 0);
        $reason = trim($input['reason'] ?? '');
        $adminId = SessionManager::get('user')['id'];
        
        if (!$leaveId) {
            $this->apiBadRequest('Leave ID is required');
            return;
        }
        
        if (empty($reason)) {
            $this->apiBadRequest('취소 요청 반려 사유를 입력해야 합니다.');
            return;
        }
        
        [$success, $message] = $this->leaveService->rejectCancellation($leaveId, $adminId, $reason);
        
        if ($success) {
            $this->apiSuccess(null, $message);
        } else {
            $this->apiError($message);
        }
    }

    /**
     * Get leave history for an employee
     */
    private function getHistory(): void
    {
        $employeeId = (int)($_GET['employee_id'] ?? 0);
        $year = (int)($_GET['year'] ?? date('Y'));
        
        if (!$employeeId) {
            $this->apiBadRequest('Employee ID is required');
            return;
        }
        
        $entitlement = LeaveRepository::findEntitlement($employeeId, $year);
        $leaves = LeaveRepository::findByEmployeeId($employeeId, ['year' => $year]);
        
        $this->apiSuccess([
            'entitlement' => $entitlement,
            'leaves' => $leaves
        ]);
    }

    /**
     * Grant annual leave for all employees
     */
    private function grantForAll(): void
    {
        $input = $this->getJsonInput();
        $year = (int)($input['year'] ?? date('Y'));
        
        [$success, $message] = $this->leaveService->grantAnnualLeaveForAllEmployees($year);
        
        if ($success) {
            $this->apiSuccess(null, $message);
        } else {
            $this->apiError($message);
        }
    }

    /**
     * Manual adjustment of leave entitlement
     */
    private function manualAdjustment(): void
    {
        $input = $this->getJsonInput();
        $employeeId = (int)($input['employee_id'] ?? 0);
        $year = (int)($input['year'] ?? date('Y'));
        $adjustedDays = (float)($input['adjustment_days'] ?? 0);
        $reason = trim($input['reason'] ?? '');
        $adminId = SessionManager::get('user')['id'];
        
        if (!$employeeId) {
            $this->apiBadRequest('Employee ID is required');
            return;
        }
        
        if (empty($reason)) {
            $this->apiBadRequest('Adjustment reason is required');
            return;
        }
        
        try {
            if ($this->leaveService->adjustLeaveEntitlement($employeeId, $year, $adjustedDays, $reason, $adminId)) {
                $this->apiSuccess(null, "연차 조정이 완료되었습니다.");
            } else {
                $this->apiError("연차 조정 처리 중 오류가 발생했습니다.");
            }
        } catch (\Exception $e) {
            $this->apiError($e->getMessage());
        }
    }
}