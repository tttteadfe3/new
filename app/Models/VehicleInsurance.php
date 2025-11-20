<?php

namespace App\Models;

class VehicleInsurance extends BaseModel
{
    protected array $fillable = [
        'vehicle_id',
        'insurer',
        'policy_number',
        'start_date',
        'end_date',
        'premium',
        'document_path',
    ];

    protected array $rules = [
        'vehicle_id' => 'required|integer',
        'insurer' => 'string|max:100',
        'policy_number' => 'string|max:50',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'premium' => 'numeric',
        'document_path' => 'string|max:255',
    ];
}
