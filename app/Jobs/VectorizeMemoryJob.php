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

            // Call AiModelsHub Gateway for Embeddings
            $startTime = microtime(true);
            $gateway = app(\App\Services\AiModelsHub\UniversalAiGatewayService::class);
            $vector = $gateway->generateEmbeddings($this->content);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            if (empty($vector)) {
                throw new Exception("Failed to generate embedding: empty vector returned from Gateway");
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
