<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class BatchProgressUpdated extends Event implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $batchId,
        public int $progress,
        public int $total,
        public string $status,
    ) {
        parent::__construct();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("job.batch.{$this->batchId}");
    }

    public function broadcastWith(): array
    {
        $percentage = $this->total > 0 ? round(($this->progress / $this->total) * 100, 2) : 0;

        return [
            'batch_id' => $this->batchId,
            'progress' => $this->progress,
            'total' => $this->total,
            'status' => $this->status,
            'percentage' => $percentage,
            'timestamp' => $this->timestamp->toDateTimeString(),
        ];
    }
}
