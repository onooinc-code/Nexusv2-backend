<?php

namespace App\Events;

use App\Models\Agent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentRegistered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Agent $agent) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('agents')];
    }

    public function broadcastAs(): string
    {
        return 'agent.registered';
    }

    public function broadcastWith(): array
    {
        return [
            'id'        => $this->agent->id,
            'name'      => $this->agent->name,
            'type'      => $this->agent->type,
            'status'    => $this->agent->status,
            'is_system' => $this->agent->is_system,
        ];
    }
}
