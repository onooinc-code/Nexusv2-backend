<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\AIProvider;
use App\Models\AIModel;
use App\Services\AiModelsHub\EncryptedApiKeyStorage;

class GoogleGeminiProvider implements \App\Services\AiModelsHub\AiProviderInterface
{
    protected $provider;
    protected $apiKey;
    protected $baseUrl;
    protected $models = [];

    public function __construct(string $providerId, EncryptedApiKeyStorage $encryptedKeyStorage)
    {
        $this->provider = AIProvider::find($providerId);
        
        if (!$this->provider) {
            throw new \Exception("Provider not found: {$providerId}");
        }
        
        $this->apiKey = $encryptedKeyStorage->getDecryptedKey($providerId);
        $this->baseUrl = rtrim($this->provider->base_url, '/');
        
        // Load models from database
        $this->loadModelsFromDatabase();
    }

    protected function loadModelsFromDatabase()
    {
        $this->models = [];
        $dbModels = AIModel::where('provider_id', $this->provider->id)->get();
        
        foreach ($dbModels as $model) {
            $this->models[$model->id] = [
                'name' => $model->name,
                'max_tokens' => $model->context_window ?? 8192,
                'cost_per_1k_input' => $model->input_cost_per_m / 1000,
                'cost_per_1k_output' => $model->output_cost_per_m / 1000,
            ];
        }
        
        // If no models in database, fallback to some defaults
        if (empty($this->models)) {
            $this->models = [
                'gemini-1.5-pro' => [
                    'name' => 'Gemini 1.5 Pro',
                    'max_tokens' => 8192,
                    'cost_per_1k_input' => 0.0035,
                    'cost_per_1k_output' => 0.0105,
                ],
                'gemini-1.5-flash' => [
                    'name' => 'Gemini 1.5 Flash',
                    'max_tokens' => 8192,
                    'cost_per_1k_input' => 0.000075,
                    'cost_per_1k_output' => 0.0003,
                ],
                'gemini-2.0-flash' => [
                    'name' => 'Gemini 2.0 Flash',
                    'max_tokens' => 8192,
                    'cost_per_1k_input' => 0.0001,
                    'cost_per_1k_output' => 0.0004,
                ],
            ];
        }
    }

    public function getProviderName(): string
    {
        return $this->provider->name;
    }

    public function getAvailableModels(): array
    {
        return array_keys($this->models);
    }

    public function getDefaultModel(): string
    {
        // Return first available model or fallback
        $models = $this->getAvailableModels();
        return $models[0] ?? 'gemini-1.5-pro';
    }

    public function generateText(string $prompt, array $options = []): array
    {
        $validation = $this->validateRequest([
            'prompt' => $prompt,
            'model' => $options['model'] ?? $this->getDefaultModel(),
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? null,
        ]);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => implode(', ', $validation['errors']),
                'provider' => $this->getProviderName(),
            ];
        }

        $model = $options['model'] ?? $this->getDefaultModel();
        $messages = [['role' => 'user', 'content' => $prompt]];
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? null;

        try {
            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $prompt]]
                    ]
                ],
            ];

            if ($maxTokens !== null) {
                $payload['generationConfig'] = ['maxOutputTokens' => $maxTokens];
            }
            if (isset($options['temperature'])) {
                $payload['generationConfig']['temperature'] = $options['temperature'];
            }
            if (isset($options['top_p'])) {
                $payload['generationConfig']['topP'] = $options['top_p'];
            }

            $headers = [
                'Content-Type' => 'application/json',
            ];

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post("{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}", $payload);

            if (!$response->successful()) {
                throw new \Exception("Gemini API error: HTTP {$response->status()} - {$response->body()}");
            }

            $responseData = $response->json();

            $content = '';
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $content = $responseData['candidates'][0]['content']['parts'][0]['text'];
            }

            $usage = [
                'prompt_tokens' => $responseData['usageMetadata']['promptTokenCount'] ?? 0,
                'completion_tokens' => $responseData['usageMetadata']['candidatesTokenCount'] ?? 0,
                'total_tokens' => $responseData['usageMetadata']['totalTokenCount'] ?? 0,
            ];

            return [
                'success' => true,
                'provider' => $this->getProviderName(),
                'model' => $model,
                'content' => $content,
                'usage' => $usage,
            ];
        } catch (\Throwable $e) {
            Log::error("Gemini API error: " . $e->getMessage());

            return [
                'success' => false,
                'provider' => $this->getProviderName(),
                'model' => $model,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function generateEmbeddings(string $text, array $options = []): array
    {
        // Embeddings implementation would go here
        // For now, return a placeholder as Gemini embedding API might differ
        return [
            'success' => false,
            'provider' => $this->getProviderName(),
            'error' => 'Embeddings not implemented for Gemini provider',
        ];
    }

    public function validateRequest(array $request): array
    {
        $errors = [];

        if (empty($request['prompt'])) {
            $errors[] = 'Prompt is required';
        }

        if (isset($request['model']) && !isset($this->models[$request['model']])) {
            $errors[] = "Unknown model: {$request['model']}";
        }

        if (isset($request['temperature']) && ($request['temperature'] < 0 || $request['temperature'] > 2)) {
            $errors[] = 'Temperature must be between 0 and 2';
        }

        if (isset($request['max_tokens']) && $request['max_tokens'] <= 0) {
            $errors[] = 'Max tokens must be positive';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
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

    public function getHealthStatus(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/models?key={$this->apiKey}");

            if ($response->successful()) {
                return [
                    'provider' => $this->getProviderName(),
                    'status' => 'healthy',
                    'model' => $this->getDefaultModel(),
                ];
            } else {
                return [
                    'provider' => $this->getProviderName(),
                    'status' => 'unhealthy',
                    'error' => "HTTP {$response->status()}",
                ];
            }
        } catch (\Throwable $e) {
            return [
                'provider' => $this->getProviderName(),
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getRateLimitStatus(): array
    {
        // This would typically come from API headers
        return [
            'provider' => $this->getProviderName(),
            'limit' => 60,
            'remaining' => 60, // Would be from headers in real implementation
            'reset_at' => now()->addMinute()->toISOString(),
        ];
    }

    // Backward compatibility methods
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