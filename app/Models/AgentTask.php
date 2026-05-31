<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentTask extends BaseModel
{
    use SoftDeletes;

    // Status constants matching the spec
    const STATUS_TODO = 'todo';
    const STATUS_IN_PROGRESS = 'in-progress';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'agent_id',
        'workflow_id',
        'title',
        'description',
        'status',
        'priority',
        'progress',
        'due_date',
        'type',
        'contact_id',
        'conversation_id',
        'payload_data',
        'result_data',
        'metadata',
    ];

    protected $casts = [
        'priority' => 'integer',
        'progress' => 'integer',
        'due_date' => 'datetime',
        'metadata' => 'json',
        'payload_data' => 'json',
        'result_data' => 'json',
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

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    // Mutator to map internal status values to API values
    public function setStatusAttribute($value)
    {
        // Map old status values to new ones if needed
        $statusMap = [
            'pending' => self::STATUS_TODO,
            'running' => self::STATUS_IN_PROGRESS,
            'paused' => self::STATUS_BLOCKED, // or could map to blocked
            'completed' => self::STATUS_COMPLETED,
            'failed' => self::STATUS_FAILED,
            'cancelled' => self::STATUS_CANCELLED,
        ];

        // If it's an old status, map it; otherwise use as-is
        $mappedStatus = $statusMap[$value] ?? $value;
        $this->attributes['status'] = $mappedStatus;
    }

    // Accessor to get the status
    public function getStatusAttribute($value)
    {
        return $value;
    }
}
