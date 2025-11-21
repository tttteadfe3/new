<?php

namespace App\Models;

class Vehicle extends BaseModel
{
    protected array $fillable = [
        'vin',
        'license_plate',
        'make',
        'model',
        'year',
        'department_id',
        'status',
        'driver_id',
    ];

    protected array $rules = [
        'vin' => 'required|string|max:17',
        'license_plate' => 'required|string|max:20',
        'make' => 'string|max:50',
        'model' => 'string|max:50',
        'year' => 'integer',
        'department_id' => 'integer',
        'status' => 'string|max:20',
    ];
}
