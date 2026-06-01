<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends BaseModel
{
    use SoftDeletes;

    public const TYPE_CONTACT = 'contact';
    public const TYPE_CLIENT = 'client';
    public const TYPE_FAMILY = 'family';
    public const TYPE_FRIEND = 'friend';
    public const TYPE_FIANCEE = 'fiancée';
    public const TYPE_PARTNER = 'partner';
    public const TYPE_PROSPECT = 'prospect';
    public const TYPE_VENDOR = 'vendor';

    public const TYPES = [
        self::TYPE_CONTACT,
        self::TYPE_CLIENT,
        self::TYPE_FAMILY,
        self::TYPE_FRIEND,
        self::TYPE_FIANCEE,
        self::TYPE_PARTNER,
        self::TYPE_PROSPECT,
        self::TYPE_VENDOR,
    ];

    protected $fillable = [
        'uuid',
        'user_id',
        'phone',
        'whatsapp_number',
        'name',
        'display_name',
        'alternate_name',
        'canonical_name',
        'email',
        'primary_identifier',
        'type',
        'gender',
        'title',
        'company',
        'avatar_url',
        'metadata',
        'attributes',
        'is_active',
        'last_seen_at',
        'last_interaction_at',
        'reply_mode_override',
        'profile_confidence',
        'memory_freshness',
    ];

    protected $casts = [
        'metadata' => 'json',
        'attributes' => 'json',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_interaction_at' => 'datetime',
        'memory_freshness' => 'datetime',
        'profile_confidence' => 'integer',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ContactNote::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(ContactTag::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(ContactReplyRule::class);
    }

    public function replyRules(): HasMany
    {
        return $this->hasMany(ContactReplyRule::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(ContactCustomField::class);
    }

    public function memories(): HasMany
    {
        return $this->hasMany(Memory::class);
    }

    /**
     * AI-extracted memories stored in the vNext contact_memories table.
     * Distinct from memories() which relates to the Nexus AI Memory model.
     */
    public function contactMemories(): HasMany
    {
        return $this->hasMany(ContactMemory::class);
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(ContactIdentifier::class);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(ContactRelationship::class, 'source_contact_id');
    }

    public function relatedTo(): HasMany
    {
        return $this->hasMany(ContactRelationship::class, 'target_contact_id');
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(ContactPreference::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(ContactAlias::class, 'contact_id');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(ContactChannel::class);
    }

    public function messageThreads(): HasMany
    {
        return $this->hasMany(ContactMessageThread::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }

    public function analysisRuns(): HasMany
    {
        return $this->hasMany(ContactAnalysisRun::class);
    }

    public function analysisFindings(): HasMany
    {
        return $this->hasMany(ContactAnalysisFinding::class);
    }



    public function topics(): HasMany
    {
        return $this->hasMany(ContactTopic::class);
    }

    public function profileSnapshots(): HasMany
    {
        return $this->hasMany(ContactProfileSnapshot::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(ContactAuditEvent::class);
    }

    public function scopeOfType($query, string $type)
    {
        if (!in_array($type, self::TYPES, true)) {
            return $query;
        }

        return $query->where('type', $type);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function ($subQuery) use ($term) {
            $subQuery->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('company', 'like', "%{$term}%")
                ->orWhere('title', 'like', "%{$term}%");
        });
    }

    public static function findByIdentifiers(array $identifiers): ?self
    {
        $candidates = array_filter($identifiers, function ($identifier) {
            return !empty($identifier['type']) && !empty($identifier['value']);
        });

        if (empty($candidates)) {
            return null;
        }

        $query = ContactIdentifier::query();

        foreach ($candidates as $identifier) {
            $normalizedValue = ContactIdentifier::normalize($identifier['type'], $identifier['value']);

            $query->orWhere(function ($subQuery) use ($identifier, $normalizedValue) {
                $subQuery->where('type', $identifier['type'])
                    ->where('value', $normalizedValue);
            });
        }

        $identifier = $query->first();

        return $identifier?->contact;
    }

    public function getTypeLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->type ?? self::TYPE_CONTACT));
    }

    public static function getAvailableTypes(): array
    {
        return self::TYPES;
    }
}
