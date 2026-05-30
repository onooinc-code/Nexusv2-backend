<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AiModelsHub\UsageTracker;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AiCostAnalyticsController extends Controller
{
    protected $usageTracker;

    public function __construct(UsageTracker $usageTracker)
    {
        $this->usageTracker = $usageTracker;
    }

    /**
     * Get cost forecasting and analytics
     */
    public function forecast(Request $request)
    {
        $workspaceId = $request->user() ? $request->user()->workspace_id : null;
        
        $budgetQuery = DB::table('cost_budgets')->where('is_active', true);
        if ($workspaceId) {
            $budgetQuery->where('workspace_id', $workspaceId);
        } else {
            $budgetQuery->whereNull('workspace_id');
        }
        
        $budget = $budgetQuery->first();
        
        // Calculate estimated usage for the rest of the month based on current spend
        $currentSpend = $budget ? $budget->current_spend : 0;
        $monthlyLimit = $budget ? $budget->monthly_limit : 0;
        
        $daysInMonth = Carbon::now()->daysInMonth;
        $currentDay = Carbon::now()->day;
        
        $dailyAverage = $currentDay > 0 ? ($currentSpend / $currentDay) : 0;
        $forecastedTotal = $dailyAverage * $daysInMonth;
        $remainingBudget = max(0, $monthlyLimit - $currentSpend);
        
        $status = 'healthy';
        if ($monthlyLimit > 0) {
            if ($forecastedTotal > $monthlyLimit) {
                $status = 'over_budget_predicted';
            }
            if ($currentSpend >= $monthlyLimit) {
                $status = 'budget_exceeded';
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'current_spend' => $currentSpend,
                'monthly_limit' => $monthlyLimit,
                'remaining_budget' => $remainingBudget,
                'forecasted_total' => $forecastedTotal,
                'daily_average' => $dailyAverage,
                'status' => $status
            ]
        ]);
    }

    /**
     * Set a new budget limit
     */
    public function setBudget(Request $request)
    {
        $request->validate([
            'monthly_limit' => 'required|numeric|min:0',
            'workspace_id' => 'nullable|uuid'
        ]);
        
        $workspaceId = $request->workspace_id ?? ($request->user() ? $request->user()->workspace_id : null);
        
        $exists = DB::table('cost_budgets')->where('workspace_id', $workspaceId)->exists();
        if ($exists) {
            DB::table('cost_budgets')->where('workspace_id', $workspaceId)->update([
                'monthly_limit' => $request->monthly_limit,
                'is_active' => true,
                'updated_at' => Carbon::now()
            ]);
        } else {
            DB::table('cost_budgets')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'workspace_id' => $workspaceId,
                'monthly_limit' => $request->monthly_limit,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Budget limit updated successfully',
        ]);
    }
}
