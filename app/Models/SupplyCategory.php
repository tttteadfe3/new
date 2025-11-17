<?php

namespace App\Models;

class SupplyCategory extends BaseModel
{
    protected array $fillable = [
        'parent_id',
        'category_code',
        'category_name',
        'level',
        'is_active',
        'display_order'
    ];

    protected array $rules = [
        'category_code' => 'required|string|max:20',
        'category_name' => 'required|string|max:100',
        'level' => 'required|integer|in:1,2',
        'parent_id' => 'integer',
        'is_active' => 'integer|in:0,1',
        'display_order' => 'integer'
    ];

    /**
     * 하위 분류 목록을 가져옵니다.
     */
    public function getChildren(): array
    {
        // 이 메서드는 리포지토리에서 구현됩니다
        return [];
    }

    /**
     * 상위 분류를 가져옵니다.
     */
    public function getParent(): ?SupplyCategory
    {
        // 이 메서드는 리포지토리에서 구현됩니다
        return null;
    }

    /**
     * 분류가 활성 상태인지 확인합니다.
     */
    public function isActive(): bool
    {
        return (bool) $this->getAttribute('is_active');
    }

    /**
     * 분류가 대분류인지 확인합니다.
     */
    public function isMainCategory(): bool
    {
        return $this->getAttribute('level') === 1;
    }

    /**
     * 분류가 소분류인지 확인합니다.
     */
    public function isSubCategory(): bool
    {
        return $this->getAttribute('level') === 2;
    }

    /**
     * 분류 코드의 고유성을 검증합니다.
     */
    public function validate(): bool
    {
        $isValid = parent::validate();

        // 비즈니스 규칙: 소분류는 반드시 상위 분류가 있어야 함
        if ($this->getAttribute('level') === 2 && !$this->getAttribute('parent_id')) {
            $this->errors['parent_id'] = '소분류는 상위 분류를 선택해야 합니다.';
            $isValid = false;
        }

        // 비즈니스 규칙: 대분류는 상위 분류가 없어야 함
        if ($this->getAttribute('level') === 1 && $this->getAttribute('parent_id')) {
            $this->errors['parent_id'] = '대분류는 상위 분류를 가질 수 없습니다.';
            $isValid = false;
        }

        return $isValid;
    }
}