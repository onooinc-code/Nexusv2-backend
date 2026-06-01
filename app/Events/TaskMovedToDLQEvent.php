<?php

namespace App\Events;

use App\Models\AgentTask;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class TaskMovedToDLQEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AgentTask $task,
        public Throwable $exception
    ) {}
}
