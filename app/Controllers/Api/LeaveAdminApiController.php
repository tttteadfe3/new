<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\JsonResponse;
use App\Services\AuthService;
use App\Services\LeaveManagementService;
use App\Repositories\LeaveRepository;
use App\Validators\LeaveRequestValidator;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Services\DataScopeService;
use Exception;

class LeaveAdminApiController extends BaseApiController
{
    private LeaveManagementService $leaveManagementService;
    private LeaveRepository $leaveRepository;
    private DataScopeService $dataScopeService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        LeaveManagementService $leaveManagementService,
        LeaveRepository $leaveRepository,
        DataScopeService $dataScopeService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->leaveManagementService = $leaveManagementService;
        $this->leaveRepository = $leaveRepository;
        $this->dataScopeService = $dataScopeService;
    }

    public function getLeaveRequests(): void
    {
        $filters = $this->request->all();
        try {
            $visibleDeptIds = $this->dataScopeService->getVisibleDepartmentIdsForCurrentUser();

            if ($visibleDeptIds !== null) {
                $filters['department_ids'] = $visibleDeptIds;
            }

            $requests = $this->leaveRepository->findRequestsByAdmin($filters);
            $this->jsonResponse->success($requests);
        } catch (Exception $e) {
            $this->jsonResponse->error('신청 목록 조회 중 오류 발생: ' . $e->getMessage(), null, 500);
        }
    }

    public function getLeaveBalances(): void
    {
        $filters = $this->request->all();
        try {
            // 연차 관리 권한이 있는지 확인
            if (!$this->authService->check('leave.manage_entitlement')) {
                // 권한이 없다면, 데이터 스코프 서비스를 통해 볼 수 있는 부서만 필터링
                $visibleDeptIds = $this->dataScopeService->getVisibleDepartmentIdsForCurrentUser();

                // 볼 수 있는 부서가 전혀 없으면 빈 결과를 반환
                if (is_array($visibleDeptIds) && empty($visibleDeptIds)) {
                    $this->jsonResponse->success([]);
                    return;
                }

                if ($visibleDeptIds !== null) {
                    $filters['department_ids'] = $visibleDeptIds;
                }
            }
            // 연차 관리 권한이 있다면 department_ids 필터를 적용하지 않아 모든 직원을 조회

            $balances = $this->leaveRepository->getBalancesByAdmin($filters);
            $this->jsonResponse->success($balances);
        } catch (Exception $e) {
            $this->jsonResponse->error('연차 현황 조회 중 오류 발생: ' . $e->getMessage(), null, 500);
        }
    }

    public function previewGrantAnnualLeave(): void
    {
        $year = (int)$this->request->input('year', date('Y'));
        $departmentId = $this->request->input('department_id') ? (int)$this->request->input('department_id') : null;

        try {
            // 전체 부여 권한이 있는지 확인
            if (!$this->authService->check('leave.manage_entitlement')) {
                // 권한이 없다면, 현재 사용자가 볼 수 있는 부서 목록을 가져옴
                $visibleDeptIds = $this->dataScopeService->getVisibleDepartmentIdsForCurrentUser();

                // departmentId가 지정되었지만 볼 수 없는 부서인 경우, 권한 없음 오류
                if ($departmentId && is_array($visibleDeptIds) && !in_array($departmentId, $visibleDeptIds)) {
                    $this->jsonResponse->forbidden('해당 부서에 대한 조회 권한이 없습니다.');
                    return;
                }

                // departmentId가 지정되지 않은 경우, 담당하는 첫 번째 부서로 자동 설정 (또는 오류 처리)
                if (!$departmentId && is_array($visibleDeptIds) && !empty($visibleDeptIds)) {
                    $departmentId = $visibleDeptIds[0];
                } else if (!$departmentId) {
                     $this->jsonResponse->badRequest('조회할 부서를 선택해야 합니다.');
                     return;
                }
            }
            // 전체 부여 권한이 있다면 departmentId 필터는 사용자의 선택을 그대로 따름 (null일 경우 전체)

            $previewData = $this->leaveManagementService->previewAnnualLeaveGrant($year, $departmentId);
            $this->jsonResponse->success($previewData);
        } catch (Exception $e) {
            $this->jsonResponse->error('연차 부여 미리보기 계산 중 오류 발생: ' . $e->getMessage(), null, 500);
        }
    }

    public function approveRequest(int $id): void
    {
        try {
            $this->leaveManagementService->approveLeaveRequest($id, $this->authService->getCurrentUserId());
            $this->activityLogger->log('leave_request_approved', "Leave request ID: {$id} approved.", $this->authService->getCurrentUserId());
            $this->jsonResponse->success(null, '연차 신청이 승인되었습니다.');
        } catch (Exception $e) {
            $this->jsonResponse->badRequest($e->getMessage());
        }
    }

    public function rejectRequest(int $id): void
    {
        $data = $this->request->all();
        $reason = $data['reason'] ?? '사유 없음';

        try {
            $this->leaveManagementService->rejectLeaveRequest($id, $this->authService->getCurrentUserId(), $reason);
            $this->activityLogger->log('leave_request_rejected', "Leave request ID: {$id} rejected.", $this->authService->getCurrentUserId());
            $this->jsonResponse->success(null, '연차 신청이 반려되었습니다.');
        } catch (Exception $e) {
            $this->jsonResponse->badRequest($e->getMessage());
        }
    }

    public function approveCancellation(int $id): void
    {
        try {
            $this->leaveManagementService->approveCancellationRequest($id, $this->authService->getCurrentUserId());
            $this->activityLogger->log('leave_cancellation_approved', "Leave cancellation for request ID: {$id} approved.", $this->authService->getCurrentUserId());
            $this->jsonResponse->success(null, '연차 취소 요청이 승인되었습니다.');
        } catch (Exception $e) {
            $this->jsonResponse->badRequest($e->getMessage());
        }
    }

    public function rejectCancellation(int $id): void
    {
        try {
            $this->leaveManagementService->rejectCancellationRequest($id, $this->authService->getCurrentUserId());
            $this->activityLogger->log('leave_cancellation_rejected', "Leave cancellation for request ID: {$id} rejected.", $this->authService->getCurrentUserId());
            $this->jsonResponse->success(null, '연차 취소 요청이 반려되었습니다.');
        } catch (Exception $e) {
            $this->jsonResponse->badRequest($e->getMessage());
        }
    }

    public function grantAnnualLeaveForAll(): void
    {
        $data = $this->request->all();
        $year = (int)($data['year'] ?? date('Y'));

        try {
            $result = $this->leaveManagementService->grantAnnualLeaveToAllEmployees($year, $this->authService->getCurrentUserId());
            $this->activityLogger->log('annual_leave_granted', "Annual leave granted for year {$year}.", $this->authService->getCurrentUserId());
            $message = !empty($result['failed_ids'])
                ? "{$year}년 연차 부여가 완료되었습니다. (일부 실패)"
                : "{$year}년 연차 부여가 성공적으로 완료되었습니다.";
            $this->jsonResponse->success($result, $message);
        } catch (Exception $e) {
            $this->jsonResponse->error($e->getMessage(), null, 500);
        }
    }

    public function expireUnusedLeaveForAll(): void
    {
        $data = $this->request->all();
        $year = (int)($data['year'] ?? date('Y') - 1);

        try {
            $result = $this->leaveManagementService->expireUnusedLeaveForAll($year, $this->authService->getCurrentUserId());
            $this->activityLogger->log('unused_leave_expired', "Unused leave expired for year {$year}.", $this->authService->getCurrentUserId());
            $message = !empty($result['failed_ids'])
                ? "{$year}년 미사용 연차 소멸 처리가 완료되었습니다. (일부 실패)"
                : "{$year}년 미사용 연차 소멸 처리가 성공적으로 완료되었습니다.";
            $this->jsonResponse->success($result, $message);
        } catch (Exception $e) {
            $this->jsonResponse->error($e->getMessage(), null, 500);
        }
    }

    public function manualAdjustment(): void
    {
        $data = $this->request->all();
        $employeeId = (int)($data['employee_id'] ?? 0);
        $days = (float)($data['days'] ?? 0);
        $reason = $data['reason'] ?? '';
        $year = (int)($data['year'] ?? date('Y'));

        if (empty($employeeId) || empty($days) || empty($reason)) {
            $this->jsonResponse->badRequest('필수 입력 항목이 누락되었습니다.');
            return;
        }

        try {
            $this->leaveManagementService->manualAdjustment($employeeId, $year, $days, $reason, $this->authService->getCurrentUserId());
            $this->activityLogger->log('manual_leave_adjustment', "Manual leave adjustment for employee ID: {$employeeId}, Days: {$days}, Reason: {$reason}", $this->authService->getCurrentUserId());
            $this->jsonResponse->success(null, '연차 조정이 성공적으로 완료되었습니다.');
        } catch (Exception $e) {
            $this->jsonResponse->error($e->getMessage(), null, 500);
        }
    }
}
