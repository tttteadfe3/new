<?php

namespace App\Models;

class VehicleConsumable extends BaseModel
{
    protected string $table = 'vehicle_consumables_categories';
    
    protected array $fillable = [
        'name',
        'parent_id',
        'level',
        'path',
        'sort_order',
        'unit',
        'note'
    ];
    
    protected array $rules = [
        'name' => 'required|string'
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
