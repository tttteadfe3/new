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

class LeaveAdminApiController extends BaseApiController
{
    private LeaveManagementService $leaveManagementService;
    private LeaveRepository $leaveRepository;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        LeaveManagementService $leaveManagementService,
        LeaveRepository $leaveRepository
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->leaveManagementService = $leaveManagementService;
        $this->leaveRepository = $leaveRepository;
    }

    public function getLeaveRequests(): void
    {
        $filters = $this->request->all();
        try {
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

    public function approveCancellation(int $id): void
    {
        $adminEmployeeId = $this->authService->getCurrentEmployeeId();
        try {
            $this->leaveManagementService->approveCancellationRequest($id, $adminEmployeeId);
            $this->jsonResponse->send(['success' => true, 'message' => '연차 취소 요청을 승인했습니다.']);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function rejectCancellation(int $id): void
    {
        $adminEmployeeId = $this->authService->getCurrentEmployeeId();
        try {
            $this->leaveManagementService->rejectCancellationRequest($id, $adminEmployeeId);
            $this->jsonResponse->send(['success' => true, 'message' => '연차 취소 요청을 반려했습니다.']);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

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

    public function expireUnusedLeaveForAll(): void
    {
        $adminEmployeeId = $this->authService->getCurrentEmployeeId();
        $data = $this->request->getJsonRawBody();
        $year = $data['year'] ?? (int)date('Y') - 1;
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

    public function manualAdjustment(): void
    {
        $adminEmployeeId = $this->authService->getCurrentEmployeeId();
        $data = $this->request->getJsonRawBody();
        $errors = LeaveRequestValidator::validateAdjustment($data);
        if (!empty($errors)) {
            $this->jsonResponse->send(['success' => false, 'message' => '입력값이 유효하지 않습니다.', 'errors' => $errors], 422);
            return;
        }
        try {
            $this->leaveManagementService->manualAdjustment((int)$data['employee_id'], (int)$data['year'], (float)$data['days'], $data['reason'], $adminEmployeeId);
            $this->jsonResponse->send(['success' => true, 'message' => '연차 조정이 완료되었습니다.']);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '조정 처리 중 오류 발생: ' . $e->getMessage()], 500);
        }
    }
}
