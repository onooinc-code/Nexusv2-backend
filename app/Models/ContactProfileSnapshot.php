<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactProfileSnapshot extends Model
{
    protected $fillable = [
        'contact_id',
        'snapshot_data',
        'reason',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
