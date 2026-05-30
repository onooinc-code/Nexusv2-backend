<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorReverbHealth extends Command
{
    protected $signature = 'monitor:reverb-health';

    protected $description = 'Execute a periodic health check against the Reverb WebSocket service.';

    public function handle(): int
    {
        $host = config('broadcasting.connections.reverb.host', env('REVERB_HOST', '127.0.0.1'));
        $port = config('broadcasting.connections.reverb.port', env('REVERB_PORT', 6001));
        $scheme = config('broadcasting.connections.reverb.scheme', env('REVERB_SCHEME', 'https'));

        try {
            // Test TCP connection to Reverb server (WebSocket servers don't expose HTTP /health)
            $sock = @fsockopen($host, $port, $errno, $errstr, 3);

            if ($sock) {
                fclose($sock);
                $this->info("Reverb health check passed (listening on {$host}:{$port}).");
                Log::info('Reverb health check passed.', [
                    'host' => $host,
                    'port' => $port,
                ]);
                return self::SUCCESS;
            }

            $message = sprintf('Reverb health check failed: cannot connect to %s:%s. %s', $host, $port, $errstr);
            Log::warning('Reverb health check failed.', [
                'host' => $host,
                'port' => $port,
                'error' => $errstr,
            ]);
            $this->error($message);

            return self::FAILURE;
        } catch (\Throwable $exception) {
            Log::error('Reverb health check error.', [
                'host' => $host,
                'port' => $port,
                'exception' => $exception->getMessage(),
            ]);

            $this->error('Reverb health check error: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
