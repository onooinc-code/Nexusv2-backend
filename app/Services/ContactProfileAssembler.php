<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\Cache;

class ContactProfileAssembler
{
    /**
     * Assemble the complete 360-degree view profile for a contact.
     *
     * @param Contact $contact
     * @param bool $useCache
     * @return array
     */
    public function assemble(Contact $contact, bool $useCache = true): array
    {
        $cacheKey = "contact:profile:{$contact->id}";

        if ($useCache) {
            return Cache::remember($cacheKey, 60, fn() => $this->buildProfileData($contact));
        }

        $data = $this->buildProfileData($contact);
        Cache::put($cacheKey, $data, 60);

        return $data;
    }

    /**
     * Clear the assembled profile cache.
     */
    public function clearCache(Contact $contact): void
    {
        Cache::forget("contact:profile:{$contact->id}");
    }

    /**
     * Build the raw profile data payload.
     */
    protected function buildProfileData(Contact $contact): array
    {
        $contact->load([
            'channels',
            'identifiers',
            'aliases',
            'preferences',
            'replyRules',
            'topics',
        ]);

        // Build relationships safely
        $relationships = $contact->relationships()->with('targetContact')->get()->map(function ($rel) {
            return [
                'id' => $rel->id,
                'target_contact_id' => $rel->target_contact_id,
                'target_name' => $rel->targetContact?->name ?? 'Unknown',
                'type' => $rel->type,
                'direction' => $rel->direction,
                'strength' => (float) $rel->strength,
                'confidence' => (float) $rel->confidence,
                'evidence' => $rel->evidence,
                'start_date' => $rel->start_date?->toDateString(),
                'end_date' => $rel->end_date?->toDateString(),
                'notes' => $rel->notes,
            ];
        });

        // Build preferences
        $preferences = $contact->preferences->pluck('value', 'key')->toArray();

        // Get latest memories
        $memories = $contact->memories()->latest()->take(20)->get()->map(function ($mem) {
            return [
                'id' => $mem->id,
                'content' => $mem->content,
                'confidence' => (float) $mem->confidence,
                'source_type' => $mem->source_type,
                'source_id' => $mem->source_id,
                'version' => $mem->version,
                'last_validated_at' => $mem->last_validated_at?->toDateTimeString(),
            ];
        });

        return [
            'id' => $contact->id,
            'uuid' => $contact->uuid,
            'name' => $contact->name,
            'display_name' => $contact->display_name ?: $contact->name,
            'alternate_name' => $contact->alternate_name,
            'canonical_name' => $contact->canonical_name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'whatsapp_number' => $contact->whatsapp_number,
            'type' => $contact->type,
            'gender' => $contact->gender,
            'title' => $contact->title,
            'company' => $contact->company,
            'avatar_url' => $contact->avatar_url,
            'is_active' => (bool) $contact->is_active,
            'profile_confidence' => (int) $contact->profile_confidence,
            'memory_freshness' => $contact->memory_freshness?->toDateTimeString(),
            'last_interaction_at' => $contact->last_interaction_at?->toDateTimeString(),
            'last_seen_at' => $contact->last_seen_at?->toDateTimeString(),
            'reply_mode_override' => $contact->reply_mode_override,
            'metadata' => $contact->metadata ?? [],
            'attributes' => $contact->attributes ?? [],
            
            // Channels and Identifiers
            'channels' => $contact->channels->map(fn($c) => [
                'name' => $c->name,
                'type' => $c->type,
                'metadata' => $c->metadata,
            ])->toArray(),
            
            'identifiers' => $contact->identifiers->map(fn($id) => [
                'type' => $id->type,
                'value' => $id->value,
                'is_primary' => (bool) $id->is_primary,
            ])->toArray(),

            'aliases' => $contact->aliases->pluck('name')->toArray(),
            'preferences' => $preferences,
            'reply_rules' => $contact->replyRules->map(fn($r) => [
                'rule' => $r->rule,
                'is_active' => (bool) $r->is_active,
                'source_type' => $r->source_type,
            ])->toArray(),

            'topics' => $contact->topics->map(fn($t) => [
                'topic' => $t->topic,
                'status' => $t->status,
            ])->toArray(),

            'relationships' => $relationships->toArray(),
            'memories' => $memories->toArray(),
        ];
    }
}
