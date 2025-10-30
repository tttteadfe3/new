<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\JsonResponse;
use App\Services\AuthService;
use App\Repositories\LeaveRepository;
use App\Validators\LeaveRequestValidator; // 가상의 Validator 클래스

class LeaveApiController extends BaseApiController
{
    private LeaveRepository $leaveRepository;
    private LeaveManagementService $leaveManagementService; // 잔여 연차 체크를 위해 추가

    public function __construct(
        Request $request,
        AuthService $authService,
        JsonResponse $jsonResponse,
        LeaveRepository $leaveRepository,
        LeaveManagementService $leaveManagementService
    ) {
        parent::__construct($request, $authService, $jsonResponse);
        $this->leaveRepository = $leaveRepository;
        $this->leaveManagementService = $leaveManagementService;
    }

    public function getMyBalance(): void
    {
        // ... unchanged ...
    }

    public function index(): void
    {
        // ... unchanged ...
    }

    public function store(): void
    {
        $employeeId = $this->authService->getCurrentEmployeeId();
        $data = $this->request->getJsonRawBody();

        // 1. 입력 데이터 유효성 검사
        $errors = LeaveRequestValidator::validateStore($data);
        if (!empty($errors)) {
            $this->jsonResponse->send(['success' => false, 'message' => '입력값이 유효하지 않습니다.', 'errors' => $errors], 422);
            return;
        }
        
        try {
            // 2. 신청 가능한 잔여 연차가 있는지 확인
            $canRequest = $this->leaveManagementService->canRequestLeave($employeeId, $data['start_date'], $data['days_count']);
            if (!$canRequest) {
                 $this->jsonResponse->send(['success' => false, 'message' => '잔여 연차가 부족하여 신청할 수 없습니다.'], 400);
                return;
            }

            $requestId = $this->leaveRepository->createLeaveRequest($employeeId, $data);
            if ($requestId) {
                $this->jsonResponse->send(['success' => true, 'message' => '연차 신청이 완료되었습니다.', 'request_id' => $requestId], 201);
            } else {
                $this->jsonResponse->send(['success' => false, 'message' => '연차 신청에 실패했습니다.'], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '처리 중 오류가 발생했습니다: ' . $e->getMessage()], 500);
        }
    }

    public function requestCancellation(int $id): void
    {
        // ... unchanged ...
    }
}
