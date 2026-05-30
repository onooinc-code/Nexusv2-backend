<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'phone' => fake()->unique()->phoneNumber(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'type' => 'contact',
            'title' => fake()->jobTitle(),
            'company' => fake()->company(),
            'avatar_url' => fake()->imageUrl(200, 200, 'people'),
            'metadata' => ['source' => 'factory'],
            'attributes' => ['language' => fake()->languageCode()],
            'is_active' => true,
            'last_seen_at' => now()->subMinutes(rand(1, 120)),
        ];
    }
}
