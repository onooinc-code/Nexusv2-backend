<?php

namespace App\Services\AiModelsHub;

use Illuminate\Support\Facades\Log;
use App\Models\AIModel;

class UsageCalculator
{
    /**
     * Calculate cost based on token usage and model pricing
     */
    public static function calculateCost(string $modelId, int $inputTokens, int $outputTokens = 0): float
    {
        $aiModel = AIModel::find($modelId);
        
        if (!$aiModel) {
            Log::warning("Model not found for ID: {$modelId}");
            return 0.0;
        }

        $inputCost = ($inputTokens / 1000) * ($aiModel->input_cost_per_m ?? 0);
        $outputCost = ($outputTokens / 1000) * ($aiModel->output_cost_per_m ?? 0);

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Calculate total tokens used
     */
    public static function calculateTotalTokens(int $inputTokens, int $outputTokens = 0): int
    {
        return $inputTokens + $outputTokens;
    }

    /**
     * Get cost per token for input and output
     */
    public static function getCostPerToken(string $modelId): array
    {
        $aiModel = AIModel::find($modelId);
        
        if (!$aiModel) {
            return [
                'input' => 0.0,
                'output' => 0.0,
            ];
        }

        return [
            'input' => ($aiModel->input_cost_per_m ?? 0) / 1000,
            'output' => ($aiModel->output_cost_per_m ?? 0) / 1000,
        ];
    }

    /**
     * Format usage data for storage or display
     */
    public static function formatUsage(array $usage, string $modelId): array
    {
        $inputTokens = $usage['input_tokens'] ?? $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['output_tokens'] ?? $usage['completion_tokens'] ?? 0;
        $totalTokens = $usage['total_tokens'] ?? self::calculateTotalTokens($inputTokens, $outputTokens);
        
        $cost = self::calculateCost($modelId, $inputTokens, $outputTokens);
        $costPerToken = self::getCostPerToken($modelId);

        return [
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $totalTokens,
            'cost' => $cost,
            'cost_per_token' => $costPerToken,
            'model_id' => $modelId,
        ];
    }
}