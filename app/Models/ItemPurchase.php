<?php

namespace App\Models;

class ItemPurchase extends BaseModel
{
    /**
     * 대량 할당이 가능한 속성입니다.
     *
     * @var array
     */
    protected array $fillable = [
        'item_id',
        'plan_id',
        'purchase_date',
        'quantity',
        'unit_price',
        'supplier',
        'is_stocked',
        'stocked_by',
        'stocked_at',
        'created_by'
    ];

    /**
     * 모델 데이터의 유효성 검사 규칙입니다.
     *
     * @var array
     */
    protected array $rules = [
        'item_id' => 'required|integer',
        'plan_id' => 'integer',
        'purchase_date' => 'required|date',
        'quantity' => 'required|integer',
        'unit_price' => 'required|numeric',
        'supplier' => 'string|max:255',
        'is_stocked' => 'in:0,1',
    ];
}
