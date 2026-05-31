<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactTopic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contact_id',
        'topic',
        'status',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(ContactTopicMention::class, 'topic_id');
    }
}
