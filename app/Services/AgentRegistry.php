<?php

namespace App\Services;

use App\Models\Agent;
use App\Agents\ReflectionAgent;
use App\Agents\TeamAgent;
use App\Agents\AutonomousAgent;
use App\Agents\SpecializedAgent;
use App\Agents\SupervisorAgent;
use Illuminate\Support\Facades\Log;

class AgentRegistry
{
    protected array $agentTypes = [
        Agent::TYPE_REFLECTION => ReflectionAgent::class,
        Agent::TYPE_TEAM => TeamAgent::class,
        Agent::TYPE_AUTONOMOUS => AutonomousAgent::class,
        Agent::TYPE_SPECIALIZED => SpecializedAgent::class,
        Agent::TYPE_SUPERVISOR => SupervisorAgent::class,
    ];

    protected array $instances = [];

    public function register(string $type, string $class): void
    {
        $this->agentTypes[$type] = $class;
        Log::info("Agent type registered: {$type} => {$class}");
    }

    public function resolve(Agent $agent): object
    {
        $type = $agent->type;

        if (!isset($this->agentTypes[$type])) {
            throw new \InvalidArgumentException("Unknown agent type: {$type}");
        }

        $class = $this->agentTypes[$type];

        if (!isset($this->instances[$type])) {
            $this->instances[$type] = new $class($agent);
        }

        return $this->instances[$type];
    }

    public function has(string $type): bool
    {
        return isset($this->agentTypes[$type]);
    }

    public function all(): array
    {
        return $this->agentTypes;
    }

    public function getRegisteredTypes(): array
    {
        return array_keys($this->agentTypes);
    }

    public function getAgentClass(string $type): ?string
    {
        return $this->agentTypes[$type] ?? null;
    }

    public function clearCache(): void
    {
        $this->instances = [];
    }
}
