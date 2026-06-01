<?php

namespace App\Services\AiModelsHub;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DynamicRestProvider implements AiProviderInterface
{
    protected string $providerId;
    protected EncryptedApiKeyStorage $keyStorage;
    protected ?object $providerRecord = null;

    public function __construct(string $providerId, EncryptedApiKeyStorage $keyStorage)
    {
        $this->providerId = $providerId;
        $this->keyStorage = $keyStorage;
        $this->providerRecord = DB::table('ai_providers')->where('id', $providerId)->first();
    }

    protected function getProviderRecord(): ?object
    {
        return $this->providerRecord;
    }

    protected function getApiKey(): ?string
    {
        return $this->keyStorage->getDecryptedKey($this->providerId);
    }

    protected function buildHeaders(): array
    {
        $apiKey = $this->getApiKey();
        $record = $this->getProviderRecord();
        
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($apiKey && $record && $record->auth_header_format) {
            // Replace {KEY} or {API_KEY} in the format string
            $authValue = str_replace(['{KEY}', '{API_KEY}'], $apiKey, $record->auth_header_format);
            $headers['Authorization'] = $authValue;
        } elseif ($apiKey) {
            // Default to Bearer if format isn't specified
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }

        return $headers;
    }

    public function getProviderName(): string
    {
        return $this->getProviderRecord() ? $this->getProviderRecord()->name : 'Unknown Dynamic Provider';
    }

    public function getAvailableModels(): array
    {
        $record = $this->getProviderRecord();
        if (!$record || !$record->models_fetch_endpoint) {
            return [];
        }

        try {
            $url = rtrim($record->base_url, '/') . '/' . ltrim($record->models_fetch_endpoint, '/');
            $response = Http::withHeaders($this->buildHeaders())->timeout(15)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return $this->normalizeModelsResponse($data);
            }

            Log::warning("Model fetch returned HTTP {$response->status()} for provider {$this->providerId}");
        } catch (\Exception $e) {
            Log::error('Failed to fetch dynamic models: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Normalize different provider API response formats into a consistent array.
     * Handles: OpenAI { data: [] }, Anthropic { models: [] }, direct arrays []
     */
    protected function normalizeModelsResponse(mixed $data): array
    {
        $rawList = [];

        // OpenAI format: { "object": "list", "data": [ { "id": "gpt-4", ... } ] }
        if (isset($data['data']) && is_array($data['data'])) {
            $rawList = $data['data'];
        }
        // Anthropic format: { "models": [ { "id": "claude-3", "display_name": "..." } ] }
        elseif (isset($data['models']) && is_array($data['models'])) {
            $rawList = $data['models'];
        }
        // Ollama / direct array: [ { "name": "llama3", ... } ]
        elseif (is_array($data) && !empty($data) && isset($data[0])) {
            $rawList = $data;
        }

        return array_values(array_filter(array_map(function ($model) {
            // Prefer explicit name fields over id
            $id   = $model['id'] ?? $model['name'] ?? null;
            $name = $model['display_name'] ?? $model['name'] ?? $model['id'] ?? null;

            if (!$id && !$name) {
                return null; // skip malformed entries
            }

            return [
                'id'          => $id ?? $name,
                'name'        => $name ?? $id,
                'description' => $model['description'] ?? null,
            ];
        }, $rawList)));
    }

    public function getDefaultModel(): string
    {
        $models = $this->getAvailableModels();
        return !empty($models) ? $models[0]['id'] : '';
    }

    public function generateText(string $prompt, array $options = []): array
    {
        $record = $this->getProviderRecord();
        if (!$record || !$record->generate_endpoint) {
            return ['success' => false, 'error' => 'No generation endpoint configured'];
        }

        $url = rtrim($record->base_url, '/') . '/' . ltrim($record->generate_endpoint, '/');
        
        $payload = [
            'model' => $options['model'] ?? $this->getDefaultModel(),
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $options['temperature'] ?? 0.7,
        ];

        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }

        try {
            $response = Http::withHeaders($this->buildHeaders())->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                $usage = $data['usage'] ?? ['input_tokens' => 0, 'output_tokens' => 0];

                return [
                    'success' => true,
                    'provider' => $this->getProviderName(),
                    'model' => $payload['model'],
                    'content' => $content,
                    'usage' => [
                        'input_tokens' => $usage['prompt_tokens'] ?? 0,
                        'output_tokens' => $usage['completion_tokens'] ?? 0,
                    ]
                ];
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Dynamic generation failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function generateEmbeddings(string $text, array $options = []): array
    {
        $record = $this->getProviderRecord();
        if (!$record) {
            return ['success' => false, 'error' => 'Provider record not found'];
        }

        // Default to OpenAI compatible embeddings endpoint
        $url = rtrim($record->base_url, '/') . '/v1/embeddings';
        
        $payload = [
            'model' => $options['model'] ?? 'text-embedding-3-small',
            'input' => $text,
        ];

        try {
            $response = Http::withHeaders($this->buildHeaders())->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $embedding = $data['data'][0]['embedding'] ?? null;
                
                if ($embedding) {
                    return [
                        'success' => true,
                        'provider' => $this->getProviderName(),
                        'model' => $payload['model'],
                        'vector' => $embedding,
                        'usage' => [
                            'input_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                        ]
                    ];
                }
                return ['success' => false, 'error' => 'Malformed response from provider'];
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Dynamic embeddings failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function validateRequest(array $request): array
    {
        return ['valid' => true];
    }

    public function estimateCost(string $model, int $inputTokens, int $outputTokens = 0): float
    {
        return 0.0;
    }

    public function getHealthStatus(): array
    {
        $record = $this->getProviderRecord();
        if (!$record || !$record->test_endpoint) {
            return ['status' => 'unknown'];
        }

        $url = rtrim($record->base_url, '/') . '/' . ltrim($record->test_endpoint, '/');
        
        try {
            $response = Http::withHeaders($this->buildHeaders())->get($url);
            return [
                'status' => $response->successful() ? 'healthy' : 'unhealthy',
                'latency' => 100 // Can be measured dynamically
            ];
        } catch (\Exception $e) {
            return ['status' => 'offline', 'error' => $e->getMessage()];
        }
    }

    public function getRateLimitStatus(): array
    {
        return ['limit' => -1, 'remaining' => -1, 'reset' => -1];
    }
}
