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

    /**
     * 소모품 목록 조회
     */
    public function index(): void
    {
        try {
            $filters = [
                'category' => $this->request->input('category'),
                'search' => $this->request->input('search'),
                'low_stock' => $this->request->input('low_stock')
            ];

            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $consumables = $this->service->getAllConsumables($filters);
            $this->apiSuccess($consumables);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 소모품 상세 조회
     */
    public function show(int $id): void
    {
        try {
            $consumable = $this->service->getConsumable($id);
            
            if (!$consumable) {
                $this->apiError('소모품을 찾을 수 없습니다', 'NOT_FOUND', 404);
                return;
            }
            
            $this->apiSuccess($consumable);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 소모품 등록
     */
    public function store(): void
    {
        try {
            $data = $this->request->all();
            $id = $this->service->createConsumable($data);
            $this->apiSuccess(['id' => $id], '소모품이 등록되었습니다', 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 소모품 수정
     */
    public function update(int $id): void
    {
        try {
            $data = $this->request->all();
            $this->service->updateConsumable($id, $data);
            $this->apiSuccess(null, '소모품이 수정되었습니다');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 소모품 삭제
     */
    public function destroy(int $id): void
    {
        try {
            $this->service->deleteConsumable($id);
            $this->apiSuccess(null, '소모품이 삭제되었습니다');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 입고 처리
     */
    public function stockIn(int $id): void
    {
        try {
            $user = $this->authService->user();
            
            $data = $this->request->all();
            $data['registered_by'] = $user['employee_id'] ?? null;
            
            $this->service->stockIn($id, $data);
            $this->apiSuccess(null, '입고 처리되었습니다');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 출고/사용 처리
     */
    public function use(int $id): void
    {
        try {
            $user = $this->authService->user();
            
            $data = $this->request->all();
            $data['used_by'] = $user['employee_id'] ?? null;
            
            $this->service->useConsumable($id, $data);
            $this->apiSuccess(null, '출고 처리되었습니다');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 카테고리 목록
     */
    public function categories(): void
    {
        try {
            $categories = $this->service->getCategories();
            $this->apiSuccess($categories);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 사용 이력
     */
    public function usageHistory(int $id): void
    {
        try {
            $history = $this->service->getUsageHistory($id);
            $this->apiSuccess($history);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 입고 이력
     */
    public function stockInHistory(int $id): void
    {
        try {
            $history = $this->service->getStockInHistory($id);
            $this->apiSuccess($history);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
