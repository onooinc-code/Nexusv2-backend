<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactAnalysisRun extends Model
{
    protected $fillable = [
        'contact_id',
        'status',
        'options',
        'results',
        'trace_id',
        'cost_metadata',
    ];

    protected $casts = [
        'options' => 'array',
        'results' => 'array',
        'cost_metadata' => 'array',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(ContactAnalysisFinding::class, 'analysis_run_id');
    }
}
