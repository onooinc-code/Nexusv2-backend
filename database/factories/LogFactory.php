<?php

namespace Database\Factories;

use App\Models\Log;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Log>
 */
class LogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Log>
     */
    protected $model = Log::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level' => $this->faker->randomElement([
                Log::LEVEL_DEBUG,
                Log::LEVEL_INFO,
                Log::LEVEL_NOTICE,
                Log::LEVEL_WARNING,
                Log::LEVEL_ERROR,
                Log::LEVEL_CRITICAL,
                Log::LEVEL_ALERT,
                Log::LEVEL_EMERGENCY,
            ]),
            'channel' => $this->faker->randomElement([
                Log::CHANNEL_AUTH,
                Log::CHANNEL_SECURITY,
                Log::CHANNEL_API,
                Log::CHANNEL_WORKFLOW,
                Log::CHANNEL_AGENT,
                Log::CHANNEL_AI,
                Log::CHANNEL_SYSTEM,
                Log::CHANNEL_DATABASE,
                Log::CHANNEL_CACHE,
                Log::CHANNEL_QUEUE,
            ]),
            'message' => $this->faker->sentence(),
            'context' => $this->faker->optional()->words(3, true),
            'type' => $this->faker->randomElement([
                Log::TYPE_APPLICATION,
                Log::TYPE_SYSTEM,
                Log::TYPE_SECURITY,
            ]),
            'user_id' => null,
            'related_id' => null,
            'related_type' => null,
        ];
    }

    /**
     * Set the log level.
     *
     * @param string $level
     * @return static
     */
    public function level(string $level): static
    {
        return $this->state(fn(array $attributes) => ['level' => $level]);
    }

    /**
     * Set the log channel.
     *
     * @param string $channel
     * @return static
     */
    public function channel(string $channel): static
    {
        return $this->state(fn(array $attributes) => ['channel' => $channel]);
    }

    /**
     * Set the log type.
     *
     * @param string $type
     * @return static
     */
    public function type(string $type): static
    {
        return $this->state(fn(array $attributes) => ['type' => $type]);
    }

    /**
     * Set the related entity.
     *
     * @param int $relatedId
     * @param string $relatedType
     * @return static
     */
    public function related(int $relatedId, string $relatedType): static
    {
        return $this->state(fn(array $attributes) => [
            'related_id' => $relatedId,
            'related_type' => $relatedType,
        ]);
    }

    /**
     * Set the user.
     *
     * @param int $userId
     * @return static
     */
    public function forUser(int $userId): static
    {
        return $this->state(fn(array $attributes) => ['user_id' => $userId]);
    }

    /**
     * Create an info-level log.
     *
     * @return static
     */
    public function info(): static
    {
        return $this->level(Log::LEVEL_INFO);
    }

    /**
     * Create a warning-level log.
     *
     * @return static
     */
    public function warning(): static
    {
        return $this->level(Log::LEVEL_WARNING);
    }

    /**
     * Create an error-level log.
     *
     * @return static
     */
    public function error(): static
    {
        return $this->level(Log::LEVEL_ERROR);
    }
}
