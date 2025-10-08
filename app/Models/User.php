<?php

namespace App\Models;

class User extends BaseModel
{
    protected array $fillable = [
        'id',
        'kakao_id',
        'nickname',
        'email',
        'status',
        'employee_id',
        'created_at',
        'updated_at'
    ];

    protected array $rules = [
        'nickname' => 'required|string|max:255',
        'email' => 'email|max:255',
        'status' => 'required|in:pending,active,inactive,deleted'
    ];

    // The findOrCreateFromKakao method, which contained direct DB queries,
    // has been moved to UserRepository to properly separate concerns.
    // This model no longer has direct database dependencies.
}