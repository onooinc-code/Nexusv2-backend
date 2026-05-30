<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * GlobalAgentPauseToggled Event
 *
 * Broadcast when global agent pause is toggled.
 * Allows all clients to respond to emergency pause in real-time.
 */
class GlobalAgentPauseToggled extends Event implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public bool $enabled,
        public ?string $reason = null,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('system.emergency'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'emergency' => 'agent_pause',
            'enabled' => $this->enabled,
            'reason' => $this->reason,
            'timestamp' => now()->toIso8601String(),
            'message' => $this->enabled
                ? 'Global agent pause ACTIVATED'
                : 'Global agent pause DEACTIVATED',
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'global.agent.pause';
    }
}
