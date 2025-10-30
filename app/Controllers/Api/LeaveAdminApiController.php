<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\JsonResponse;
use App\Services\AuthService;
use App\Services\LeaveManagementService;
use App\Repositories\LeaveRepository;
use App\Validators\LeaveRequestValidator; // 가상의 Validator 클래스

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

    public function getLeaveRequests(): void
    {
        $filters = $this->request->all();

        try {
            // 1. 현재 관리자가 조회 가능한 부서 ID 목록을 가져옵니다.
            $visibleDeptIds = $this->authService->getVisibleDepartmentIdsForCurrentUser();
            $filters['department_ids'] = $visibleDeptIds;

            $requests = $this->leaveRepository->findRequestsByAdmin($filters);
            $this->jsonResponse->send(['success' => true, 'data' => $requests]);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '신청 목록 조회 중 오류 발생'], 500);
        }
    }

    public function approveRequest(int $id): void
    {
        $adminEmployeeId = $this->authService->getCurrentEmployeeId();
        try {
            $this->leaveManagementService->approveLeaveRequest($id, $adminEmployeeId);
            $this->jsonResponse->send(['success' => true, 'message' => '연차 신청을 승인했습니다.']);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

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
            $this->leaveManagementService->rejectLeaveRequest($id, $adminEmployeeId, $reason);
            $this->jsonResponse->send(['success' => true, 'message' => '연차 신청을 반려했습니다.']);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ... approveCancellation, rejectCancellation - unchanged ...

    public function grantAnnualLeaveForAll(): void
    {
        // ... unchanged ...
    }

    public function expireUnusedLeaveForAll(): void
    {
        // ... unchanged ...
    }

    public function manualAdjustment(): void
    {
        $adminEmployeeId = $this->authService->getCurrentEmployeeId();
        $data = $this->request->getJsonRawBody();

        // 2. 입력값 유효성 검사
        $errors = LeaveRequestValidator::validateAdjustment($data);
        if (!empty($errors)) {
            $this->jsonResponse->send(['success' => false, 'message' => '입력값이 유효하지 않습니다.', 'errors' => $errors], 422);
            return;
        }

        try {
            $this->leaveManagementService->manualAdjustment(
                (int)$data['employee_id'],
                (int)$data['year'],
                (float)$data['days'],
                $data['reason'],
                $adminEmployeeId
            );
            $this->jsonResponse->send(['success' => true, 'message' => '연차 조정이 완료되었습니다.']);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '조정 처리 중 오류 발생: ' . $e->getMessage()], 500);
        }
    }
}
