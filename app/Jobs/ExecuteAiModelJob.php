<?php

namespace App\Jobs;

use App\Events\AiModelExecutionCompleted;
use App\Models\ApiKey;
use App\Services\AI\GoogleGeminiProvider;
use App\Services\AI\OpenAIProvider;
use App\Services\AI\AnthropicProvider;
use App\Services\AI\GroqProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class ExecuteAiModelJob extends BaseJob
{
    public $queue = 'llm-inference';
    public int $timeout = 600;
    public int $tries = 3;

    public function __construct(
        protected int $userId,
        protected string $executionId,
        protected string $provider,
        protected string $model,
        protected ?string $prompt = null,
        protected ?array $messages = null,
        protected array $options = []
    ) {
        $this->idempotencyKey = "execute_ai_model:{$this->userId}:{$this->executionId}";
    }

    public function handle(): void
    {
        $this->logJobStart([
            'execution_id' => $this->executionId,
            'user_id' => $this->userId,
            'provider' => $this->provider,
            'model' => $this->model,
        ]);

        try {
            if ($this->isProcessed()) {
                $this->logJobComplete([
                    'reason' => 'idempotent_skip',
                    'execution_id' => $this->executionId,
                ]);
                return;
            }

            $apiKey = $this->getApiKeyForProvider($this->provider);
            if (!$apiKey) {
                throw new Exception("No active API key for provider: {$this->provider}");
            }

            $provider = $this->resolveProvider($this->provider, $apiKey);
            $request = [
                'model' => $this->model,
                'prompt' => $this->prompt,
                'messages' => $this->messages,
                'options' => $this->options,
            ];

            $startTime = microtime(true);
            $result = $provider->execute($request);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            if (!($result['success'] ?? false)) {
                throw new Exception($result['error'] ?? 'AI execution failed');
            }

            $payload = [
                'execution_id' => $this->executionId,
                'provider' => $this->provider,
                'model' => $this->model,
                'result' => $result,
                'duration_ms' => $durationMs,
                'completed_at' => now()->toDateTimeString(),
            ];

            event(new AiModelExecutionCompleted(
                $this->userId,
                $this->executionId,
                $payload
            ));

            $this->markAsProcessed($payload);

            $this->logJobComplete([
                'execution_id' => $this->executionId,
                'duration_ms' => $durationMs,
            ]);
        } catch (Exception $e) {
            $this->logJobFailure($e, [
                'execution_id' => $this->executionId,
                'user_id' => $this->userId,
            ]);
            throw $e;
        }
    }

    protected function getApiKeyForProvider(string $provider): ?string
    {
        return ApiKey::where('provider', $provider)
            ->where('type', 'ai_provider')
            ->where('is_active', true)
            ->first()?->key;
    }

    protected function resolveProvider(string $provider, string $apiKey): object
    {
        return match ($provider) {
            'google_gemini' => new GoogleGeminiProvider($apiKey),
            'openai' => new OpenAIProvider($apiKey),
            'anthropic' => new AnthropicProvider($apiKey),
            'groq' => new GroqProvider($apiKey),
            default => throw new Exception("Unknown provider: {$provider}"),
        };
    }

    protected function extractIdempotencyData(): array
    {
        return [
            'user_id' => $this->userId,
            'execution_id' => $this->executionId,
        ];
    }
}
