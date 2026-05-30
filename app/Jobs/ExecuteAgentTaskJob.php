<?php

namespace App\Jobs;

use App\Models\Agent;
use App\Services\AgentExecutionService;
use App\Services\AgentQuarantineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteAgentTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Maximum execution time in seconds.
     */
    public int $timeout = 120;

    public function __construct(
        public string $agentId,
        public array  $input,
        public string $traceId
    ) {}

    public function handle(
        AgentExecutionService  $executionService,
        AgentQuarantineService $quarantineService
    ): void {
        $agent = Agent::find($this->agentId);

        if (!$agent) {
            Log::error("ExecuteAgentTaskJob: Agent [{$this->agentId}] not found.");
            return;
        }

        try {
            // Guard: reject quarantined agents
            $quarantineService->guardExecution($agent);

            $agent->increment('execution_count');
            $agent->update(['last_executed_at' => now()]);

            $context = $executionService->buildExecutionContext($agent, $this->input);

            // Use the private callLLM via a helper
            $result = $this->executeWithLLM($executionService, $agent, $context);

            $executionService->logStep(
                $agent, null, $this->traceId, 'async_completed',
                $this->input, $result, null
            );

            $agent->recordSuccess();

            Log::info("ExecuteAgentTaskJob: Agent [{$agent->name}] completed. Trace: {$this->traceId}");
        } catch (\Throwable $e) {
            $agent->recordError();

            $executionService->logStep(
                $agent, null, $this->traceId, 'async_failed',
                $this->input, ['error' => $e->getMessage()], null
            );

            Log::error("ExecuteAgentTaskJob: Agent [{$agent->name}] failed. {$e->getMessage()}");

            // Re-throw so Laravel can handle retries
            throw $e;
        }
    }

    /**
     * Handle a job failure (all retries exhausted).
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical(
            "ExecuteAgentTaskJob permanently failed for Agent [{$this->agentId}]. " .
            "Trace: {$this->traceId}. Error: {$exception->getMessage()}"
        );
    }

    /**
     * Invoke the execution service's callLLM method.
     * We do this by calling runSync internally but skipping the double-tracking.
     */
    protected function executeWithLLM(
        AgentExecutionService $service,
        Agent $agent,
        array $context
    ): array {
        // Directly run sync from inside the job
        return $service->runSync($agent, $this->input);
    }
}
