<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class WorkflowCompleted extends Event implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $workflowId,
        public array $result,
        public array $metadata = [],
    ) {
        parent::__construct();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("workflow.{$this->workflowId}");
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->workflowId,
            'result' => $this->result,
            'metadata' => $this->metadata,
            'completed_at' => $this->timestamp->toDateTimeString(),
        ];
    }
}
