<?php

namespace App\Models;

class Maintenance extends BaseModel
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
        'vehicle_id' => 'required|integer|exists:vm_vehicles,id',
        'driver_employee_id' => 'required|integer|exists:hr_employees,id',
        'maintenance_item' => 'required|string|max:255',
        'description' => 'nullable|string',
        'used_parts' => 'nullable|string',
        'status' => 'required|string|in:COMPLETED,APPROVED'
    ];
}
