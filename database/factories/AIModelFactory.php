<?php

namespace Database\Factories;

use App\Models\AIModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIModel>
 */
class AIModelFactory extends Factory
{
    protected $model = AIModel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word() . ' Model',
            'provider_id' => null,
            'context_window' => fake()->optional()->numberBetween(512, 8192),
            'input_cost_per_m' => fake()->optional()->randomFloat(6, 0, 0.1),
            'output_cost_per_m' => fake()->optional()->randomFloat(6, 0, 0.1),
            'last_synced_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
