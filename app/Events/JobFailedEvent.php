<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobFailedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $jobClass,
        public string $queue,
        public string $errorMessage,
        public string $jobData
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('admin.dlq');
    }

    public function broadcastWith(): array
    {
        return [
            'job_class' => $this->jobClass,
            'queue' => $this->queue,
            'error_message' => $this->errorMessage,
            'job_data' => json_decode($this->jobData, true),
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
