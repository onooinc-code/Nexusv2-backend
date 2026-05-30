<?php

namespace App\Events;

use App\Models\Agent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentQuarantined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Agent  $agent,
        public string $reason
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('agents'),
            new PrivateChannel("agent.{$this->agent->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'agent.quarantined';
    }

    public function broadcastWith(): array
    {
        return [
            'agent_id'       => $this->agent->id,
            'agent_name'     => $this->agent->name,
            'reason'         => $this->reason,
            'quarantined_at' => now()->toISOString(),
        ];
    }
}
