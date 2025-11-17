<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Services\AuthService;
use App\Services\SupplyItemService;

class SupplyItemApiController extends BaseApiController
{
    private SupplyItemService $itemService;

    public function __construct(
        Request $request,
        AuthService $authService,
        SupplyItemService $itemService
    ) {
        parent::__construct($request, $authService);
        $this->itemService = $itemService;
    }

    /**
     * 품목 목록 조회
     * GET /api/supply/items
     */
    public function index(): void
    {
        try {
            $filters = [
                'category_id' => $this->request->get('category_id'),
                'is_active' => $this->request->get('is_active'),
                'search' => $this->request->get('search')
            ];

            $items = $this->itemService->getAllItems(array_filter($filters));

            $this->jsonResponse([
                'success' => true,
                'data' => $items
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 품목 상세 조회
     * GET /api/supply/items/{id}
     */
    public function show(int $id): void
    {
        try {
            $item = $this->itemService->getItemById($id);

            if (!$item) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => '품목을 찾을 수 없습니다.'
                ], 404);
                return;
            }

            $this->jsonResponse([
                'success' => true,
                'data' => $item
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 활성 품목 목록 조회
     * GET /api/supply/items/active
     */
    public function getActiveItems(): void
    {
        try {
            $items = $this->itemService->getActiveItems();

            $this->jsonResponse([
                'success' => true,
                'data' => $items
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 품목 생성
     * POST /api/supply/items
     */
    public function store(): void
    {
        try {
            $data = [
                'item_code' => $this->request->post('item_code'),
                'item_name' => $this->request->post('item_name'),
                'category_id' => $this->request->post('category_id'),
                'unit' => $this->request->post('unit', '개'),
                'description' => $this->request->post('description'),
                'is_active' => $this->request->post('is_active', 1)
            ];

            $itemId = $this->itemService->createItem($data);

            $this->jsonResponse([
                'success' => true,
                'message' => '품목이 생성되었습니다.',
                'data' => ['id' => $itemId]
            ], 201);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => '품목 생성 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 품목 수정
     * PUT /api/supply/items/{id}
     */
    public function update(int $id): void
    {
        try {
            $data = [
                'item_code' => $this->request->post('item_code'),
                'item_name' => $this->request->post('item_name'),
                'category_id' => $this->request->post('category_id'),
                'unit' => $this->request->post('unit'),
                'description' => $this->request->post('description'),
                'is_active' => $this->request->post('is_active')
            ];

            // null 값 제거
            $data = array_filter($data, function ($value) {
                return $value !== null && $value !== '';
            });

            $this->itemService->updateItem($id, $data);

            $this->jsonResponse([
                'success' => true,
                'message' => '품목이 수정되었습니다.'
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => '품목 수정 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 품목 삭제
     * DELETE /api/supply/items/{id}
     */
    public function destroy(int $id): void
    {
        try {
            $this->itemService->deleteItem($id);

            $this->jsonResponse([
                'success' => true,
                'message' => '품목이 삭제되었습니다.'
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => '품목 삭제 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 품목 상태 변경
     * PUT /api/supply/items/{id}/toggle-status
     */
    public function toggleStatus(int $id): void
    {
        try {
            $this->itemService->toggleItemStatus($id);

            $this->jsonResponse([
                'success' => true,
                'message' => '품목 상태가 변경되었습니다.'
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => '품목 상태 변경 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 다음 품목 코드 생성
     * GET /api/supply/items/generate-code
     */
    public function generateCode(): void
    {
        try {
            $code = $this->itemService->generateNextCode();

            $this->jsonResponse([
                'success' => true,
                'data' => ['code' => $code]
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
