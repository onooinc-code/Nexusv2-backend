<?php

namespace App\Services;

use App\Models\AgentTool;
use Illuminate\Support\Facades\Log;

class AgentToolRegistry
{
    protected array $tools = [];
    protected array $executors = [];

    public function register(string $name, array $definition, callable $executor = null): void
    {
        $this->tools[$name] = array_merge([
            'name' => $name,
            'description' => '',
            'parameters' => [],
            'required' => [],
            'registered_at' => now()->toISOString(),
        ], $definition);

        if ($executor) {
            $this->executors[$name] = $executor;
        }

        Log::info("Tool registered: {$name}");
    }

    public function get(string $name): ?array
    {
        return $this->tools[$name] ?? null;
    }

    public function getAll(): array
    {
        return $this->tools;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function execute(string $name, array $params = []): mixed
    {
        if (!isset($this->tools[$name])) {
            throw new \InvalidArgumentException("Tool not found: {$name}");
        }

        $tool = $this->tools[$name];
        $this->validateParameters($name, $params);

        if (isset($this->executors[$name])) {
            $result = ($this->executors[$name])($params);
            Log::info("Tool executed via callback: {$name}");
            return $result;
        }

        return $this->defaultExecute($name, $params);
    }

    protected function validateParameters(string $name, array $params): void
    {
        $tool = $this->tools[$name];
        $required = $tool['required'] ?? [];

        foreach ($required as $param) {
            if (!array_key_exists($param, $params)) {
                throw new \InvalidArgumentException("Missing required parameter: {$param} for tool {$name}");
            }
        }
    }

    protected function defaultExecute(string $name, array $params): array
    {
        Log::info("Tool executed with default handler: {$name}", $params);

        return [
            'tool' => $name,
            'status' => 'executed',
            'params' => $params,
            'result' => "Default execution of {$name}",
            'executed_at' => now()->toISOString(),
        ];
    }

    public function registerFromModel(AgentTool $agentTool): void
    {
        $name = $agentTool->name;
        $definition = [
            'description' => $agentTool->description,
            'parameters' => $agentTool->parameters ?? [],
            'required' => $agentTool->required ?? [],
            'agent_tool_id' => $agentTool->id,
            'agent_id' => $agentTool->agent_id,
        ];

        $this->register($name, $definition);
    }

    public function unregister(string $name): bool
    {
        unset($this->tools[$name], $this->executors[$name]);
        return true;
    }

    public function clear(): void
    {
        $this->tools = [];
        $this->executors = [];
    }
}
