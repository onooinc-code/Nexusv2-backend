<?php

namespace App\Services;

use App\Models\Agent;
use App\Services\AgentExecutionService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * AgentSimulationService
 *
 * Provides a sandbox execution mode where tool calls return mocked responses
 * and no production data is affected.
 */
class AgentSimulationService
{
    public function __construct(
        protected AgentExecutionService $executionService
    ) {}

    /**
     * Run the agent in sandbox mode with optional mock tool responses.
     *
     * @param Agent  $agent
     * @param array  $input      The mock input to provide to the agent
     * @param array  $mockTools  Keyed by tool name => mock response
     */
    public function simulate(Agent $agent, array $input, array $mockTools = []): array
    {
        $traceId = Str::uuid()->toString();
        $startedAt = microtime(true);

        Log::info("AgentSimulationService: Starting simulation for agent [{$agent->id}]", [
            'trace_id' => $traceId,
        ]);

        try {
            // Build the execution context
            $context = $this->executionService->buildExecutionContext($agent, $input);

            // Inject mock tool responses into context
            if (!empty($mockTools)) {
                $context['mock_tools'] = $mockTools;
                $context['system_prompt'] .= "\n\n[SIMULATION MODE] The following tool responses are mocked:\n";
                foreach ($mockTools as $toolName => $mockResponse) {
                    $context['system_prompt'] .= "- {$toolName}: " . json_encode($mockResponse) . "\n";
                }
            }

            // Add simulation flag so the LLM knows it's a dry-run
            $context['system_prompt'] .= "\n\n[SIMULATION MODE] This is a sandbox run. Do not affect real data.";

            // Build a thought-trace of what the agent would do
            $thoughtProcess = $this->buildThoughtProcess($agent, $context, $mockTools);

            $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

            // Log simulation step (no real DB changes)
            $this->executionService->logStep(
                $agent, null, $traceId, 'simulation', $input,
                ['thought_process' => $thoughtProcess, 'mock_tools' => $mockTools],
                $durationMs
            );

            return [
                'success'         => true,
                'mode'            => 'simulation',
                'trace_id'        => $traceId,
                'duration_ms'     => $durationMs,
                'agent'           => [
                    'id'   => $agent->id,
                    'name' => $agent->name,
                    'type' => $agent->type,
                ],
                'context'         => [
                    'system_prompt' => $context['system_prompt'],
                    'tools_count'   => count($context['tools']),
                ],
                'thought_process' => $thoughtProcess,
                'mock_tools_used' => array_keys($mockTools),
            ];
        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

            Log::error("AgentSimulationService: Simulation failed - {$e->getMessage()}");

            return [
                'success'     => false,
                'mode'        => 'simulation',
                'trace_id'    => $traceId,
                'duration_ms' => $durationMs,
                'error'       => $e->getMessage(),
            ];
        }
    }

    /**
     * Build a simple thought-process trace of what the agent would do.
     */
    protected function buildThoughtProcess(Agent $agent, array $context, array $mockTools): array
    {
        $steps = [];

        $steps[] = [
            'step'        => 'persona_loaded',
            'description' => 'Agent persona system prompt compiled.',
            'detail'      => substr($context['system_prompt'], 0, 200) . '...',
        ];

        $steps[] = [
            'step'        => 'tools_attached',
            'description' => count($context['tools']) . ' tool(s) attached.',
            'tools'       => array_column($context['tools'], 'name'),
        ];

        foreach ($mockTools as $tool => $response) {
            $steps[] = [
                'step'        => "tool_invoked:{$tool}",
                'description' => "Tool [{$tool}] would return mocked response.",
                'mock_response'=> $response,
            ];
        }

        $steps[] = [
            'step'        => 'llm_call_simulated',
            'description' => 'LLM call would be dispatched to AIModelsHub.',
            'input_preview'=> is_string($context['input'])
                ? substr($context['input'], 0, 100)
                : json_encode($context['input']),
        ];

        return $steps;
    }
}
