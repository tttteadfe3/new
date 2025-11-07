<?php

namespace App\Controllers\Api;

use App\Services\LeaveService;
use App\Repositories\LeaveRepository;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use DateTime;

class LeaveApiController extends BaseApiController
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

    // ===================================================================
    // 연차 신청 관련 API (Leave Application APIs)
    // ===================================================================

    /**
     * 연차 신청
     * 요구사항: 3.1, 3.3 - 0.5일 단위 반차 신청 허용, 잔여량 검증
     * POST /api/leaves/apply
     */
    public function applyLeave(): void
    {
        $employeeId = $this->getCurrentEmployeeId();
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }

        $data = $this->getJsonInput();
        if (!$this->validateRequired($data, ['start_date', 'end_date', 'day_type'])) {
            return;
        }

        try {
            $startDate = new DateTime($data['start_date']);
            $endDate = new DateTime($data['end_date']);
            $dayType = $data['day_type']; // 전일 or 반차
            $reason = $data['reason'] ?? null;
            
            // 백엔드에서 일수 자동 계산
            $isHalfDay = ($dayType === '반차');
            $days = $this->leaveService->calculateLeaveDays(
                $data['start_date'],
                $data['end_date'],
                $employeeId,
                $isHalfDay
            );

            $applicationId = $this->leaveService->applyLeave(
                $employeeId,
                $startDate,
                $endDate,
                $days,
                $dayType,
                $reason
            );

            $this->apiSuccess([
                'application_id' => $applicationId
            ], '연차 신청이 완료되었습니다.');

        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'APPLICATION_FAILED', 400);
        }
    }





    /**
     * 연차 잔여량 조회
     * 요구사항: 5.1 - 실시간 잔여 연차 표시
     * GET /api/leaves/balance
     */
    public function getBalance(): void
    {
        $employeeId = $this->getCurrentEmployeeId();
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }

        try {
            $year = (int)$this->request->input('year', date('Y'));
            $balance = $this->leaveService->getCurrentBalance($employeeId);
            $stats = $this->leaveService->getEmployeeLeaveStatistics($employeeId, $year);
            
            // 부여된 연차 계산 (현재 잔여량 + 사용한 연차)
            $grantedLeave = $balance + $stats['used_days_this_year'];
            
            // 승인 대기 중인 일수 계산
            $pendingDays = $this->leaveRepository->getPendingApplicationDays($employeeId);

            $this->apiSuccess([
                'balance' => $balance,
                'granted' => $grantedLeave,
                'used' => $stats['used_days_this_year'],
                'pending' => $pendingDays,
                'current_balance' => $balance,
                'used_this_year' => $stats['used_days_this_year'],
                'pending_applications' => $stats['pending_requests']
            ]);

        } catch (Exception $e) {
            $this->apiError('연차 잔여량을 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }



    /**
     * 연차 사용 이력 조회
     * 요구사항: 5.4 - 사용 이력 조회
     * GET /api/leaves/history
     */
    public function getHistory(): void
    {
        $employeeId = $this->getCurrentEmployeeId();
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }

        try {
            $year = (int)$this->request->input('year', date('Y'));
            $status = $this->request->input('status');
            
            $history = $this->leaveRepository->getLeaveHistory($employeeId, $year, $status);

            $this->apiSuccess($history);

        } catch (Exception $e) {
            $this->apiError('연차 이력을 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 개별 신청 ID 기반 취소
     * POST /api/leaves/applications/{id}/cancel
     */
    public function cancelApplicationById(string $id): void
    {
        $employeeId = $this->getCurrentEmployeeId();
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }

        try {
            $this->leaveService->cancelApplication((int)$id, $employeeId);
            $this->apiSuccess(null, '연차 신청이 취소되었습니다.');

        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'CANCEL_FAILED', 400);
        }
    }

    /**
     * 개별 신청 ID 기반 취소 신청
     * POST /api/leaves/applications/{id}/request-cancel
     */
    public function requestCancellationById(string $id): void
    {
        $employeeId = $this->getCurrentEmployeeId();
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }

        $data = $this->getJsonInput();
        if (!$this->validateRequired($data, ['reason'])) {
            return;
        }

        try {
            $cancellationId = $this->leaveService->requestCancellation(
                (int)$id,
                $employeeId,
                $data['reason']
            );

            $this->apiSuccess([
                'cancellation_id' => $cancellationId
            ], '연차 취소 신청이 완료되었습니다.');

        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'CANCELLATION_REQUEST_FAILED', 400);
        }
    }

    // ===================================================================
    // 승인 관련 API (Approval APIs)
    // ===================================================================





    // ===================================================================
    // 조회 관련 API (Query APIs)
    // ===================================================================











    // ===================================================================
    // 관리자 API (Administrative APIs)
    // ===================================================================







    // ===================================================================
    // 유틸리티 API (Utility APIs)
    // ===================================================================

    /**
     * 주어진 기간 동안의 휴가 일수를 계산합니다.
     * 요구사항: 9.1 - 잔여량 검증
     * POST /api/leaves/calculate-days
     */
    public function calculateDays(): void
    {
        $employeeId = $this->getCurrentEmployeeId();
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }

        $data = $this->getJsonInput();
        if (!$this->validateRequired($data, ['start_date', 'end_date'])) {
            return;
        }

        try {
            $isHalfDay = $data['is_half_day'] ?? false;
            $days = $this->leaveService->calculateLeaveDays(
                $data['start_date'],
                $data['end_date'],
                $employeeId,
                $isHalfDay
            );

            $this->apiSuccess(['days' => $days]);

        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'CALCULATION_FAILED', 400);
        }
    }


}
