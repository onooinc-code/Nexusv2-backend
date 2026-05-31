<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowExecution extends BaseModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'workflow_id',
        'workflow_version_id',
        'user_id',
        'trigger_source',
        'run_mode',
        'status',
        'input_payload',
        'runtime_state',
        'output',
        'error',
        'started_at',
        'paused_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'input_payload' => 'array',
        'runtime_state' => 'array',
        'output' => 'array',
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'run_mode' => 'async',
        'trigger_source' => 'manual',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stepLogs(): HasMany
    {
        return $this->hasMany(WorkflowStepLog::class, 'execution_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ], true);
    }
}
