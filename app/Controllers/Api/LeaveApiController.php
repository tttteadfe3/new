<?php

namespace App\Controllers\Api;

use App\Services\LeaveService;
use App\Repositories\LeaveRepository;
use App\Repositories\EmployeeRepository;

class LeaveApiController extends BaseApiController
{
    private LeaveService $leaveService;

    public function __construct()
    {
        parent::__construct();
        $this->leaveService = new LeaveService();
    }

    /**
     * Handle all leave API requests based on action parameter
     */
    public function index(): void
    {
        $action = $this->getAction();
        
        try {
            switch ($action) {
                case 'get_my_status':
                    $this->getMyStatus();
                    break;
                case 'submit_request':
                    $this->submitRequest();
                    break;
                case 'cancel_request':
                    $this->cancelRequest();
                    break;
                case 'calculate_days':
                    $this->calculateDays();
                    break;
                case 'check_overlap':
                    $this->checkOverlap();
                    break;
                default:
                    $this->apiBadRequest('Invalid action');
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get current user's leave status for a specific year
     */
    private function getMyStatus(): void
    {
        $this->requireAuth('leave_view');
        
        $employeeId = $this->getCurrentEmployeeId();
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.');
            return;
        }
        
        $year = (int)($_GET['year'] ?? date('Y'));
        
        $entitlement = LeaveRepository::findEntitlement($employeeId, $year);
        $leaves = LeaveRepository::findByEmployeeId($employeeId, ['year' => $year]);
        
        $this->apiSuccess([
            'entitlement' => $entitlement,
            'leaves' => $leaves
        ]);
    }

    /**
     * Submit a new leave request
     */
    private function submitRequest(): void
    {
        $this->requireAuth('leave_request');
        
        $employeeId = $this->getCurrentEmployeeId();
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.');
            return;
        }
        
        $input = $this->getJsonInput();
        if (empty($input)) {
            $this->apiBadRequest('Leave request data is required');
            return;
        }
        
        [$success, $message] = $this->leaveService->requestLeave($input, $employeeId);
        
        if ($success) {
            $this->apiSuccess(null, $message);
        } else {
            $this->apiError($message);
        }
    }

    /**
     * Cancel a leave request
     */
    private function cancelRequest(): void
    {
        $this->requireAuth('leave_request');
        
        $employeeId = $this->getCurrentEmployeeId();
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.');
            return;
        }
        
        $input = $this->getJsonInput();
        $leaveId = (int)($input['id'] ?? 0);
        $reason = $input['reason'] ?? null;
        
        if (!$leaveId) {
            $this->apiBadRequest('Leave ID is required');
            return;
        }
        
        [$success, $message] = $this->leaveService->cancelRequest($leaveId, $employeeId, $reason);
        
        if ($success) {
            $this->apiSuccess(null, $message);
        } else {
            $this->apiError($message);
        }
    }

    /**
     * Calculate leave days for a given period
     */
    private function calculateDays(): void
    {
        $this->requireAuth('leave_request');
        
        $employeeId = $this->getCurrentEmployeeId();
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.');
            return;
        }
        
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        
        if (empty($startDate) || empty($endDate)) {
            $this->apiBadRequest('시작일과 종료일이 필요합니다.');
            return;
        }
        
        // 반차는 항상 0.5일이므로 isHalfDay는 false로 고정
        $days = $this->leaveService->calculateLeaveDays($startDate, $endDate, $employeeId, false);
        $this->apiSuccess(['days' => $days]);
    }

    /**
     * Check for overlapping leave requests
     */
    private function checkOverlap(): void
    {
        $this->requireAuth('leave_request');
        
        $employeeId = $this->getCurrentEmployeeId();
        if (!$employeeId) {
            $this->apiError('연결된 직원 정보가 없습니다.');
            return;
        }
        
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        
        if (empty($startDate) || empty($endDate)) {
            $this->apiBadRequest('시작일과 종료일이 필요합니다.');
            return;
        }
        
        $isOverlapping = LeaveRepository::findOverlappingLeaves($employeeId, $startDate, $endDate);
        $this->apiSuccess(['is_overlapping' => $isOverlapping]);
    }
}