<?php

namespace App\Controllers\Api;

use App\Services\VehicleConsumableService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use Exception;

class VehicleConsumableApiController extends BaseApiController
{
    private VehicleConsumableService $service;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        VehicleConsumableService $service
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->service = $service;
    }

    // ============ 카테고리 관리 ============

    /**
     * 카테고리 목록 조회
     */
    public function categories(): void
    {
        try {
            $filters = [
                'parent_id' => $this->request->input('parent_id'),
                'level' => $this->request->input('level'),
                'search' => $this->request->input('search')
            ];

            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $categories = $this->service->getAllCategories($filters);
            $this->apiSuccess($categories);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 카테고리 트리 조회
     */
    public function categoryTree(): void
    {
        try {
            $tree = $this->service->getCategoryTree();
            $this->apiSuccess($tree);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 카테고리 상세 조회
     */
    public function show(int $id): void
    {
        try {
            $category = $this->service->getCategory($id);
            
            if (!$category) {
                $this->apiError('카테고리를 찾을 수 없습니다', 'NOT_FOUND', 404);
                return;
            }
            
            $this->apiSuccess($category);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 카테고리 등록
     */
    public function store(): void
    {
        try {
            $data = $this->request->all();
            $id = $this->service->createCategory($data);
            $this->apiSuccess(['id' => $id], '카테고리가 등록되었습니다', 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 카테고리 수정
     */
    public function update(int $id): void
    {
        try {
            $data = $this->request->all();
            $this->service->updateCategory($id, $data);
            $this->apiSuccess(null, '카테고리가 수정되었습니다');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 카테고리 삭제
     */
    public function destroy(int $id): void
    {
        try {
            $this->service->deleteCategory($id);
            $this->apiSuccess(null, '카테고리가 삭제되었습니다');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ============ 입고 관리 ============

    /**
     * 입고 처리
     */
    public function stockIn(): void
    {
        try {
            $user = $this->authService->user();
            
            $data = $this->request->all();
            $data['registered_by'] = $user['employee_id'] ?? null;
            
            $id = $this->service->stockIn($data);
            $this->apiSuccess(['id' => $id], '입고 처리되었습니다', 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 입고 이력 조회
     */
    public function stockInHistory(int $categoryId): void
    {
        try {
            $history = $this->service->getStockInHistory($categoryId);
            $this->apiSuccess($history);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ============ 사용 관리 ============

    /**
     * 사용 처리
     */
    public function use(): void
    {
        try {
            $user = $this->authService->user();
            
            $data = $this->request->all();
            $data['used_by'] = $user['employee_id'] ?? null;
            
            $id = $this->service->useConsumable($data);
            $this->apiSuccess(['id' => $id], '사용 처리되었습니다', 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 사용 이력 조회
     */
    public function usageHistory(int $categoryId): void
    {
        try {
            $history = $this->service->getUsageHistory($categoryId);
            $this->apiSuccess($history);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ============ 재고 조회 ============

    /**
     * 카테고리별 재고 조회
     */
    public function stockByCategory(int $categoryId): void
    {
        try {
            $stock = $this->service->getStockByCategory($categoryId);
            $this->apiSuccess($stock);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 품명별 재고 조회
     */
    public function stockByItem(int $categoryId): void
    {
        try {
            $items = $this->service->getStockByItem($categoryId);
            $this->apiSuccess($items);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 재고 조정
     */
    public function adjustStock(): void
    {
        try {
            $categoryId = $this->request->input('category_id');
            $quantity = $this->request->input('quantity');
            $itemName = $this->request->input('item_name') ?? '재고조정';
            
            if ($quantity === null || $quantity == 0) {
                $this->apiError('조정 수량을 입력해주세요', 'INVALID_INPUT', 400);
                return;
            }
            
            $this->service->adjustStock($categoryId, (int)$quantity, $itemName);
            $this->apiSuccess(null, '재고가 조정되었습니다');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
