<?php

namespace App\Models;

class VehicleInspection extends BaseModel
{
    protected array $fillable = [
        'vehicle_id',
        'inspection_date',
        'expiry_date',
        'result',
        'inspector',
    ];

    protected array $rules = [
        'vehicle_id' => 'required|integer',
        'inspection_date' => 'required|date',
        'expiry_date' => 'required|date',
        'result' => 'string|max:50',
        'inspector' => 'string|max:100',
    ];
}
