<?php

namespace Database\Factories;

use App\Models\ApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word() . ' key',
            'key' => Str::random(40),
            'type' => 'api',
            'permissions' => ['read', 'write'],
            'last_used_at' => now()->subDays(rand(0, 30)),
            'expires_at' => now()->addMonths(rand(1, 12)),
            'is_active' => true,
        ];
    }
}
