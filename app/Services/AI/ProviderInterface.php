<?php

namespace App\Services\AI;

interface ProviderInterface
{
    public function getProviderName(): string;
    public function getAvailableModels(): array;
    public function getDefaultModel(): string;
    public function execute(array $request): array;
    public function validateRequest(array $request): array;
    public function getRateLimitStatus(): array;
    public function getHealthStatus(): array;
    public function formatRequest(array $prompt, array $options = []): array;
    public function parseResponse(array $response): array;
    public function estimateCost(string $model, int $inputTokens, int $outputTokens = 0): float;
}
