<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\JsonResponse;
use App\Services\AuthService;
use App\Repositories\LeaveRepository;
use App\Services\LeaveManagementService;
use App\Validators\LeaveRequestValidator;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;

class LeaveApiController extends BaseApiController
{
    private LeaveRepository $leaveRepository;
    private LeaveManagementService $leaveManagementService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        LeaveRepository $leaveRepository,
        LeaveManagementService $leaveManagementService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->leaveRepository = $leaveRepository;
        $this->leaveManagementService = $leaveManagementService;
    }

    public function getMyBalance(): void
    {
        $employeeId = $this->getCurrentEmployeeId();
        $year = (int)$this->request->input('year', date('Y'));
        try {
            $balance = $this->leaveRepository->findBalanceByEmployeeAndYear($employeeId, $year);
            $this->jsonResponse->send(['success' => true, 'data' => $balance]);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '연차 정보를 불러오는 중 오류 발생'], 500);
        }
    }

    public function index(): void
    {
        $employeeId = $this->getCurrentEmployeeId();
        $filters = $this->request->all();
        try {
            $requests = $this->leaveRepository->findRequestsByEmployee($employeeId, $filters);
            $this->jsonResponse->send(['success' => true, 'data' => $requests]);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '신청 목록 조회 중 오류 발생'], 500);
        }
    }

    public function store(): void
    {
        $employeeId = $this->getCurrentEmployeeId();
        $data = $this->request->getJsonRawBody();
        $errors = LeaveRequestValidator::validateStore($data);
        if (!empty($errors)) {
            $this->jsonResponse->send(['success' => false, 'message' => '입력값이 유효하지 않습니다.', 'errors' => $errors], 422);
            return;
        }
        try {
            if (!$this->leaveManagementService->canRequestLeave($employeeId, $data['start_date'], (float)$data['days_count'])) {
                 $this->jsonResponse->send(['success' => false, 'message' => '잔여 연차가 부족하여 신청할 수 없습니다.'], 400);
                return;
            }
            $requestId = $this->leaveRepository->createLeaveRequest($employeeId, $data);
            $this->jsonResponse->send(['success' => true, 'message' => '연차 신청이 완료되었습니다.', 'request_id' => $requestId], 21);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '처리 중 오류가 발생했습니다: ' . $e->getMessage()], 500);
        }
    }

    public function requestCancellation(int $id): void
    {
        $employeeId = $this->getCurrentEmployeeId();
        $data = $this->request->getJsonRawBody();
        $cancellationReason = $data['reason'] ?? '';

        try {
            $request = $this->leaveRepository->findRequestById($id);
            if (!$request || $request['employee_id'] != $employeeId || !in_array($request['status'], ['pending', 'approved'])) {
                $this->jsonResponse->send(['success' => false, 'message' => '취소 요청할 수 없는 상태입니다.'], 400);
                return;
            }

            if ($request['status'] === 'pending') {
                 $this->leaveRepository->updateRequestStatus($id, 'cancelled');
            } else { // approved
                 $this->leaveRepository->updateRequestStatus($id, 'cancellation_requested', ['cancellation_reason' => $cancellationReason]);
            }

            $this->jsonResponse->send(['success' => true, 'message' => '연차 취소 요청이 완료되었습니다.']);
        } catch (\Exception $e) {
            $this->jsonResponse->send(['success' => false, 'message' => '처리 중 오류가 발생했습니다.'], 500);
        }
    }
}
