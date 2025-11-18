<?php

namespace App\Models;

class Inspection extends BaseModel
{
    protected array $fillable = [
        'vehicle_id',
        'inspection_date',
        'expiry_date',
        'inspector_name',
        'result',
        'cost',
        'document_path'
    ];

    protected array $rules = [
        'vehicle_id' => 'required|integer|exists:vm_vehicles,id',
        'inspection_date' => 'required|date',
        'expiry_date' => 'required|date',
        'inspector_name' => 'nullable|string|max:255',
        'result' => 'required|string|max:50',
        'cost' => 'nullable|numeric'
    ];
}
