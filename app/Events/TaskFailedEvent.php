<?php

namespace App\Events;

use App\Models\AgentTask;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a task fails
 */
class TaskFailedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AgentTask $task;
    public string $error;

    /**
     * Create a new event instance.
     */
    public function __construct(AgentTask $task, string $error = '')
    {
        $this->task = $task;
        $this->error = $error;
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
        return 'task.failed';
    }
}