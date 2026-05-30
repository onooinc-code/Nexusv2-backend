<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactIdentifier extends BaseModel
{
    public const TYPE_EMAIL = 'email';
    public const TYPE_PHONE = 'phone';
    public const TYPE_EXTERNAL_ID = 'external_id';

    public const TYPES = [
        self::TYPE_EMAIL,
        self::TYPE_PHONE,
        self::TYPE_EXTERNAL_ID,
    ];

    protected $fillable = [
        'contact_id',
        'type',
        'value',
        'trusted',
    ];

    protected $casts = [
        'trusted' => 'boolean',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Normalize an identifier value (e.g. strip formatting from phone numbers).
     */
    public static function normalize(string $type, string $value): string
    {
        if ($type === self::TYPE_PHONE) {
            // Strip non-numeric characters except leading +
            $normalized = preg_replace('/[^\d+]/', '', $value);
            return $normalized ?: $value;
        }

        if ($type === self::TYPE_EMAIL) {
            return strtolower(trim($value));
        }

        return trim($value);
    }
}
