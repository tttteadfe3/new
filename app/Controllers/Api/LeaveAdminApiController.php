<?php

namespace App\Controllers\Api;

use App\Services\LeaveService;
use App\Services\LeaveAdminService;
use App\Repositories\LeaveRepository;
use App\Repositories\EmployeeRepository;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Core\JsonResponse;

class LeaveAdminApiController extends BaseApiController
{
    private LeaveService $leaveService;
    private LeaveAdminService $leaveAdminService;
    private LeaveRepository $leaveRepository;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        LeaveService $leaveService,
        LeaveAdminService $leaveAdminService,
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
        $this->leaveAdminService = $leaveAdminService;
        $this->leaveRepository = $leaveRepository;
    }

    /**
     * 상태별 휴가 요청을 나열합니다.
     * GET /api/leaves_admin/requests에 해당합니다.
     */
    public function listRequests(): void
    {
        try {
            // 이제 서비스 계층을 사용하며, 이는 권한을 올바르게 처리합니다.
            // 서비스 메서드는 기본적으로 'pending'이며 승인 페이지의 요구 사항에 맞게 특별히 제작되었습니다.
            $data = $this->leaveAdminService->getPendingApplications();
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 개별 휴가 요청 조회
     * GET /api/leaves_admin/requests/{id}에 해당합니다.
     */
    public function getRequest(string $id): void
    {
        try {
            $request = $this->leaveRepository->getApplicationById((int)$id);
            if (!$request) {
                $this->apiError('요청을 찾을 수 없습니다.', 'NOT_FOUND', 404);
                return;
            }

            $this->apiSuccess($request);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 휴가 요청을 승인합니다.
     * POST /api/leaves_admin/requests/{id}/approve에 해당합니다.
     */
    public function approveRequest(string $id): void
    {
        $adminId = $this->getCurrentEmployeeId();
        if (!$adminId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }

        try {
            $this->leaveAdminService->approveApplication((int)$id, true, $adminId, null);
            $this->apiSuccess(null, '연차 신청이 승인되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    /**
     * 휴가 요청을 거부합니다.
     * POST /api/leaves_admin/requests/{id}/reject에 해당합니다.
     */
    public function rejectRequest(string $id): void
    {
        $adminId = $this->getCurrentEmployeeId();
        if (!$adminId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }
        $reason = $this->request->input('reason');

        if (empty($reason)) {
            $this->apiError('반려 사유는 필수입니다.', 'VALIDATION_ERROR', 422);
            return;
        }

        try {
            $this->leaveAdminService->approveApplication((int)$id, false, $adminId, $reason);
            $this->apiSuccess(null, '연차 신청이 반려되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    /**
     * 개별 취소 요청 조회
     * GET /api/leaves_admin/cancellations/{id}에 해당합니다.
     */
    public function getCancellation(string $id): void
    {
        try {
            $cancellation = $this->leaveRepository->getCancellationById((int)$id);
            if (!$cancellation) {
                $this->apiError('취소 요청을 찾을 수 없습니다.', 'NOT_FOUND', 404);
                return;
            }

            $this->apiSuccess($cancellation);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 휴가 취소 요청을 승인합니다.
     * POST /api/leaves_admin/cancellations/{id}/approve에 해당합니다.
     */
    public function approveCancellation(string $id): void
    {
        $adminId = $this->getCurrentEmployeeId();
        if (!$adminId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }

        try {
            $this->leaveAdminService->approveCancellation((int)$id, true, $adminId, null);
            $this->apiSuccess(null, '취소 신청이 승인되었습니다.');
        } catch (Exception $e) {
            $this->apiError('취소 승인 처리 중 오류 발생', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 휴가 취소 요청을 거부합니다.
     * POST /api/leaves_admin/cancellations/{id}/reject에 해당합니다.
     */
    public function rejectCancellation(string $id): void
    {
        $adminId = $this->getCurrentEmployeeId();
        if (!$adminId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }
        $reason = $this->request->input('reason');
        
        if (empty($reason)) {
            $this->apiError('반려 사유는 필수입니다.', 'VALIDATION_ERROR', 422);
            return;
        }

        try {
            $this->leaveAdminService->approveCancellation((int)$id, false, $adminId, $reason);
            $this->apiSuccess(null, '취소 신청이 반려되었습니다.');
        } catch (Exception $e) {
            $this->apiError('취소 반려 처리 중 오류 발생', 'SERVER_ERROR', 500);
        }
    }















    /**
     * 관리자 대시보드 데이터 조회
     * 요구사항: 6.1, 6.2 - 팀별 연차 소진율, 통계 데이터
     * GET /api/leaves_admin/dashboard
     */
    public function getDashboardData(): void
    {
        try {
            $year = (int)$this->request->input('year', date('Y'));
            $departmentId = $this->request->input('department_id');
            
            // 빈 문자열을 null로 변환
            $departmentId = empty($departmentId) ? null : (int)$departmentId;

            // 디버깅 로그
            error_log("getDashboardData called with year: $year, departmentId: " . ($departmentId ?? 'null'));

            // 통계 데이터 조회
            $statistics = $this->leaveAdminService->getLeaveStatistics($year, $departmentId);
            error_log("Statistics: " . json_encode($statistics));
            
            // 차트 데이터 조회
            $chartData = [
                'usage_rate' => $this->leaveAdminService->getUsageRateData($year, $departmentId),
                'department_summary' => $this->leaveAdminService->getDepartmentSummary($year, $departmentId),
                'monthly_trend' => $this->leaveAdminService->getMonthlyTrend($year, $departmentId)
            ];

            $response = [
                'statistics' => $statistics,
                'charts' => $chartData
            ];
            
            error_log("Dashboard response: " . json_encode($response));

            $this->apiSuccess($response);

        } catch (Exception $e) {
            // 더 자세한 오류 정보를 로그에 기록
            error_log("LeaveAdminApiController::getDashboardData Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $this->apiError('대시보드 데이터를 불러오는 중 오류가 발생했습니다: ' . $e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    /**
     * 승인 대기 목록 조회
     * 요구사항: 6.4 - 승인 대기 목록 관리
     * GET /api/leaves_admin/pending-requests
     */
    public function getPendingRequests(): void
    {
        try {
            $departmentId = $this->request->input('department_id');
            $departmentId = empty($departmentId) ? null : (int)$departmentId;
            
            error_log("getPendingRequests called with departmentId: " . ($departmentId ?? 'null'));
            
            $pendingRequests = $this->leaveAdminService->getPendingApplications($departmentId);
            
            // 각 신청에 대해 정확한 잔여량 재계산
            foreach ($pendingRequests as &$request) {
                $request['current_balance'] = $this->leaveService->calculateCurrentBalance($request['employee_id']);
            }
            
            error_log("Pending requests count: " . count($pendingRequests));
            error_log("Pending requests: " . json_encode($pendingRequests));

            $this->apiSuccess($pendingRequests);

        } catch (Exception $e) {
            error_log("getPendingRequests Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->apiError('승인 대기 목록을 불러오는 중 오류가 발생했습니다: ' . $e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    /**
     * 취소 신청 대기 목록 조회
     * 요구사항: 12.2 - 취소 신청 승인 관리
     * GET /api/leaves_admin/pending-cancellations
     */
    public function getPendingCancellations(): void
    {
        try {
            $departmentId = $this->request->input('department_id');
            $departmentId = empty($departmentId) ? null : (int)$departmentId;
            $pendingCancellations = $this->leaveAdminService->getPendingCancellations($departmentId);

            $this->apiSuccess($pendingCancellations);

        } catch (Exception $e) {
            $this->apiError('취소 신청 목록을 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }



    /**
     * 연차 조정 처리
     * 요구사항: 7.3, 7.4 - 연차 조정 기능
     * POST /api/leaves_admin/adjust-leave
     */
    public function adjustLeave(): void
    {
        $adminId = $this->getCurrentEmployeeId();
        if (!$adminId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }

        $data = $this->getJsonInput();
        if (!$this->validateRequired($data, ['employee_id', 'amount', 'reason'])) {
            return;
        }

        // 연차 추가 시 grant_year 필수 체크
        $amount = (float)$data['amount'];
        if ($amount > 0 && empty($data['grant_year'])) {
            $this->apiError('연차 추가 시 부여연도(grant_year)는 필수입니다.', 'GRANT_YEAR_REQUIRED', 400);
            return;
        }

        try {
            $grantYear = isset($data['grant_year']) ? (int)$data['grant_year'] : null;
            
            $this->leaveAdminService->adjustLeave(
                (int)$data['employee_id'],
                $amount,
                $data['reason'],
                $adminId,
                $grantYear
            );

            $this->apiSuccess(null, '연차 조정이 완료되었습니다.');

        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'ADJUST_FAILED', 400);
        }
    }

    /**
     * 연차 소멸 처리
     * 요구사항: 10.1, 10.2, 10.3, 10.4 - 연차 소멸 처리
     * POST /api/leaves_admin/expire-leave
     */
    public function expireLeave(): void
    {
        $adminId = $this->getCurrentEmployeeId();
        if (!$adminId) {
            $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
            return;
        }

        $data = $this->getJsonInput();
        
        try {
            $employeeIds = $data['employee_ids'] ?? [];
            $reason = $data['reason'] ?? '연차 소멸';

            [$processedCount, $totalExpiredDays] = $this->leaveAdminService->expireLeave(
                $employeeIds,
                $reason,
                $adminId
            );

            $this->apiSuccess([
                'processed_count' => $processedCount,
                'total_expired_days' => $totalExpiredDays
            ], "연차 소멸 처리가 완료되었습니다. (처리: {$processedCount}건, 소멸: {$totalExpiredDays}일)");

        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'EXPIRE_FAILED', 500);
        }
    }

    /**
     * 처리 완료된 요청 목록 조회
     * GET /api/leaves_admin/processed-requests
     */
    public function getProcessedRequests(): void
    {
        try {
            $departmentId = $this->request->input('department_id');
            $typeFilter = $this->request->input('type_filter');
            $dateFrom = $this->request->input('date_from');
            $dateTo = $this->request->input('date_to');

            $departmentId = empty($departmentId) ? null : (int)$departmentId;

            $filters = [
                'department_id' => $departmentId,
                'type_filter' => $typeFilter,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ];

            // 빈 값 제거
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $processedRequests = $this->leaveAdminService->getProcessedRequests($filters);
            $this->apiSuccess($processedRequests);

        } catch (Exception $e) {
            error_log("getProcessedRequests Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->apiError('처리 완료 목록을 불러오는 중 오류가 발생했습니다: ' . $e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    /**
     * 팀 캘린더 데이터 조회
     * GET /api/leaves_admin/team-calendar
     */
    public function getTeamCalendar(): void
    {
        try {
            $year = (int)$this->request->input('year', date('Y'));
            $month = (int)$this->request->input('month', date('n'));
            $departmentId = $this->request->input('department_id');
            
            $departmentId = empty($departmentId) ? null : (int)$departmentId;

            $calendarData = $this->leaveAdminService->getTeamCalendarData($year, $month, $departmentId);
            $this->apiSuccess($calendarData);

        } catch (Exception $e) {
            $this->apiError('팀 캘린더 데이터를 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 팀 현황 조회
     * GET /api/leaves_admin/team-status
     */
    public function getTeamStatus(): void
    {
        try {
            $departmentId = $this->request->input('department_id');
            $departmentId = empty($departmentId) ? null : (int)$departmentId;

            $teamStatus = $this->leaveAdminService->getTeamLeaveStatus($departmentId);
            $this->apiSuccess($teamStatus);

        } catch (Exception $e) {
            $this->apiError('팀 현황을 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 월별 통계 조회
     * GET /api/leaves_admin/monthly-stats
     */
    public function getMonthlyStats(): void
    {
        try {
            $year = (int)$this->request->input('year', date('Y'));
            $month = (int)$this->request->input('month', date('n'));
            $departmentId = $this->request->input('department_id');
            
            $departmentId = empty($departmentId) ? null : (int)$departmentId;

            $monthlyStats = $this->leaveAdminService->getMonthlyLeaveStats($year, $month, $departmentId);
            $this->apiSuccess($monthlyStats);

        } catch (Exception $e) {
            $this->apiError('월별 통계를 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 일별 상세 정보 조회
     * GET /api/leaves_admin/day-detail
     */
    public function getDayDetail(): void
    {
        try {
            $date = $this->request->input('date');
            $departmentId = $this->request->input('department_id');
            
            if (!$date) {
                $this->apiBadRequest('날짜가 필요합니다.');
                return;
            }

            $departmentId = empty($departmentId) ? null : (int)$departmentId;

            $dayDetail = $this->leaveAdminService->getDayLeaveDetail($date, $departmentId);
            $this->apiSuccess($dayDetail);

        } catch (Exception $e) {
            $this->apiError('일별 상세 정보를 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 연차 부여 대상자 계산
     * POST /api/leaves_admin/calculate-grant-targets
     */
    public function calculateGrantTargets(): void
    {
        try {
            $data = $this->getJsonInput();
            $year = (int)($data['year'] ?? date('Y'));
            $departmentId = $data['department_id'] ?? null;
            $previewMode = $data['preview_mode'] ?? true;

            $departmentId = empty($departmentId) ? null : (int)$departmentId;

            $targets = $this->leaveAdminService->calculateGrantTargets($year, $departmentId, $previewMode);
            $this->apiSuccess($targets);

        } catch (Exception $e) {
            $this->apiError('연차 부여 대상자 계산 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 연차 부여 실행
     * POST /api/leaves_admin/execute-grant
     */
    public function executeGrant(): void
    {
        try {
            $data = $this->getJsonInput();
            $year = (int)($data['year'] ?? date('Y'));
            $employeeIds = $data['employee_ids'] ?? [];

            if (empty($employeeIds)) {
                $this->apiBadRequest('부여할 직원을 선택해주세요.');
                return;
            }

            $result = $this->leaveAdminService->executeGrantForEmployees($year, $employeeIds);
            $this->apiSuccess($result);

        } catch (Exception $e) {
            $this->apiError('연차 부여 실행 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 연차 조정 내역 조회
     * GET /api/leaves_admin/adjustment-history
     */
    public function getAdjustmentHistory(): void
    {
        try {
            $limit = (int)$this->request->input('limit', 50);
            
            $adjustmentHistory = $this->leaveAdminService->getAdjustmentHistory($limit);
            $this->apiSuccess($adjustmentHistory);

        } catch (Exception $e) {
            $this->apiError('조정 내역을 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 연차 소멸 대상자 검색
     * POST /api/leaves_admin/search-expire-targets
     */
    public function searchExpireTargets(): void
    {
        try {
            $data = $this->getJsonInput();
            $year = (int)($data['year'] ?? date('Y'));
            $departmentId = $data['department_id'] ?? null;
            $previewMode = $data['preview_mode'] ?? true;

            $departmentId = empty($departmentId) ? null : (int)$departmentId;

            $targets = $this->leaveAdminService->searchExpireTargets($year, $departmentId, $previewMode);
            $this->apiSuccess($targets);

        } catch (Exception $e) {
            $this->apiError('소멸 대상자 검색 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 연차 소멸 실행
     * POST /api/leaves_admin/execute-expire
     */
    public function executeExpire(): void
    {
        try {
            $data = $this->getJsonInput();
            $employeeIds = $data['employee_ids'] ?? [];

            if (empty($employeeIds)) {
                $this->apiBadRequest('소멸할 직원을 선택해주세요.');
                return;
            }

            $adminId = $this->getCurrentEmployeeId();
            if (!$adminId) {
                $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
                return;
            }

            $result = $this->leaveAdminService->executeExpireForEmployees($employeeIds, $adminId);
            $this->apiSuccess($result);

        } catch (Exception $e) {
            $this->apiError('연차 소멸 실행 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 일괄 승인 처리
     * POST /api/leaves_admin/bulk-approve
     */
    public function bulkApprove(): void
    {
        try {
            $data = $this->getJsonInput();
            $type = $data['type'] ?? 'requests'; // requests 또는 cancellations
            $requestIds = $data['request_ids'] ?? [];
            $reason = $data['reason'] ?? null;

            if (empty($requestIds)) {
                $this->apiBadRequest('처리할 항목을 선택해주세요.');
                return;
            }

            $adminId = $this->getCurrentEmployeeId();
            if (!$adminId) {
                $this->apiError('연결된 직원 정보가 없습니다.', 'NO_EMPLOYEE_LINK', 400);
                return;
            }

            $result = $this->leaveAdminService->bulkApprove($type, $requestIds, $adminId, $reason);
            $this->apiSuccess($result);

        } catch (Exception $e) {
            $this->apiError('일괄 승인 처리 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * 데이터 내보내기 (엑셀)
     * GET /api/leaves_admin/export
     */
    public function exportData(): void
    {
        try {
            $type = $this->request->input('type', 'current_status');
            $year = (int)$this->request->input('year', date('Y'));
            $departmentId = $this->request->input('department_id');
            
            $departmentId = empty($departmentId) ? null : (int)$departmentId;

            $exportData = $this->leaveAdminService->exportLeaveData($type, $year, $departmentId);
            
            // JSON으로 데이터 반환 (프론트엔드에서 CSV로 변환)
            $this->apiSuccess($exportData, '데이터 내보내기가 완료되었습니다.');

        } catch (Exception $e) {
            error_log("LeaveAdminApiController::exportData Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->apiError('데이터 내보내기 중 오류가 발생했습니다: ' . $e->getMessage(), 'SERVER_ERROR', 500);
        }
    }
}
