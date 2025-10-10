<?php

namespace App\Controllers\Api;

use App\Services\WasteCollectionService;
use Exception;

class WasteCollectionApiController extends BaseApiController
{
    private WasteCollectionService $wasteCollectionService;

    public function __construct()
    {
        parent::__construct();
        $this->wasteCollectionService = new WasteCollectionService();
    }

    /**
     * Get waste collections for the current user.
     * Corresponds to GET /api/waste-collections
     */
    public function index(): void
    {
        try {
            $data = $this->wasteCollectionService->getCollections();
            $this->apiSuccess($data, '수거 목록 조회 성공');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    /**
     * Register a new waste collection request.
     * Corresponds to POST /api/waste-collections
     */
    public function store(): void
    {
        $user = $this->user();
        try {
            $result = $this->wasteCollectionService->registerCollection($_POST, $_FILES, $user['id'], $user['employee_id'] ?? null);
            $this->apiSuccess($result, '폐기물 수거 정보가 성공적으로 등록되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'VALIDATION_ERROR', 422);
        }
    }

    /**
     * Get waste collections for the admin view with filters.
     * Corresponds to GET /api/waste-collections/admin
     */
    public function getAdminCollections(): void
    {
        try {
            $collections = $this->wasteCollectionService->getAdminCollections($this->request->all());
            $this->apiSuccess($collections);
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    /**
     * Process a collection item.
     * Corresponds to POST /api/waste-collections/admin/{id}/process
     */
    public function processCollection(int $id): void
    {
        try {
            $result = $this->wasteCollectionService->processCollectionById($id);
            $this->apiSuccess($result, '선택한 항목이 처리되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'VALIDATION_ERROR', 422);
        }
    }

    /**
     * Update collection items.
     * Corresponds to PUT /api/waste-collections/admin/{id}/items
     */
    public function updateItems(int $id): void
    {
        try {
            $items = $this->request->input('items', '[]');
            $result = $this->wasteCollectionService->updateCollectionItems($id, $items);
            $this->apiSuccess($result, '품목이 저장되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'VALIDATION_ERROR', 422);
        }
    }

    /**
     * Update admin memo.
     * Corresponds to PUT /api/waste-collections/admin/{id}/memo
     */
    public function updateMemo(int $id): void
    {
        try {
            $memo = $this->request->input('memo', '');
            $result = $this->wasteCollectionService->updateAdminMemo($id, $memo);
            $this->apiSuccess($result, '메모가 업데이트되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'VALIDATION_ERROR', 422);
        }
    }

    /**
     * Parse HTML file for batch registration.
     * Corresponds to POST /api/waste-collections/admin/parse-html
     */
    public function parseHtmlFile(): void
    {
        if (empty($_FILES['htmlFile'])) {
            $this->apiError('HTML 파일이 없습니다.', 'INVALID_INPUT', 400);
            return;
        }
        try {
            $result = $this->wasteCollectionService->parseHtmlFile($_FILES['htmlFile']);
            $this->apiSuccess($result);
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    /**
     * Batch register collections.
     * Corresponds to POST /api/waste-collections/admin/batch-register
     */
    public function batchRegister(): void
    {
        $userId = $this->user()['id'];
        $collections = $this->request->input('collections', []);
        if (empty($collections)) {
            $this->apiError('등록할 데이터가 없습니다.', 'INVALID_INPUT', 400);
            return;
        }
        try {
            $result = $this->wasteCollectionService->batchRegisterCollections($collections, $userId);
            $this->apiSuccess($result, '데이터가 일괄 등록되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    /**
     * Clear all online submissions.
     * Corresponds to DELETE /api/waste-collections/admin/online-submissions
     */
    public function clearOnlineSubmissions(): void
    {
        try {
            $result = $this->wasteCollectionService->clearOnlineSubmissions();
            $this->apiSuccess($result, '모든 인터넷 배출 데이터가 삭제되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }
}