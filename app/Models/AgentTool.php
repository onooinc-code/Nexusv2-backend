<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTool extends BaseModel
{
    protected $fillable = [
        'agent_id',
        'name',
        'type',
        'description',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'json',
        'is_active' => 'boolean',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
