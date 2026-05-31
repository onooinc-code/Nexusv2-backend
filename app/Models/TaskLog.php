<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\TaskLog
 *
 * Model for storing task-specific execution logs
 */
class TaskLog extends Model
{
    protected $table = 'task_logs';

    protected $fillable = [
        'task_id',
        'level',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'json',
        'created_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(AgentTask::class);
    }
}