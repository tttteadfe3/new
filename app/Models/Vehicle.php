<?php

namespace App\Models;

class Vehicle extends BaseModel
{
    protected array $fillable = [
        'vehicle_number',
        'model',
        'year',
        'department_id',
        'status_code'
    ];

    protected array $rules = [
        'vehicle_number' => 'required|string|max:255|unique:vm_vehicles,vehicle_number',
        'model' => 'required|string|max:255',
        'year' => 'nullable|integer|min:1900',
        'department_id' => 'nullable|integer|exists:hr_departments,id',
        'status_code' => 'required|string|in:NORMAL,REPAIRING,DISPOSED'
    ];
}
