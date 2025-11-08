<?php

namespace App\Models;

class ItemCategory extends BaseModel
{
    /**
     * 대량 할당이 가능한 속성입니다.
     *
     * @var array
     */
    protected array $fillable = [
        'parent_id',
        'name',
        'is_active'
    ];

    /**
     * 모델 데이터의 유효성 검사 규칙입니다.
     *
     * @var array
     */
    protected array $rules = [
        'name' => 'required|string|max:255',
        'is_active' => 'required|in:0,1',
        'parent_id' => 'integer'
    ];
}
