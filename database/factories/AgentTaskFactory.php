<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\AgentTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentTask>
 */
class AgentTaskFactory extends Factory
{
    protected $model = AgentTask::class;

    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['pending', 'running', 'paused', 'completed', 'failed', 'cancelled']),
            'priority' => fake()->numberBetween(10, 100),
            'progress' => fake()->numberBetween(0, 100),
            'due_at' => now()->addDays(fake()->numberBetween(1, 14)),
            'metadata' => ['task_type' => 'workflow'],
        ];
    }
}
