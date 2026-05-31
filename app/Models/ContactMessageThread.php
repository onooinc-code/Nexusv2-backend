<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactMessageThread extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contact_id',
        'source',
        'source_thread_id',
        'channel',
        'name',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'thread_id');
    }
}
