<?php

namespace App\Console\Commands;

use App\Services\CredentialValidationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorSettingsHealth extends Command
{
    protected $signature = 'monitor:settings-health';

    protected $description = 'Execute periodic health checks for settings and integration credentials.';

    public function __construct(
        protected CredentialValidationService $validationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->info('Starting settings and credential health checks...');

            // Validate all credentials
            $result = $this->validationService->validateAllCredentials();

            $this->info("Credential validation completed:");
            $this->info("  ✓ Valid credentials: {$result['valid_count']}");
            $this->info("  ✗ Invalid credentials: {$result['invalid_count']}");
            $this->info("  Total checked: {$result['total']}");

            // Log detailed results
            Log::info('Settings health check completed', [
                'channel' => 'monitoring',
                'type' => 'health_check',
                'results' => $result,
            ]);

            // If there are invalid credentials, log a warning
            if ($result['invalid_count'] > 0) {
                Log::warning('Some credentials are invalid', [
                    'channel' => 'monitoring',
                    'type' => 'health_check',
                    'invalid_credentials' => collect($result['results'])
                        ->filter(fn ($r) => !$r['valid'])
                        ->keys()
                        ->all(),
                ]);
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Settings health check failed: ' . $exception->getMessage());
            Log::error('Settings health check error', [
                'channel' => 'monitoring',
                'type' => 'health_check',
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
