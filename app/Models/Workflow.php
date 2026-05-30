<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workflow extends BaseModel
{
    protected $appends = ['progress', 'total_steps', 'completed_steps'];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_RUNNING = 'running';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_SCHEDULED = 'scheduled';
    public const TRIGGER_EVENT = 'event';
    public const TRIGGER_WEBHOOK = 'webhook';

    protected $fillable = [
        'name',
        'key',
        'description',
        'steps',
        'trigger_type',
        'trigger_config',
        'status',
        'settings',
        'metadata',
        'is_active',
        'last_executed_at',
        'execution_count',
        'success_count',
        'error_count',
    ];

    protected $casts = [
        'steps' => 'json',
        'trigger_config' => 'json',
        'settings' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
        'last_executed_at' => 'datetime',
        'execution_count' => 'integer',
        'success_count' => 'integer',
        'error_count' => 'integer',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'trigger_type' => self::TRIGGER_MANUAL,
        'is_active' => true,
        'execution_count' => 0,
        'success_count' => 0,
        'error_count' => 0,
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(AgentTask::class);
    }

    public function activeTasks()
    {
        return $this->tasks()->whereIn('status', ['pending', 'running'])->get();
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canExecute(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PAUSED, self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED])
            && $this->is_active;
    }

    public function getSuccessRate(): float
    {
        if ($this->execution_count === 0) {
            return 0.0;
        }
        return round(($this->success_count / $this->execution_count) * 100, 2);
    }

    public function incrementExecution(): void
    {
        $this->increment('execution_count');
        $this->update(['last_executed_at' => now()]);
    }

    public function recordSuccess(): void
    {
        $this->increment('success_count');
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public function recordError(): void
    {
        $this->increment('error_count');
        $this->update(['status' => self::STATUS_FAILED]);
    }

    public function setRunning(): void
    {
        $this->update(['status' => self::STATUS_RUNNING]);
    }

    public function setPaused(): void
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    public function setCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTriggerType($query, string $triggerType)
    {
        return $query->where('trigger_type', $triggerType);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_RUNNING => 'Running',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }

    public function getTriggerTypeLabelAttribute(): string
    {
        return match($this->trigger_type) {
            self::TRIGGER_MANUAL => 'Manual',
            self::TRIGGER_SCHEDULED => 'Scheduled',
            self::TRIGGER_EVENT => 'Event',
            self::TRIGGER_WEBHOOK => 'Webhook',
            default => 'Unknown',
        };
    }

    public function getProgressAttribute(): int
    {
        $totalSteps = count($this->steps ?? []);
        if ($totalSteps === 0) return 0;

        $completedSteps = collect($this->steps ?? [])
            ->where('status', 'completed')
            ->count();

        return (int) round(($completedSteps / $totalSteps) * 100);
    }

    public function getTotalStepsAttribute(): int
    {
        return count($this->steps ?? []);
    }

    public function getCompletedStepsAttribute(): int
    {
        return collect($this->steps ?? [])
            ->where('status', 'completed')
            ->count();
    }
}
