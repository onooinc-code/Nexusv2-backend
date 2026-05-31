<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowVersion extends BaseModel
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'workflow_id',
        'version_number',
        'definition',
        'created_by',
        'change_summary',
    ];

    protected $casts = [
        'definition' => 'array',
        'version_number' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class);
    }
}
