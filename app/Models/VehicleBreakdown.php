<?php

namespace App\Models;

class VehicleBreakdown extends BaseModel
{
    protected array $fillable = [
        'vehicle_id',
        'driver_employee_id',
        'breakdown_item',
        'description',
        'mileage',
        'photo_path',
        'status'
    ];

    protected array $rules = [
        'vehicle_id' => 'required|integer',
        'driver_employee_id' => 'required|integer',
        'breakdown_item' => 'required|string',
        'status' => 'required|string'
    ];
}
