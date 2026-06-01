<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * WorkflowStepCompleted
 *
 * Dispatched when a single step within a workflow completes.
 */
class WorkflowStepCompleted extends Event implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $workflowId,
        public string $stepId,
        public string $stepTitle,
        public string $status,
        public array $result = [],
        public array $metadata = []
    ) {
        parent::__construct();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("workflow.{$this->workflowId}");
    }

    public function broadcastAs(): string
    {
        return 'workflow.step_completed';
    }

    public function broadcastWith(): array
    {
        return [
            'step_id' => $this->stepId,
            'step_name' => $this->stepTitle,  // alias for frontend compatibility
            'step_title' => $this->stepTitle,
            'status' => $this->status,
            'result' => $this->result,
            'metadata' => $this->metadata,
            'duration_ms' => $this->metadata['duration_ms'] ?? null,
            'error' => $this->metadata['error'] ?? null,
            'timestamp' => $this->timestamp->toDateTimeString(),
        ];
    }
}
