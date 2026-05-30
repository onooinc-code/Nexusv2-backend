<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AIProvider extends BaseModel
{
    protected $table = 'ai_providers';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'base_url',
        'models_fetch_endpoint',
        'generate_endpoint',
        'test_endpoint',
        'auth_header_format',
        'payload_format',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function models(): HasMany
    {
        return $this->hasMany(AIModel::class, 'provider_id');
    }
}
