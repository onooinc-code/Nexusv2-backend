<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactCustomField extends BaseModel
{
    protected $fillable = [
        'contact_id',
        'field_key',
        'label',
        'value',
        'type',
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
