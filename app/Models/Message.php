<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends BaseModel
{
    protected $fillable = [
        'conversation_id',
        'sender',
        'sender_name',
        'sender_type',
        'sender_id',
        'channel',
        'thread_id',
        'direction',
        'content_type',
        'content',
        'metadata',
        'status',
        'sent_at',
        'received_at',
    ];

    protected $casts = [
        'metadata' => 'json',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
