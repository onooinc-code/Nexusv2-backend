<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMemoryVersion extends Model
{
    protected $fillable = [
        'memory_id',
        'version',
        'content',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function memory(): BelongsTo
    {
        return $this->belongsTo(ContactMemory::class, 'memory_id');
    }
}
