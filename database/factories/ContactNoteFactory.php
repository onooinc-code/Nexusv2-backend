<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactNote>
 */
class ContactNoteFactory extends Factory
{
    protected $model = ContactNote::class;

    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'user_id' => User::factory(),
            'note' => fake()->paragraph(),
            'summary' => fake()->sentence(),
            'is_pinned' => fake()->boolean(20),
            'metadata' => ['source' => 'factory'],
        ];
    }
}
