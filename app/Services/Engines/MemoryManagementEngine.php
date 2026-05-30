<?php

namespace App\Services\Engines;

use App\Models\Memory;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;

class MemoryManagementEngine
{
    protected array $memoryTypes = [
        'fact' => ['description' => 'Objective facts about the contact', 'priority' => 10],
        'preference' => ['description' => 'User preferences and likes', 'priority' => 8],
        'event' => ['description' => 'Important events and dates', 'priority' => 9],
        'context' => ['description' => 'Conversation context and state', 'priority' => 7],
        'general' => ['description' => 'General information', 'priority' => 5],
    ];

    protected int $maxMemoriesPerContact = 100;
    protected int $retentionDays = 365;

    public function store(array $data): array
    {
        $contactId = $data['contact_id'] ?? null;
        $type = $data['type'] ?? 'general';
        $content = $data['content'] ?? '';
        $source = $data['source'] ?? 'manual';
        $metadata = $data['metadata'] ?? [];

        if (!$contactId || !$content) {
            return [
                'success' => false,
                'error' => 'contact_id and content are required',
            ];
        }

        $contact = Contact::find($contactId);
        if (!$contact) {
            return [
                'success' => false,
                'error' => "Contact not found: {$contactId}",
            ];
        }

        $memory = Memory::create([
            'contact_id' => $contactId,
            'type' => $type,
            'content' => $content,
            'source' => $source,
            'metadata' => $metadata,
            'status' => 'active',
        ]);

        $this->enforceMemoryLimit($contactId);

        Log::info('Memory stored', [
            'memory_id' => $memory->id,
            'contact_id' => $contactId,
            'type' => $type,
        ]);

        return [
            'success' => true,
            'memory' => $memory,
        ];
    }

    public function retrieve(array $criteria = []): array
    {
        $contactId = $criteria['contact_id'] ?? null;
        $type = $criteria['type'] ?? null;
        $limit = $criteria['limit'] ?? 10;
        $query = Memory::query();

        if ($contactId) {
            $query->forContact($contactId);
        }
        if ($type) {
            $query->where('type', $type);
        }

        $memories = $query->orderBy('created_at', 'desc')->limit($limit)->get();

        return [
            'success' => true,
            'memories' => $memories,
            'count' => $memories->count(),
        ];
    }

    public function search(string $query, array $criteria = []): array
    {
        $contactId = $criteria['contact_id'] ?? null;
        $limit = $criteria['limit'] ?? 10;

        $memories = Memory::query()
            ->when($contactId, fn($q) => $q->forContact($contactId))
            ->where('content', 'like', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'query' => $query,
            'memories' => $memories,
            'count' => $memories->count(),
        ];
    }

    public function forget(string $memoryId): array
    {
        $memory = Memory::find($memoryId);
        if (!$memory) {
            return [
                'success' => false,
                'error' => "Memory not found: {$memoryId}",
            ];
        }

        $memory->delete();

        Log::info('Memory forgotten', ['memory_id' => $memoryId]);

        return [
            'success' => true,
            'memory_id' => $memoryId,
        ];
    }

    protected function enforceMemoryLimit(string $contactId): void
    {
        $count = Memory::where('contact_id', $contactId)->count();
        if ($count > $this->maxMemoriesPerContact) {
            $excess = $count - $this->maxMemoriesPerContact;
            $toDelete = Memory::where('contact_id', $contactId)
                ->orderBy('created_at', 'asc')
                ->limit($excess)
                ->get();

            foreach ($toDelete as $memory) {
                $memory->delete();
            }

            Log::info("Enforced memory limit for contact {$contactId}", [
                'deleted' => $excess,
            ]);
        }
    }

    public function getMemoryTypes(): array
    {
        return $this->memoryTypes;
    }
}
