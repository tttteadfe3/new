<?php

namespace App\Models;

class Vehicle extends BaseModel
{
    protected array $fillable = [
        'vehicle_number',
        'model',
        'payload_capacity',
        'year',
        'release_date',
        'vehicle_type',
        'department_id',
        'driver_employee_id',
        'status_code'
    ];

    protected array $rules = [
        'vehicle_number' => 'required|string',
        'model' => 'required|string',
        'payload_capacity' => 'string',
        'year' => 'integer',
        'release_date' => 'date',
        'vehicle_type' => 'string',
        'department_id' => 'integer',
        'driver_employee_id' => 'integer',
        'status_code' => 'string'
    ];
}
