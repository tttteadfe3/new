<?php

namespace App\Models;

class VehicleRepair extends BaseModel
{
    protected array $fillable = [
        'breakdown_id',
        'repair_type',
        'repair_item',
        'parts_used',
        'cost',
        'repairer_id',
        'completed_at',
    ];

    protected array $rules = [
        'breakdown_id' => 'required|integer',
        'repair_type' => 'string|max:50',
        'repair_item' => 'string|max:100',
        'parts_used' => 'string',
        'cost' => 'numeric',
        'repairer_id' => 'integer',
        'completed_at' => 'date',
    ];
}
