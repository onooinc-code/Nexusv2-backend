<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeadLetterTask extends Model
{
    protected $table = 'dead_letter_tasks';

    protected $fillable = [
        'task_id',
        'queue',
        'exception_message',
        'exception_trace',
        'failed_at',
    ];

    protected $casts = [
        'failed_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(AgentTask::class, 'task_id');
    }
}
