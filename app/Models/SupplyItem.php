<?php

namespace App\Models;

class SupplyItem extends BaseModel
{
    protected array $fillable = [
        'item_code',
        'item_name',
        'category_id',
        'unit',
        'description',
        'is_active'
    ];

    protected array $rules = [
        'item_code' => 'required|string|max:30',
        'item_name' => 'required|string|max:200',
        'category_id' => 'required|integer',
        'unit' => 'string|max:20',
        'description' => 'string',
        'is_active' => 'integer|in:0,1'
    ];

    /**
     * 지급품이 속한 분류를 가져옵니다.
     */
    public function getCategory(): ?SupplyCategory
    {
        // 이 메서드는 리포지토리에서 구현됩니다
        return null;
    }

    /**
     * 지급품이 활성 상태인지 확인합니다.
     */
    public function isActive(): bool
    {
        return (bool) $this->getAttribute('is_active');
    }

    /**
     * 지급품의 단위를 가져옵니다 (기본값: '개').
     */
    public function getUnit(): string
    {
        return $this->getAttribute('unit') ?: '개';
    }

    /**
     * 지급품 코드의 고유성을 검증합니다.
     */
    public function validate(): bool
    {
        $isValid = parent::validate();

        // 추가 비즈니스 규칙은 리포지토리에서 구현됩니다
        // (예: 품목 코드 중복 검사)

        return $isValid;
    }
}