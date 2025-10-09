<?php

namespace App\Controllers\Api;

use App\Services\LeaveService;
use App\Repositories\LeaveRepository;
use Exception;

class LeaveApiController extends BaseApiController
{
    private LeaveService $leaveService;

    public function __construct()
    {
        parent::__construct();
        $this->leaveService = new LeaveService();
    }

    /**
     * Get current user's leave status for a specific year.
     * Corresponds to GET /api/leaves
     */
    public function index(): void
    {
        $this->requireAuth('leave_view');
        
        $employeeId = $this->user()['employee_id'] ?? null;
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }
        
        $year = (int)$this->request->input('year', date('Y'));
        
        try {
            $entitlement = LeaveRepository::findEntitlement($employeeId, $year);
            $leaves = LeaveRepository::findByEmployeeId($employeeId, ['year' => $year]);

            $this->apiSuccess([
                'entitlement' => $entitlement,
                'leaves' => $leaves
            ]);
        } catch (Exception $e) {
            $this->apiError('연차 정보를 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Submit a new leave request.
     * Corresponds to POST /api/leaves
     */
    public function store(): void
    {
        $this->requireAuth('leave_request');
        
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
     * Cancel a leave request.
     * Corresponds to POST /api/leaves/{id}/cancel
     */
    public function cancel(int $id): void
    {
        $this->requireAuth('leave_request');
        
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
     * Calculate leave days for a given period.
     * Corresponds to POST /api/leaves/calculate-days
     */
    public function calculateDays(): void
    {
        $this->requireAuth('leave_request');
        
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