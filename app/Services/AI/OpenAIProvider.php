<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\AIProvider;
use App\Models\AIModel;
use App\Services\AiModelsHub\EncryptedApiKeyStorage;
use App\Services\AiModelsHub\AiProviderInterface;

class OpenAIProvider implements AiProviderInterface
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
                'max_tokens' => $model->context_window ?? 4096,
                'cost_per_1k_input' => $model->input_cost_per_m / 1000,
                'cost_per_1k_output' => $model->output_cost_per_m / 1000,
            ];
        }
        
        // If no models in database, fallback to some defaults
        if (empty($this->models)) {
            $this->models = [
                'gpt-4o' => [
                    'name' => 'GPT-4o',
                    'max_tokens' => 4096,
                    'supports_vision' => true,
                    'cost_per_1k_input' => 0.005,
                    'cost_per_1k_output' => 0.015,
                ],
                'gpt-4o-mini' => [
                    'name' => 'GPT-4o Mini',
                    'max_tokens' => 4096,
                    'supports_vision' => true,
                    'cost_per_1k_input' => 0.00015,
                    'cost_per_1k_output' => 0.0006,
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
        return $models[0] ?? 'gpt-4o';
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
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ];
            
            if ($maxTokens !== null) {
                $payload['max_tokens'] = $maxTokens;
            }

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ];

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($this->baseUrl . '/chat/completions', $payload);

            if (!$response->successful()) {
                throw new \Exception("OpenAI API error: HTTP {$response->status()} - {$response->body()}");
            }

            $responseData = $response->json();

            $content = '';
            if (isset($responseData['choices'][0]['message']['content'])) {
                $content = $responseData['choices'][0]['message']['content'];
            }

            $usage = [
                'input_tokens' => $responseData['usage']['prompt_tokens'] ?? 0,
                'output_tokens' => $responseData['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $responseData['usage']['total_tokens'] ?? 0,
            ];

            return [
                'success' => true,
                'provider' => $this->getProviderName(),
                'model' => $model,
                'content' => $content,
                'usage' => $usage,
            ];
        } catch (\Throwable $e) {
            Log::error("OpenAI API error: " . $e->getMessage());

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
        // For now, return a placeholder
        return [
            'success' => false,
            'provider' => $this->getProviderName(),
            'error' => 'Embeddings not implemented for OpenAI provider',
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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/models');

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

    // Legacy methods for backward compatibility
    public function execute(array $request): array
    {
        // Convert legacy request format to new format
        $prompt = $request['prompt'] ?? '';
        $model = $request['model'] ?? $this->getDefaultModel();
        $temperature = $request['temperature'] ?? 0.7;
        $maxTokens = $request['max_tokens'] ?? null;
        
        return $this->generateText($prompt, [
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ]);
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
            $response = $this->callOpenAI($this->getDefaultModel(), [['role' => 'user', 'content' => 'hi']], ['max_tokens' => 5]);
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
        ];
        
        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }
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
        
        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
        }
        
        if (isset($response['usage'])) {
            $usage = [
                'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $response['usage']['total_tokens'] ?? 0,
            ];
        }
        
        return [
            'content' => $content,
            'usage' => $usage,
        ];
    }

    protected function callOpenAI(string $model, array $messages, array $options = []): array
    {
        $url = "{$this->baseUrl}/chat/completions";
        
        $payload = $this->formatRequest($messages, array_merge($options, ['model' => $model]));
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => $options['timeout'] ?? 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new \RuntimeException("OpenAI API error: HTTP {$httpCode} - {$response}");
        }
        
        return json_decode($response, true) ?: [];
    }
}