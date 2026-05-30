<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Agent',
            'key' => Str::slug(fake()->unique()->word() . '-' . fake()->unique()->numberBetween(100, 999)),
            'description' => fake()->sentence(10),
            'provider' => fake()->randomElement(['openai', 'gemini', 'custom']),
            'status' => fake()->randomElement([
                Agent::STATUS_ACTIVE,
                Agent::STATUS_INACTIVE,
                Agent::STATUS_QUARANTINED,
            ]),
            'settings' => ['timeout' => 30, 'retries' => 3],
            'metadata' => ['built_for' => 'testing'],
            'is_active' => true,
        ];
    }
}
