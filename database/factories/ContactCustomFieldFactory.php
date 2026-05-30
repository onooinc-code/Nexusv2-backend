<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactCustomField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactCustomField>
 */
class ContactCustomFieldFactory extends Factory
{
    protected $model = ContactCustomField::class;

    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'field_key' => fake()->word(),
            'label' => fake()->word(),
            'value' => fake()->sentence(),
            'type' => fake()->randomElement(['string', 'number', 'boolean', 'date']),
            'metadata' => ['source' => 'factory'],
        ];
    }
}
