<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactPreference extends BaseModel
{
    public const TYPE_CHANNEL = 'channel';
    public const TYPE_TONE = 'tone';
    public const TYPE_TIMEZONE = 'timezone';
    public const TYPE_LANGUAGE = 'language';
    public const TYPE_OPT_OUT = 'opt_out';

    public const TYPES = [
        self::TYPE_CHANNEL,
        self::TYPE_TONE,
        self::TYPE_TIMEZONE,
        self::TYPE_LANGUAGE,
        self::TYPE_OPT_OUT,
    ];

    protected $fillable = [
        'contact_id',
        'preference_type',
        'value',
        'confidence',
        'inferred_from_count',
    ];

    protected $casts = [
        'confidence' => 'float',
        'inferred_from_count' => 'integer',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
