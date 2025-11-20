<?php

namespace App\Models;

class VehicleTax extends BaseModel
{
    protected array $fillable = [
        'vehicle_id',
        'tax_type',
        'payment_date',
        'amount',
        'year',
    ];

    protected array $rules = [
        'vehicle_id' => 'required|integer',
        'tax_type' => 'string|max:50',
        'payment_date' => 'required|date',
        'amount' => 'required|numeric',
        'year' => 'required|integer',
    ];
}
