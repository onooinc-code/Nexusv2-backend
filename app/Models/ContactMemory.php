<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactMemory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contact_id',
        'content',
        'confidence',
        'source_type',
        'source_id',
        'version',
        'last_validated_at',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'last_validated_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContactMemoryVersion::class, 'memory_id');
    }
}
