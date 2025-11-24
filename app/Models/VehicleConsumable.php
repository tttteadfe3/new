<?php

namespace App\Models;

class VehicleConsumable extends BaseModel
{
    protected string $table = 'vehicle_consumables';
    
    protected array $fillable = [
        'name',
        'category',
        'part_number',
        'unit',
        'unit_price',
        'current_stock',
        'minimum_stock',
        'location',
        'note'
    ];
    
    protected array $rules = [
        'name' => 'required|string',
        'category' => 'string',
        'unit' => 'required|string',
        'unit_price' => 'numeric',
        'current_stock' => 'integer',
        'minimum_stock' => 'integer'
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
