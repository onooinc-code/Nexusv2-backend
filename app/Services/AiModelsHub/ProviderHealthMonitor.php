<?php

namespace App\Services\AiModelsHub;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\AIProvider;
use Carbon\Carbon;

class ProviderHealthMonitor
{
    /**
     * Poll all active providers for their health metrics
     */
    public function pollAllProviders()
    {
        $providers = AIProvider::where('is_active', true)->get();

        foreach ($providers as $provider) {
            $this->pollProvider($provider);
        }
    }

    /**
     * Poll a specific provider and save its metrics
     */
    public function pollProvider(AIProvider $provider)
    {
        $startTime = microtime(true);
        $status = 'offline';
        $rateLimitLimit = null;
        $rateLimitRemaining = null;
        $rateLimitReset = null;

        try {
            $response = Http::timeout(10)->get($provider->base_url . '/' . ltrim($provider->models_fetch_endpoint, '/'));
            
            $latencyMs = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $status = $latencyMs > 2000 ? 'degraded' : 'healthy';

                // Attempt to parse standard rate limit headers
                $rateLimitLimit = $response->header('X-RateLimit-Limit') ?? $response->header('RateLimit-Limit');
                $rateLimitRemaining = $response->header('X-RateLimit-Remaining') ?? $response->header('RateLimit-Remaining');
                $rateLimitReset = $response->header('X-RateLimit-Reset') ?? $response->header('RateLimit-Reset');
            } elseif ($response->status() === 429) {
                $status = 'degraded'; // or 'rate_limited'
            }

        } catch (\Exception $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000);
            Log::warning("Health check failed for provider {$provider->name}: " . $e->getMessage());
        }

        // Save to provider_health_metrics table
        DB::table('provider_health_metrics')->insert([
            'provider_id' => $provider->id,
            'status' => $status,
            'latency_ms' => $latencyMs,
            'rate_limit_limit' => $rateLimitLimit,
            'rate_limit_remaining' => $rateLimitRemaining,
            'rate_limit_reset' => $rateLimitReset,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return [
            'status' => $status,
            'latency_ms' => $latencyMs
        ];
    }

    /**
     * Get a health scorecard for routing engine
     */
    public function getScorecard()
    {
        // Simple scorecard: Average latency over the last hour, and current status
        return DB::table('provider_health_metrics')
            ->select('provider_id', 'status', DB::raw('AVG(latency_ms) as avg_latency'))
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->groupBy('provider_id', 'status')
            ->get()
            ->keyBy('provider_id')
            ->toArray();
    }
}
