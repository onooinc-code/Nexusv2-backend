<?php

namespace App\Services\Pipelines;

use App\Models\Conversation;
use App\Models\Memory;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;

class ContextAssemblyPipeline
{
    protected int $maxMemories = 10;
    protected int $maxMessages = 20;
    protected array $context = [];

    public function __construct(int $maxMemories = 10, int $maxMessages = 20)
    {
        $this->maxMemories = $maxMemories;
        $this->maxMessages = $maxMessages;
    }

    public function assemble(array $payload): array
    {
        $conversationId = $payload['conversation_id'] ?? null;
        $contactId = $payload['contact_id'] ?? null;
        $userId = $payload['user_id'] ?? null;
        $includeMemories = $payload['include_memories'] ?? true;
        $includeHistory = $payload['include_history'] ?? true;

        $context = [
            'timestamp' => now()->toISOString(),
            'conversation' => null,
            'contact' => null,
            'memories' => [],
            'history' => [],
            'summary' => '',
        ];

        if ($conversationId) {
            $conversation = Conversation::with(['contact'])->find($conversationId);
            if ($conversation) {
                $context['conversation'] = $conversation;
                $context['contact'] = $conversation->contact;
                $contactId = $contactId ?? $conversation->contact_id;
            }
        }

        if ($contactId) {
            $contact = Contact::find($contactId);
            if ($contact) {
                $context['contact'] = $contact;
            }
        }

        if ($includeMemories && $contactId) {
            $context['memories'] = $this->loadMemories($contactId, $userId);
        }

        if ($includeHistory && $conversationId) {
            $context['history'] = $this->loadHistory($conversationId);
        }

        if (!empty($context['memories'])) {
            $context['summary'] = $this->buildSummary($context['memories']);
        }

        $this->context = $context;

        return [
            'success' => true,
            'context' => $context,
            'memory_count' => count($context['memories']),
            'history_count' => count($context['history']),
        ];
    }

    protected function loadMemories(string $contactId, ?string $userId): array
    {
        $query = Memory::query()
            ->where(function ($q) use ($contactId, $userId) {
                $q->where('contact_id', $contactId);
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            })
            ->orderBy('created_at', 'desc')
            ->limit($this->maxMemories)
            ->get();

        return $query->all();
    }

    protected function loadHistory(string $conversationId): array
    {
        $conversation = Conversation::with(['messages' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit($this->maxMessages);
        }])->find($conversationId);

        if (!$conversation) return [];

        return $conversation->messages
            ->sortBy('created_at')
            ->values()
            ->all();
    }

    protected function buildSummary(array $memories): string
    {
        $summaryParts = [];
        foreach ($memories as $memory) {
            $type = $memory->type ?? 'general';
            $content = $memory->content ?? '';
            $summaryParts[] = "[{$type}] {$content}";
        }

        return implode("\n", $summaryParts);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function buildPrompt(array $context, string $userMessage): string
    {
        $parts = [];

        if (!empty($context['summary'])) {
            $parts[] = "## Relevant Context\n{$context['summary']}";
        }

        if (!empty($context['history'])) {
            $parts[] = "## Conversation History";
            foreach ($context['history'] as $msg) {
                $role = $msg->sender_type === 'user' ? 'User' : 'Assistant';
                $parts[] = "{$role}: {$msg->content}";
            }
        }

        $parts[] = "## Current Message\n{$userMessage}";

        return implode("\n\n", $parts);
    }
}
