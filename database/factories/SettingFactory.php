<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(),
            'value' => fake()->word(),
            'type' => fake()->randomElement([
                Setting::TYPE_STRING,
                Setting::TYPE_INTEGER,
                Setting::TYPE_BOOLEAN,
                Setting::TYPE_JSON,
                Setting::TYPE_TEXT,
            ]),
            'group' => fake()->randomElement([
                Setting::GROUP_GENERAL,
                Setting::GROUP_SECURITY,
                Setting::GROUP_AI,
                Setting::GROUP_NOTIFICATIONS,
                Setting::GROUP_INTEGRATIONS,
                Setting::GROUP_UI,
            ]),
            'is_public' => fake()->boolean(),
            'description' => fake()->sentence(),
        ];
    }
}
