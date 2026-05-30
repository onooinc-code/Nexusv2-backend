<?php

namespace App\Models;

class ApiKey extends BaseModel
{
    protected $fillable = [
        'name',
        'key',
        'type',
        'permissions',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'json',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
