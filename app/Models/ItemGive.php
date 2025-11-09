<?php

namespace App\Models;

class ItemGive extends BaseModel
{
    /**
     * 대량 할당이 가능한 속성입니다.
     *
     * @var array
     */
    protected array $fillable = [
        'item_id',
        'give_date',
        'department_id',
        'employee_id',
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
        'item_id' => 'required|integer',
        'give_date' => 'required|date',
        'department_id' => 'integer',
        'employee_id' => 'integer',
        'quantity' => 'required|integer|min:1',
        'note' => 'string'
    ];
}
