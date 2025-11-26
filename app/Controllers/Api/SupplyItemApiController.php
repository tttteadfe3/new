<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Services\AuthService;
use App\Services\SupplyItemService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use Exception;

class SupplyItemApiController extends BaseApiController
{
    private SupplyItemService $itemService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        SupplyItemService $itemService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->itemService = $itemService;
    }

    /**
     * 품목 목록 조회
     */
    public function index(): void
    {
        try {
            $filters = [
                'category_id' => $this->request->input('category_id'),
                'is_active' => $this->request->input('is_active'),
                'search' => $this->request->input('search')
            ];

            $items = $this->itemService->getAllItems(array_filter($filters));
            $this->apiSuccess($items);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 품목 상세 조회
     */
    public function show(int $id): void
    {
        try {
            $item = $this->itemService->getItemById($id);
            if (!$item) {
                $this->apiNotFound('품목을 찾을 수 없습니다.');
                return;
            }
            $this->apiSuccess($item);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 활성 품목 목록 조회
     */
    public function getActiveItems(): void
    {
        try {
            $items = $this->itemService->getActiveItems();
            $this->apiSuccess($items);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 품목 생성
     */
    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            $itemId = $this->itemService->createItem($data);
            $this->apiSuccess(['id' => $itemId], '품목이 생성되었습니다.', 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 품목 수정
     */
    public function update(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            $this->itemService->updateItem($id, $data);
            $this->apiSuccess(null, '품목이 수정되었습니다.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 품목 삭제
     */
    public function destroy(int $id): void
    {
        try {
            $this->itemService->deleteItem($id);
            $this->apiSuccess(null, '품목이 삭제되었습니다.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 품목 상태 변경
     */
    public function toggleStatus(int $id): void
    {
        try {
            $this->itemService->toggleItemStatus($id);
            $this->apiSuccess(null, '품목 상태가 변경되었습니다.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }



    /**
     * 예외를 처리합니다.
     */
    protected function handleException(Exception $e): void
    {
        if ($e instanceof \InvalidArgumentException) {
            $this->apiBadRequest($e->getMessage());
        } else {
            $this->apiError('서버 오류가 발생했습니다: ' . $e->getMessage());
        }
    }
}
