<?php

namespace App\Models;

class AIApiKey extends BaseModel
{
    protected $table = 'ai_api_keys';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'provider_id',
        'key_hash',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(AIProvider::class, 'provider_id');
    }
}
