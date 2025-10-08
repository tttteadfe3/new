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
            $this->error('연결된 직원 정보가 없습니다.', [], 400);
            return;
        }
        
        $year = (int)$this->request->input('year', date('Y'));
        
        try {
            $entitlement = LeaveRepository::findEntitlement($employeeId, $year);
            $leaves = LeaveRepository::findByEmployeeId($employeeId, ['year' => $year]);

            $this->success([
                'entitlement' => $entitlement,
                'leaves' => $leaves
            ]);
        } catch (Exception $e) {
            $this->error('연차 정보를 불러오는 중 오류가 발생했습니다.', ['exception' => $e->getMessage()], 500);
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
            $this->error('연결된 직원 정보가 없습니다.', [], 400);
            return;
        }
        
        $data = $this->request->all();
        if (empty($data)) {
            $this->validationError([], '연차 신청 정보가 필요합니다.');
            return;
        }
        
        try {
            [$success, $message] = $this->leaveService->requestLeave($data, $employeeId);

            if ($success) {
                $this->success(null, $message);
            } else {
                $this->error($message);
            }
        } catch (Exception $e) {
            $this->error('신청 처리 중 오류가 발생했습니다.', ['exception' => $e->getMessage()], 500);
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
            $this->error('연결된 직원 정보가 없습니다.', [], 400);
            return;
        }
        
        $reason = $this->request->input('reason');
        
        try {
            [$success, $message] = $this->leaveService->cancelRequest($id, $employeeId, $reason);

            if ($success) {
                $this->success(null, $message);
            } else {
                $this->error($message);
            }
        } catch (Exception $e) {
            $this->error('취소 처리 중 오류가 발생했습니다.', ['exception' => $e->getMessage()], 500);
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
            $this->error('연결된 직원 정보가 없습니다.', [], 400);
            return;
        }
        
        $startDate = $this->request->input('start_date');
        $endDate = $this->request->input('end_date');
        
        if (empty($startDate) || empty($endDate)) {
            $this->validationError(['start_date' => '기간이 필요합니다.', 'end_date' => '기간이 필요합니다.'], '시작일과 종료일이 필요합니다.');
            return;
        }
        
        try {
            // isHalfDay는 false로 고정 (반차는 0.5일로 클라이언트에서 계산)
            $days = $this->leaveService->calculateLeaveDays($startDate, $endDate, $employeeId, false);
            $this->success(['days' => $days]);
        } catch (Exception $e) {
            $this->error('일수 계산 중 오류가 발생했습니다.', ['exception' => $e->getMessage()], 500);
        }
    }
}