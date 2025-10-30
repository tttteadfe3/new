<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\JsonResponse;
use App\Services\AuthService;
use App\Services\LeaveManagementService; // 새로운 ManagementService 사용
use App\Repositories\LeaveRepository;

class LeaveAdminApiController extends BaseApiController
{
    private LeaveManagementService $leaveManagementService;
    private LeaveRepository $leaveRepository;

    public function __construct(
        Request $request,
        AuthService $authService,
        JsonResponse $jsonResponse,
        LeaveManagementService $leaveManagementService,
        LeaveRepository $leaveRepository
    ) {
        parent::__construct($request, $authService, $jsonResponse);
        $this->leaveManagementService = $leaveManagementService;
        $this->leaveRepository = $leaveRepository;
    }

    /**
     * 관리자가 연차 신청 목록을 조회합니다.
     * GET /api/admin/leaves/requests
     */
    public function getLeaveRequests(): void
    {
        $filters = $this->request->all(); // status, department_id, year 등 필터링

        try {
            // TODO: 현재 관리자가 조회 가능한 부서 ID 목록을 가져오는 로직 추가
            // $visibleDeptIds = $this->authService->getVisibleDepartmentIds();
            // $filters['department_ids'] = $visibleDeptIds;

            $requests = $this->leaveRepository->findRequestsByAdmin($filters);
            $this->jsonResponse->send(['success' => true, 'data' => $requests]);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '신청 목록 조회 중 오류 발생'], 500);
        }
    }

    /**
     * 연차 신청을 승인합니다.
     * POST /api/admin/leaves/requests/{id}/approve
     */
    public function approveRequest(int $id): void
    {
        $adminEmployeeId = $this->authService->getCurrentEmployeeId();

        try {
            $success = $this->leaveManagementService->approveLeaveRequest($id, $adminEmployeeId);
            if ($success) {
                $this->jsonResponse->send(['success' => true, 'message' => '연차 신청을 승인했습니다.']);
            } else {
                $this->jsonResponse->send(['success' => false, 'message' => '연차 승인에 실패했습니다. 이미 처리되었거나 유효하지 않은 요청입니다.'], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 연차 신청을 반려합니다.
     * POST /api/admin/leaves/requests/{id}/reject
     */
    public function rejectRequest(int $id): void
    {
        $adminEmployeeId = $this->authService->getCurrentEmployeeId();
        $data = $this->request->getJsonRawBody();
        $reason = $data['reason'] ?? '';

        if (empty($reason)) {
            $this->jsonResponse->send(['success' => false, 'message' => '반려 사유는 필수입니다.'], 422);
            return;
        }

        try {
            $success = $this->leaveManagementService->rejectLeaveRequest($id, $adminEmployeeId, $reason);
            if ($success) {
                $this->jsonResponse->send(['success' => true, 'message' => '연차 신청을 반려했습니다.']);
            } else {
                $this->jsonResponse->send(['success' => false, 'message' => '연차 반려에 실패했습니다.'], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 연차 취소 요청을 승인합니다.
     * POST /api/admin/leaves/requests/{id}/approve-cancellation
     */
    public function approveCancellation(int $id): void
    {
        $adminEmployeeId = $this->authService->getCurrentEmployeeId();

        try {
            $success = $this->leaveManagementService->approveCancellationRequest($id, $adminEmployeeId);
            if ($success) {
                $this->jsonResponse->send(['success' => true, 'message' => '연차 취소 요청을 승인했습니다.']);
            } else {
                $this->jsonResponse->send(['success' => false, 'message' => '취소 승인에 실패했습니다.'], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 연차 취소 요청을 반려합니다.
     * POST /api/admin/leaves/requests/{id}/reject-cancellation
     */
    public function rejectCancellation(int $id): void
    {
        $adminEmployeeId = $this->authService->getCurrentEmployeeId();
        
        try {
            $success = $this->leaveManagementService->rejectCancellationRequest($id, $adminEmployeeId);
            if ($success) {
                $this->jsonResponse->send(['success' => true, 'message' => '연차 취소 요청을 반려했습니다.']);
            } else {
                $this->jsonResponse->send(['success' => false, 'message' => '취소 반려에 실패했습니다.'], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 연초, 모든 직원에게 연차를 일괄 부여합니다.
     * POST /api/admin/leaves/grant-annual
     */
    public function grantAnnualLeaveForAll(): void
    {
        $adminEmployeeId = $this->authService->getCurrentEmployeeId();
        $data = $this->request->getJsonRawBody();
        $year = $data['year'] ?? (int)date('Y');

        try {
            $result = $this->leaveManagementService->grantAnnualLeaveToAllEmployees($year, $adminEmployeeId);
            if (empty($result['failed_ids'])) {
                $this->jsonResponse->send(['success' => true, 'message' => "{$year}년 연차 일괄 부여가 완료되었습니다."]);
            } else {
                $this->jsonResponse->send(['success' => false, 'message' => '일부 직원 연차 부여에 실패했습니다.', 'failed_ids' => $result['failed_ids']], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '일괄 부여 처리 중 오류 발생: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 연말, 모든 직원의 미사용 연차를 소멸시킵니다.
     * POST /api/admin/leaves/expire-unused
     */
    public function expireUnusedLeaveForAll(): void
    {
        $adminEmployeeId = $this->authService->getCurrentEmployeeId();
        $data = $this->request->getJsonRawBody();
        $year = $data['year'] ?? (int)date('Y') - 1; // 보통 작년 연차를 소멸

        try {
            $result = $this->leaveManagementService->expireUnusedLeaveForAll($year, $adminEmployeeId);
             if (empty($result['failed_ids'])) {
                $this->jsonResponse->send(['success' => true, 'message' => "{$year}년 미사용 연차 소멸 처리가 완료되었습니다."]);
            } else {
                $this->jsonResponse->send(['success' => false, 'message' => '일부 직원 연차 소멸에 실패했습니다.', 'failed_ids' => $result['failed_ids']], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '일괄 소멸 처리 중 오류 발생: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 특정 직원의 연차를 수동으로 조정합니다.
     * POST /api/admin/leaves/adjust
     */
    public function manualAdjustment(): void
    {
        $adminEmployeeId = $this->authService->getCurrentEmployeeId();
        $data = $this->request->getJsonRawBody();

        // TODO: 입력 데이터 유효성 검사 (employee_id, days, reason 등)

        try {
            $success = $this->leaveManagementService->manualAdjustment(
                $data['employee_id'],
                $data['year'],
                $data['days'],
                $data['reason'],
                $adminEmployeeId
            );
            if ($success) {
                $this->jsonResponse->send(['success' => true, 'message' => '연차 조정이 완료되었습니다.']);
            } else {
                $this->jsonResponse->send(['success' => false, 'message' => '연차 조정에 실패했습니다.'], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '조정 처리 중 오류 발생: ' . $e->getMessage()], 500);
        }
    }
}
