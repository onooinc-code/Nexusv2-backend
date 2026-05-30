<?php

namespace App\Listeners;

use App\Services\LogService;
use Illuminate\Queue\Events\JobFailed;

/**
 * LogJobFailed
 *
 * Automatically logs when a queued job fails.
 */
class LogJobFailed
{
    /**
     * Create the event listener.
     */
    public function __construct(protected LogService $logService) {}

    /**
     * Handle the event.
     */
    public function handle(JobFailed $event): void
    {
        $this->logService->error('Job failed', [
            'channel' => 'queue',
            'type' => 'job_failed',
            'context' => [
                'job' => $event->job->getName(),
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'exception' => $event->exception->getMessage(),
                'exception_class' => get_class($event->exception),
            ],
        ]);
    }
}
