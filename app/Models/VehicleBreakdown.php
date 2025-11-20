<?php

namespace App\Models;

class VehicleBreakdown extends BaseModel
{
    protected array $fillable = [
        'vehicle_id',
        'reporter_id',
        'breakdown_item',
        'description',
        'mileage',
        'status',
        'reported_at',
        'confirmed_at',
        'resolved_at',
        'approved_at',
    ];

    protected array $rules = [
        'vehicle_id' => 'required|integer',
        'reporter_id' => 'required|integer',
        'breakdown_item' => 'required|string|max:100',
        'description' => 'string',
        'mileage' => 'integer',
        'status' => 'string|max:20|in:reported,confirmed,in_progress,resolved,approved',
        'reported_at' => 'date',
        'confirmed_at' => 'date',
        'resolved_at' => 'date',
        'approved_at' => 'date',
    ];
}
