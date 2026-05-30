<?php

namespace Database\Factories;

use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workflow>
 */
class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'key' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'steps' => [
                ['name' => 'Step 1', 'status' => 'pending'],
                ['name' => 'Step 2', 'status' => 'pending'],
            ],
            'trigger_type' => fake()->randomElement([
                Workflow::TRIGGER_MANUAL,
                Workflow::TRIGGER_SCHEDULED,
                Workflow::TRIGGER_EVENT,
                Workflow::TRIGGER_WEBHOOK,
            ]),
            'trigger_config' => ['schedule' => '0 * * * *'],
            'status' => fake()->randomElement([
                Workflow::STATUS_DRAFT,
                Workflow::STATUS_ACTIVE,
                Workflow::STATUS_RUNNING,
                Workflow::STATUS_PAUSED,
                Workflow::STATUS_COMPLETED,
                Workflow::STATUS_FAILED,
                Workflow::STATUS_CANCELLED,
            ]),
            'settings' => ['timeout' => 300],
            'metadata' => ['source' => 'factory'],
            'is_active' => true,
            'last_executed_at' => now()->subDays(rand(1, 30)),
            'execution_count' => rand(0, 50),
            'success_count' => rand(0, 50),
            'error_count' => rand(0, 10),
        ];
    }
}
