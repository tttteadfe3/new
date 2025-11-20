<?php

namespace App\Models;

class Insurance extends BaseModel
{
    protected array $fillable = [
        'vehicle_id',
        'insurer_name',
        'policy_number',
        'start_date',
        'end_date',
        'premium',
        'document_path'
    ];

    protected array $rules = [
        'vehicle_id' => 'required|integer|exists:vm_vehicles,id',
        'insurer_name' => 'required|string|max:255',
        'policy_number' => 'required|string|max:255',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'premium' => 'required|numeric'
    ];
}
