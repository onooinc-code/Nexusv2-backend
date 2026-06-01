<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowSchedule extends Model
{
    protected $fillable = [
        'workflow_id',
        'cron_expression',
        'input_payload',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'input_payload' => 'json',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
