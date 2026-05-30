<?php

namespace App\Services;

use App\Models\AgentSkill;
use Illuminate\Support\Facades\Log;

class AgentSkillLibrary
{
    protected array $skills = [];
    protected array $skillInstances = [];

    public function register(string $name, array $definition, callable $handler = null): void
    {
        $this->skills[$name] = array_merge([
            'name' => $name,
            'description' => '',
            'category' => 'general',
            'parameters' => [],
            'required' => [],
            'registered_at' => now()->toISOString(),
        ], $definition);

        if ($handler) {
            $this->skillInstances[$name] = $handler;
        }

        Log::info("Skill registered: {$name}");
    }

    public function get(string $name): ?array
    {
        return $this->skills[$name] ?? null;
    }

    public function getAll(): array
    {
        return $this->skills;
    }

    public function getByCategory(string $category): array
    {
        return array_filter($this->skills, fn($skill) => $skill['category'] === $category);
    }

    public function has(string $name): bool
    {
        return isset($this->skills[$name]);
    }

    public function execute(string $name, array $params = []): mixed
    {
        if (!isset($this->skills[$name])) {
            throw new \InvalidArgumentException("Skill not found: {$name}");
        }

        $skill = $this->skills[$name];
        $this->validateParameters($name, $params);

        if (isset($this->skillInstances[$name])) {
            $result = ($this->skillInstances[$name])($params);
            Log::info("Skill executed via handler: {$name}");
            return $result;
        }

        return $this->defaultExecute($name, $params);
    }

    protected function validateParameters(string $name, array $params): void
    {
        $skill = $this->skills[$name];
        $required = $skill['required'] ?? [];

        foreach ($required as $param) {
            if (!array_key_exists($param, $params)) {
                throw new \InvalidArgumentException("Missing required parameter: {$param} for skill {$name}");
            }
        }
    }

    protected function defaultExecute(string $name, array $params): array
    {
        Log::info("Skill executed with default handler: {$name}", $params);

        return [
            'skill' => $name,
            'status' => 'executed',
            'params' => $params,
            'result' => "Default execution of {$name}",
            'executed_at' => now()->toISOString(),
        ];
    }

    public function registerFromModel(AgentSkill $agentSkill): void
    {
        $name = $agentSkill->name;
        $definition = [
            'description' => $agentSkill->description,
            'category' => $agentSkill->category ?? 'general',
            'parameters' => $agentSkill->parameters ?? [],
            'required' => $agentSkill->required ?? [],
            'agent_skill_id' => $agentSkill->id,
            'agent_id' => $agentSkill->agent_id,
        ];

        $this->register($name, $definition);
    }

    public function unregister(string $name): bool
    {
        unset($this->skills[$name], $this->skillInstances[$name]);
        return true;
    }

    public function clear(): void
    {
        $this->skills = [];
        $this->skillInstances = [];
    }

    public function search(string $query): array
    {
        $queryLower = strtolower($query);
        return array_filter($this->skills, function ($skill) use ($queryLower) {
            return str_contains(strtolower($skill['name']), $queryLower) ||
                   str_contains(strtolower($skill['description']), $queryLower);
        });
    }
}
