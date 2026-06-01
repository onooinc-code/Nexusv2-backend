<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ContactMemoryMaintenanceRun;
use App\Services\Contact\ContactMemoryMaintenancePipeline;

/**
 * Runs a ContactMemoryMaintenanceRun asynchronously in the queue.
 * Dispatched by ContactController@memoryMaintenance when dry_run === false.
 * Dry-run requests are still processed synchronously for immediate UI feedback.
 */
class RunContactMemoryMaintenanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum execution time (seconds) before the job is considered failed. */
    public int $timeout = 300;

    /** Retry the job once on transient failures. */
    public int $tries = 2;

    public function __construct(
        public ContactMemoryMaintenanceRun $run
    ) {}

    public function handle(ContactMemoryMaintenancePipeline $pipeline): void
    {
        $pipeline->process($this->run);
    }
}
