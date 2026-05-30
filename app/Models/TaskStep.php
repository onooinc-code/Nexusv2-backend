<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskStep extends BaseModel
{
    protected $fillable = [
        'agent_task_id',
        'title',
        'description',
        'step_order',
        'status',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'completed_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(AgentTask::class, 'agent_task_id');
    }
}
