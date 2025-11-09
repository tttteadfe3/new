<?php

namespace App\Services;

use App\Models\ItemPurchase;
use App\Repositories\ItemPurchaseRepository;
use App\Repositories\ItemRepository;
use App\Repositories\ItemPlanRepository;
use InvalidArgumentException;

class ItemPurchaseService
{
    private ItemPurchaseRepository $itemPurchaseRepository;
    private ItemRepository $itemRepository;
    private ItemPlanRepository $itemPlanRepository;
    private AuthService $authService;

    public function __construct(
        ItemPurchaseRepository $itemPurchaseRepository,
        ItemRepository $itemRepository,
        ItemPlanRepository $itemPlanRepository,
        AuthService $authService
    ) {
        $this->itemPurchaseRepository = $itemPurchaseRepository;
        $this->itemRepository = $itemRepository;
        $this->itemPlanRepository = $itemPlanRepository;
        $this->authService = $authService;
    }

    /**
     * 필터링된 구매 내역 목록을 조회합니다.
     * @param array $filters
     * @return array
     */
    public function getPurchases(array $filters = []): array
    {
        return $this->itemPurchaseRepository->findAll($filters);
    }

    /**
     * 새로운 구매 내역을 생성합니다.
     * @param array $data
     * @return string
     */
    public function createPurchase(array $data): string
    {
        $user = $this->authService->user();
        $data['created_by'] = $user['employee_id'] ?? null;

        $purchase = ItemPurchase::make($data);
        if (!$purchase->validate()) {
            throw new InvalidArgumentException(implode(', ', $purchase->getErrors()));
        }

        if (!$this->itemRepository->findById($data['item_id'])) {
            throw new InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        if (!empty($data['plan_id']) && !$this->itemPlanRepository->findById($data['plan_id'])) {
            throw new InvalidArgumentException('존재하지 않는 계획입니다.');
        }

        return $this->itemPurchaseRepository->create($data);
    }

    /**
     * 구매 내역을 수정합니다. (입고 전만 가능)
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updatePurchase(int $id, array $data): bool
    {
        $purchase = $this->itemPurchaseRepository->findById($id);
        if (!$purchase) {
            throw new InvalidArgumentException('존재하지 않는 구매 내역입니다.');
        }
        if ($purchase['is_stocked'] == 1) {
            throw new \RuntimeException('이미 입고된 내역은 수정할 수 없습니다.');
        }

        $mergedData = array_merge($purchase, $data);
        $validationModel = ItemPurchase::make($mergedData);
        if (!$validationModel->validate()) {
            throw new InvalidArgumentException(implode(', ', $validationModel->getErrors()));
        }

        return $this->itemPurchaseRepository->update($id, $data);
    }

    /**
     * 구매 내역을 삭제합니다. (입고 전만 가능)
     * @param int $id
     * @return bool
     */
    public function deletePurchase(int $id): bool
    {
        $purchase = $this->itemPurchaseRepository->findById($id);
        if (!$purchase) {
            throw new InvalidArgumentException('존재하지 않는 구매 내역입니다.');
        }

        if (!$this->itemPurchaseRepository->delete($id)) {
            throw new \RuntimeException('이미 입고된 내역은 삭제할 수 없습니다.');
        }

        return true;
    }

    /**
     * 구매 내역을 입고 처리합니다.
     * @param int $purchaseId
     * @return bool
     */
    public function processStockIn(int $purchaseId): bool
    {
        $user = $this->authService->user();
        $stockerId = $user['employee_id'] ?? null;

        if (!$stockerId) {
             throw new \RuntimeException('입고 처리자 정보를 찾을 수 없습니다.');
        }

        $success = $this->itemPurchaseRepository->processStockIn($purchaseId, $stockerId);

        if (!$success) {
            throw new \RuntimeException('입고 처리에 실패했습니다. 이미 처리되었거나 존재하지 않는 구매 내역일 수 있습니다.');
        }

        return true;
    }
}
