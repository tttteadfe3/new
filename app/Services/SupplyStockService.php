<?php

namespace App\Services;

use App\Repositories\SupplyStockRepository;
use App\Repositories\SupplyItemRepository;
use App\Models\SupplyStock;
use App\Services\ActivityLogger;

class SupplyStockService
{
    private SupplyStockRepository $stockRepository;
    private SupplyItemRepository $itemRepository;
    private ActivityLogger $activityLogger;

    public function __construct(
        SupplyStockRepository $stockRepository,
        SupplyItemRepository $itemRepository,
        ActivityLogger $activityLogger
    ) {
        $this->stockRepository = $stockRepository;
        $this->itemRepository = $itemRepository;
        $this->activityLogger = $activityLogger;
    }

    /**
     * 구매로 인한 재고 증가를 처리합니다.
     */
    public function updateStockFromPurchase(int $itemId, int $quantity): void
    {
        // 품목 존재 여부 확인
        $item = $this->itemRepository->findById($itemId);
        if (!$item) {
            throw new \InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        // 수량 검증
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('수량은 양수여야 합니다.');
        }

        // 재고 레코드 초기화 (없으면 생성)
        $this->stockRepository->initializeStock($itemId);

        // 재고 업데이트
        $success = $this->stockRepository->updateStock($itemId, $quantity, 'purchase');
        
        if (!$success) {
            throw new \RuntimeException('재고 업데이트에 실패했습니다.');
        }
    }

    /**
     * 지급으로 인한 재고 감소를 처리합니다.
     */
    public function updateStockFromDistribution(int $itemId, int $quantity): void
    {
        // 품목 존재 여부 확인
        $item = $this->itemRepository->findById($itemId);
        if (!$item) {
            throw new \InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        // 수량 검증
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('수량은 양수여야 합니다.');
        }

        // 재고 확인
        $currentStock = $this->getCurrentStock($itemId);
        if ($currentStock < $quantity) {
            throw new \InvalidArgumentException(
                "재고가 부족합니다. 현재 재고: {$currentStock}, 요청 수량: {$quantity}"
            );
        }

        // 재고 업데이트
        $success = $this->stockRepository->updateStock($itemId, $quantity, 'distribution');
        
        if (!$success) {
            throw new \RuntimeException('재고 업데이트에 실패했습니다.');
        }
    }

    /**
     * 지급 취소로 인한 재고 복원을 처리합니다.
     */
    public function updateStockFromCancelDistribution(int $itemId, int $quantity): void
    {
        // 품목 존재 여부 확인
        $item = $this->itemRepository->findById($itemId);
        if (!$item) {
            throw new \InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        // 수량 검증
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('수량은 양수여야 합니다.');
        }

        // 재고 복원
        $success = $this->stockRepository->updateStock($itemId, $quantity, 'cancel_distribution');
        
        if (!$success) {
            throw new \RuntimeException('재고 복원에 실패했습니다.');
        }
    }

    /**
     * 품목의 현재 재고를 조회합니다.
     */
    public function getCurrentStock(int $itemId): int
    {
        // 재고 레코드 초기화 (없으면 생성)
        $this->stockRepository->initializeStock($itemId);
        
        return $this->stockRepository->getCurrentStock($itemId);
    }

    /**
     * 재고 부족 품목을 조회합니다.
     */
    public function getLowStockItems(int $threshold = 10): array
    {
        return $this->stockRepository->findLowStockItems($threshold);
    }

    /**
     * 재고가 있는 품목을 조회합니다.
     */
    public function getItemsWithStock(): array
    {
        return $this->stockRepository->findItemsWithStock();
    }

    /**
     * 재고가 없는 품목을 조회합니다.
     */
    public function getOutOfStockItems(): array
    {
        return $this->stockRepository->findOutOfStockItems();
    }

    /**
     * 모든 재고 현황을 조회합니다.
     */
    public function getAllStocks(): array
    {
        return $this->stockRepository->findWithItems();
    }

    /**
     * 품목별 재고 상세 정보를 조회합니다.
     */
    public function getStockByItemId(int $itemId): ?SupplyStock
    {
        return $this->stockRepository->findByItemId($itemId);
    }

    /**
     * 품목의 재고 변동 이력을 조회합니다.
     */
    public function getStockHistory(int $itemId): array
    {
        // 품목 존재 여부 확인
        $item = $this->itemRepository->findById($itemId);
        if (!$item) {
            throw new \InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        return $this->stockRepository->getStockHistory($itemId);
    }

    /**
     * 재고 요약 정보를 조회합니다.
     */
    public function getStockSummary(): array
    {
        return $this->stockRepository->getStockSummary();
    }

    /**
     * 재고 부족 여부를 확인합니다.
     */
    public function hasAvailableStock(int $itemId, int $requestedQuantity): bool
    {
        $currentStock = $this->getCurrentStock($itemId);
        return $currentStock >= $requestedQuantity;
    }

    /**
     * 지급 가능한 품목인지 검증합니다.
     */
    public function validateDistribution(int $itemId, int $quantity): void
    {
        // 품목 존재 여부 확인
        $item = $this->itemRepository->findById($itemId);
        if (!$item) {
            throw new \InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        // 품목 활성화 여부 확인
        if (!$item->getAttribute('is_active')) {
            throw new \InvalidArgumentException('비활성화된 품목은 지급할 수 없습니다.');
        }

        // 수량 검증
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('수량은 양수여야 합니다.');
        }

        // 재고 확인
        if (!$this->hasAvailableStock($itemId, $quantity)) {
            $currentStock = $this->getCurrentStock($itemId);
            throw new \InvalidArgumentException(
                "재고가 부족합니다. 현재 재고: {$currentStock}, 요청 수량: {$quantity}"
            );
        }
    }

    /**
     * 품목별 재고를 초기화합니다.
     */
    public function initializeStock(int $itemId): bool
    {
        // 품목 존재 여부 확인
        $item = $this->itemRepository->findById($itemId);
        if (!$item) {
            throw new \InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        return $this->stockRepository->initializeStock($itemId);
    }
}
