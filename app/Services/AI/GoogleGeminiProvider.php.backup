<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleGeminiProvider implements ProviderInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    protected array $models = [
        'gemini-1.5-pro' => [
            'name' => 'Gemini 1.5 Pro',
            'max_tokens' => 8192,
            'supports_vision' => true,
            'cost_per_1k_input' => 0.0035,
            'cost_per_1k_output' => 0.0105,
        ],
        'gemini-1.5-flash' => [
            'name' => 'Gemini 1.5 Flash',
            'max_tokens' => 8192,
            'supports_vision' => true,
            'cost_per_1k_input' => 0.000075,
            'cost_per_1k_output' => 0.0003,
        ],
        'gemini-2.0-flash' => [
            'name' => 'Gemini 2.0 Flash',
            'max_tokens' => 8192,
            'supports_vision' => true,
            'cost_per_1k_input' => 0.0001,
            'cost_per_1k_output' => 0.0004,
        ],
    ];

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getProviderName(): string
    {
        return 'google_gemini';
    }

    public function getAvailableModels(): array
    {
        return array_keys($this->models);
    }

    public function getDefaultModel(): string
    {
        return 'gemini-1.5-pro';
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
        $prompt = $request['prompt'] ?? $request['messages'] ?? '';
        $options = $request['options'] ?? [];

        $startTime = microtime(true);

        try {
            $response = $this->callGeminiApi($model, $prompt, $options);
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
            Log::error("Gemini API error: " . $e->getMessage());

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
            $response = $this->callGeminiApi($this->getDefaultModel(), 'health check', ['max_tokens' => 5]);
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

        $contents = [];
        if (is_string($prompt)) {
            $contents[] = ['role' => 'user', 'parts' => [['text' => $prompt]]];
        } elseif (is_array($prompt)) {
            foreach ($prompt as $message) {
                $role = $message['role'] ?? 'user';
                $text = $message['content'] ?? $message['text'] ?? '';
                $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
            }
        }

        $payload = [
            'contents' => $contents,
        ];

        if (isset($options['max_tokens'])) {
            $payload['generationConfig'] = ['maxOutputTokens' => $options['max_tokens']];
        }
        if (isset($options['temperature'])) {
            $payload['generationConfig']['temperature'] = $options['temperature'];
        }
        if (isset($options['top_p'])) {
            $payload['generationConfig']['topP'] = $options['top_p'];
        }

        return $payload;
    }

    public function parseResponse(array $response): array
    {
        $content = '';
        $usage = [];

        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $response['candidates'][0]['content']['parts'][0]['text'];
        }

        if (isset($response['usageMetadata'])) {
            $usage = [
                'prompt_tokens' => $response['usageMetadata']['promptTokenCount'] ?? 0,
                'completion_tokens' => $response['usageMetadata']['candidatesTokenCount'] ?? 0,
                'total_tokens' => $response['usageMetadata']['totalTokenCount'] ?? 0,
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

    protected function callGeminiApi(string $model, $prompt, array $options = []): array
    {
        $url = "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}";

        $payload = $this->formatRequest($prompt, array_merge($options, ['model' => $model]));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => $options['timeout'] ?? 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new \RuntimeException("Gemini API error: HTTP {$httpCode} - {$response}");
        }

        return json_decode($response, true) ?: [];
    }
}
