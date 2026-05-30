<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Exception;
use Throwable;

/**
 * Base Job Class with Resilience Patterns
 *
 * This abstract class provides common resilience patterns for all async jobs:
 * - Idempotency: Prevents duplicate processing of same job
 * - Model Missing Handling: Safely handles deleted Eloquent models
 * - Rate Limit Safe Release: Uses release() instead of sleep() for rate limits
 *
 * All jobs should extend this class for consistency and reliability.
 */
abstract class BaseJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * Number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public int $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 120;

    /**
     * The number of seconds to wait before retrying a job that encounters a mitigable error.
     *
     * @var int
     */
    public int $backoff = 5;

    /**
     * Idempotency key for this job (override in child class if needed).
     *
     * @var string|null
     */
    protected ?string $idempotencyKey = null;

    /**
     * Cache TTL for idempotency key (in seconds).
     *
     * @var int
     */
    protected int $idempotencyCacheTtl = 3600; // 1 hour

    /**
     * Whether to delete the job if its models are missing.
     *
     * @var bool
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Execute the job.
     *
     * This method is called by the queue worker and must be implemented by child classes.
     *
     * @return void
     */
    abstract public function handle(): void;

    /**
     * Generate idempotency key for this job.
     *
     * Override this method in child classes to customize idempotency key generation.
     * Default implementation uses class name and first constructor parameter.
     *
     * @return string
     */
    protected function generateIdempotencyKey(): string
    {
        if ($this->idempotencyKey) {
            return $this->idempotencyKey;
        }

        // Generate key from class name and job attributes
        $keyParts = [
            class_basename($this),
            json_encode($this->extractIdempotencyData()),
        ];

        return 'job_idempotency:' . hash('sha256', implode(':', $keyParts));
    }

    /**
     * Extract data for idempotency key generation.
     *
     * Override in child classes to specify which properties make the job unique.
     * Default: all public properties.
     *
     * @return array
     */
    protected function extractIdempotencyData(): array
    {
        $data = [];
        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!in_array($property->getName(), ['queue', 'delay', 'connection'])) {
                $data[$property->getName()] = $property->getValue($this);
            }
        }

        return $data;
    }

    /**
     * Check if this job has already been processed (idempotency check).
     *
     * @return bool
     */
    protected function isProcessed(): bool
    {
        $key = $this->generateIdempotencyKey();
        return Cache::has($key);
    }

    /**
     * Get the cached result from a previous execution (if exists).
     *
     * @return mixed
     */
    protected function getCachedResult(): mixed
    {
        $key = $this->generateIdempotencyKey();
        return Cache::get($key);
    }

    /**
     * Mark this job as processed and cache the result.
     *
     * @param mixed $result The result to cache
     * @return void
     */
    protected function markAsProcessed(mixed $result = null): void
    {
        $key = $this->generateIdempotencyKey();
        Cache::put($key, $result, $this->idempotencyCacheTtl);
    }

    /**
     * Safely retrieve a model instance, checking if it still exists.
     *
     * @param string $modelClass The fully-qualified model class name
     * @param mixed $id The model ID
     * @return Model|null
     */
    protected function safelyGetModel(string $modelClass, mixed $id): ?Model
    {
        try {
            $model = $modelClass::find($id);
            if (!$model) {
                \Log::warning("Model not found: {$modelClass} with ID {$id}", [
                    'job' => class_basename($this),
                    'idempotency_key' => $this->generateIdempotencyKey(),
                ]);
            }
            return $model;
        } catch (Exception $e) {
            \Log::error("Error retrieving model: {$modelClass}", [
                'exception' => $e->getMessage(),
                'job' => class_basename($this),
            ]);
            return null;
        }
    }

    /**
     * Handle rate limit response from API (HTTP 429).
     *
     * Instead of sleeping, release the job back to the queue with exponential backoff.
     * This prevents blocking the worker process.
     *
     * @param int $attempt Current attempt number (1-based)
     * @param int $baseDelay Base delay in seconds (default 5)
     * @return void
     *
     * @throws Exception Thrown to trigger job release
     */
    protected function handleRateLimit(int $attempt = 1, int $baseDelay = 5): void
    {
        // Calculate exponential backoff: 5s, 25s, 125s, etc.
        $delay = $baseDelay * pow(5, max(0, $attempt - 1));

        \Log::warning("Rate limit hit, releasing job back to queue", [
            'job' => class_basename($this),
            'attempt' => $attempt,
            'delay_seconds' => $delay,
            'idempotency_key' => $this->generateIdempotencyKey(),
        ]);

        $this->release($delay);

        throw new Exception("Rate limited - job released back to queue");
    }

    /**
     * Get current attempt number from queue worker.
     *
     * @return int The current attempt number (1-based)
     */
    protected function getCurrentAttempt(): int
    {
        return (int) $this->attempts();
    }

    /**
     * Check if this is the final attempt before job fails.
     *
     * @return bool
     */
    protected function isFinalAttempt(): bool
    {
        return $this->getCurrentAttempt() >= $this->tries;
    }

    /**
     * Log job start with context information.
     *
     * @param array $context Additional context data
     * @return void
     */
    protected function logJobStart(array $context = []): void
    {
        \Log::info("Job started", array_merge([
            'job' => class_basename($this),
            'attempt' => $this->getCurrentAttempt(),
            'queue' => $this->queue,
            'idempotency_key' => $this->generateIdempotencyKey(),
        ], $context));
    }

    /**
     * Log job completion with context information.
     *
     * @param array $context Additional context data
     * @return void
     */
    protected function logJobComplete(array $context = []): void
    {
        \Log::info("Job completed successfully", array_merge([
            'job' => class_basename($this),
            'attempt' => $this->getCurrentAttempt(),
            'queue' => $this->queue,
            'idempotency_key' => $this->generateIdempotencyKey(),
        ], $context));
    }

    /**
     * Log job failure with context information.
     *
     * @param Throwable $exception The exception that caused failure
     * @param array $context Additional context data
     * @return void
     */
    protected function logJobFailure(Throwable $exception, array $context = []): void
    {
        \Log::error("Job failed", array_merge([
            'job' => class_basename($this),
            'attempt' => $this->getCurrentAttempt(),
            'queue' => $this->queue,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'idempotency_key' => $this->generateIdempotencyKey(),
        ], $context));
    }

    /**
     * Handle a job failure.
     *
     * Called by Laravel queue when job fails after max attempts.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        $this->logJobFailure($exception);

        // Fire failed event that listeners can handle
        // This will be picked up by NotifyJobFailed listener
        event(new \App\Events\JobFailedEvent(
            class_basename($this),
            $this->queue ?? 'default',
            $exception->getMessage(),
            json_encode($this->extractIdempotencyData())
        ));
    }
}
