<?php

namespace App\Controllers\Api;

use App\Services\SupplyStockService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use Exception;

class SupplyStockApiController extends BaseApiController
{
    private SupplyStockService $supplyStockService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        SupplyStockService $supplyStockService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->supplyStockService = $supplyStockService;
    }

    /**
     * 재고 목록을 조회합니다.
     */
    public function index(): void
    {
        try {
            $filters = [
                'category_id' => $this->request->input('category_id'),
                'stock_status' => $this->request->input('stock_status'),
                'search' => $this->request->input('search')
            ];

            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $stocks = $this->supplyStockService->getStockList($filters);

            $this->apiSuccess($stocks);
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

    /**
     * 재고 상세 정보를 조회합니다.
     */
    public function show(int $id): void
    {
        try {
            $stockDetails = $this->supplyStockService->getStockDetails($id);
            if (!$stockDetails) {
                $this->apiNotFound('재고 정보를 찾을 수 없습니다.');
                return;
            }
            $this->apiSuccess($stockDetails);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
