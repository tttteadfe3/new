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

    /**
     * 특정 연도의 현재 사용자 휴가 상태를 가져옵니다.
     * GET /api/leaves에 해당합니다.
     */
    public function index(): void
    {
        
        $employeeId = $this->user()['employee_id'] ?? null;
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }
        
        $year = (int)$this->request->input('year', date('Y'));
        
        try {
            $entitlement = $this->leaveRepository->findEntitlement($employeeId, $year);
            $leaves = $this->leaveRepository->findByEmployeeId($employeeId, ['year' => $year]);

            $this->apiSuccess([
                'entitlement' => $entitlement,
                'leaves' => $leaves
            ]);
        } catch (Exception $e) {
            $this->apiError('연차 정보를 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 새 휴가 요청을 제출합니다.
     * POST /api/leaves에 해당합니다.
     */
    public function store(): void
    {
        
        $employeeId = $this->user()['employee_id'] ?? null;
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }
        
        $data = $this->getJsonInput();
        if (empty($data)) {
            $this->apiError('연차 신청 정보가 필요합니다.', 'INVALID_INPUT', 422);
            return;
        }
        
        try {
            [$success, $message] = $this->leaveService->requestLeave($data, $employeeId);

            if ($success) {
                $this->apiSuccess(null, $message);
            } else {
                $this->apiError($message, 'OPERATION_FAILED');
            }
        } catch (Exception $e) {
            $this->apiError('신청 처리 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 휴가 요청을 취소합니다.
     * POST /api/leaves/{id}/cancel에 해당합니다.
     */
    public function cancel(int $id): void
    {
        
        $employeeId = $this->user()['employee_id'] ?? null;
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }
        
        $data = $this->getJsonInput();
        $reason = $data['reason'] ?? '';
        
        try {
            [$success, $message] = $this->leaveService->cancelRequest($id, $employeeId, $reason);

            if ($success) {
                $this->apiSuccess(null, $message);
            } else {
                $this->apiError($message, 'OPERATION_FAILED');
            }
        } catch (Exception $e) {
            $this->apiError('취소 처리 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 주어진 기간 동안의 휴가 일수를 계산합니다.
     * POST /api/leaves/calculate-days에 해당합니다.
     */
    public function calculateDays(): void
    {
        
        $employeeId = $this->user()['employee_id'] ?? null;
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }
        
        $data = $this->getJsonInput();
        $startDate = $data['start_date'] ?? null;
        $endDate = $data['end_date'] ?? null;
        
        if (empty($startDate) || empty($endDate)) {
            $this->apiError('시작일과 종료일이 필요합니다.', 'VALIDATION_ERROR', 422);
            return;
        }
        
        try {
            // isHalfDay는 false로 고정 (반차는 0.5일로 클라이언트에서 계산)
            $days = $this->leaveService->calculateLeaveDays($startDate, $endDate, $employeeId, false);
            $this->apiSuccess(['days' => $days]);
        } catch (Exception $e) {
            $this->apiError('일수 계산 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }
}
