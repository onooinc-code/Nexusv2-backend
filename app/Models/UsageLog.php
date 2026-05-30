<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageLog extends BaseModel
{
    protected $table = 'usage_logs';

    protected $fillable = [
        'provider_id',
        'model_id',
        'intent_name',
        'input_tokens',
        'output_tokens',
        'input_cost',
        'output_cost',
        'total_cost',
        'timestamp',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'input_cost' => 'float',
        'output_cost' => 'float',
        'total_cost' => 'float',
        'timestamp' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AIProvider::class, 'provider_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AIModel::class, 'model_id');
    }
}
