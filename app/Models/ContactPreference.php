<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactPreference extends BaseModel
{
    protected $fillable = [
        'contact_id',
        'key',
        'value',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
