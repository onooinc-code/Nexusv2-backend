<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\AgentSkill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentSkill>
 */
class AgentSkillFactory extends Factory
{
    protected $model = AgentSkill::class;

    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'name' => fake()->word(),
            'category' => fake()->randomElement(['reasoning', 'coding', 'writing', 'analysis']),
            'level' => fake()->randomElement(['basic', 'intermediate', 'advanced', 'expert']),
            'status' => fake()->randomElement(['active', 'inactive', 'training']),
            'description' => fake()->sentence(),
            'metadata' => ['source' => 'factory'],
        ];
    }
}
