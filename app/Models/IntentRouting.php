<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntentRouting extends BaseModel
{
    protected $table = 'intent_routing';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'intent_name',
        'default_provider_id',
        'default_model_id',
        'fallback_provider_id',
        'fallback_model_id',
    ];

    public function defaultProvider(): BelongsTo
    {
        return $this->belongsTo(AIProvider::class, 'default_provider_id');
    }

    public function defaultModel(): BelongsTo
    {
        return $this->belongsTo(AIModel::class, 'default_model_id');
    }

    public function fallbackProvider(): BelongsTo
    {
        return $this->belongsTo(AIProvider::class, 'fallback_provider_id');
    }

    public function fallbackModel(): BelongsTo
    {
        return $this->belongsTo(AIModel::class, 'fallback_model_id');
    }
}
