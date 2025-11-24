<?php

namespace App\Models;

class VehicleWork extends BaseModel
{
    protected string $table = 'vehicle_works';
    
    protected array $fillable = [
        'vehicle_id',
        'type',
        'status',
        'reporter_id',
        'work_item',
        'description',
        'mileage',
        'photo_path',
        'photo2_path',
        'photo3_path',
        'repair_type',
        'decided_at',
        'decided_by',
        'parts_used',
        'cost',
        'worker_id',
        'repair_shop',
        'completed_at',
        'confirmed_at',
        'confirmed_by'
    ];
    
    protected array $rules = [
        'vehicle_id' => 'required|integer',
        'type' => 'required|string',
        'reporter_id' => 'required|integer',
        'work_item' => 'required|string'
    ];
    
    public function getTable(): string
    {
        return $this->table;
    }
    
    public function getFillable(): array
    {
        return $this->fillable;
    }
    
    public function getRules(): array
    {
        return $this->rules;
    }
}
