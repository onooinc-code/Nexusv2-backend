<?php

namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\Redis;

class TaskRateLimiting
{
    /**
     * Process the queued job.
     */
    public function handle(mixed $job, callable $next): void
    {
        // Rate limit agent tasks to prevent AI provider 429s
        // Allow 10 tasks every 60 seconds
        Redis::throttle('agent-tasks')
            ->block(0)
            ->allow(10)
            ->every(60)
            ->then(function () use ($job, $next) {
                // Lock obtained, execute the job
                $next($job);
            }, function () use ($job) {
                // Could not obtain lock, apply backpressure
                // Release the job back into the queue with a delay
                $job->release(15); // Release back after 15 seconds
            });
    }
}
