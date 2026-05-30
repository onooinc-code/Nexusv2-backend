<?php

namespace App\Events;

use App\Models\Agent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Agent  $agent,
        public string $traceId
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("agent.{$this->agent->id}")];
    }

    public function broadcastAs(): string
    {
        return 'agent.started';
    }

    public function broadcastWith(): array
    {
        return [
            'agent_id' => $this->agent->id,
            'trace_id' => $this->traceId,
            'started_at' => now()->toISOString(),
        ];
    }
}
