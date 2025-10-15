<?php

namespace App\Controllers\Api;

use App\Services\LeaveService;
use App\Repositories\LeaveRepository;
use App\Repositories\EmployeeRepository;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\JsonResponse;

class LeaveAdminApiController extends BaseApiController
{
    private LeaveService $leaveService;
    private LeaveRepository $leaveRepository;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        LeaveService $leaveService,
        LeaveRepository $leaveRepository
    ) {
        parent::__construct(
            $request,
            $authService,
            $viewDataService,
            $activityLogger,
            $employeeRepository,
            $jsonResponse
        );
        $this->leaveService = $leaveService;
        $this->leaveRepository = $leaveRepository;
    }

    /**
     * List leave requests by status.
     * Corresponds to GET /api/leaves_admin/requests
     */
    public function listRequests(): void
    {
        try {
            // This now uses the service layer, which correctly handles permissions.
            // The service method defaults to 'pending' and is specifically for the approval page's needs.
            $data = $this->leaveService->getPendingLeaveRequests();
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Approve a leave request.
     * Corresponds to POST /api/leaves_admin/requests/{id}/approve
     */
    public function approveRequest(int $id): void
    {
        $adminId = $this->user()['id'];

        try {
            [$success, $message] = $this->leaveService->approveRequest($id, $adminId);
            if ($success) {
                $this->apiSuccess(null, $message);
            } else {
                $this->apiError($message, 'OPERATION_FAILED');
            }
        } catch (Exception $e) {
            $this->apiError('승인 처리 중 오류 발생', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Reject a leave request.
     * Corresponds to POST /api/leaves_admin/requests/{id}/reject
     */
    public function rejectRequest(int $id): void
    {
        $adminId = $this->user()['id'];
        $reason = $this->request->input('reason');

        if (empty($reason)) {
            $this->apiError('반려 사유는 필수입니다.', 'VALIDATION_ERROR', 422);
            return;
        }

        try {
            [$success, $message] = $this->leaveService->rejectRequest($id, $adminId, $reason);
            if ($success) {
                $this->apiSuccess(null, $message);
            } else {
                $this->apiError($message, 'OPERATION_FAILED');
            }
        } catch (Exception $e) {
            $this->apiError('반려 처리 중 오류 발생', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Approve a leave cancellation request.
     * Corresponds to POST /api/leaves_admin/cancellations/{id}/approve
     */
    public function approveCancellation(int $id): void
    {
        $adminId = $this->user()['id'];

        try {
            [$success, $message] = $this->leaveService->approveCancellation($id, $adminId);
            if ($success) {
                $this->apiSuccess(null, $message);
            } else {
                $this->apiError($message, 'OPERATION_FAILED');
            }
        } catch (Exception $e) {
            $this->apiError('취소 승인 처리 중 오류 발생', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Reject a leave cancellation request.
     * Corresponds to POST /api/leaves_admin/cancellations/{id}/reject
     */
    public function rejectCancellation(int $id): void
    {
        $adminId = $this->user()['id'];
        $reason = $this->request->input('reason');
        
        if (empty($reason)) {
            $this->apiError('반려 사유는 필수입니다.', 'VALIDATION_ERROR', 422);
            return;
        }

        try {
            [$success, $message] = $this->leaveService->rejectCancellation($id, $adminId, $reason);
            if ($success) {
                $this->apiSuccess(null, $message);
            } else {
                $this->apiError($message, 'OPERATION_FAILED');
            }
        } catch (Exception $e) {
            $this->apiError('취소 반려 처리 중 오류 발생', 'SERVER_ERROR', 500);
        }
    }

    /**
     * List all employee leave entitlements.
     * Corresponds to GET /api/leaves_admin/entitlements
     */
    public function listEntitlements(): void
    {
        $filters = [
            'year' => $this->request->input('year', date('Y')),
            'department_id' => $this->request->input('department_id')
        ];
        
        try {
            // Use the service layer which applies visibility rules
            $data = $this->leaveService->getAllEntitlements(array_filter($filters));
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->apiError('연차 부여 내역 조회 중 오류 발생', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Grant annual leave for all employees for a specific year.
     * Corresponds to POST /api/leaves_admin/grant-all
     */
    public function grantForAll(): void
    {
        $year = (int)$this->request->input('year', date('Y'));

        try {
            [$success, $message] = $this->leaveService->grantAnnualLeaveForAllEmployees($year);
            if ($success) {
                $this->apiSuccess(null, $message);
            } else {
                $this->apiError($message, 'OPERATION_FAILED');
            }
        } catch (Exception $e) {
            $this->apiError('전체 연차 부여 중 오류 발생', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Get leave history for all employees with filters.
     * Corresponds to GET /api/leaves_admin/history
     */
    public function history(): void
    {
        try {
            $filters = [
                'year' => $this->request->input('year', date('Y')),
                'department_id' => $this->request->input('department_id'),
                'status' => $this->request->input('status')
            ];
            // Remove any empty filters
            $filters = array_filter($filters);

            $data = $this->leaveService->getLeaveHistory($filters);
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get leave history for a specific employee.
     * Corresponds to GET /api/leaves_admin/history/{employeeId}
     */
    public function getHistoryForEmployee(int $employeeId): void
    {
        $year = (int)$this->request->input('year', date('Y'));

        try {
            $entitlement = $this->leaveRepository->findEntitlement($employeeId, $year);
            $leaves = $this->leaveRepository->findByEmployeeId($employeeId, ['year' => $year]);

            $this->apiSuccess([
                'entitlement' => $entitlement,
                'leaves' => $leaves
            ]);
        } catch (Exception $e) {
            $this->apiError('연차 내역 조회 중 오류 발생', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Manually adjust leave entitlement for an employee.
     * Corresponds to POST /api/leaves_admin/adjust
     */
    public function manualAdjustment(): void
    {
        $adminId = $this->user()['id'];
        
        $data = $this->getJsonInput();
        $employeeId = (int)($data['employee_id'] ?? 0);
        $year = (int)($data['year'] ?? date('Y'));
        $adjustedDays = (float)($data['adjustment_days'] ?? 0);
        $reason = trim($data['reason'] ?? '');
        
        if (!$employeeId || empty($reason)) {
            $this->apiError('필수 입력값이 누락되었습니다.', 'VALIDATION_ERROR', 422);
            return;
        }
        
        try {
            if ($this->leaveService->adjustLeaveEntitlement($employeeId, $year, $adjustedDays, $reason, $adminId)) {
                $this->apiSuccess(null, "연차 조정이 완료되었습니다.");
            } else {
                $this->apiError("연차 조정 처리 중 오류가 발생했습니다.", 'OPERATION_FAILED');
            }
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'SERVER_ERROR');
        }
    }

    /**
     * Calculate leaves for employees based on filters.
     * Corresponds to POST /api/leaves_admin/calculate
     */
    public function calculateLeaves(): void
    {
        
        $data = $this->getJsonInput();
        $year = (int)($data['year'] ?? date('Y'));
        $department_id = $data['department_id'] ?? null;
        
        try {
            // Use the service layer which applies visibility rules
            $employees = $this->leaveService->getEmployeesForLeaveCalculation($department_id);

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
        } catch (Exception $e) {
            $this->apiError('연차 계산 중 오류 발생', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Save calculated leave entitlements for multiple employees.
     * Corresponds to POST /api/leaves_admin/save-entitlements
     */
    public function saveEntitlements(): void
    {
        
        $data = $this->getJsonInput();
        $employees_data = $data['employees'] ?? [];
        $year = (int)($data['year'] ?? date('Y'));
        
        $success_count = 0;
        $failed_count = 0;
        $errors = [];
        
        foreach ($employees_data as $employee) {
            try {
                $this->leaveService->grantCalculatedAnnualLeave((int)$employee['id'], $year);
                $success_count++;
            } catch (\Exception $e) {
                $failed_count++;
                $errors[] = ($employee['name'] ?? $employee['id']) . ": " . $e->getMessage();
            }
        }

        $message = "총 {$success_count}명의 연차 부여를 완료했습니다.";
        if ($failed_count > 0) {
            $this->apiError("{$failed_count}명 실패. 오류: " . implode(', ', $errors), 'PARTIAL_SUCCESS');
        } else {
            $this->apiSuccess(null, $message);
        }
    }
}
