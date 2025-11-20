<?php

namespace App\Models;

class VehicleConsumable extends BaseModel
{
    protected array $fillable = [
        'name',
        'unit',
        'unit_price',
    ];

    protected array $rules = [
        'name' => 'required|string|max:100',
        'unit' => 'string|max:20',
        'unit_price' => 'numeric',
    ];
}
