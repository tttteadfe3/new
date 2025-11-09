<?php

namespace App\Models;

class ItemPlan extends BaseModel
{
    /**
     * 대량 할당이 가능한 속성입니다.
     *
     * @var array
     */
    protected array $fillable = [
        'year',
        'item_id',
        'unit_price',
        'quantity',
        'note',
        'created_by'
    ];

    /**
     * 모델 데이터의 유효성 검사 규칙입니다.
     *
     * @var array
     */
    protected array $rules = [
        'year' => 'required|integer',
        'item_id' => 'required|integer',
        'unit_price' => 'required|numeric',
        'quantity' => 'required|integer',
        'note' => 'string'
    ];
}
