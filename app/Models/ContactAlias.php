<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactAlias extends BaseModel
{
    protected $fillable = [
        'primary_contact_id',
        'alias_name',
        'confidence',
        'created_context',
    ];

    protected $casts = [
        'confidence' => 'float',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }
}
