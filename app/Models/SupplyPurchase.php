<?php

namespace App\Models;

class SupplyPurchase extends BaseModel
{
    protected array $fillable = [
        'item_id',
        'purchase_date',
        'quantity',
        'unit_price',
        'total_amount',
        'supplier',
        'is_received',
        'received_date',
        'notes',
        'created_by'
    ];

    protected array $rules = [
        'item_id' => 'required|integer',
        'purchase_date' => 'required|date',
        'quantity' => 'required|integer|min:1',
        'unit_price' => 'required|numeric|min:0',
        'supplier' => 'string|max:200',
        'is_received' => 'integer|in:0,1',
        'received_date' => 'date',
        'notes' => 'string',
        'created_by' => 'required|integer'
    ];

    /**
     * 구매한 지급품 정보를 가져옵니다.
     */
    public function getItem(): ?SupplyItem
    {
        // 이 메서드는 리포지토리에서 구현됩니다
        return null;
    }

    /**
     * 총 구매 금액을 계산합니다.
     */
    public function calculateTotalAmount(): float
    {
        $quantity = (int) $this->getAttribute('quantity');
        $unitPrice = (float) $this->getAttribute('unit_price');
        
        return $quantity * $unitPrice;
    }

    /**
     * 입고 완료 여부를 확인합니다.
     */
    public function isReceived(): bool
    {
        return (bool) $this->getAttribute('is_received');
    }

    /**
     * 입고 처리를 합니다.
     */
    public function markAsReceived(): void
    {
        $this->setAttribute('is_received', 1);
        $this->setAttribute('received_date', date('Y-m-d'));
    }

    /**
     * 재고를 업데이트합니다 (비즈니스 로직은 서비스에서 처리).
     */
    public function updateStock(): void
    {
        // 이 메서드는 서비스 레이어에서 구현됩니다
        // 재고 업데이트 로직은 SupplyStockService에서 처리
    }

    /**
     * 구매 데이터의 유효성을 검증합니다.
     */
    public function validate(bool $isUpdate = false): bool
    {
        $isValid = parent::validate($isUpdate);

        // 비즈니스 규칙: 총 금액 자동 계산
        $this->setAttribute('total_amount', $this->calculateTotalAmount());

        // 비즈니스 규칙: 구매일은 미래일 수 없음
        $purchaseDate = $this->getAttribute('purchase_date');
        if ($purchaseDate && strtotime($purchaseDate) > time()) {
            $this->errors['purchase_date'] = '구매일은 미래일 수 없습니다.';
            $isValid = false;
        }

        // 비즈니스 규칙: 입고일은 구매일보다 이전일 수 없음
        $receivedDate = $this->getAttribute('received_date');
        if ($purchaseDate && $receivedDate && strtotime($receivedDate) < strtotime($purchaseDate)) {
            $this->errors['received_date'] = '입고일은 구매일보다 이전일 수 없습니다.';
            $isValid = false;
        }

        // 비즈니스 규칙: 입고일은 미래일 수 없음
        if ($receivedDate && strtotime($receivedDate) > time()) {
            $this->errors['received_date'] = '입고일은 미래일 수 없습니다.';
            $isValid = false;
        }

        return $isValid;
    }
}