<?php

namespace App\Jobs;

use App\Models\Memory;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Save to Pinecone Job - Store vectorized memory in Pinecone vector database
 *
 * This job takes a vectorized memory and upserts it to Pinecone
 * for semantic search and retrieval capabilities.
 *
 * Queue: default
 * Timeout: 60 seconds
 * Retries: 2 attempts
 */
class SaveToPineconeJob extends BaseJob
{
    /**
     * Queue assignment.
     *
     * @var string
     */
    public $queue = 'default';

    /**
     * Job timeout.
     *
     * @var int
     */
    public int $timeout = 60;

    /**
     * Number of retry attempts.
     *
     * @var int
     */
    public int $tries = 2;

    /**
     * Constructor.
     *
     * @param string $memoryId Memory UUID
     * @param array $vector Vector embedding array
     * @param array $metadata Metadata for Pinecone record
     */
    public function __construct(
        protected string $memoryId,
        protected array $vector,
        protected array $metadata = [],
    ) {
        $this->idempotencyKey = "pinecone_save:{$memoryId}";
    }

    /**
     * Execute the job - Save vector to Pinecone.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $this->logJobStart([
            'memory_id' => $this->memoryId,
            'vector_dimension' => count($this->vector),
        ]);

        try {
            // Check idempotency - prevent duplicate Pinecone entries
            if ($this->isProcessed()) {
                $this->logJobComplete([
                    'reason' => 'idempotent_skip',
                    'memory_id' => $this->memoryId,
                ]);
                return;
            }

            // Fetch memory to ensure it exists and get metadata
            $memory = $this->safelyGetModel(Memory::class, $this->memoryId);
            if (!$memory) {
                throw new Exception("Memory not found: {$this->memoryId}");
            }

            // Validate vector
            if (empty($this->vector) || !is_array($this->vector)) {
                throw new Exception("Invalid vector data for memory: {$this->memoryId}");
            }

            // Prepare Pinecone metadata (sanitized, no sensitive data)
            $pineconeMetadata = [
                'memory_id' => $this->memoryId,
                'type' => $memory->type ?? 'general',
                'source' => $memory->source ?? 'unknown',
                'created_at' => $memory->created_at?->toDateTimeString(),
                'contact_id' => $memory->contact_id,
                'conversation_id' => $memory->conversation_id,
            ];

            // Merge with provided metadata
            $pineconeMetadata = array_merge($pineconeMetadata, $this->metadata);

            // Call Pinecone API
            $startTime = microtime(true);
            $pineconeResponse = $this->upsertToPinecone($this->memoryId, $this->vector, $pineconeMetadata);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            if (!$pineconeResponse) {
                throw new Exception("Pinecone upsert failed for memory: {$this->memoryId}");
            }

            // Update memory with Pinecone ID and status
            $memory->update([
                'metadata' => array_merge(
                    $memory->metadata ?? [],
                    [
                        'pinecone_id' => $this->memoryId,
                        'pinecone_indexed_at' => now()->toDateTimeString(),
                        'pinecone_duration_ms' => $durationMs,
                        'indexed' => true,
                    ]
                ),
            ]);

            // Broadcast event that memory has been indexed
            event(new \App\Events\MemoryIndexed(
                $this->memoryId,
                $this->memoryId
            ));

            // Mark as processed for idempotency
            $this->markAsProcessed([
                'pinecone_id' => $this->memoryId,
                'duration_ms' => $durationMs,
            ]);

            $this->logJobComplete([
                'memory_id' => $this->memoryId,
                'pinecone_id' => $this->memoryId,
                'duration_ms' => $durationMs,
            ]);

        } catch (Exception $e) {
            $this->logJobFailure($e, [
                'memory_id' => $this->memoryId,
                'attempt' => $this->getCurrentAttempt(),
            ]);

            throw $e;
        }
    }

    /**
     * Upsert vector to Pinecone.
     *
     * @param string $id Unique ID for the vector
     * @param array $vector Vector embedding
     * @param array $metadata Vector metadata
     * @return bool Success indicator
     */
    protected function upsertToPinecone(string $id, array $vector, array $metadata): bool
    {
        try {
            $apiKey = config('services.pinecone.api_key');
            $environment = config('services.pinecone.environment', 'us-east-1-aws');
            $indexName = config('services.pinecone.index_name', 'nexussoul-memory');

            if (!$apiKey) {
                throw new Exception("Pinecone API key not configured");
            }

            // Pinecone API endpoint
            $host = "https://{$indexName}-{$environment}.pinecone.io";

            // Prepare request body
            $vectors = [
                [
                    'id' => $id,
                    'values' => $vector,
                    'metadata' => $metadata,
                ],
            ];

            // Call Pinecone upsert endpoint
            $response = Http::withHeaders([
                'Api-Key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$host}/vectors/upsert", [
                'vectors' => $vectors,
            ]);

            if ($response->failed()) {
                \Log::error("Pinecone upsert failed", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'vector_id' => $id,
                ]);
                return false;
            }

            $result = $response->json();
            return isset($result['upsertedCount']) && $result['upsertedCount'] > 0;

        } catch (Exception $e) {
            \Log::error("Error upserting to Pinecone", [
                'exception' => $e->getMessage(),
                'vector_id' => $id,
            ]);
            return false;
        }
    }

    /**
     * Extract idempotency data from job properties.
     *
     * @return array
     */
    protected function extractIdempotencyData(): array
    {
        return [
            'memory_id' => $this->memoryId,
        ];
    }
}
