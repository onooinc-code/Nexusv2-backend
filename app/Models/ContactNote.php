<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactNote extends BaseModel
{
    protected $fillable = [
        'contact_id',
        'user_id',
        'note',
        'summary',
        'is_pinned',
        'metadata',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'metadata' => 'json',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
