<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactRelationship extends BaseModel
{
    public const TYPE_FAMILY = 'family';
    public const TYPE_WORK = 'work';
    public const TYPE_SOCIAL = 'social';
    public const TYPE_VENDOR = 'vendor';
    public const TYPE_PARTNER = 'partner';

    public const TYPES = [
        self::TYPE_FAMILY,
        self::TYPE_WORK,
        self::TYPE_SOCIAL,
        self::TYPE_VENDOR,
        self::TYPE_PARTNER,
    ];

    protected $fillable = [
        'contact_id',
        'related_contact_id',
        'relationship_type',
        'mention_count',
        'confidence',
    ];

    protected $casts = [
        'mention_count' => 'integer',
        'confidence' => 'float',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function relatedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'related_contact_id');
    }
}
