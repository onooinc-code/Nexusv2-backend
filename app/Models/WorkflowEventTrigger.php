<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowEventTrigger extends Model
{
    protected $fillable = [
        'workflow_id',
        'event_name',
        'condition_payload',
        'is_active',
    ];

    protected $casts = [
        'condition_payload' => 'json',
        'is_active' => 'boolean',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
