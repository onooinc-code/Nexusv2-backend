<?php

namespace App\Listeners;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use App\Services\LogService;

/**
 * Base Listener class for event handling
 */
abstract class Listener
{
    /**
     * Determine if the listener should be queued
     *
     * @var bool
     */
    public bool $shouldQueue = false;

    /**
     * The name of the queue connection to use
     *
     * @var string|null
     */
    public ?string $connection = 'redis';

    /**
     * The name of the queue to use
     *
     * @var string
     */
    public string $queue = 'default';

    /**
     * The number of seconds the job can run before timing out
     *
     * @var int
     */
    public int $timeout = 0;

    /**
     * The number of times the queued listener may be attempted
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * The log service instance.
     *
     * @var LogService
     */
    protected LogService $logService;

    /**
     * Create the event listener.
     */
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Get the name of the listener
     *
     * @return string
     */
    public function getName(): string
    {
        return class_basename(static::class);
    }

    /**
     * Log a message
     *
     * @param string $message
     * @param string $level
     * @return void
     */
    protected function log(string $message, string $level = 'info'): void
    {
        $this->logService->log($level, "[{$this->getName()}] " . $message);
    }

    /**
     * Dispatch another event
     *
     * @param object $event
     * @return mixed
     */
    protected function dispatchEvent(object $event): mixed
    {
        return event($event);
    }

    /**
     * Handle failure
     *
     * @param \Exception $exception
     * @return void
     */
    public function failed(\Exception $exception): void
    {
        $this->log("Failed: " . $exception->getMessage(), 'error');
    }
}
