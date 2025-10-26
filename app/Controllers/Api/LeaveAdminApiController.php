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
     * 상태별 휴가 요청을 나열합니다.
     * GET /api/leaves_admin/requests에 해당합니다.
     */
    public function listRequests(): void
    {
        try {
            // 이제 서비스 계층을 사용하며, 이는 권한을 올바르게 처리합니다.
            // 서비스 메서드는 기본적으로 'pending'이며 승인 페이지의 요구 사항에 맞게 특별히 제작되었습니다.
            $data = $this->leaveService->getPendingLeaveRequests();
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 휴가 요청을 승인합니다.
     * POST /api/leaves_admin/requests/{id}/approve에 해당합니다.
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
     * 휴가 요청을 거부합니다.
     * POST /api/leaves_admin/requests/{id}/reject에 해당합니다.
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
     * 휴가 취소 요청을 승인합니다.
     * POST /api/leaves_admin/cancellations/{id}/approve에 해당합니다.
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
     * 휴가 취소 요청을 거부합니다.
     * POST /api/leaves_admin/cancellations/{id}/reject에 해당합니다.
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
     * 모든 직원의 휴가 부여 내역을 나열합니다.
     * GET /api/leaves_admin/entitlements에 해당합니다.
     */
    public function listEntitlements(): void
    {
        $filters = [
            'year' => $this->request->input('year', date('Y')),
        ];
        
        try {
            $data = $this->leaveService->getAllEntitlements(array_filter($filters));
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->apiError('연차 부여 내역 조회 중 오류 발생', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 특정 연도에 모든 직원에 대해 연차 휴가를 부여합니다.
     * POST /api/leaves_admin/grant-all에 해당합니다.
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
     * 필터가 적용된 모든 직원의 휴가 내역을 가져옵니다.
     * GET /api/leaves_admin/history에 해당합니다.
     */
    public function history(): void
    {
        try {
            $filters = [
                'year' => $this->request->input('year', date('Y')),
                'department_id' => $this->request->input('department_id'),
                'status' => $this->request->input('status')
            ];
            // 빈 필터 제거
            $filters = array_filter($filters);

            $data = $this->leaveService->getLeaveHistory($filters);
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 직원의 휴가 내역을 가져옵니다.
     * GET /api/leaves_admin/history/{employeeId}에 해당합니다.
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
     * 직원의 휴가 부여 내역을 수동으로 조정합니다.
     * POST /api/leaves_admin/adjust에 해당합니다.
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
     * 필터를 기반으로 직원의 휴가를 계산합니다.
     * POST /api/leaves_admin/calculate에 해당합니다.
     */
    public function calculateLeaves(): void
    {
        
        $data = $this->getJsonInput();
        $year = (int)($data['year'] ?? date('Y'));
        $department_id = $data['department_id'] ?? null;
        
        $employeeFilters = ['status' => 'active'];
        if ($department_id) {
            $employeeFilters['department_id'] = $department_id;
        }

        try {
            $employees = $this->employeeRepository->getAll($employeeFilters);

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
     * 여러 직원에 대해 계산된 휴가 부여 내역을 저장합니다.
     * POST /api/leaves_admin/save-entitlements에 해당합니다.
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
