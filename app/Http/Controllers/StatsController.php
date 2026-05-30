<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AIProvider;
use App\Models\Agent;
use App\Models\AgentTask;
use App\Models\Contact;
use App\Models\UsageLog;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class StatsController extends Controller
{
    public function usage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'range' => ['required', 'string', 'in:today,7d,30d,custom'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $range = $request->input('range', '7d');
        $end = Carbon::now()->endOfDay();

        switch ($range) {
            case 'today':
                $start = Carbon::now()->startOfDay();
                break;
            case '7d':
                $start = Carbon::now()->subDays(6)->startOfDay();
                break;
            case '30d':
                $start = Carbon::now()->subDays(29)->startOfDay();
                break;
            case 'custom':
                if (! $request->filled('from') || ! $request->filled('to')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Both from and to dates are required for custom ranges.',
                    ], 422);
                }

                $start = Carbon::parse($request->input('from'))->startOfDay();
                $end = Carbon::parse($request->input('to'))->endOfDay();

                if ($end->lt($start)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The end date must be the same as or after the start date.',
                    ], 422);
                }
                break;
            default:
                $start = Carbon::now()->subDays(6)->startOfDay();
                break;
        }

        $usageLogs = UsageLog::with('provider')
            ->whereBetween('timestamp', [$start, $end])
            ->orderBy('timestamp')
            ->get();

        $dateCursor = $start->copy();
        $tokenUsage = [];
        $costEstimate = [];

        while ($dateCursor->lte($end)) {
            $key = $dateCursor->format('Y-m-d');
            $tokenUsage[$key] = 0;
            $costEstimate[$key] = 0.0;
            $dateCursor->addDay();
        }

        $providerCalls = [];
        $topIntents = [];

        foreach ($usageLogs as $entry) {
            $day = $entry->timestamp->format('Y-m-d');
            $tokenUsage[$day] += ($entry->input_tokens + $entry->output_tokens);
            $costEstimate[$day] += (float) $entry->total_cost;

            $providerName = $entry->provider?->name ?? 'Unknown';
            $providerCalls[$providerName] = ($providerCalls[$providerName] ?? 0) + 1;

            $intent = trim($entry->intent_name ?: 'Unknown');
            $topIntents[$intent] = ($topIntents[$intent] ?? 0) + 1;
        }

        $formattedProviderCalls = collect($providerCalls)
            ->map(fn ($count, $provider) => ['provider' => $provider, 'count' => $count])
            ->sortByDesc('count')
            ->values()
            ->all();

        $formattedTopIntents = collect($topIntents)
            ->map(fn ($count, $intent) => ['intent' => $intent, 'count' => $count])
            ->sortByDesc('count')
            ->values()
            ->take(10)
            ->all();

        return response()->json([
            'success' => true,
            'data' => [
                'token_usage' => collect($tokenUsage)->map(fn ($value, $date) => ['date' => $date, 'value' => $value])->values()->all(),
                'provider_calls' => $formattedProviderCalls,
                'cost_estimate' => collect($costEstimate)->map(fn ($value, $date) => ['date' => $date, 'amount' => round($value, 4)])->values()->all(),
                'top_intents' => $formattedTopIntents,
            ],
        ]);
    }

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'contacts' => Contact::count(),
                'agents' => Agent::count(),
                'workflows' => Workflow::count(),
                'tasks' => AgentTask::count(),
            ],
        ]);
    }
}
