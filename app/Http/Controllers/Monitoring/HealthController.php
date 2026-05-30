<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class HealthController extends Controller
{
    public function health(Request $request)
    {
        $redis = $this->checkRedis();
        $database = $this->checkDatabase();
        $reverb = $this->checkReverb();
        $queue = $this->checkQueue();
        $pinecone = $this->checkPinecone();
        $neo4j = $this->checkNeo4j();
        $waha = $this->checkWaha();
        $aiProviders = $this->checkAiProviders();

        $allChecks = [$redis['ok'], $database['ok'], $reverb['ok'], $queue['ok'], $pinecone['ok'], $neo4j['ok'], $waha['ok']];

        $criticalChecks = [$redis['ok'], $database['ok']];
        if (!collect($criticalChecks)->every(fn ($ok) => $ok)) {
            $status = 'critical';
        } elseif (collect($allChecks)->every(fn ($ok) => $ok)) {
            $status = 'healthy';
        } else {
            $status = 'degraded';
        }

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toDateTimeString(),
            'checks' => [
                'redis' => $redis,
                'database' => $database,
                'reverb' => $reverb,
                'queue' => $queue,
                'pinecone' => $pinecone,
                'neo4j' => $neo4j,
                'waha' => $waha,
                'ai_providers' => $aiProviders,
            ],
        ]);
    }

    public function reverb()
    {
        return response()->json($this->checkReverb());
    }

    public function queue()
    {
        return response()->json($this->checkQueue());
    }

    protected function checkRedis(): array
    {
        try {
            // Since we're using REDIS_CLIENT=none, Redis is disabled
            // This check is kept for reference but will always fail gracefully
            return ['ok' => false, 'driver' => 'redis', 'note' => 'Redis disabled in configuration'];
        } catch (\Throwable $exception) {
            Log::warning('Redis health check failed', ['exception' => $exception->getMessage()]);
            return ['ok' => false, 'error' => $exception->getMessage()];
        }
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['ok' => true, 'driver' => DB::getDefaultConnection()];
        } catch (\Throwable $exception) {
            Log::warning('Database health check failed', ['exception' => $exception->getMessage()]);
            return ['ok' => false, 'error' => $exception->getMessage()];
        }
    }

    protected function checkReverb(): array
    {
        $host = config('broadcasting.connections.reverb.host', env('REVERB_HOST', '127.0.0.1'));
        $port = config('broadcasting.connections.reverb.port', env('REVERB_PORT', 6001));
        $scheme = config('broadcasting.connections.reverb.scheme', env('REVERB_SCHEME', 'https'));

        try {
            // Test TCP connection to Reverb server (WebSocket servers don't expose HTTP /health)
            $sock = @fsockopen($host, $port, $errno, $errstr, 3);

            if ($sock) {
                fclose($sock);
                return [
                    'ok' => true,
                    'host' => $host,
                    'port' => $port,
                    'status' => 'listening',
                ];
            }

            return [
                'ok' => false,
                'host' => $host,
                'port' => $port,
                'error' => $errstr,
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'host' => $host,
                'port' => $port,
                'error' => $exception->getMessage(),
            ];
        }
    }

    protected function checkQueue(): array
    {
        try {
            // Since we're using QUEUE_CONNECTION=sync, Redis queues are not critical
            $failedJobs = DB::table('failed_jobs')->count();
            return [
                'ok' => true,
                'driver' => config('queue.default', 'sync'),
                'failed_jobs' => $failedJobs,
            ];
        } catch (\Throwable $exception) {
            Log::warning('Queue health check failed', ['exception' => $exception->getMessage()]);
            return ['ok' => false, 'error' => $exception->getMessage()];
        }
    }

    protected function checkPinecone(): array
    {
        try {
            $apiKey = config('services.pinecone.api_key', env('PINECONE_API_KEY'));
            if (!$apiKey) {
                return ['ok' => false, 'error' => 'Pinecone API key not configured'];
            }

            $response = Http::timeout(2)->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->get('https://api.pinecone.io/indexes');

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'indexes' => $response->successful() ? count($response->json()) : 0,
            ];
        } catch (\Throwable $exception) {
            Log::warning('Pinecone health check failed', ['exception' => $exception->getMessage()]);
            return ['ok' => false, 'error' => $exception->getMessage()];
        }
    }

    protected function checkNeo4j(): array
    {
        try {
            $host = config('database.connections.neo4j.host', env('NEO4J_HOST', 'localhost'));
            $port = config('database.connections.neo4j.port', env('NEO4J_PORT', 7687));
            $username = config('database.connections.neo4j.user', env('NEO4J_USER', 'neo4j'));
            $password = config('database.connections.neo4j.password', env('NEO4J_PASSWORD'));

            if (!$password) {
                return ['ok' => false, 'error' => 'Neo4j credentials not configured'];
            }

            // Test connection via HTTP endpoint with timeout
            $response = Http::timeout(2)->withBasicAuth($username, $password)
                ->get("http://{$host}:7474/db/neo4j/tx");

            return [
                'ok' => in_array($response->status(), [200, 201]),
                'host' => $host,
                'port' => $port,
                'status' => $response->status(),
            ];
        } catch (\Throwable $exception) {
            Log::warning('Neo4j health check failed', ['exception' => $exception->getMessage()]);
            return ['ok' => false, 'host' => $host ?? 'unknown', 'error' => $exception->getMessage()];
        }
    }

    protected function checkWaha(): array
    {
        try {
            $apiUrl = config('services.waha.api_url', env('WAHA_API_URL', 'http://localhost:3000'));
            $apiToken = config('services.waha.api_token', env('WAHA_API_TOKEN'));

            if (!$apiToken) {
                return ['ok' => false, 'error' => 'WAHA API token not configured'];
            }

            $response = Http::timeout(2)->withHeaders([
                'Authorization' => "Bearer {$apiToken}",
            ])->get("{$apiUrl}/health");

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'url' => $apiUrl,
            ];
        } catch (\Throwable $exception) {
            Log::warning('WAHA health check failed', ['exception' => $exception->getMessage()]);
            return ['ok' => false, 'error' => $exception->getMessage()];
        }
    }

    protected function checkAiProviders(): array
    {
        $providers = [];

        // Check OpenAI
        try {
            $apiKey = env('OPENAI_API_KEY');
            if ($apiKey) {
                $response = Http::timeout(2)->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                ])->get('https://api.openai.com/v1/models');
                $providers['openai'] = ['ok' => $response->successful(), 'status' => $response->status()];
            }
        } catch (\Throwable $e) {
            $providers['openai'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        // Check Anthropic
        try {
            $apiKey = env('ANTHROPIC_API_KEY');
            if ($apiKey) {
                $response = Http::timeout(2)->withHeaders([
                    'x-api-key' => $apiKey,
                ])->get('https://api.anthropic.com/v1/models');
                $providers['anthropic'] = ['ok' => $response->successful(), 'status' => $response->status()];
            }
        } catch (\Throwable $e) {
            $providers['anthropic'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        // Check Gemini
        try {
            $apiKey = env('GEMINI_API_KEY');
            if ($apiKey) {
                $response = Http::timeout(2)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
                $providers['gemini'] = ['ok' => $response->successful(), 'status' => $response->status()];
            }
        } catch (\Throwable $e) {
            $providers['gemini'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        // Check Groq
        try {
            $apiKey = env('GROQ_API_KEY');
            if ($apiKey) {
                $response = Http::timeout(2)->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                ])->get('https://api.groq.com/openai/v1/models');
                $providers['groq'] = ['ok' => $response->successful(), 'status' => $response->status()];
            }
        } catch (\Throwable $e) {
            $providers['groq'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        return [
            'ok' => collect($providers)->filter(fn ($p) => isset($p['ok']))->every(fn ($p) => $p['ok']),
            'providers' => $providers,
        ];
    }
}
