<?php

namespace App\Models;

class VehicleDocument extends BaseModel
{
    protected array $fillable = [
        'vehicle_id',
        'document_type',
        'file_path',
    ];

    protected array $rules = [
        'vehicle_id' => 'required|integer',
        'document_type' => 'required|string|max:50',
        'file_path' => 'required|string|max:255',
    ];
}
