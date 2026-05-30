<?php

namespace App\Services\AiModelsHub;

interface AiProviderInterface
{
    /**
     * Get the provider name
     */
    public function getProviderName(): string;

    /**
     * Get available models for this provider
     */
    public function getAvailableModels(): array;

    /**
     * Get the default model for this provider
     */
    public function getDefaultModel(): string;

    /**
     * Generate text using the provider
     */
    public function generateText(string $prompt, array $options = []): array;

    /**
     * Generate embeddings using the provider
     */
    public function generateEmbeddings(string $text, array $options = []): array;

    /**
     * Validate a request before sending to provider
     */
    public function validateRequest(array $request): array;

    /**
     * Estimate cost for a request
     */
    public function estimateCost(string $model, int $inputTokens, int $outputTokens = 0): float;

    /**
     * Get provider health status
     */
    public function getHealthStatus(): array;

    /**
     * Get rate limit status
     */
    public function getRateLimitStatus(): array;
}