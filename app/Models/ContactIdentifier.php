<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class ContactIdentifier extends BaseModel
{
    use SoftDeletes;

    public const TYPE_EMAIL = 'email';
    public const TYPE_PHONE = 'phone';
    public const TYPE_EXTERNAL_ID = 'external_id';
    public const TYPE_WHATSAPP = 'whatsapp';
    public const TYPE_FACEBOOK = 'facebook';

    public const TYPES = [
        self::TYPE_EMAIL,
        self::TYPE_PHONE,
        self::TYPE_EXTERNAL_ID,
        self::TYPE_WHATSAPP,
        self::TYPE_FACEBOOK,
    ];

    protected $fillable = [
        'contact_id',
        'type',
        'value',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
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
