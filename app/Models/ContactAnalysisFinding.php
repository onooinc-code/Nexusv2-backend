<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactAnalysisFinding extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contact_id',
        'analysis_run_id',
        'finding_type',  // primary field used by pipeline
        'type',          // legacy alias kept for backward compatibility
        'content',
        'confidence',
        'confidence_score',
        'evidence_refs',
        'metadata',
    ];

    protected $casts = [
        'evidence_refs' => 'array',
        'metadata'      => 'array',
        'content'       => 'array',
        'confidence'    => 'decimal:2',
        'confidence_score' => 'decimal:2',
    ];

    /**
     * Normalise field access: prefer 'finding_type', fall back to 'type'.
     * Ensures consumers always get a value regardless of which column was populated.
     */
    public function getFindingTypeAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['type'] ?? null;
    }

    public function getTypeAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['finding_type'] ?? null;
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(ContactAnalysisRun::class, 'analysis_run_id');
    }
}
