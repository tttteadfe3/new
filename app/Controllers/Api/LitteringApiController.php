<?php

namespace App\Controllers\Api;

use App\Services\LitteringService;
use Exception;

class LitteringApiController extends BaseApiController
{
    private LitteringService $litteringService;

    public function __construct()
    {
        $this->litteringService = new LitteringService();
    }

    /**
     * Get littering reports based on status.
     * Corresponds to GET /api/littering
     */
    public function index(): void
    {
        $this->requireAuth('littering_view');
        $status = $this->request->input('status', 'active'); // 'active', 'processed'

        try {
            if ($status === 'active') {
                $data = $this->litteringService->getActiveLittering();
            } elseif ($status === 'processed') {
                $data = $this->litteringService->getProcessedLittering();
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
     * Register a new littering report.
     * Corresponds to POST /api/littering
     */
    public function store(): void
    {
        $this->requireAuth('littering_process');

        $user = $this->user();
        if (!$user) {
            $this->error('로그인이 필요합니다.', [], 401);
            return;
        }
        
        $userId = $user['id'];
        $employeeId = $user['employee_id'] ?? null;
        
        try {
            // Note: File uploads are not part of the request body, so we access $_FILES directly.
            // This is a common practice even in modern frameworks when dealing with multipart/form-data.
            $result = $this->litteringService->registerLittering(
                $this->request->all(),
                $_FILES,
                $userId,
                $employeeId
            );
            $this->success($result, '부적정배출 정보가 성공적으로 등록되었습니다.');
        } catch (Exception $e) {
            $this->error($e->getMessage(), ['exception' => $e->getMessage()], 422);
        }
    }

    /**
     * Process a littering report (collection).
     * Corresponds to POST /api/littering/{id}/process
     */
    public function process(int $id): void
    {
        $this->requireAuth('littering_process');
        
        try {
            $data = $this->request->all();
            $data['id'] = $id; // Ensure the ID from URL is used

            // Note: Accessing $_FILES for file uploads.
            $result = $this->litteringService->processLittering($data, $_FILES);
            $this->success($result, '처리 상태가 성공적으로 업데이트되었습니다.');
        } catch (Exception $e) {
            $this->error($e->getMessage(), ['exception' => $e->getMessage()], 422);
        }
    }
}