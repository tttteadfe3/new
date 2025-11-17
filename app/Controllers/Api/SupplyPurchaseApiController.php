<?php

namespace App\Controllers\Api;

use App\Services\SupplyPurchaseService;
use App\Services\SupplyStockService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use Exception;

class SupplyPurchaseApiController extends BaseApiController
{
    private SupplyPurchaseService $supplyPurchaseService;
    private SupplyStockService $supplyStockService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        SupplyPurchaseService $supplyPurchaseService,
        SupplyStockService $supplyStockService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->supplyPurchaseService = $supplyPurchaseService;
        $this->supplyStockService = $supplyStockService;
    }

    /**
     * 구매 목록을 조회합니다.
     */
    public function index(): void
    {
        try {
            $itemId = $this->request->input('item_id');
            $startDate = $this->request->input('start_date');
            $endDate = $this->request->input('end_date');
            $isReceived = $this->request->input('is_received');
            
            // 필터링 조건에 따라 조회
            if ($itemId) {
                $purchases = $this->supplyPurchaseService->getPurchasesByItem((int) $itemId);
            } elseif ($startDate && $endDate) {
                $purchases = $this->supplyPurchaseService->getPurchasesByDateRange($startDate, $endDate);
            } elseif ($isReceived !== null) {
                $purchases = $this->supplyPurchaseService->getPendingPurchases();
            } else {
                $purchases = $this->supplyPurchaseService->getAllPurchases();
            }
            
            $this->apiSuccess([
                'purchases' => $purchases,
                'total' => count($purchases)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 구매의 상세 정보를 조회합니다.
     */
    public function show(int $id): void
    {
        try {
            $purchase = $this->supplyPurchaseService->getPurchaseById($id);
            if (!$purchase) {
                $this->apiNotFound('구매를 찾을 수 없습니다.');
                return;
            }

            $this->apiSuccess($purchase->toArray());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 새로운 구매를 등록합니다.
     */
    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            
            // 필수 필드 검증
            $requiredFields = ['item_id', 'purchase_date', 'quantity', 'unit_price'];
            if (!$this->validateRequired($data, $requiredFields)) {
                return;
            }

            // 데이터 타입 변환
            $data['item_id'] = (int) $data['item_id'];
            $data['quantity'] = (int) $data['quantity'];
            $data['unit_price'] = (float) $data['unit_price'];
            $data['is_received'] = isset($data['is_received']) ? (bool) $data['is_received'] : false;

            $success = $this->supplyPurchaseService->createPurchase($data);
            
            if ($success) {
                $this->apiSuccess(null, '구매가 성공적으로 등록되었습니다.');
            } else {
                $this->apiError('구매 등록에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 구매 정보를 수정합니다.
     */
    public function update(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data)) {
                $this->apiBadRequest('수정할 데이터가 없습니다.');
                return;
            }

            // 허용된 필드만 필터링
            $allowedFields = ['purchase_date', 'quantity', 'unit_price', 'supplier', 'notes'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            if (empty($updateData)) {
                $this->apiBadRequest('수정 가능한 필드가 없습니다.');
                return;
            }

            // 데이터 타입 변환
            if (isset($updateData['quantity'])) {
                $updateData['quantity'] = (int) $updateData['quantity'];
            }
            if (isset($updateData['unit_price'])) {
                $updateData['unit_price'] = (float) $updateData['unit_price'];
            }

            $success = $this->supplyPurchaseService->updatePurchase($id, $updateData);
            
            if ($success) {
                $this->apiSuccess(null, '구매가 성공적으로 수정되었습니다.');
            } else {
                $this->apiError('구매 수정에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 구매를 삭제합니다.
     */
    public function destroy(int $id): void
    {
        try {
            $success = $this->supplyPurchaseService->deletePurchase($id);
            
            if ($success) {
                $this->apiSuccess(null, '구매가 성공적으로 삭제되었습니다.');
            } else {
                $this->apiError('구매 삭제에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 입고 처리를 합니다.
     */
    public function markReceived(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            $receivedDate = $data['received_date'] ?? null;

            $success = $this->supplyPurchaseService->markAsReceived($id, $receivedDate);
            
            if ($success) {
                $this->apiSuccess(null, '입고 처리가 완료되었습니다.');
            } else {
                $this->apiError('입고 처리에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 미입고 구매 목록을 조회합니다.
     */
    public function getPendingPurchases(): void
    {
        try {
            $pendingPurchases = $this->supplyPurchaseService->getPendingPurchases();
            
            $this->apiSuccess([
                'purchases' => $pendingPurchases,
                'total' => count($pendingPurchases)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 연도별 구매 통계를 조회합니다.
     */
    public function getStatistics(): void
    {
        try {
            $year = $this->request->input('year', date('Y'));
            $year = (int) $year;
            
            $stats = $this->supplyPurchaseService->getPurchaseStatsByYear($year);
            
            $this->apiSuccess([
                'year' => $year,
                'statistics' => $stats
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 품목별 구매 이력을 조회합니다.
     */
    public function getPurchaseHistory(): void
    {
        try {
            $itemId = $this->request->input('item_id');
            
            if (!$itemId) {
                $this->apiBadRequest('품목 ID가 필요합니다.');
                return;
            }

            $purchases = $this->supplyPurchaseService->getPurchasesByItem((int) $itemId);
            
            $this->apiSuccess([
                'item_id' => (int) $itemId,
                'purchases' => $purchases,
                'total' => count($purchases)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 구매 데이터를 검증합니다.
     */
    public function validate(): void
    {
        try {
            $data = $this->getJsonInput();
            
            // 필수 필드 검증
            $requiredFields = ['item_id', 'purchase_date', 'quantity', 'unit_price'];
            if (!$this->validateRequired($data, $requiredFields)) {
                return;
            }

            // 데이터 타입 변환
            $data['item_id'] = (int) $data['item_id'];
            $data['quantity'] = (int) $data['quantity'];
            $data['unit_price'] = (float) $data['unit_price'];

            // 서비스 레이어에서 검증
            $errors = $this->supplyPurchaseService->validatePurchaseData($data);
            
            if (empty($errors)) {
                $this->apiSuccess([
                    'valid' => true,
                    'message' => '유효한 구매 데이터입니다.'
                ]);
            } else {
                $this->apiBadRequest('데이터 검증 실패: ' . implode(', ', $errors));
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 일괄 입고 처리를 합니다.
     */
    public function bulkReceive(): void
    {
        try {
            $data = $this->getJsonInput();
            
            if (!isset($data['purchase_ids']) || !is_array($data['purchase_ids'])) {
                $this->apiBadRequest('입고 처리할 구매 ID 목록이 필요합니다.');
                return;
            }

            $purchaseIds = $data['purchase_ids'];
            $receivedDate = $data['received_date'] ?? date('Y-m-d');

            $successCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($purchaseIds as $purchaseId) {
                try {
                    $this->supplyPurchaseService->markAsReceived((int) $purchaseId, $receivedDate);
                    $successCount++;
                } catch (Exception $e) {
                    $errors[] = "구매 ID {$purchaseId}: " . $e->getMessage();
                    $failedCount++;
                }
            }

            $this->apiSuccess([
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'errors' => $errors
            ], "일괄 입고 처리가 완료되었습니다. (성공: {$successCount}건, 실패: {$failedCount}건)");
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 구매 검색을 수행합니다.
     */
    public function search(): void
    {
        try {
            $query = $this->request->input('q', '');
            $startDate = $this->request->input('start_date');
            $endDate = $this->request->input('end_date');
            
            if (empty($query) && (!$startDate || !$endDate)) {
                $this->apiBadRequest('검색어 또는 날짜 범위를 입력해주세요.');
                return;
            }

            // 날짜 범위로 먼저 필터링
            if ($startDate && $endDate) {
                $purchases = $this->supplyPurchaseService->getPurchasesByDateRange($startDate, $endDate);
            } else {
                $purchases = $this->supplyPurchaseService->getAllPurchases();
            }

            // 검색어로 추가 필터링
            if (!empty($query)) {
                $purchases = array_filter($purchases, function($purchase) use ($query) {
                    return stripos($purchase['item_name'], $query) !== false || 
                           stripos($purchase['item_code'], $query) !== false ||
                           stripos($purchase['supplier'] ?? '', $query) !== false;
                });
            }

            $this->apiSuccess([
                'query' => $query,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'purchases' => array_values($purchases),
                'total' => count($purchases)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 재고 현황을 조회합니다.
     */
    public function getStockStatus(): void
    {
        try {
            $itemId = $this->request->input('item_id');
            
            if (!$itemId) {
                $this->apiBadRequest('품목 ID가 필요합니다.');
                return;
            }

            $currentStock = $this->supplyStockService->getCurrentStock((int) $itemId);
            $stockHistory = $this->supplyStockService->getStockHistory((int) $itemId);
            
            $this->apiSuccess([
                'item_id' => (int) $itemId,
                'current_stock' => $currentStock,
                'history' => $stockHistory
            ]);
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
