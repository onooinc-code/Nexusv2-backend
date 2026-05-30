<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\AgentTool;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentTool>
 */
class AgentToolFactory extends Factory
{
    protected $model = AgentTool::class;

    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'name' => fake()->word(),
            'type' => fake()->randomElement(['tool', 'integration', 'api']),
            'description' => fake()->sentence(),
            'metadata' => ['source' => 'factory'],
            'is_active' => true,
        ];
    }
}
