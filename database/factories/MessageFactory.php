<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_type' => fake()->randomElement(['contact', 'agent', 'system']),
            'sender_id' => null,
            'direction' => fake()->randomElement(['inbound', 'outbound']),
            'content_type' => 'text',
            'content' => fake()->paragraph(),
            'metadata' => ['source' => 'factory'],
            'status' => fake()->randomElement(['delivered', 'failed', 'pending']),
            'sent_at' => now()->subMinutes(rand(1, 90)),
            'received_at' => now()->subMinutes(rand(0, 60)),
        ];
    }
}
