<?php

namespace App\Services;

use App\Models\Agent;
use App\Services\LogService;
use App\Events\AgentRegistered;
use Illuminate\Support\Facades\Log;

class AgentLifecycleService
{
    protected array $stateTransitions = [
        Agent::STATUS_ACTIVE      => [Agent::STATUS_INACTIVE, Agent::STATUS_QUARANTINED],
        Agent::STATUS_INACTIVE    => [Agent::STATUS_ACTIVE],
        Agent::STATUS_QUARANTINED => [Agent::STATUS_ACTIVE],
    ];

    public function __construct(protected LogService $logService) {}

    /**
     * Initialize an agent for execution (increment counters).
     */
    public function initialize(Agent $agent): Agent
    {
        $agent->increment('execution_count');
        $agent->update(['last_executed_at' => now()]);

        $this->logService->info("Agent initialized: {$agent->name} (ID: {$agent->id})", [
            'channel'      => 'agent',
            'type'         => 'lifecycle',
            'related_id'   => $agent->id,
            'related_type' => Agent::class,
        ]);

        return $agent->fresh();
    }

    /**
     * Activate an agent.
     */
    public function activate(Agent $agent): Agent
    {
        return $this->transition($agent, Agent::STATUS_ACTIVE);
    }

    /**
     * Deactivate an agent (soft disable).
     */
    public function deactivate(Agent $agent): Agent
    {
        return $this->transition($agent, Agent::STATUS_INACTIVE);
    }

    /**
     * Register a brand-new agent and emit agent.registered event.
     */
    public function register(Agent $agent): Agent
    {
        try {
            event(new AgentRegistered($agent));
        } catch (\Throwable $e) {
            Log::warning("Could not emit AgentRegistered event: {$e->getMessage()}");
        }

        $this->logService->info("Agent registered: {$agent->name}", [
            'channel'      => 'agent',
            'type'         => 'registered',
            'related_id'   => $agent->id,
            'related_type' => Agent::class,
        ]);

        return $agent;
    }

    /**
     * Transition an agent to a new status.
     */
    public function transition(Agent $agent, string $newStatus): Agent
    {
        $currentStatus = $agent->status;

        if (!isset($this->stateTransitions[$currentStatus])) {
            throw new \InvalidArgumentException("Invalid current status: {$currentStatus}");
        }

        if (!in_array($newStatus, $this->stateTransitions[$currentStatus])) {
            throw new \InvalidArgumentException(
                "Invalid state transition from {$currentStatus} to {$newStatus}"
            );
        }

        $agent->update(['status' => $newStatus]);

        $this->logService->info("Agent transition: {$agent->name} [{$currentStatus} → {$newStatus}]", [
            'channel'      => 'agent',
            'type'         => 'lifecycle',
            'related_id'   => $agent->id,
            'related_type' => Agent::class,
            'context'      => ['from' => $currentStatus, 'to' => $newStatus],
        ]);

        return $agent->fresh();
    }

    public function complete(Agent $agent): Agent
    {
        $agent->recordSuccess();

        $this->logService->info("Agent completed: {$agent->name}", [
            'channel'      => 'agent',
            'type'         => 'lifecycle',
            'related_id'   => $agent->id,
            'related_type' => Agent::class,
        ]);

        return $agent->fresh();
    }

    public function fail(Agent $agent, ?string $errorMessage = null): Agent
    {
        $agent->recordError();

        $this->logService->error("Agent failed: {$agent->name}" . ($errorMessage ? " - {$errorMessage}" : ''), [
            'channel'      => 'agent',
            'type'         => 'lifecycle',
            'related_id'   => $agent->id,
            'related_type' => Agent::class,
            'context'      => ['error' => $errorMessage],
        ]);

        return $agent->fresh();
    }

    public function canTransition(Agent $agent, string $newStatus): bool
    {
        return isset($this->stateTransitions[$agent->status]) &&
               in_array($newStatus, $this->stateTransitions[$agent->status]);
    }

    public function getAvailableTransitions(Agent $agent): array
    {
        return $this->stateTransitions[$agent->status] ?? [];
    }
}