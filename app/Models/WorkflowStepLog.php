<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStepLog extends BaseModel
{
    protected $fillable = [
        'execution_id',
        'workflow_id',
        'step_id',
        'step_name',
        'step_type',
        'status',
        'input',
        'output',
        'error',
        'attempt',
        'duration_ms',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'attempt' => 'integer',
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function execution(): BelongsTo
    {
        return $this->belongsTo(WorkflowExecution::class, 'execution_id');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
