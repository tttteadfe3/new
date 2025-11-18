<?php

namespace App\Models;

class Tax extends BaseModel
{
    protected array $fillable = [
        'vehicle_id',
        'payment_date',
        'amount',
        'tax_type',
        'document_path'
    ];

    protected array $rules = [
        'vehicle_id' => 'required|integer|exists:vm_vehicles,id',
        'payment_date' => 'required|date',
        'amount' => 'required|numeric',
        'tax_type' => 'nullable|string|max:100'
    ];
}
