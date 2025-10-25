<?php

namespace App\Models;

class Department extends BaseModel {
    public ?int $id = null;
    public string $name;
    public ?int $parent_id = null;
    public ?string $path = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected static string $tableName = 'hr_departments';
}
