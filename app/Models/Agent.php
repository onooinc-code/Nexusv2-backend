<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Agent extends BaseModel
{
    // Agent Types
    public const TYPE_REFLECTION = 'reflection';
    public const TYPE_TEAM = 'team';
    public const TYPE_AUTONOMOUS = 'autonomous';
    public const TYPE_SPECIALIZED = 'specialized';
    public const TYPE_SUPERVISOR = 'supervisor';

    // Agent Statuses
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_QUARANTINED = 'quarantined';

    protected $fillable = [
        'name',
        'key',
        'description',
        'type',
        'provider',
        'status',
        'settings',
        'metadata',
        'is_active',
        'last_executed_at',
        'execution_count',
        'success_count',
        'error_count',
        'owner_id',
        'persona_id',
        'is_system',
        'rate_limit_per_minute',
    ];

    protected $casts = [
        'settings' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
        'last_executed_at' => 'datetime',
        'execution_count' => 'integer',
        'success_count' => 'integer',
        'error_count' => 'integer',
        'is_system' => 'boolean',
        'rate_limit_per_minute' => 'integer',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'is_active' => true,
        'execution_count' => 0,
        'success_count' => 0,
        'error_count' => 0,
        'is_system' => false,
        'rate_limit_per_minute' => 60,
    ];

    public function tools(): HasMany
    {
        return $this->hasMany(AgentTool::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(AgentSkill::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(AgentTask::class);
    }

    public function persona()
    {
        return $this->belongsTo(AgentPersona::class, 'persona_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function mcpServers(): BelongsToMany
    {
        return $this->belongsToMany(MCPServer::class, 'agent_mcp_servers');
    }

    public function runtimeLogs(): HasMany
    {
        return $this->hasMany(AgentRuntimeLog::class);
    }

    public function activeTools()
    {
        return $this->tools()->where('is_active', true)->get();
    }

    public function activeSkills()
    {
        return $this->skills()->where('is_active', true)->get();
    }

    public function isRunning(): bool
    {
        return $this->tasks()->where('status', 'running')->exists();
    }

    public function isIdle(): bool
    {
        return !$this->isRunning();
    }

    public function hasError(): bool
    {
        return false; // Can be linked to error threshold logic
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isQuarantined(): bool
    {
        return $this->status === self::STATUS_QUARANTINED;
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
    }

    public function recordError(): void
    {
        $this->increment('error_count');
    }

    public function setRunning(): void
    {
        // deprecated
    }

    public function setIdle(): void
    {
        // deprecated
    }

    public function quarantine(): void
    {
        $this->update(['status' => self::STATUS_QUARANTINED]);
    }

    public function unquarantine(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithToolsAndSkills($query)
    {
        return $query->with(['tools', 'skills']);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_REFLECTION => 'Reflection Agent',
            self::TYPE_TEAM => 'Team Agent',
            self::TYPE_AUTONOMOUS => 'Autonomous Agent',
            self::TYPE_SPECIALIZED => 'Specialized Agent',
            self::TYPE_SUPERVISOR => 'Supervisor Agent',
            default => 'Unknown Agent',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_QUARANTINED => 'Quarantined',
            default => 'Unknown',
        };
    }
}
