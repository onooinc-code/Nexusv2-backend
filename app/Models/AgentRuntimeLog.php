<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgentRuntimeLog extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'task_id',
        'trace_id',
        'step',
        'input',
        'output',
        'duration_ms',
    ];

    protected $casts = [
        'input' => 'json',
        'output' => 'json',
        'duration_ms' => 'integer',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
