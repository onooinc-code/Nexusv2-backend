<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAuditTrail extends Model
{
    protected $fillable = [
        'event_type',
        'provider_id',
        'model_id',
        'intent',
        'status',
        'latency_ms',
        'fallback_triggered',
        'fallback_sequence',
        'estimated_cost',
        'input_tokens',
        'output_tokens',
        'error_type',
        'error_message',
        'workspace_id',
        'user_id',
        'metadata',
    ];

    protected $casts = [
        'fallback_triggered' => 'boolean',
        'metadata' => 'array',
        'estimated_cost' => 'decimal:6',
    ];
}
