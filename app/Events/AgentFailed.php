<?php

namespace App\Events;

use App\Models\Agent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Agent  $agent,
        public string $traceId,
        public string $error,
        public bool   $escalation = false
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("agent.{$this->agent->id}")];
    }

    public function broadcastAs(): string
    {
        return 'agent.failed';
    }

    public function broadcastWith(): array
    {
        return [
            'agent_id'   => $this->agent->id,
            'trace_id'   => $this->traceId,
            'error'      => $this->error,
            'escalation' => $this->escalation,
            'failed_at'  => now()->toISOString(),
        ];
    }
}
