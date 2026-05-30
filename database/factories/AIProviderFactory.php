<?php

namespace Database\Factories;

use App\Models\AIProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class AIProviderFactory extends Factory
{
    protected $model = AIProvider::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'name' => fake()->company() . ' AI',
            'base_url' => fake()->url(),
            'models_fetch_endpoint' => '/v1/models',
            'generate_endpoint' => '/v1/chat/completions',
            'auth_header_format' => 'Bearer {KEY}',
            'payload_format' => 'openai',
            'is_active' => true,
        ];
    }
}
