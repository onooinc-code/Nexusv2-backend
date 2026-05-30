<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
