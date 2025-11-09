<?php

namespace App\Models;

class Item extends BaseModel
{
    /**
     * 대량 할당이 가능한 속성입니다.
     *
     * @var array
     */
    protected array $fillable = [
        'category_id',
        'name',
        'stock',
        'note'
    ];

    /**
     * 모델 데이터의 유효성 검사 규칙입니다.
     *
     * @var array
     */
    protected array $rules = [
        'category_id' => 'required|integer',
        'name' => 'required|string|max:255',
        'stock' => 'required|integer',
        'note' => 'string'
    ];
}
