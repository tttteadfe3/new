<?php

namespace App\Controllers\Api;

use App\Services\LitteringService;
use Exception;

class LitteringAdminApiController extends BaseApiController
{
    private LitteringService $litteringService;

    public function __construct()
    {
        parent::__construct();
        $this->litteringService = new LitteringService();
    }

    /**
     * Get littering reports for admin based on status.
     * Corresponds to GET /api/littering_admin/reports
     */
    public function listReports(): void
    {
        $this->requireAuth('littering_manage');
        $status = $this->request->input('status', 'pending'); // 'pending', 'deleted'

        try {
            if ($status === 'pending') {
                $data = $this->litteringService->getPendingLittering();
            } elseif ($status === 'deleted') {
                $data = $this->litteringService->getDeletedLittering();
            } else {
                $this->error('Invalid status value.', [], 400);
                return;
            }
            $this->success($data);
        } catch (Exception $e) {
            $this->error('목록을 불러오는 중 오류가 발생했습니다.', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Confirm a littering report.
     * Corresponds to POST /api/littering_admin/reports/{id}/confirm
     */
    public function confirm(int $id): void
    {
        $this->requireAuth('littering_manage');
        $adminId = $this->user()['id'];
        
        try {
            $data = $this->request->all();
            $data['id'] = $id; // Ensure ID from URL is used

            $result = $this->litteringService->confirmLittering($data, $adminId);
            $this->success($result, '민원 정보가 성공적으로 확인되었습니다.');
        } catch (Exception $e) {
            $this->error($e->getMessage(), ['exception' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete (soft delete) a littering report.
     * Corresponds to DELETE /api/littering_admin/reports/{id}
     */
    public function destroy(int $id): void
    {
        $this->requireAuth('littering_manage');
        $adminId = $this->user()['id'];

        try {
            $data = $this->request->all();
            $data['id'] = $id;

            $result = $this->litteringService->deleteLittering($data, $adminId);
            $this->success($result, '민원 정보가 성공적으로 삭제되었습니다.');
        } catch (Exception $e) {
            $this->error($e->getMessage(), ['exception' => $e->getMessage()], 422);
        }
    }

    /**
     * Restore a deleted littering report.
     * Corresponds to POST /api/littering_admin/reports/{id}/restore
     */
    public function restore(int $id): void
    {
        $this->requireAuth('littering_admin');
        
        try {
            $data = ['id' => $id];
            $result = $this->litteringService->restoreLittering($data);
            $this->success($result, '민원 정보가 성공적으로 복원되었습니다.');
        } catch (Exception $e) {
            $this->error($e->getMessage(), ['exception' => $e->getMessage()], 422);
        }
    }

    /**
     * Permanently delete a littering report.
     * Corresponds to DELETE /api/littering_admin/reports/{id}/permanent
     */
    public function permanentlyDelete(int $id): void
    {
        $this->requireAuth('littering_admin'); // Or a higher permission if needed

        try {
            $data = ['id' => $id];
            $result = $this->litteringService->permanentlyDeleteLittering($data);
            $this->success($result, '민원 정보가 영구적으로 삭제되었습니다.');
        } catch (Exception $e) {
            $this->error($e->getMessage(), ['exception' => $e->getMessage()], 422);
        }
    }
}