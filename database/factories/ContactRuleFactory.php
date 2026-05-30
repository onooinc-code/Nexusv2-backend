<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactRule>
 */
class ContactRuleFactory extends Factory
{
    protected $model = ContactRule::class;

    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'rule' => fake()->sentence(6),
            'priority' => fake()->numberBetween(10, 100),
            'is_active' => true,
            'metadata' => ['source' => 'factory'],
        ];
    }
}
