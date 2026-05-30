<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentTask extends BaseModel
{
    protected $fillable = [
        'agent_id',
        'title',
        'description',
        'status',
        'priority',
        'progress',
        'due_at',
        'metadata',
    ];

    protected $casts = [
        'priority' => 'integer',
        'progress' => 'integer',
        'due_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TaskStep::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
