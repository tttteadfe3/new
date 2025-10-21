<?php

namespace App\Controllers\Api;

use App\Services\LitteringService;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;

class LitteringAdminApiController extends BaseApiController
{
    private LitteringService $litteringService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        LitteringService $litteringService
    ) {
        parent::__construct(
            $request,
            $authService,
            $viewDataService,
            $activityLogger,
            $employeeRepository,
            $jsonResponse
        );
        $this->litteringService = $litteringService;
    }

    /**
     * Get littering reports for admin based on status.
     * Corresponds to GET /api/littering_admin/reports
     */
    public function listReports(): void
    {
        $status = $this->request->input('status', 'pending'); // 'pending', 'processed_for_approval', 'deleted'

        try {
            $data = [];
            switch ($status) {
                case 'pending':
                    $data = $this->litteringService->getPendingLittering();
                    break;
                case 'processed_for_approval':
                    $data = $this->litteringService->getProcessedLitteringForApproval();
                    break;
                case 'deleted':
                    $data = $this->litteringService->getDeletedLittering();
                    break;
                default:
                    $this->apiError('Invalid status value.', 'INVALID_INPUT', 400);
                    return;
            }
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->apiError('목록을 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Confirm a littering report.
     * Corresponds to POST /api/littering_admin/reports/{id}/confirm
     */
    public function confirm(int $id): void
    {
        $adminId = $this->user()['employee_id'];
        
        try {
            $data = $this->getJsonInput();
            $data['id'] = $id; // Ensure ID from URL is used

            $result = $this->litteringService->confirmLittering($data, $adminId);
            $this->apiSuccess($result, '민원 정보가 성공적으로 확인되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'OPERATION_FAILED', 422);
        }
    }

    /**
     * Approve a processed littering report.
     * Corresponds to POST /api/littering_admin/reports/{id}/approve
     */
    public function approve(int $id): void
    {
        $adminId = $this->user()['employee_id'];

        try {
            $postData = ['id' => $id];
            $result = $this->litteringService->approveLittering($postData, $adminId);
            $this->apiSuccess($result, '처리가 최종 승인되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'OPERATION_FAILED', 422);
        }
    }

    /**
     * Delete (soft delete) a littering report.
     * Corresponds to DELETE /api/littering_admin/reports/{id}
     */
    public function destroy(int $id): void
    {
        $adminId = $this->user()['employee_id'];

        try {
            $data = $this->getJsonInput();
            $data['id'] = $id;

            $result = $this->litteringService->deleteLittering($data, $adminId);
            $this->apiSuccess($result, '민원 정보가 성공적으로 삭제되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'OPERATION_FAILED', 422);
        }
    }

    /**
     * Restore a deleted littering report.
     * Corresponds to POST /api/littering_admin/reports/{id}/restore
     */
    public function restore(int $id): void
    {
        
        try {
            $data = ['id' => $id];
            $result = $this->litteringService->restoreLittering($data);
            $this->apiSuccess($result, '민원 정보가 성공적으로 복원되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'OPERATION_FAILED', 422);
        }
    }

    /**
     * Permanently delete a littering report.
     * Corresponds to DELETE /api/littering_admin/reports/{id}/permanent
     */
    public function permanentlyDelete(int $id): void
    {

        try {
            $data = ['id' => $id];
            $result = $this->litteringService->permanentlyDeleteLittering($data);
            $this->apiSuccess($result, '민원 정보가 영구적으로 삭제되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'OPERATION_FAILED', 422);
        }
    }
}
