<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactReplyRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contact_id',
        'rule',
        'is_active',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
