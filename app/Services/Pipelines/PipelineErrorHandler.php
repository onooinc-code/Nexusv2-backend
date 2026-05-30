<?php

namespace App\Services\Pipelines;

use Illuminate\Support\Facades\Log;

class PipelineErrorHandler
{
    protected array $retryStrategies = [];
    protected array $fallbackHandlers = [];
    protected int $maxRetries = 3;

    public function __construct(array $retryStrategies = [], array $fallbackHandlers = [])
    {
        $this->retryStrategies = $retryStrategies;
        $this->fallbackHandlers = $fallbackHandlers;
    }

    public function registerRetryStrategy(string $errorType, callable $strategy): void
    {
        $this->retryStrategies[$errorType] = $strategy;
    }

    public function registerFallbackHandler(string $pipelineStage, callable $handler): void
    {
        $this->fallbackHandlers[$pipelineStage] = $handler;
    }

    public function handle(string $stage, \Throwable $error, array $context = []): array
    {
        Log::error("Pipeline error at stage: {$stage}", [
            'error' => $error->getMessage(),
            'context' => $context,
        ]);

        if (isset($this->fallbackHandlers[$stage])) {
            try {
                $result = ($this->fallbackHandlers[$stage])($error, $context);
                return [
                    'success' => true,
                    'recovered' => true,
                    'stage' => $stage,
                    'result' => $result,
                    'method' => 'fallback',
                ];
            } catch (\Throwable $e) {
                Log::error("Fallback handler failed for stage {$stage}: " . $e->getMessage());
            }
        }

        $errorType = $this->classifyError($error);
        if (isset($this->retryStrategies[$errorType])) {
            return [
                'success' => false,
                'recovered' => false,
                'stage' => $stage,
                'error' => $error->getMessage(),
                'error_type' => $errorType,
                'retry_strategy' => $errorType,
                'retryable' => true,
            ];
        }

        return [
            'success' => false,
            'recovered' => false,
            'stage' => $stage,
            'error' => $error->getMessage(),
            'error_type' => $errorType,
            'retryable' => false,
        ];
    }

    protected function classifyError(\Throwable $error): string
    {
        $message = strtolower($error->getMessage());

        if (str_contains($message, 'timeout')) return 'timeout';
        if (str_contains($message, 'rate limit')) return 'rate_limit';
        if (str_contains($message, 'connection')) return 'connection';
        if (str_contains($message, 'api key')) return 'auth';
        if (str_contains($message, 'validation')) return 'validation';
        if (str_contains($message, 'not found')) return 'not_found';

        return 'unknown';
    }

    public function shouldRetry(string $errorType, int $attempt): bool
    {
        $retryableTypes = ['timeout', 'rate_limit', 'connection'];
        if (!in_array($errorType, $retryableTypes)) return false;
        if ($attempt >= $this->maxRetries) return false;

        return true;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function setMaxRetries(int $max): void
    {
        $this->maxRetries = $max;
    }
}
