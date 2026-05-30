<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class AnthropicProvider implements ProviderInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.anthropic.com/v1';
    protected array $models = [
        'claude-3-5-sonnet-20241022' => [
            'name' => 'Claude 3.5 Sonnet',
            'max_tokens' => 4096,
            'supports_vision' => true,
            'cost_per_1k_input' => 0.003,
            'cost_per_1k_output' => 0.015,
        ],
        'claude-3-opus-20240229' => [
            'name' => 'Claude 3 Opus',
            'max_tokens' => 4096,
            'supports_vision' => true,
            'cost_per_1k_input' => 0.015,
            'cost_per_1k_output' => 0.075,
        ],
        'claude-3-haiku-20240307' => [
            'name' => 'Claude 3 Haiku',
            'max_tokens' => 4096,
            'supports_vision' => true,
            'cost_per_1k_input' => 0.00025,
            'cost_per_1k_output' => 0.00125,
        ],
    ];

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getProviderName(): string
    {
        return 'anthropic';
    }

    public function getAvailableModels(): array
    {
        return array_keys($this->models);
    }

    public function getDefaultModel(): string
    {
        return 'claude-3-5-sonnet-20241022';
    }

    public function execute(array $request): array
    {
        $validation = $this->validateRequest($request);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => implode(', ', $validation['errors']),
                'provider' => $this->getProviderName(),
            ];
        }

        $model = $request['model'] ?? $this->getDefaultModel();
        $prompt = $request['prompt'] ?? '';
        $messages = $request['messages'] ?? [['role' => 'user', 'content' => $prompt]];
        $options = $request['options'] ?? [];

        $startTime = microtime(true);

        try {
            $response = $this->callAnthropic($model, $messages, $options);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            $parsed = $this->parseResponse($response);

            return [
                'success' => true,
                'provider' => $this->getProviderName(),
                'model' => $model,
                'content' => $parsed['content'] ?? '',
                'usage' => $parsed['usage'] ?? [],
                'duration_ms' => $durationMs,
                'raw' => $response,
            ];
        } catch (\Throwable $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);
            Log::error("Anthropic API error: " . $e->getMessage());

            return [
                'success' => false,
                'provider' => $this->getProviderName(),
                'model' => $model,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ];
        }
    }

    public function validateRequest(array $request): array
    {
        $errors = [];

        if (empty($request['prompt']) && empty($request['messages'])) {
            $errors[] = 'Prompt or messages are required';
        }

        if (isset($request['model']) && !isset($this->models[$request['model']])) {
            $errors[] = "Unknown model: {$request['model']}";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function getRateLimitStatus(): array
    {
        return [
            'provider' => $this->getProviderName(),
            'limit' => 60,
            'remaining' => 60,
            'reset_at' => now()->addMinute()->toISOString(),
        ];
    }

    public function getHealthStatus(): array
    {
        try {
            $response = $this->callAnthropic($this->getDefaultModel(), [['role' => 'user', 'content' => 'hi']], ['max_tokens' => 5]);
            return [
                'provider' => $this->getProviderName(),
                'status' => 'healthy',
                'latency_ms' => 0,
                'model' => $this->getDefaultModel(),
            ];
        } catch (\Throwable $e) {
            return [
                'provider' => $this->getProviderName(),
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function formatRequest(array $prompt, array $options = []): array
    {
        $model = $options['model'] ?? $this->getDefaultModel();

        if (is_string($prompt)) {
            $messages = [['role' => 'user', 'content' => $prompt]];
        } else {
            $messages = $prompt;
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? 4096,
        ];

        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }
        if (isset($options['top_p'])) {
            $payload['top_p'] = $options['top_p'];
        }
        if (isset($options['stream'])) {
            $payload['stream'] = $options['stream'];
        }

        return $payload;
    }

    public function parseResponse(array $response): array
    {
        $content = '';
        $usage = [];

        if (isset($response['content'][0]['text'])) {
            $content = $response['content'][0]['text'];
        }

        if (isset($response['usage'])) {
            $usage = [
                'prompt_tokens' => $response['usage']['input_tokens'] ?? 0,
                'completion_tokens' => $response['usage']['output_tokens'] ?? 0,
                'total_tokens' => ($response['usage']['input_tokens'] ?? 0) + ($response['usage']['output_tokens'] ?? 0),
            ];
        }

        return [
            'content' => $content,
            'usage' => $usage,
        ];
    }

    public function estimateCost(string $model, int $inputTokens, int $outputTokens = 0): float
    {
        $modelConfig = $this->models[$model] ?? null;
        if (!$modelConfig) return 0.0;

        $inputCost = ($inputTokens / 1000) * $modelConfig['cost_per_1k_input'];
        $outputCost = ($outputTokens / 1000) * $modelConfig['cost_per_1k_output'];

        return round($inputCost + $outputCost, 6);
    }

    protected function callAnthropic(string $model, array $messages, array $options = []): array
    {
        $url = "{$this->baseUrl}/messages";

        $payload = $this->formatRequest($messages, array_merge($options, ['model' => $model]));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT => $options['timeout'] ?? 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new \RuntimeException("Anthropic API error: HTTP {$httpCode} - {$response}");
        }

        return json_decode($response, true) ?: [];
    }
}
