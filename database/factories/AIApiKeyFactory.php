<?php

namespace Database\Factories;

use App\Models\AIApiKey;
use App\Models\AIProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class AIApiKeyFactory extends Factory
{
    protected $model = AIApiKey::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'provider_id' => AIProvider::factory(),
            'key_hash' => encrypt('sk-' . fake()->password(24)),
            'name' => 'Test API Key',
            'is_active' => true,
        ];
    }
}
