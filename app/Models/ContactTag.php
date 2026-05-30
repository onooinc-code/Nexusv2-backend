<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactTag extends BaseModel
{
    protected $fillable = [
        'contact_id',
        'name',
        'color',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
