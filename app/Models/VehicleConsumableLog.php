<?php

namespace App\Models;

class VehicleConsumableLog extends BaseModel
{
    protected array $fillable = [
        'vehicle_id',
        'consumable_id',
        'quantity',
        'replacement_date',
        'replacer_id',
    ];

    protected array $rules = [
        'vehicle_id' => 'required|integer',
        'consumable_id' => 'required|integer',
        'quantity' => 'required|integer',
        'replacement_date' => 'required|date',
        'replacer_id' => 'integer',
    ];
}
