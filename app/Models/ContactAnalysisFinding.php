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
        'type',
        'content',
        'confidence',
        'evidence_refs',
    ];

    protected $casts = [
        'evidence_refs' => 'array',
        'confidence' => 'decimal:2',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(ContactAnalysisRun::class, 'analysis_run_id');
    }
}
