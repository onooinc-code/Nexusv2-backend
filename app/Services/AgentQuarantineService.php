<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Events\AgentQuarantined;

/**
 * AgentQuarantineService
 *
 * Implements the Kill-Switch pattern:
 *  - Sets agent status to 'quarantined'
 *  - Prevents further executions
 *  - Emits escalation event
 */
class AgentQuarantineService
{
    /**
     * Quarantine a misbehaving agent (kill-switch).
     */
    public function quarantine(Agent $agent, string $reason = 'Manual quarantine'): array
    {
        if ($agent->isQuarantined()) {
            return [
                'success' => false,
                'message' => "Agent [{$agent->name}] is already quarantined.",
            ];
        }

        $agent->quarantine();

        Log::warning("Agent QUARANTINED: [{$agent->id}] {$agent->name}. Reason: {$reason}");

        try {
            event(new AgentQuarantined($agent, $reason));
        } catch (\Throwable $e) {
            Log::warning("Could not emit AgentQuarantined event: {$e->getMessage()}");
        }

        return [
            'success' => true,
            'message' => "Agent [{$agent->name}] has been quarantined.",
            'reason'  => $reason,
        ];
    }

    /**
     * Restore a quarantined agent back to active status.
     */
    public function unquarantine(Agent $agent): array
    {
        if (!$agent->isQuarantined()) {
            return [
                'success' => false,
                'message' => "Agent [{$agent->name}] is not quarantined.",
            ];
        }

        $agent->unquarantine();

        Log::info("Agent UNQUARANTINED: [{$agent->id}] {$agent->name}.");

        return [
            'success' => true,
            'message' => "Agent [{$agent->name}] has been restored to active status.",
        ];
    }

    /**
     * Check if an agent is quarantined and throw if so.
     * Used as a guard before executing agent tasks.
     */
    public function guardExecution(Agent $agent): void
    {
        if ($agent->isQuarantined()) {
            throw new \RuntimeException(
                "Agent [{$agent->name}] is quarantined and cannot execute tasks."
            );
        }

        if (!$agent->isActive()) {
            throw new \RuntimeException(
                "Agent [{$agent->name}] is inactive and cannot execute tasks."
            );
        }
    }
}
