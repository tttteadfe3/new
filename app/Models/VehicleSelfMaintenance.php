<?php

namespace App\Models;

class VehicleSelfMaintenance extends BaseModel
{
    protected array $fillable = [
        'vehicle_id',
        'driver_id',
        'maintenance_item',
        'description',
        'parts_used',
        'maintenance_date',
    ];

    protected array $rules = [
        'vehicle_id' => 'required|integer',
        'driver_id' => 'required|integer',
        'maintenance_item' => 'required|string|max:100',
        'description' => 'string',
        'parts_used' => 'string',
        'maintenance_date' => 'required|date',
    ];
}
