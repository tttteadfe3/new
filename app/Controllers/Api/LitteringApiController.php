<?php

namespace App\Controllers\Api;

use App\Services\LitteringService;
use Exception;

class LitteringApiController extends BaseApiController
{
    private LitteringService $litteringService;

    public function __construct()
    {
        parent::__construct();
        $this->litteringService = new LitteringService();
    }

    /**
     * Get littering reports based on status.
     * Corresponds to GET /api/littering
     */
    public function index(): void
    {
        $status = $this->request->input('status', 'active'); // 'active', 'processed'

        try {
            if ($status === 'active') {
                $data = $this->litteringService->getActiveLittering();
            } elseif ($status === 'processed') {
                $data = $this->litteringService->getProcessedLittering();
            } else {
                $this->apiError('Invalid status value.', 'INVALID_INPUT', 400);
                return;
            }
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->apiError('목록을 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Register a new littering report.
     * Corresponds to POST /api/littering
     */
    public function store(): void
    {

        $user = $this->user();
        if (!$user) {
            $this->apiError('로그인이 필요합니다.', 'UNAUTHORIZED', 401);
            return;
        }
        
        $userId = $user['id'];
        $employeeId = $user['employee_id'] ?? null;
        
        try {
            // Note: File uploads are handled via $_FILES, not JSON body.
            $result = $this->litteringService->registerLittering(
                $this->request->all(), // POST data is appropriate here
                $_FILES,
                $userId,
                $employeeId
            );
            $this->apiSuccess($result, '부적정배출 정보가 성공적으로 등록되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'VALIDATION_ERROR', 422);
        }
    }

    /**
     * Process a littering report (collection).
     * Corresponds to POST /api/littering/{id}/process
     */
    public function process(int $id): void
    {
        
        try {
            $data = $this->request->all(); // POST data is appropriate here
            $data['id'] = $id; // Ensure the ID from URL is used

            // Note: Accessing $_FILES for file uploads.
            $result = $this->litteringService->processLittering($data, $_FILES);
            $this->apiSuccess($result, '처리 상태가 성공적으로 업데이트되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'VALIDATION_ERROR', 422);
        }
    }
}