<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConversationSession>
 */
class ConversationSessionFactory extends Factory
{
    protected $model = ConversationSession::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'name' => fake()->sentence(2),
            'status' => fake()->randomElement(['active', 'paused', 'archived']),
            'source' => fake()->randomElement(['whatsapp', 'agent', 'system']),
            'metadata' => ['session_type' => 'conversation'],
            'started_at' => now()->subHours(rand(1, 8)),
            'ended_at' => now()->addHours(rand(1, 5)),
        ];
    }
}
