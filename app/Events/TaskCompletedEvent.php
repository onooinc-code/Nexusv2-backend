<?php

namespace App\Events;

use App\Models\AgentTask;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a task is completed successfully
 */
class TaskCompletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AgentTask $task;

    /**
     * Create a new event instance.
     */
    public function __construct(AgentTask $task)
    {
        $this->task = $task;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel('task.' . $this->task->id),
            new \Illuminate\Broadcasting\Channel('tasks'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'task.completed';
    }
}