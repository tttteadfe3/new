<?php

namespace App\Models;

class ConsumableLog extends BaseModel
{
    protected array $fillable = [
        'vehicle_id',
        'consumable_id',
        'quantity',
        'total_cost',
        'replaced_by_employee_id',
        'replacement_date'
    ];

    protected array $rules = [
        'vehicle_id' => 'required|integer|exists:vm_vehicles,id',
        'consumable_id' => 'required|integer|exists:vm_vehicle_consumables,id',
        'quantity' => 'required|numeric',
        'total_cost' => 'required|numeric',
        'replaced_by_employee_id' => 'nullable|integer|exists:hr_employees,id',
        'replacement_date' => 'required|date'
    ];
}
