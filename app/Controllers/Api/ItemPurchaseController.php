<?php

namespace App\Controllers\Api;

use App\Services\ItemPurchaseService;
use Exception;
use InvalidArgumentException;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use App\Repositories\LogRepository;

class ItemPurchaseController extends BaseApiController
{
    private ItemPurchaseService $itemPurchaseService;
    private LogRepository $logRepository;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        ItemPurchaseService $itemPurchaseService,
        LogRepository $logRepository
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->itemPurchaseService = $itemPurchaseService;
        $this->logRepository = $logRepository;
    }

    /**
     * 구매 내역 목록을 가져옵니다.
     */
    public function index(): void
    {
        try {
            $filters = $this->request->all();
            $purchases = $this->itemPurchaseService->getPurchases($filters);
            $this->apiSuccess($purchases);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 새 구매 내역을 생성합니다.
     */
    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            $newPurchaseId = $this->itemPurchaseService->createPurchase($data);

            if ($newPurchaseId) {
                $this->logAction('item_purchase_create', ['id' => $newPurchaseId, 'data' => $data]);
                $this->apiSuccess(['id' => $newPurchaseId], '구매 내역이 성공적으로 등록되었습니다.');
            } else {
                $this->apiError('구매 내역 등록에 실패했습니다.');
            }
        } catch (InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 구매 내역을 업데이트합니다.
     * @param int $id
     */
    public function update(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            $success = $this->itemPurchaseService->updatePurchase($id, $data);

            if ($success) {
                $this->logAction('item_purchase_update', ['id' => $id, 'data' => $data]);
                $this->apiSuccess(null, '구매 내역이 성공적으로 수정되었습니다.');
            } else {
                $this->apiError('수정에 실패했거나 변경된 내용이 없습니다.');
            }
        } catch (InvalidArgumentException | \RuntimeException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 구매 내역을 삭제합니다.
     * @param int $id
     */
    public function destroy(int $id): void
    {
        try {
            $success = $this->itemPurchaseService->deletePurchase($id);
            if ($success) {
                $this->logAction('item_purchase_delete', ['id' => $id]);
                $this->apiSuccess(null, '구매 내역이 성공적으로 삭제되었습니다.');
            } else {
                $this->apiError('삭제에 실패했습니다.');
            }
        } catch (InvalidArgumentException | \RuntimeException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 구매 내역을 입고 처리합니다.
     * @param int $id
     */
    public function stockIn(int $id): void
    {
        try {
            $success = $this->itemPurchaseService->processStockIn($id);
            if ($success) {
                $this->logAction('item_stock_in', ['id' => $id]);
                $this->apiSuccess(null, '입고 처리가 완료되었습니다.');
            } else {
                $this->apiError('입고 처리에 실패했습니다.');
            }
        } catch (\RuntimeException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    private function logAction(string $action, array $details)
    {
        $currentUser = $this->authService->user();
        $this->logRepository->insert([
            'user_id' => $currentUser['id'],
            'employee_id' => $currentUser['employee_id'],
            'action' => $action,
            'details' => json_encode($details),
        ]);
    }
}
