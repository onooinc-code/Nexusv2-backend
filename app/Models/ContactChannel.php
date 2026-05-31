<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactChannel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contact_id',
        'name',
        'type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
