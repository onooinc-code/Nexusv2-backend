<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIModel extends BaseModel
{
    protected $table = 'ai_models';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'provider_id',
        'context_window',
        'input_cost_per_m',
        'output_cost_per_m',
        'description',
        'last_synced_at',
        'quality_tier',
        'cost_profile',
        'latency_profile',
        'security_class',
        'language_support',
        'version_tag',
        'presets',
    ];

    protected $casts = [
        'language_support' => 'array',
        'presets'          => 'array',
        'last_synced_at'   => 'datetime',
        'context_window'   => 'integer',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AIProvider::class, 'provider_id');
    }
}
