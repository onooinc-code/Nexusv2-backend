<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Memory extends BaseModel
{
    protected $fillable = [
        'contact_id',
        'conversation_id',
        'source',
        'type',
        'title',
        'content',
        'vector',
        'metadata',
        'tags',
        'expires_at',
    ];

    protected $casts = [
        'vector' => 'json',
        'metadata' => 'json',
        'tags' => 'json',
        'expires_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
