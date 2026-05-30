<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\AIModel;
use App\Models\ApiKey;
use App\Services\AI\RateLimitService;
use App\Services\AI\GoogleGeminiProvider;
use App\Services\AI\OpenAIProvider;
use App\Services\AI\AnthropicProvider;
use App\Services\AI\GroqProvider;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Process AI Inference Job - Handles LLM API calls with token streaming
 *
 * This job dispatches AI inference requests to the appropriate provider,
 * streams tokens via broadcast events, and stores the complete response.
 *
 * Queue: llm-inference (long timeout for LLM API calls)
 * Timeout: 600 seconds (10 minutes)
 * Retries: 3 attempts with exponential backoff
 */
class ProcessAiInferenceJob extends BaseJob
{
    /**
     * Queue assignment.
     *
     * @var string
     */
    public $queue = 'llm-inference';

    /**
     * Job timeout (10 minutes for LLM calls).
     *
     * @var int
     */
    public int $timeout = 600;

    /**
     * Number of retry attempts.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Constructor.
     *
     * @param string $conversationId Conversation UUID
     * @param string $messageId Message UUID (the AI response message)
     * @param string $prompt User's prompt/question
     * @param string|null $modelId AI Model UUID
     * @param string|null $providerId Provider ID (google_gemini, openai, etc.)
     */
    public function __construct(
        protected string $conversationId,
        protected string $messageId,
        protected string $prompt,
        protected ?string $modelId,
        protected ?string $providerId,
    ) {
        $this->idempotencyKey = "llm_inference:{$conversationId}:{$messageId}";
    }

    /**
     * Execute the job - Perform AI inference.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $this->logJobStart([
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'provider' => $this->providerId,
            'model' => $this->modelId,
        ]);

        try {
            // Check idempotency - prevent duplicate inference
            if ($this->isProcessed()) {
                $this->logJobComplete([
                    'reason' => 'idempotent_skip',
                    'conversation_id' => $this->conversationId,
                ]);
                return;
            }

            // Fetch models (safely handling deletion)
            $conversation = $this->safelyGetModel(Conversation::class, $this->conversationId);
            if (!$conversation) {
                throw new Exception("Conversation not found: {$this->conversationId}");
            }

            $responseMessage = $this->safelyGetModel(Message::class, $this->messageId);
            if (!$responseMessage) {
                throw new Exception("Response message not found: {$this->messageId}");
            }

            $aiModel = $this->safelyGetModel(AIModel::class, $this->modelId);
            if (!$aiModel) {
                throw new Exception("AI model not found: {$this->modelId}");
            }

            // Get API key for provider
            $apiKey = $this->getApiKeyForProvider($this->providerId);
            if (!$apiKey) {
                throw new Exception("No active API key for provider: {$this->providerId}");
            }

            // Check rate limits
            $rateLimitService = app(RateLimitService::class);
            $rateLimitStatus = $rateLimitService->check($this->providerId, $apiKey);
            if (!$rateLimitStatus['allowed']) {
                // Rate limited - release job with exponential backoff
                $this->handleRateLimit($this->getCurrentAttempt(), 5);
            }

            // Get provider instance
            $provider = $this->getProviderInstance($this->providerId, $apiKey);

            // Build request for provider
            $execRequest = [
                'model' => $aiModel->name,
                'prompt' => $this->prompt,
                'messages' => null,
                'options' => [
                    'stream' => false, // We'll handle streaming via events
                    'temperature' => 0.7,
                    'max_tokens' => 2000,
                ],
            ];

            // Execute inference
            $startTime = microtime(true);
            $result = $provider->execute($execRequest);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            if (!$result['success']) {
                throw new Exception("LLM inference failed: " . ($result['error'] ?? 'Unknown error'));
            }

            // Extract response content
            $responseContent = $result['output'] ?? $result['choices'][0]['message']['content'] ?? '';

            // Broadcast tokens one at a time for real-time streaming UI
            $tokens = preg_split('/(\s+)/', $responseContent, -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach ($tokens as $token) {
                if (trim($token) === '') {
                    continue;
                }

                // Broadcast token stream event
                event(new \App\Events\TokenStreamed(
                    $this->conversationId,
                    $this->messageId,
                    $token . ' '
                ));

                // Small delay to simulate streaming (optional - remove for real-time)
                usleep(10000); // 10ms
            }

            // Update message with response content
            $responseMessage->update([
                'content' => $responseContent,
                'status' => 'completed',
                'metadata' => array_merge(
                    $responseMessage->metadata ?? [],
                    [
                        'provider' => $this->providerId,
                        'model' => $aiModel->name,
                        'duration_ms' => $durationMs,
                        'tokens_used' => count($tokens),
                        'inference_at' => now()->toDateTimeString(),
                    ]
                ),
            ]);

            // Record success in rate limiter
            $rateLimitService->recordSuccess($this->providerId, $apiKey);

            // Broadcast message completed event
            event(new \App\Events\MessageCompleted(
                $this->conversationId,
                $this->messageId,
                $responseContent
            ));

            // Mark as processed for idempotency
            $this->markAsProcessed([
                'content_length' => strlen($responseContent),
                'duration_ms' => $durationMs,
                'completed_at' => now()->toDateTimeString(),
            ]);

            $this->logJobComplete([
                'conversation_id' => $this->conversationId,
                'response_length' => strlen($responseContent),
                'duration_ms' => $durationMs,
            ]);

        } catch (Exception $e) {
            $this->logJobFailure($e, [
                'conversation_id' => $this->conversationId,
                'attempt' => $this->getCurrentAttempt(),
            ]);

            // Update message status to failed
            try {
                $responseMessage = $this->safelyGetModel(Message::class, $this->messageId);
                if ($responseMessage) {
                    $responseMessage->update([
                        'status' => 'failed',
                        'metadata' => array_merge(
                            $responseMessage->metadata ?? [],
                            ['error' => $e->getMessage()]
                        ),
                    ]);
                }
            } catch (Exception $updateEx) {
                \Log::error("Failed to update message status", [
                    'message_id' => $this->messageId,
                    'exception' => $updateEx->getMessage(),
                ]);
            }

            // Re-throw exception to trigger job failure
            throw $e;
        }
    }

    /**
     * Get active API key for provider.
     *
     * @param string $provider Provider ID
     * @return string|null API key or null
     */
    protected function getApiKeyForProvider(string $provider): ?string
    {
        return ApiKey::where('provider', $provider)
            ->where('type', 'ai_provider')
            ->where('is_active', true)
            ->first()?->key;
    }

    /**
     * Get provider instance based on provider ID.
     *
     * @param string $provider Provider ID
     * @param string $apiKey API key for provider
     * @return object Provider instance
     * @throws Exception
     */
    protected function getProviderInstance(string $provider, string $apiKey): object
    {
        return match ($provider) {
            'google_gemini' => new GoogleGeminiProvider($apiKey),
            'openai' => new OpenAIProvider($apiKey),
            'anthropic' => new AnthropicProvider($apiKey),
            'groq' => new GroqProvider($apiKey),
            default => throw new Exception("Unknown provider: {$provider}"),
        };
    }

    /**
     * Extract idempotency data from job properties.
     *
     * @return array
     */
    protected function extractIdempotencyData(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'provider' => $this->providerId,
            'model_id' => $this->modelId,
        ];
    }
}
