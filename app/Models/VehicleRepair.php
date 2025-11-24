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
        'repair_shop',
        'completed_at'
    ];

    protected array $rules = [
        'breakdown_id' => 'required|integer',
        'repair_type' => 'string',
        'cost' => 'numeric'
    ];
}
