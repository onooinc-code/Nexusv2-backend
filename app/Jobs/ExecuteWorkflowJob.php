<?php

namespace App\Jobs;

use App\Models\WorkflowExecution;
use App\Services\Workflows\WorkflowInterpreter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 900;
    public array $backoff = [300, 900, 3600];

    public function __construct(public string $executionId)
    {
        $this->onQueue('workflows');
    }

    public function handle(WorkflowInterpreter $interpreter): void
    {
        $execution = WorkflowExecution::with(['workflow', 'version'])->findOrFail($this->executionId);
        $interpreter->run($execution);
    }
}
