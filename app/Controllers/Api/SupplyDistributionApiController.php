<?php

namespace App\Controllers\Api;

use App\Services\SupplyDistributionService;
use App\Services\SupplyStockService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use Exception;

class SupplyDistributionApiController extends BaseApiController
{
    private SupplyDistributionService $supplyDistributionService;
    private SupplyStockService $supplyStockService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        SupplyDistributionService $supplyDistributionService,
        SupplyStockService $supplyStockService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->supplyDistributionService = $supplyDistributionService;
        $this->supplyStockService = $supplyStockService;
    }

    /**
     * 지급 문서 목록을 조회합니다.
     */
    public function index(): void
    {
        try {
            $search = $this->request->input('search', '');
            
            $filters = [];
            if (!empty($search)) {
                $filters['search'] = $search;
            }
            
            $documents = $this->supplyDistributionService->getDocuments($filters);
            
            $this->apiSuccess([
                'distributions' => $documents,
                'total' => count($documents)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 지급 문서의 상세 정보를 조회합니다.
     */
    public function show(int $id): void
    {
        try {
            $document = $this->supplyDistributionService->getDocumentById($id);
            
            if (!$document) {
                $this->apiNotFound('지급 문서를 찾을 수 없습니다.');
                return;
            }
            
            $this->apiSuccess($document);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 지급 가능한 품목 목록을 조회합니다.
     */
    public function getAvailableItems(): void
    {
        try {
            $items = $this->supplyDistributionService->getAvailableItems();
            
            $this->apiSuccess([
                'items' => $items,
                'total' => count($items)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 지급 문서를 생성합니다.
     */
    public function storeDocument(): void
    {
        try {
            $data = $this->getJsonInput();
            
            // Basic validation
            if (empty($data['title']) || empty($data['items']) || empty($data['employees'])) {
                $this->apiBadRequest('문서 제목, 품목, 직원은 필수입니다.');
                return;
            }

            $documentId = $this->supplyDistributionService->createDocument($data, $this->getCurrentEmployeeId());

            $this->apiSuccess(['document_id' => $documentId], '지급 문서가 성공적으로 생성되었습니다.');
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 지급 문서를 수정합니다.
     */
    public function updateDocument(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            
            // Basic validation
            if (empty($data['title']) || empty($data['items']) || empty($data['employees'])) {
                $this->apiBadRequest('문서 제목, 품목, 직원은 필수입니다.');
                return;
            }

            $this->supplyDistributionService->updateDocument($id, $data, $this->getCurrentEmployeeId());

            $this->apiSuccess(['document_id' => $id], '지급 문서가 성공적으로 수정되었습니다.');
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 지급 문서를 삭제합니다.
     */
    public function deleteDocument(int $id): void
    {
        try {
            $this->supplyDistributionService->deleteDocument($id);

            $this->apiSuccess(null, '지급 문서가 성공적으로 삭제되었습니다. 재고가 복원되었습니다.');
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 지급 문서를 취소합니다.
     */
    public function cancelDocument(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            $reason = $data['cancel_reason'] ?? $data['reason'] ?? null;
            
            if (empty($reason)) {
                $this->apiBadRequest('취소 사유는 필수입니다.');
                return;
            }

            $this->supplyDistributionService->cancelDocument($id, $reason);

            $this->apiSuccess(null, '지급 문서가 성공적으로 취소되었습니다. 재고가 복원되었습니다.');
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 재고 확인을 수행합니다.
     */
    public function checkStock(): void
    {
        try {
            $itemId = $this->request->input('item_id');
            $quantity = $this->request->input('quantity');
            
            if (!$itemId || !$quantity) {
                $this->apiBadRequest('품목 ID와 수량이 필요합니다.');
                return;
            }

            $itemId = (int) $itemId;
            $quantity = (int) $quantity;
            
            $currentStock = $this->supplyStockService->getCurrentStock($itemId);
            $hasStock = $this->supplyStockService->hasAvailableStock($itemId, $quantity);
            
            $this->apiSuccess([
                'item_id' => $itemId,
                'requested_quantity' => $quantity,
                'current_stock' => $currentStock,
                'has_available_stock' => $hasStock,
                'message' => $hasStock 
                    ? '지급 가능한 재고가 있습니다.' 
                    : "재고가 부족합니다. (현재 재고: {$currentStock}, 요청 수량: {$quantity})"
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 부서의 직원 목록을 조회합니다.
     */
    public function getEmployeesByDepartment(int $deptId): void
    {
        try {
            $employees = $this->employeeRepository->getByDepartment($deptId);
            
            $this->apiSuccess([
                'department_id' => $deptId,
                'employees' => $employees,
                'total' => count($employees)
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 현재 로그인한 사용자의 직원 ID를 가져옵니다.
     */
    protected function getCurrentEmployeeId(): int
    {
        $user = $this->authService->user();
        if (!$user) {
            throw new \RuntimeException('로그인이 필요합니다.');
        }
        if (empty($user['employee_id'])) {
            throw new \RuntimeException('직원 정보가 연결되지 않았습니다.');
        }
        return (int) $user['employee_id'];
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