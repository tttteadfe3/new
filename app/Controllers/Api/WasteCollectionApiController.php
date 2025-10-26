<?php

namespace App\Controllers\Api;

use App\Services\WasteCollectionService;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;

class WasteCollectionApiController extends BaseApiController
{
    private WasteCollectionService $wasteCollectionService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        WasteCollectionService $wasteCollectionService
    ) {
        parent::__construct(
            $request,
            $authService,
            $viewDataService,
            $activityLogger,
            $employeeRepository,
            $jsonResponse
        );
        $this->wasteCollectionService = $wasteCollectionService;
    }

    /**
     * 현재 사용자의 폐기물 수거 내역을 가져옵니다.
     * GET /api/waste-collections에 해당합니다.
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
     * 새 폐기물 수거 요청을 등록합니다.
     * POST /api/waste-collections에 해당합니다.
     */
    public function store(): void
    {
        $user = $this->user();
        try {
            $result = $this->wasteCollectionService->registerCollection($_POST, $_FILES, $user['employee_id']);
            $this->apiSuccess($result, '폐기물 수거 정보가 성공적으로 등록되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'VALIDATION_ERROR', 422);
        }
    }

    /**
     * 필터가 적용된 관리자 보기의 폐기물 수거 내역을 가져옵니다.
     * GET /api/waste-collections/admin에 해당합니다.
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
     * 수거 항목을 처리합니다.
     * POST /api/waste-collections/admin/{id}/process에 해당합니다.
     */
    public function processCollection(int $id): void
    {
        $user = $this->user();
        try {
            $result = $this->wasteCollectionService->processCollectionById($id, $user['employee_id']);
            $this->apiSuccess($result, '선택한 항목이 처리되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'VALIDATION_ERROR', 422);
        }
    }

    /**
     * 수거 품목을 업데이트합니다.
     * PUT /api/waste-collections/admin/{id}/items에 해당합니다.
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
     * 관리자 메모를 업데이트합니다.
     * PUT /api/waste-collections/admin/{id}/memo에 해당합니다.
     */
    public function updateMemo(int $id): void
    {
        $user = $this->user();
        try {
            $memo = $this->request->input('memo', '');
            $result = $this->wasteCollectionService->updateAdminMemo($id, $memo, $user['employee_id']);
            $this->apiSuccess($result, '메모가 업데이트되었습니다.');
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'VALIDATION_ERROR', 422);
        }
    }

    /**
     * 일괄 등록을 위해 HTML 파일을 구문 분석합니다.
     * POST /api/waste-collections/admin/parse-html에 해당합니다.
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
     * 수거 내역을 일괄 등록합니다.
     * POST /api/waste-collections/admin/batch-register에 해당합니다.
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
     * 모든 온라인 제출을 지웁니다.
     * DELETE /api/waste-collections/admin/online-submissions에 해당합니다.
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
