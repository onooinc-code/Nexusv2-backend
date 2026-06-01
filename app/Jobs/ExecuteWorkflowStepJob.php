<?php

namespace App\Jobs;

use App\Models\WorkflowExecution;
use App\Services\Workflows\WorkflowTaskDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteWorkflowStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 900;

    public function __construct(
        public string $executionId,
        public array $step,
        public array $variables
    ) {
        $this->onQueue('workflows');
    }

    public function handle(WorkflowTaskDispatcher $dispatcher): void
    {
        $execution = WorkflowExecution::findOrFail($this->executionId);
        $dispatcher->dispatch($execution, $this->step, $this->variables);
    }
}
