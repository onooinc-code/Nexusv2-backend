<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AgentExecuted extends Event implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $agentId,
        public string $executionId,
        public string $status,
        public array $output = [],
        public array $metadata = []
    ) {
        parent::__construct();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("agent.execution.{$this->executionId}");
    }

    public function broadcastWith(): array
    {
        return [
            'agent_id' => $this->agentId,
            'execution_id' => $this->executionId,
            'status' => $this->status,
            'output_summary' => substr(json_encode($this->output), 0, 2048),
            'metadata' => $this->metadata,
            'timestamp' => $this->timestamp->toDateTimeString(),
        ];
    }
}
