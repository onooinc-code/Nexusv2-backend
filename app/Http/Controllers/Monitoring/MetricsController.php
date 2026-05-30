<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class MetricsController extends Controller
{
    public function metrics()
    {
        $queues = $this->queueMetrics();
        $failedJobs = DB::table('failed_jobs')->count();

        return response()->json([
            'timestamp' => now()->toDateTimeString(),
            'queues' => $queues,
            'failed_jobs' => $failedJobs,
            'throughput' => null,
            'websocket' => $this->websocketMetrics(),
        ]);
    }

    public function websocket()
    {
        return response()->json($this->websocketMetrics());
    }

    protected function queueMetrics(): array
    {
        try {
            $redis = Redis::connection();
            return [
                'default' => $redis->llen('queues:default'),
                'critical' => $redis->llen('queues:critical'),
                'llm-inference' => $redis->llen('queues:llm-inference'),
                'batch' => $redis->llen('queues:batch'),
            ];
        } catch (\Throwable $exception) {
            return [
                'error' => $exception->getMessage()];
        }
    }

    protected function websocketMetrics(): array
    {
        $host = config('broadcasting.connections.reverb.host', env('REVERB_HOST', '127.0.0.1'));
        $port = config('broadcasting.connections.reverb.port', env('REVERB_PORT', 6001));
        $scheme = config('broadcasting.connections.reverb.scheme', env('REVERB_SCHEME', 'https'));
        $url = sprintf('%s://%s:%s/metrics', $scheme, $host, $port);

        try {
            $response = Http::timeout(2)->get($url);
            if ($response->successful()) {
                return array_merge(['source' => 'reverb'], $response->json());
            }
        } catch (\Throwable $exception) {
            return [
                'source' => 'reverb',
                'ok' => false,
                'error' => $exception->getMessage(),
            ];
        }

        return [
            'source' => 'reverb',
            'ok' => false,
        ];
    }
}
