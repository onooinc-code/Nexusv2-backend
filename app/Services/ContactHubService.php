<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactAlias;
use App\Models\ContactIdentifier;
use App\Models\ContactPreference;
use App\Models\ContactRelationship;
use App\Models\ContactTag;
use App\Models\ContactCustomField;
use App\Models\ContactNote;
use App\Models\Message;
use App\Services\LogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContactHubService
{
    public function __construct(protected LogService $logService) {}

    public function syncContactDetails(Contact $contact): void
    {
        $this->updateBeliefAutoUpdate($contact);
        $this->extractPreferences($contact);
        $this->syncPreferences($contact);
        $this->updateEmotionalBaseline($contact);

        $this->logService->debug('Contact details synced', [
            'channel' => 'contact',
            'type' => 'sync',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
        ]);
    }

    public function updateBeliefAutoUpdate(Contact $contact): void
    {
        $metadata = $contact->metadata ?? [];

        if (!empty($metadata['changed_by'])) {
            $contact->metadata = array_merge($metadata, [
                'beliefs' => array_merge($metadata['beliefs'] ?? [], [
                    'last_synced_by' => $metadata['changed_by'],
                    'updated_at' => now()->toDateTimeString(),
                ]),
            ]);
        }

        if (!isset($contact->metadata['beliefs'])) {
            $contact->metadata = array_merge($contact->metadata ?? [], [
                'beliefs' => [
                    'confidence' => 0.5,
                    'source' => 'system',
                ],
            ]);
        }

        $contact->save();
    }

    public function extractPreferences(Contact $contact): void
    {
        $attributes = $contact->attributes ?? [];

        if (!isset($attributes['preferences'])) {
            $attributes['preferences'] = [
                'communication' => 'default',
                'timezone' => $attributes['timezone'] ?? 'UTC',
            ];

            $contact->attributes = $attributes;
            $contact->save();
        }
    }

    public function syncPreferences(Contact $contact): void
    {
        $preferences = $contact->attributes['preferences'] ?? [];

        if (!is_array($preferences)) {
            return;
        }

        foreach ($preferences as $key => $value) {
            $contact->preferences()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_scalar($value) ? (string) $value : json_encode($value),
                ]
            );
        }
    }

    public function mergeContacts(Contact $target, Contact $source, string $strategy = 'prefer_new'): Contact
    {
        if ($target->id === $source->id) {
            throw new \InvalidArgumentException('Source and target contacts must differ for merge.');
        }

        DB::transaction(function () use ($target, $source, $strategy) {
            $fields = ['name', 'canonical_name', 'email', 'phone', 'type', 'title', 'company', 'avatar_url'];

            foreach ($fields as $field) {
                $targetValue = $target->{$field};
                $sourceValue = $source->{$field};

                if (!$targetValue && $sourceValue) {
                    $target->{$field} = $sourceValue;
                }
            }

            $target->metadata = array_merge($source->metadata ?? [], $target->metadata ?? []);
            $target->attributes = array_merge($source->attributes ?? [], $target->attributes ?? []);
            $target->save();

            foreach ($source->identifiers as $identifier) {
                $exists = $target->identifiers()
                    ->where('type', $identifier->type)
                    ->where('value', $identifier->value)
                    ->exists();

                if (!$exists) {
                    $target->identifiers()->create([
                        'type' => $identifier->type,
                        'value' => $identifier->value,
                        'is_primary' => $identifier->is_primary,
                    ]);
                }
            }

            foreach ($source->relationships as $relationship) {
                if ($relationship->target_contact_id === $target->id) {
                    continue;
                }

                $existing = $target->relationships()
                    ->where('target_contact_id', $relationship->target_contact_id)
                    ->where('type', $relationship->type)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'strength' => max($existing->strength, $relationship->strength),
                        'confidence' => max($existing->confidence, $relationship->confidence),
                    ]);
                } else {
                    $target->relationships()->create([
                        'target_contact_id' => $relationship->target_contact_id,
                        'type' => $relationship->type,
                        'direction' => $relationship->direction,
                        'strength' => $relationship->strength,
                        'confidence' => $relationship->confidence,
                        'evidence' => $relationship->evidence,
                        'start_date' => $relationship->start_date,
                        'end_date' => $relationship->end_date,
                        'notes' => $relationship->notes,
                    ]);
                }
            }

            ContactRelationship::where('target_contact_id', $source->id)->get()->each(function (ContactRelationship $reverseRelationship) use ($target) {
                if ($reverseRelationship->source_contact_id === $target->id) {
                    return;
                }

                $existing = ContactRelationship::where('source_contact_id', $reverseRelationship->source_contact_id)
                    ->where('target_contact_id', $target->id)
                    ->where('type', $reverseRelationship->type)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'strength' => max($existing->strength, $reverseRelationship->strength),
                        'confidence' => max($existing->confidence, $reverseRelationship->confidence),
                    ]);
                    $reverseRelationship->delete();
                } else {
                    $reverseRelationship->target_contact_id = $target->id;
                    $reverseRelationship->save();
                }
            });

            foreach ($source->preferences as $preference) {
                $existing = $target->preferences()->where('key', $preference->key)->first();
                if ($existing) {
                    $existing->update([
                        'value' => $existing->value ?: $preference->value,
                    ]);
                } else {
                    $target->preferences()->create([
                        'key' => $preference->key,
                        'value' => $preference->value,
                    ]);
                }
            }

            foreach ($source->aliases as $alias) {
                $exists = $target->aliases()->where('name', $alias->name)->exists();
                if (!$exists) {
                    $target->aliases()->create([
                        'name' => $alias->name,
                    ]);
                }
            }

            foreach (['notes', 'memories', 'tags', 'replyRules', 'customFields', 'conversations'] as $relation) {
                if (method_exists($source, $relation)) {
                    $source->{$relation}()->get()->each(function ($item) use ($target) {
                        $item->contact_id = $target->id;
                        $item->save();
                    });
                }
            }

            $source->notificationLogs()->update(['contact_id' => $target->id]);
            $source->delete();

            \App\Models\NotificationLog::create([
                'contact_id' => $target->id,
                'channel' => 'system',
                'recipient' => 'system',
                'subject' => 'Contacts Merged',
                'body' => "Contact {$source->name} was merged into this profile.",
                'status' => 'completed',
            ]);
        });

        $this->syncContactDetails($target);
        
        try {
            event(new \App\Events\ContactMerged($target, $source->id));
        } catch (\Throwable $e) {}

        return $target->fresh();
    }

    public function eraseContact(Contact $contact): void
    {
        DB::transaction(function () use ($contact) {
            $contact->identifiers()->delete();
            $contact->relationships()->delete();
            ContactRelationship::where('target_contact_id', $contact->id)->delete();
            $contact->preferences()->delete();
            $contact->aliases()->delete();
            $contact->notes()->delete();
            $contact->memories()->delete();
            $contact->tags()->delete();
            $contact->replyRules()->delete();
            $contact->customFields()->delete();
            $contact->conversations()->delete();
            $contact->notificationLogs()->delete();

            $contact->update([
                'name' => 'Erased Contact',
                'canonical_name' => null,
                'email' => null,
                'phone' => null,
                'avatar_url' => null,
                'metadata' => array_merge($contact->metadata ?? [], ['erased_at' => now()->toDateTimeString(), 'erasure' => true]),
                'attributes' => [],
                'is_active' => false,
            ]);

            $contact->delete();
            
            try {
                event(new \App\Events\ContactDeleted($contact));
            } catch (\Throwable $e) {}
        });
    }

    public function enrichContact(Contact $contact, array $profileData, ?string $source = null): Contact
    {
        $fields = ['name', 'canonical_name', 'email', 'phone', 'type', 'title', 'company', 'avatar_url'];

        foreach ($fields as $field) {
            if (isset($profileData[$field]) && $profileData[$field]) {
                $contact->{$field} = $profileData[$field];
            }
        }

        $metadata = $contact->metadata ?? [];
        $metadata['enrichment'] = array_merge($metadata['enrichment'] ?? [], [[
            'source' => $source ?? 'manual',
            'data' => $profileData,
            'enriched_at' => now()->toDateTimeString(),
        ]]);

        $contact->metadata = $metadata;
        $contact->save();

        $this->syncContactDetails($contact);

        return $contact->fresh();
    }

    public function buildRelationshipGraph(Contact $contact): array
    {
        $connections = [];
        $nodes = [
            ['id' => $contact->id, 'label' => $contact->name],
        ];

        $relationships = $contact->relationships()->with('targetContact')->get();

        foreach ($relationships as $relationship) {
            $related = $relationship->targetContact;
            if ($related) {
                $nodes[] = ['id' => $related->id, 'label' => $related->name];
                $connections[] = [
                    'source' => $contact->id,
                    'target' => $related->id,
                    'relationship' => $relationship->type,
                    'confidence' => $relationship->confidence,
                ];
            }
        }

        return [
            'contact' => $contact->id,
            'nodes' => array_values(array_unique($nodes, SORT_REGULAR)),
            'edges' => $connections,
        ];
    }

    public function updateEmotionalBaseline(Contact $contact): void
    {
        $notes = $contact->notes()->pluck('note')->toArray();

        $score = 0;
        foreach ($notes as $note) {
            $lower = strtolower($note);
            if (str_contains($lower, 'happy') || str_contains($lower, 'glad') || str_contains($lower, 'pleased')) {
                $score += 1;
            }

            if (str_contains($lower, 'angry') || str_contains($lower, 'frustrated') || str_contains($lower, 'upset')) {
                $score -= 1;
            }
        }

        $baseline = 'neutral';
        if ($score > 0) {
            $baseline = 'positive';
        } elseif ($score < 0) {
            $baseline = 'negative';
        }

        $contact->metadata = array_merge($contact->metadata ?? [], [
            'emotional_baseline' => $baseline,
            'emotion_updated_at' => now()->toDateTimeString(),
        ]);
        $contact->save();
    }

    public function getContactAnalytics(Contact $contact): array
    {
        return $this->getContactAnalyticsWithOptions($contact, 7);
    }

    public function getContactAnalyticsWithOptions(Contact $contact, int $days = 7): array
    {
        $cacheKey = "contact:{$contact->id}:analytics:{$days}";

        return Cache::remember($cacheKey, 60, function () use ($contact, $days) {
            $now = Carbon::now();
            $start = $now->copy()->subDays($days - 1)->startOfDay();

            $memoriesSeries = [];
            $messagesSeries = [];
            $conversationsSeries = [];

            for ($i = 0; $i < $days; $i++) {
                $day = $start->copy()->addDays($i);
                $from = $day->copy()->startOfDay();
                $to = $day->copy()->endOfDay();

                $memoriesSeries[] = [
                    'date' => $day->toDateString(),
                    'count' => $contact->memories()->whereBetween('created_at', [$from, $to])->count(),
                ];

                $messagesCount = Message::whereHas('conversation', function ($q) use ($contact) {
                    $q->where('contact_id', $contact->id);
                })->whereBetween('sent_at', [$from, $to])->count();

                $messagesSeries[] = [
                    'date' => $day->toDateString(),
                    'count' => $messagesCount,
                ];

                $conversationsSeries[] = [
                    'date' => $day->toDateString(),
                    'count' => $contact->conversations()->whereBetween('created_at', [$from, $to])->count(),
                ];
            }

            return [
                'type' => $contact->type,
                'last_seen_at' => optional($contact->last_seen_at)->toDateTimeString(),
                'memory_count' => $contact->memories()->count(),
                'tag_count' => $contact->tags()->count(),
                'rule_count' => $contact->replyRules()->count(),
                'baseline' => $contact->metadata['emotional_baseline'] ?? 'neutral',
                'time_series' => [
                    'memories' => $memoriesSeries,
                    'messages' => $messagesSeries,
                    'conversations' => $conversationsSeries,
                ],
            ];
        });
    }
}
