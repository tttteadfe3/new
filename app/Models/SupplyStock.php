<?php

namespace App\Models;

class SupplyStock extends BaseModel
{
    protected array $fillable = [
        'item_id',
        'total_purchased',
        'total_distributed',
        'current_stock',
        'last_updated'
    ];

    protected array $rules = [
        'item_id' => 'required|integer',
        'total_purchased' => 'integer|min:0',
        'total_distributed' => 'integer|min:0',
        'current_stock' => 'integer|min:0',
        'last_updated' => 'date'
    ];

    /**
     * 재고를 업데이트합니다.
     */
    public function updateStock(int $quantity, string $type): void
    {
        switch ($type) {
            case 'purchase':
                $this->setAttribute('total_purchased', $this->getTotalPurchased() + $quantity);
                break;
            case 'distribution':
                $this->setAttribute('total_distributed', $this->getTotalDistributed() + $quantity);
                break;
            case 'cancel_distribution':
                $this->setAttribute('total_distributed', max(0, $this->getTotalDistributed() - $quantity));
                break;
        }

        // 현재 재고 자동 계산
        $this->calculateCurrentStock();
        $this->setAttribute('last_updated', date('Y-m-d H:i:s'));
    }

    /**
     * 요청된 수량만큼 재고가 있는지 확인합니다.
     */
    public function hasAvailableStock(int $requestedQuantity): bool
    {
        return $this->getCurrentStock() >= $requestedQuantity;
    }

    /**
     * 현재 재고를 계산합니다.
     */
    public function calculateCurrentStock(): int
    {
        $currentStock = $this->getTotalPurchased() - $this->getTotalDistributed();
        $this->setAttribute('current_stock', max(0, $currentStock));
        return $currentStock;
    }

    /**
     * 총 구매량을 가져옵니다.
     */
    public function getTotalPurchased(): int
    {
        return (int) $this->getAttribute('total_purchased');
    }

    /**
     * 총 지급량을 가져옵니다.
     */
    public function getTotalDistributed(): int
    {
        return (int) $this->getAttribute('total_distributed');
    }

    /**
     * 현재 재고를 가져옵니다.
     */
    public function getCurrentStock(): int
    {
        return (int) $this->getAttribute('current_stock');
    }

    /**
     * 재고가 부족한지 확인합니다.
     */
    public function isLowStock(int $threshold = 10): bool
    {
        return $this->getCurrentStock() <= $threshold;
    }

    /**
     * 재고 데이터의 유효성을 검증합니다.
     */
    public function validate(): bool
    {
        $isValid = parent::validate();

        // 비즈니스 규칙: 현재 재고는 자동 계산
        $this->calculateCurrentStock();

        // 비즈니스 규칙: 지급량이 구매량을 초과할 수 없음
        if ($this->getTotalDistributed() > $this->getTotalPurchased()) {
            $this->errors['total_distributed'] = '지급량이 구매량을 초과할 수 없습니다.';
            $isValid = false;
        }

        return $isValid;
    }
}