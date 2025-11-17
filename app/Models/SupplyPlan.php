<?php

namespace App\Models;

class SupplyPlan extends BaseModel
{
    protected array $fillable = [
        'year',
        'item_id',
        'planned_quantity',
        'unit_price',
        'total_budget',
        'notes',
        'created_by'
    ];

    protected array $rules = [
        'year' => 'required|integer|min:2020|max:2050',
        'item_id' => 'required|integer',
        'planned_quantity' => 'required|integer|min:1',
        'unit_price' => 'required|numeric|min:0',
        'notes' => 'string',
        'created_by' => 'required|integer'
    ];

    /**
     * 총 예산을 계산합니다.
     */
    public function calculateTotalBudget(): float
    {
        $quantity = (int) $this->getAttribute('planned_quantity');
        $unitPrice = (float) $this->getAttribute('unit_price');
        
        return $quantity * $unitPrice;
    }

    /**
     * 계획된 지급품 정보를 가져옵니다.
     */
    public function getItem(): ?SupplyItem
    {
        // 이 메서드는 리포지토리에서 구현됩니다
        return null;
    }

    /**
     * 계획 연도를 가져옵니다.
     */
    public function getYear(): int
    {
        return (int) $this->getAttribute('year');
    }

    /**
     * 계획된 수량을 가져옵니다.
     */
    public function getPlannedQuantity(): int
    {
        return (int) $this->getAttribute('planned_quantity');
    }

    /**
     * 단가를 가져옵니다.
     */
    public function getUnitPrice(): float
    {
        return (float) $this->getAttribute('unit_price');
    }

    /**
     * 총 예산을 가져옵니다.
     */
    public function getTotalBudget(): float
    {
        return $this->calculateTotalBudget();
    }

    /**
     * 계획 데이터의 유효성을 검증합니다.
     */
    public function validate(): bool
    {
        $isValid = parent::validate();

        // 비즈니스 규칙: 총 예산 자동 계산
        $this->setAttribute('total_budget', $this->calculateTotalBudget());

        // 비즈니스 규칙: 미래 연도만 허용
        $currentYear = (int) date('Y');
        $planYear = $this->getAttribute('year');
        if ($planYear && $planYear < $currentYear) {
            $this->errors['year'] = '과거 연도에 대한 계획은 수립할 수 없습니다.';
            $isValid = false;
        }

        return $isValid;
    }
}