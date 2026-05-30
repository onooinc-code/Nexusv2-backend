<?php

namespace App\Jobs;

use App\Models\Memory;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Vectorize Memory Job - Generate embeddings for memory content
 *
 * This job takes memory content and generates vector embeddings
 * using OpenAI Embeddings API, storing the result for semantic search.
 *
 * Queue: default
 * Timeout: 120 seconds
 * Retries: 2 attempts
 */
class VectorizeMemoryJob extends BaseJob
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
    public int $timeout = 120;

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
     * @param string $content Content to vectorize
     */
    public function __construct(
        protected string $memoryId,
        protected string $content,
    ) {
        $this->idempotencyKey = "vectorize_memory:{$memoryId}";
    }

    /**
     * Execute the job - Generate vector embedding.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $this->logJobStart([
            'memory_id' => $this->memoryId,
            'content_length' => strlen($this->content),
        ]);

        try {
            // Check idempotency - prevent re-vectorizing same memory
            if ($this->isProcessed()) {
                $this->logJobComplete([
                    'reason' => 'idempotent_skip',
                    'memory_id' => $this->memoryId,
                ]);
                return;
            }

            // Fetch memory model
            $memory = $this->safelyGetModel(Memory::class, $this->memoryId);
            if (!$memory) {
                throw new Exception("Memory not found: {$this->memoryId}");
            }

            // Get OpenAI API key
            $apiKey = $this->getOpenAIApiKey();
            if (!$apiKey) {
                throw new Exception("No OpenAI API key found");
            }

            // Call OpenAI Embeddings API
            $startTime = microtime(true);
            $vector = $this->generateEmbedding($this->content, $apiKey);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            if (!$vector) {
                throw new Exception("Failed to generate embedding");
            }

            // Update memory with embedding vector
            $memory->update([
                'vector' => $vector,
                'metadata' => array_merge(
                    $memory->metadata ?? [],
                    [
                        'vectorized_at' => now()->toDateTimeString(),
                        'vector_dimension' => count($vector),
                        'embedding_duration_ms' => $durationMs,
                    ]
                ),
            ]);

            // Broadcast event that memory has been vectorized
            event(new \App\Events\MemoryVectorized(
                $this->memoryId,
                count($vector)
            ));

            // Mark as processed for idempotency
            $this->markAsProcessed([
                'vector_dimension' => count($vector),
                'duration_ms' => $durationMs,
            ]);

            $this->logJobComplete([
                'memory_id' => $this->memoryId,
                'vector_dimension' => count($vector),
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
     * Generate embedding using OpenAI API.
     *
     * @param string $text Text to embed
     * @param string $apiKey OpenAI API key
     * @return array|null Vector array or null on failure
     */
    protected function generateEmbedding(string $text, string $apiKey): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

            if ($response->failed()) {
                \Log::error("OpenAI embedding API failed", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            return $data['data'][0]['embedding'] ?? null;

        } catch (Exception $e) {
            \Log::error("Error generating embedding", [
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get OpenAI API key.
     *
     * @return string|null API key or null
     */
    protected function getOpenAIApiKey(): ?string
    {
        // Try to get from database first
        $apiKey = ApiKey::where('provider', 'openai')
            ->where('type', 'ai_provider')
            ->where('is_active', true)
            ->first()?->key;

        // Fallback to environment variable
        if (!$apiKey) {
            $apiKey = config('services.openai.api_key');
        }

        return $apiKey;
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
