<?php

namespace App\Models;

class Consumable extends BaseModel
{
    protected array $fillable = [
        'name',
        'unit_price',
        'unit'
    ];

    protected array $rules = [
        'name' => 'required|string|max:255|unique:vm_vehicle_consumables,name',
        'unit_price' => 'nullable|numeric',
        'unit' => 'nullable|string|max:50'
    ];
}
