<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactAuditEvent extends Model
{
    protected $fillable = [
        'contact_id',
        'actor_type',
        'actor_id',
        'action',
        'before_state',
        'after_state',
        'trace_id',
        'ip_address',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
