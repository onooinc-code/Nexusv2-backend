<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactTopicMention extends Model
{
    protected $fillable = [
        'topic_id',
        'message_id',
        'analysis_run_id',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ContactTopic::class, 'topic_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ContactMessage::class, 'message_id');
    }

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(ContactAnalysisRun::class, 'analysis_run_id');
    }
}
