<?php

namespace App\Services;

use App\Models\AgentTask;
use Illuminate\Support\Facades\Log;

class TaskRetryService
{
    protected array $retryHistory = [];
    protected array $backoffStrategies = [];

    public function __construct()
    {
        $this->backoffStrategies = [
            'fixed' => fn(int $attempt, int $delay) => $delay,
            'linear' => fn(int $attempt, int $delay) => $delay * $attempt,
            'exponential' => fn(int $attempt, int $delay) => $delay * (2 ** ($attempt - 1)),
        ];
    }

    public function retry(AgentTask $task, array $options = []): array
    {
        $maxRetries = $options['max_retries'] ?? $task->metadata['max_retries'] ?? 3;
        $retryDelay = $options['retry_delay'] ?? $task->metadata['retry_delay'] ?? 60;
        $strategy = $options['backoff_strategy'] ?? 'exponential';

        $retryCount = $task->metadata['retry_count'] ?? 0;

        if ($retryCount >= $maxRetries) {
            Log::warning("Max retries exceeded for task", [
                'task_id' => $task->id,
                'retry_count' => $retryCount,
                'max_retries' => $maxRetries,
            ]);

            return [
                'success' => false,
                'retry_count' => $retryCount,
                'max_retries' => $maxRetries,
                'error' => "Max retries ({$maxRetries}) exceeded",
            ];
        }

        $backoffFn = $this->backoffStrategies[$strategy] ?? $this->backoffStrategies['exponential'];
        $delay = $backoffFn($retryCount + 1, $retryDelay);
        $delay = min($delay, 300);

        $this->recordRetryAttempt($task, $retryCount + 1, $delay);

        Log::info("Task scheduled for retry", [
            'task_id' => $task->id,
            'attempt' => $retryCount + 1,
            'max_retries' => $maxRetries,
            'delay_seconds' => $delay,
            'strategy' => $strategy,
        ]);

        return [
            'success' => true,
            'retry_count' => $retryCount + 1,
            'max_retries' => $maxRetries,
            'delay_seconds' => $delay,
            'strategy' => $strategy,
            'remaining_retries' => $maxRetries - ($retryCount + 1),
        ];
    }

    public function shouldRetry(AgentTask $task, ?string $error = null): bool
    {
        $maxRetries = $task->metadata['max_retries'] ?? 3;
        $retryCount = $task->metadata['retry_count'] ?? 0;

        if ($retryCount >= $maxRetries) {
            return false;
        }

        if ($error === null) {
            return true;
        }

        $retryableErrors = $task->metadata['retryable_errors'] ?? [
            'timeout',
            'connection_error',
            'rate_limit',
            'service_unavailable',
            'internal_server_error',
        ];

        foreach ($retryableErrors as $retryableError) {
            if (str_contains(strtolower($error), strtolower($retryableError))) {
                return true;
            }
        }

        return false;
    }

    public function incrementRetryCount(AgentTask $task): AgentTask
    {
        $metadata = $task->metadata ?? [];
        $metadata['retry_count'] = ($metadata['retry_count'] ?? 0) + 1;
        $metadata['last_retry_at'] = now()->toISOString();

        $task->update(['metadata' => $metadata]);

        Log::info("Retry count incremented for task", [
            'task_id' => $task->id,
            'retry_count' => $metadata['retry_count'],
        ]);

        return $task;
    }

    public function resetRetryCount(AgentTask $task): AgentTask
    {
        $metadata = $task->metadata ?? [];
        $metadata['retry_count'] = 0;
        unset($metadata['last_retry_at']);

        $task->update(['metadata' => $metadata]);

        return $task;
    }

    protected function recordRetryAttempt(AgentTask $task, int $attempt, int $delay): void
    {
        $this->retryHistory[] = [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'attempt' => $attempt,
            'delay_seconds' => $delay,
            'timestamp' => now()->toISOString(),
        ];
    }

    public function getRetryHistory(int $taskId = null): array
    {
        if ($taskId) {
            return array_filter($this->retryHistory, fn($h) => $h['task_id'] === $taskId);
        }
        return $this->retryHistory;
    }

    public function getBackoffStrategies(): array
    {
        return array_keys($this->backoffStrategies);
    }

    public function calculateBackoff(string $strategy, int $attempt, int $baseDelay = 60): int
    {
        $backoffFn = $this->backoffStrategies[$strategy] ?? $this->backoffStrategies['exponential'];
        return min($backoffFn($attempt, $baseDelay), 300);
    }

    public function clearHistory(): void
    {
        $this->retryHistory = [];
    }
}
