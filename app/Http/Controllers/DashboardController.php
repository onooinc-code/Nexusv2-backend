<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard
     */
    public function index()
    {
        $data = $this->gatherDashboardData();
        return view('dashboard', $data);
    }

    /**
     * Get dashboard data via API
     */
    public function data(Request $request)
    {
        return response()->json($this->gatherDashboardData());
    }

    /**
     * Gather all monitoring data
     */
    private function gatherDashboardData()
    {
        return [
            'backend' => $this->getBackendMetrics(),
            'frontend' => $this->getFrontendMetrics(),
            'system' => $this->getSystemMetrics(),
            'database' => $this->getDatabaseMetrics(),
            'queue' => $this->getQueueMetrics(),
            'cache' => $this->getCacheMetrics(),
            'logs' => $this->getRecentLogs(),
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Backend health metrics
     */
    private function getBackendMetrics()
    {
        try {
            $appHealth = Http::timeout(2)->get(config('app.url') . '/api/v1/health');
            $monitoringHealth = Http::timeout(2)->get(config('app.url') . '/api/v1/monitoring/health');

            return [
                'status' => $monitoringHealth->json()['status'] ?? 'unknown',
                'app_version' => config('app.version', '1.0.0'),
                'environment' => config('app.env'),
                'debug' => config('app.debug'),
                'checks' => $monitoringHealth->json()['checks'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Backend metrics error: ' . $e->getMessage());
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Frontend project status
     */
    private function getFrontendMetrics()
    {
        $frontendPath = base_path('../nsf');

        return [
            'path' => $frontendPath,
            'exists' => is_dir($frontendPath),
            'package_json' => file_exists("$frontendPath/package.json"),
            'build_exists' => is_dir("$frontendPath/.next"),
            'node_modules' => is_dir("$frontendPath/node_modules"),
            'env_file' => file_exists("$frontendPath/.env.local"),
        ];
    }

    /**
     * System metrics
     */
    private function getSystemMetrics()
    {
        return [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'memory_usage' => memory_get_usage(true) / 1024 / 1024,
            'memory_limit' => ini_get('memory_limit'),
            'disk_free' => disk_free_space('/') / 1024 / 1024 / 1024,
            'disk_total' => disk_total_space('/') / 1024 / 1024 / 1024,
            'server_time' => now()->toDateTimeString(),
            'uptime' => $this->getServerUptime(),
        ];
    }

    /**
     * Database metrics
     */
    private function getDatabaseMetrics()
    {
        try {
            DB::connection()->getPdo();

            $dbSize = DB::selectOne("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
                FROM information_schema.TABLES
                WHERE table_schema = ?
            ", [env('DB_DATABASE')]);

            $tables = DB::select("
                SELECT table_name, TABLE_ROWS as row_count
                FROM information_schema.TABLES
                WHERE table_schema = ?
                ORDER BY TABLE_ROWS DESC
                LIMIT 10
            ", [env('DB_DATABASE')]);

            return [
                'connected' => true,
                'host' => config('database.connections.mysql.host'),
                'database' => config('database.connections.mysql.database'),
                'size_mb' => $dbSize->size_mb ?? 0,
                'tables' => $tables,
                'total_records' => array_sum(array_column($tables, 'row_count')),
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Queue metrics
     */
    private function getQueueMetrics()
    {
        try {
            $queueConnection = config('queue.default');
            $failed = DB::table('failed_jobs')->count();
            $pending = DB::table('jobs')->count();

            return [
                'driver' => $queueConnection,
                'pending_jobs' => $pending,
                'failed_jobs' => $failed,
                'status' => $pending > 0 ? 'active' : 'idle',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cache metrics
     */
    private function getCacheMetrics()
    {
        try {
            $driver = config('cache.default');
            $info = [];

            if ($driver === 'redis') {
                $info = Redis::info();
            }

            return [
                'driver' => $driver,
                'info' => $info,
            ];
        } catch (\Exception $e) {
            return [
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Recent logs
     */
    private function getRecentLogs()
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            if (!file_exists($logFile)) {
                return [];
            }

            $lines = array_reverse(file($logFile));
            $logs = [];

            foreach (array_slice($lines, 0, 50) as $line) {
                if (trim($line)) {
                    preg_match('/\[(.*?)\]\s(.*?)\.(.*?):\s(.*)/', $line, $matches);
                    if (count($matches) > 0) {
                        $logs[] = [
                            'timestamp' => $matches[1] ?? '',
                            'level' => $matches[3] ?? 'INFO',
                            'message' => $matches[4] ?? trim($line),
                        ];
                    }
                }
            }

            return array_slice($logs, 0, 10);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get server uptime
     */
    private function getServerUptime()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'N/A';
        }

        $uptime = shell_exec('uptime -p 2>/dev/null || echo "N/A"');
        return trim($uptime) ?: 'N/A';
    }

    /**
     * Refresh specific metrics
     */
    public function refreshMetric(Request $request)
    {
        $metric = $request->input('metric');

        $methods = [
            'backend' => 'getBackendMetrics',
            'frontend' => 'getFrontendMetrics',
            'system' => 'getSystemMetrics',
            'database' => 'getDatabaseMetrics',
            'queue' => 'getQueueMetrics',
            'cache' => 'getCacheMetrics',
        ];

        if (!isset($methods[$metric])) {
            return response()->json(['error' => 'Invalid metric'], 400);
        }

        return response()->json([
            $metric => $this->$methods[$metric](),
        ]);
    }

    /**
     * Clear cache
     */
    public function clearCache(Request $request)
    {
        $this->authorize('admin');
        Cache::flush();
        Log::info('Cache cleared by ' . auth()->user()->name);
        return response()->json(['success' => true, 'message' => 'Cache cleared']);
    }

    /**
     * Restart queue
     */
    public function restartQueue(Request $request)
    {
        $this->authorize('admin');
        try {
            shell_exec('php artisan queue:restart');
            Log::info('Queue restarted by ' . auth()->user()->name);
            return response()->json(['success' => true, 'message' => 'Queue restarted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
