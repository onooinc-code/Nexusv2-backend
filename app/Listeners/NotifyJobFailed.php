<?php

namespace App\Listeners;

use App\Events\JobFailedEvent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

class NotifyJobFailed
{
    public function handle(JobFailed $event): void
    {
        try {
            $payload = json_decode($event->job->payload(), true) ?: [];
            $jobClass = $payload['data']['command'] ?? class_basename($event->job->resolveName());
            $queue = $event->job->getQueue();

            event(new JobFailedEvent(
                $jobClass,
                $queue,
                $event->exception->getMessage(),
                json_encode($payload['data'] ?? [])
            ));
        } catch (\Throwable $exception) {
            Log::warning('Failed to notify job failure', ['exception' => $exception->getMessage()]);
        }
    }
}
