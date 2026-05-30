<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AiModelsHub\ProviderHealthMonitor;

class PollProviderHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:poll-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Polls AI providers to check their health and records latency/uptime metrics';

    /**
     * Execute the console command.
     */
    public function handle(ProviderHealthMonitor $monitor)
    {
        $this->info('Starting AI provider health polling...');
        
        $monitor->pollAllProviders();
        
        $this->info('Health polling completed.');
    }
}
