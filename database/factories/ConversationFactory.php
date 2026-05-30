<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Contact;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'topic_id' => Topic::factory(),
            'title' => fake()->sentence(4),
            'status' => fake()->randomElement(['open', 'closed', 'pending']),
            'metadata' => ['origin' => fake()->randomElement(['whatsapp', 'api', 'agent'])],
            'last_message_at' => now()->subMinutes(rand(5, 120)),
        ];
    }
}
