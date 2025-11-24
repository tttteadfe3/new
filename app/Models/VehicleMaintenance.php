<?php

namespace App\Models;

class VehicleMaintenance extends BaseModel
{
    protected array $fillable = [
        'vehicle_id',
        'driver_employee_id',
        'maintenance_item',
        'description',
        'used_parts',
        'photo_path',
        'status'
    ];

    protected array $rules = [
        'vehicle_id' => 'required|integer',
        'driver_employee_id' => 'required|integer',
        'maintenance_item' => 'required|string',
        'status' => 'required|string'
    ];
}
