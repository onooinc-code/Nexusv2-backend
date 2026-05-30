<?php

namespace App\Services\Pipelines;

use App\Models\Memory;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class MemoryExtractionPipeline
{
    protected array $extractors = [];
    protected array $filters = [];

    public function __construct(array $extractors = [], array $filters = [])
    {
        $this->extractors = $extractors;
        $this->filters = $filters;
    }

    public function registerExtractor(string $type, callable $extractor): void
    {
        $this->extractors[$type] = $extractor;
    }

    public function registerFilter(callable $filter): void
    {
        $this->filters[] = $filter;
    }

    public function extractFromMessage(Message $message): array
    {
        $content = $message->content ?? '';
        $extracted = [];

        foreach ($this->extractors as $type => $extractor) {
            try {
                $result = $extractor($content, $message->metadata ?? []);
                if ($result) {
                    $extracted[$type] = $result;
                }
            } catch (\Throwable $e) {
                Log::warning("Memory extractor failed for type {$type}: " . $e->getMessage());
            }
        }

        return $extracted;
    }

    public function extractAndStore(Message $message, string $contactId): array
    {
        $extracted = $this->extractFromMessage($message);
        $stored = [];

        foreach ($extracted as $type => $data) {
            $filtered = $this->applyFilters($data, $type);
            if ($filtered === null) continue;

            $memory = Memory::create([
                'contact_id' => $contactId,
                'type' => $type,
                'content' => is_string($filtered) ? $filtered : json_encode($filtered),
                'metadata' => array_merge($message->metadata ?? [], [
                    'extracted_from_message_id' => $message->id,
                    'extraction_type' => $type,
                ]),
                'source' => 'message_extraction',
                'status' => 'active',
            ]);

            $stored[] = $memory;
            Log::info("Memory extracted and stored", [
                'memory_id' => $memory->id,
                'type' => $type,
                'contact_id' => $contactId,
            ]);
        }

        return [
            'success' => true,
            'extracted_count' => count($extracted),
            'stored_count' => count($stored),
            'memories' => $stored,
        ];
    }

    protected function applyFilters($data, string $type)
    {
        foreach ($this->filters as $filter) {
            $result = $filter($data, $type);
            if ($result === null) return null;
            $data = $result;
        }
        return $data;
    }

    public function getExtractors(): array
    {
        return array_keys($this->extractors);
    }
}
