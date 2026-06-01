<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentPersona;
use App\Models\AgentRuntimeLog;
use App\Models\AIModel;
use App\Models\AIProvider;
use App\Events\AgentStarted;
use App\Events\AgentCompleted;
use App\Events\AgentFailed;
use App\Services\AiModelsHub\DynamicRestProvider;
use App\Services\AiModelsHub\EncryptedApiKeyStorage;
use App\Services\LogService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * AgentExecutionService
 *
 * Orchestrates the full execution pipeline for an agent:
 *  1. Compiles the persona system prompt
 *  2. Attaches authorized tools
 *  3. Resolves the AI model (from agent settings or the platform default)
 *  4. Sends the request to the AIModelsHub DynamicRestProvider
 *  5. Logs the full trace to agent_runtime_logs
 *  6. Emits lifecycle events
 */
class AgentExecutionService
{
    public function __construct(
        protected EncryptedApiKeyStorage $keyStorage,
        protected LogService $logService
    ) {}

    /**
     * Execute an agent synchronously and return the result immediately.
     */
    public function runSync(Agent $agent, array $input): array
    {
        $traceId = Str::uuid()->toString();
        $startedAt = microtime(true);

        try {
            $this->emitStarted($agent, $traceId);

            $context = $this->buildExecutionContext($agent, $input);

            $result = $this->callLLM($agent, $context, $traceId);

            $durationMs = (int) ((microtime(true) - $startedAt) * 1000);
            $this->logStep($agent, null, $traceId, 'completed', $input, $result, $durationMs);
            $agent->recordSuccess();
            $agent->increment('execution_count');
            $agent->update(['last_executed_at' => now()]);

            $this->emitCompleted($agent, $traceId, $result);

            return [
                'success'    => true,
                'trace_id'   => $traceId,
                'mode'       => 'sync',
                'result'     => $result,
                'duration_ms'=> $durationMs,
            ];
        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startedAt) * 1000);
            $this->logStep($agent, null, $traceId, 'failed', $input, ['error' => $e->getMessage()], $durationMs);
            $agent->recordError();
            $agent->increment('execution_count');
            $this->emitFailed($agent, $traceId, $e->getMessage());

            Log::error("AgentExecutionService: Agent [{$agent->id}] failed - {$e->getMessage()}");

            return [
                'success'    => false,
                'trace_id'   => $traceId,
                'mode'       => 'sync',
                'error'      => $e->getMessage(),
                'duration_ms'=> $durationMs,
            ];
        }
    }

    /**
     * Execute an agent asynchronously — generates an AgentTask and dispatches ExecuteAgentTaskJob to queue.
     */
    public function runAsync(Agent $agent, array $input): array
    {
        $traceId = Str::uuid()->toString();

        // 1. Create the AgentTask record
        $task = \App\Models\AgentTask::create([
            'agent_id' => $agent->id,
            'title' => 'Async Execution: ' . substr(is_array($input) ? json_encode($input) : $input, 0, 50),
            'status' => \App\Models\AgentTask::STATUS_TODO,
            'type' => 'agent',
            'payload_data' => $input,
            'metadata' => [
                'trace_id' => $traceId,
                'initiated_via' => 'AgentExecutionService@runAsync'
            ]
        ]);

        // 2. Dispatch job with the task model
        \App\Jobs\ExecuteAgentTaskJob::dispatch($task);

        return [
            'success'  => true,
            'trace_id' => $traceId,
            'task_id'  => $task->id,
            'mode'     => 'async',
            'message'  => 'Agent task queued for execution.',
        ];
    }

    /**
     * Build the full execution context from agent persona + tools + input.
     */
    public function buildExecutionContext(Agent $agent, array $input): array
    {
        $agent->loadMissing('persona', 'tools', 'skills', 'mcpServers');

        $systemPrompt = $agent->persona
            ? $agent->persona->system_prompt
            : "You are {$agent->name}, an AI agent. Complete the task provided.";

        // Append tone preferences if set
        if ($agent->persona && !empty($agent->persona->tone_preferences)) {
            $tones = $agent->persona->tone_preferences;
            if (!empty($tones['tone'])) {
                $systemPrompt .= "\n\nTone: {$tones['tone']}.";
            }
            if (!empty($tones['style'])) {
                $systemPrompt .= " Style: {$tones['style']}.";
            }
        }

        // Build tool descriptions for the prompt
        $toolDescriptions = $agent->tools
            ->where('is_active', true)
            ->map(fn($t) => "- {$t->name}: {$t->description}")
            ->implode("\n");

        if ($toolDescriptions) {
            $systemPrompt .= "\n\nAvailable Tools:\n{$toolDescriptions}";
        }

        return [
            'system_prompt' => $systemPrompt,
            'input'         => $input,
            'tools'         => $agent->tools->where('is_active', true)->values()->toArray(),
            'agent_id'      => $agent->id,
            'agent_name'    => $agent->name,
        ];
    }

    /**
     * Call the LLM via UniversalAiGatewayService.
     */
    protected function callLLM(Agent $agent, array $context, string $traceId): array
    {
        $gateway = app(\App\Services\AiModelsHub\UniversalAiGatewayService::class);
        
        $result = $gateway->executeWithAgent($agent, $context);
        
        $this->logStep($agent, null, $traceId, 'llm_call', [
            'model'     => $result['used_model'] ?? 'unknown',
            'provider'  => $result['used_provider'] ?? 'unknown',
        ], null, null);

        return $result;
    }

    /**
     * Write a step to agent_runtime_logs.
     */
    public function logStep(
        Agent   $agent,
        ?string $taskId,
        string  $traceId,
        string  $step,
        mixed   $input,
        mixed   $output,
        ?int    $durationMs
    ): void {
        AgentRuntimeLog::create([
            'id'          => Str::uuid()->toString(),
            'agent_id'    => $agent->id,
            'task_id'     => $taskId,
            'trace_id'    => $traceId,
            'step'        => $step,
            'input'       => $input,
            'output'      => $output,
            'duration_ms' => $durationMs,
        ]);
    }

    protected function emitStarted(Agent $agent, string $traceId): void
    {
        try {
            event(new AgentStarted($agent, $traceId));
        } catch (\Throwable $e) {
            Log::warning("Could not emit AgentStarted event: {$e->getMessage()}");
        }
    }

    protected function emitCompleted(Agent $agent, string $traceId, array $result): void
    {
        try {
            event(new AgentCompleted($agent, $traceId, $result));
        } catch (\Throwable $e) {
            Log::warning("Could not emit AgentCompleted event: {$e->getMessage()}");
        }
    }

    protected function emitFailed(Agent $agent, string $traceId, string $error): void
    {
        try {
            event(new AgentFailed($agent, $traceId, $error));
        } catch (\Throwable $e) {
            Log::warning("Could not emit AgentFailed event: {$e->getMessage()}");
        }
    }
}
