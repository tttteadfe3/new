<?php

namespace App\Models;

class Breakdown extends BaseModel
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
        'vehicle_id' => 'required|integer|exists:vm_vehicles,id',
        'driver_employee_id' => 'required|integer|exists:hr_employees,id',
        'breakdown_item' => 'required|string|max:255',
        'description' => 'nullable|string',
        'mileage' => 'nullable|integer',
        'status' => 'required|string|in:REGISTERED,RECEIVED,DECIDED,COMPLETED,APPROVED'
    ];
}
