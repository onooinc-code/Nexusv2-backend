<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Contact;
use App\Models\Memory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Memory>
 */
class MemoryFactory extends Factory
{
    protected $model = Memory::class;

    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'conversation_id' => Conversation::factory(),
            'source' => fake()->randomElement(['message', 'note', 'agent']),
            'type' => 'memory',
            'title' => fake()->sentence(3),
            'content' => fake()->paragraph(),
            'vector' => ['embedding' => array_map(fn () => fake()->randomFloat(4, 0, 1), range(1, 5))],
            'metadata' => ['created_by' => 'factory'],
            'tags' => fake()->words(3),
            'expires_at' => now()->addDays(rand(30, 365)),
        ];
    }
}
