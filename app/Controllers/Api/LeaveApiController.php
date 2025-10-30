<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\JsonResponse;
use App\Services\AuthService;
use App\Repositories\LeaveRepository; // 새로운 LeaveRepository 사용

class LeaveApiController extends BaseApiController
{
    private LeaveRepository $leaveRepository;

    public function __construct(
        Request $request,
        AuthService $authService,
        JsonResponse $jsonResponse,
        LeaveRepository $leaveRepository
    ) {
        parent::__construct($request, $authService, $jsonResponse);
        $this->leaveRepository = $leaveRepository;
    }

    /**
     * 현재 로그인한 사용자의 연차 현황을 조회합니다.
     * GET /api/leaves/my-balance
     */
    public function getMyBalance(): void
    {
        $employeeId = $this->authService->getCurrentEmployeeId();
        $year = (int)$this->request->input('year', date('Y'));

        try {
            $balance = $this->leaveRepository->findBalanceByEmployeeAndYear($employeeId, $year);
            $this->jsonResponse->send(['success' => true, 'data' => $balance]);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '연차 정보를 불러오는 중 오류 발생'], 500);
        }
    }

    /**
     * 현재 사용자의 연차 신청 목록을 조회합니다.
     * GET /api/leaves
     */
    public function index(): void
    {
        $employeeId = $this->authService->getCurrentEmployeeId();
        $filters = $this->request->all(); // year, status 등의 필터 적용 가능

        try {
            $requests = $this->leaveRepository->findRequestsByEmployee($employeeId, $filters);
            $this->jsonResponse->send(['success' => true, 'data' => $requests]);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '신청 목록 조회 중 오류 발생'], 500);
        }
    }

    /**
     * 새로운 연차 신청을 생성합니다.
     * POST /api/leaves
     */
    public function store(): void
    {
        $employeeId = $this->authService->getCurrentEmployeeId();
        $data = $this->request->getJsonRawBody();

        // TODO: 입력 데이터 유효성 검사 (시작일, 종료일, 사유 등)
        
        // TODO: 신청 가능한 잔여 연차가 있는지 확인하는 로직 (LeaveManagementService)

        try {
            $requestId = $this->leaveRepository->createLeaveRequest($employeeId, $data);
            if ($requestId) {
                $this->jsonResponse->send(['success' => true, 'message' => '연차 신청이 완료되었습니다.', 'request_id' => $requestId], 201);
            } else {
                $this->jsonResponse->send(['success' => false, 'message' => '연차 신청에 실패했습니다.'], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '처리 중 오류가 발생했습니다.'], 500);
        }
    }

    /**
     * 승인된 연차에 대해 취소 요청을 보냅니다.
     * POST /api/leaves/{id}/cancel
     */
    public function requestCancellation(int $id): void
    {
        $employeeId = $this->authService->getCurrentEmployeeId();
        $data = $this->request->getJsonRawBody();
        $cancellationReason = $data['reason'] ?? '';

        try {
            // 1. 해당 신청이 현재 사용자의 것이고 'approved' 상태인지 확인
            $request = $this->leaveRepository->findRequestById($id);
            if (!$request || $request['employee_id'] != $employeeId || $request['status'] !== 'approved') {
                $this->jsonResponse->send(['success' => false, 'message' => '취소 요청할 수 없는 상태입니다.'], 400);
                return;
            }

            // 2. 상태를 'cancellation_requested'로 변경
            $success = $this->leaveRepository->updateRequestStatus($id, 'cancellation_requested', ['cancellation_reason' => $cancellationReason]);

            if ($success) {
                $this->jsonResponse->send(['success' => true, 'message' => '연차 취소 요청이 완료되었습니다.']);
            } else {
                $this->jsonResponse->send(['success' => false, 'message' => '취소 요청에 실패했습니다.'], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '처리 중 오류가 발생했습니다.'], 500);
        }
    }
}
