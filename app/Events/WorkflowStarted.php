<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class WorkflowStarted extends Event implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $workflowId,
        public string $userId,
        public string $workflowName,
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
            'name' => $this->workflowName,
            'user_id' => $this->userId,
            'started_at' => $this->timestamp->toDateTimeString(),
        ];
    }
}
