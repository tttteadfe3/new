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
            // 재고 복원에 실패했더라도(이미 0이거나 등등) 프로세스는 계속 진행
            error_log("Failed to restore stock for item {$itemId}, quantity {$quantity}");
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
     * 특정 날짜 기준 재고를 계산합니다.
     */
    public function getStockAsOfDate(int $itemId, string $date): int
    {
        // 해당 날짜까지의 입고량 계산
        $purchasedSql = "SELECT COALESCE(SUM(quantity), 0) as total
                         FROM supply_purchases
                         WHERE item_id = :item_id 
                           AND is_received = 1
                           AND purchase_date <= :date";
        
        $purchasedResult = $this->stockRepository->db->fetchOne($purchasedSql, [
            ':item_id' => $itemId,
            ':date' => $date
        ]);
        $totalPurchased = (int) ($purchasedResult['total'] ?? 0);

        // 해당 날짜까지의 지급량 계산 (취소되지 않은 문서만)
        $distributedSql = "SELECT COALESCE(SUM(di.quantity * (
                                SELECT COUNT(*) 
                                FROM supply_distribution_document_employees de 
                                WHERE de.document_id = d.id
                            )), 0) as total
                           FROM supply_distribution_documents d
                           JOIN supply_distribution_document_items di ON d.id = di.document_id
                           WHERE di.item_id = :item_id
                             AND d.distribution_date <= :date
                             AND (d.status IS NULL OR d.status != '취소')";
        
        $distributedResult = $this->stockRepository->db->fetchOne($distributedSql, [
            ':item_id' => $itemId,
            ':date' => $date
        ]);
        $totalDistributed = (int) ($distributedResult['total'] ?? 0);

        return $totalPurchased - $totalDistributed;
    }

    /**
     * 지급 가능한 품목인지 검증합니다.
     * 
     * @param int $itemId 품목 ID
     * @param int $quantity 요청 수량
     * @param string|null $distributionDate 지급일자 (YYYY-MM-DD). null이면 현재 재고 기준으로 확인
     */
    public function validateDistribution(int $itemId, int $quantity, ?string $distributionDate = null): void
    {
        // 품목 존재 여부 확인
        $item = $this->itemRepository->findById($itemId);
        if (!$item) {
            throw new \InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        // 품목 활성화 여부 확인
        $isActive = is_array($item) ? ($item['is_active'] ?? false) : $item->getAttribute('is_active');
        if (!$isActive) {
            throw new \InvalidArgumentException('비활성화된 품목은 지급할 수 없습니다.');
        }

        // 수량 검증
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('수량은 양수여야 합니다.');
        }

        // 재고 확인 - 날짜 기준 또는 현재 재고
        if ($distributionDate) {
            $availableStock = $this->getStockAsOfDate($itemId, $distributionDate);
            if ($availableStock < $quantity) {
                $itemName = is_array($item) ? $item['item_name'] : $item->getAttribute('item_name');
                throw new \InvalidArgumentException(
                    "재고가 부족합니다. {$distributionDate} 기준 '{$itemName}' 재고: {$availableStock}, 요청 수량: {$quantity}"
                );
            }
        } else {
            if (!$this->hasAvailableStock($itemId, $quantity)) {
                $currentStock = $this->getCurrentStock($itemId);
                throw new \InvalidArgumentException(
                    "재고가 부족합니다. 현재 재고: {$currentStock}, 요청 수량: {$quantity}"
                );
            }
        }
    }

    /**
     * 재고 목록을 조회합니다.
     */
    public function getStockList(array $filters = []): array
    {
        return $this->stockRepository->getStockList($filters);
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

    /**
     * 재고 상세 정보를 조회합니다.
     */
    public function getStockDetails(int $stockId): ?array
    {
        return $this->stockRepository->getStockDetails($stockId);
    }
    /**
     * 재고가 있는 품목을 조회합니다.
     */
    public function findItemsWithStock(): array
    {
        return $this->stockRepository->findItemsWithStock();
    }
}
