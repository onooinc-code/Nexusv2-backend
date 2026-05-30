<?php

namespace App\Services\AiModelsHub;

use Illuminate\Support\Facades\Log;
use App\Models\AIProvider;
use App\Models\AIModel;
use App\Models\UsageLog;

class UsageTracker
{
    /**
     * Track usage for cost calculation
     */
    public function trackUsage($providerId, $modelId, $inputTokens, $outputTokens, $workspaceId = null)
    {
        try {
            // Get provider and model for cost calculation
            $provider = AIProvider::find($providerId);
            $model = AIModel::find($modelId);

            if (!$provider || !$model) {
                Log::warning("Provider or model not found for usage tracking");
                return;
            }

            // Calculate costs (defaulting to 0 if cost fields are null)
            $inputCostRate = $model->input_cost_per_m ?? 0;
            $outputCostRate = $model->output_cost_per_m ?? 0;
            
            $inputCost = ($inputTokens / 1000000) * $inputCostRate;
            $outputCost = ($outputTokens / 1000000) * $outputCostRate;
            $totalCost = $inputCost + $outputCost;

            // Create usage log entry
            $usageLogData = [
                'provider_id' => $providerId,
                'model_id' => $modelId,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'input_cost' => $inputCost,
                'output_cost' => $outputCost,
                'total_cost' => $totalCost,
                'timestamp' => now(),
            ];
            
            // Assuming workspace_id might be added to UsageLog
            // $usageLogData['workspace_id'] = $workspaceId;
            
            UsageLog::create($usageLogData);

            // Update budget
            if ($workspaceId && $totalCost > 0) {
                \Illuminate\Support\Facades\DB::table('cost_budgets')
                    ->where('workspace_id', $workspaceId)
                    ->where('is_active', true)
                    ->increment('current_spend', $totalCost);
            }
            
            // Update global budget
            \Illuminate\Support\Facades\DB::table('cost_budgets')
                ->whereNull('workspace_id')
                ->where('is_active', true)
                ->increment('current_spend', $totalCost);

        } catch (\Exception $e) {
            Log::error("Error tracking usage: {$e->getMessage()}");
            // Don't throw exception - usage tracking shouldn't break the main flow
        }
    }

    /**
     * Check if workspace has budget available for an estimated cost
     */
    public function checkBudget($workspaceId = null, $estimatedCost = 0)
    {
        $budget = \Illuminate\Support\Facades\DB::table('cost_budgets')
            ->where('workspace_id', $workspaceId)
            ->where('is_active', true)
            ->first();

        if ($budget && ($budget->current_spend + $estimatedCost) >= $budget->monthly_limit) {
            return false;
        }

        // Also check global budget
        $globalBudget = \Illuminate\Support\Facades\DB::table('cost_budgets')
            ->whereNull('workspace_id')
            ->where('is_active', true)
            ->first();

        if ($globalBudget && ($globalBudget->current_spend + $estimatedCost) >= $globalBudget->monthly_limit) {
            return false;
        }

        return true;
    }

    /**
     * Get usage statistics for a provider
     */
    public function getProviderUsage($providerId, $startDate = null, $endDate = null)
    {
        $query = UsageLog::where('provider_id', $providerId);

        if ($startDate) {
            $query->where('timestamp', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('timestamp', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Get usage statistics for a model
     */
    public function getModelUsage($modelId, $startDate = null, $endDate = null)
    {
        $query = UsageLog::where('model_id', $modelId);

        if ($startDate) {
            $query->where('timestamp', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('timestamp', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Get total cost for a provider
     */
    public function getProviderTotalCost($providerId, $startDate = null, $endDate = null)
    {
        $query = UsageLog::where('provider_id', $providerId);

        if ($startDate) {
            $query->where('timestamp', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('timestamp', '<=', $endDate);
        }

        return $query->sum('total_cost');
    }

    /**
     * Get total cost for a model
     */
    public function getModelTotalCost($modelId, $startDate = null, $endDate = null)
    {
        $query = UsageLog::where('model_id', $modelId);

        if ($startDate) {
            $query->where('timestamp', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('timestamp', '<=', $endDate);
        }

        return $query->sum('total_cost');
    }
}