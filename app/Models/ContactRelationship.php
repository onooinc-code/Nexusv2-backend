<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class ContactRelationship extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'source_contact_id',
        'target_contact_id',
        'type',
        'direction',
        'strength',
        'confidence',
        'evidence',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'strength' => 'decimal:2',
        'confidence' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function sourceContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'source_contact_id');
    }

    public function targetContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'target_contact_id');
    }
}
