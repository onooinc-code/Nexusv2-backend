<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\RateLimiter;

/**
 * AgentRateLimiter
 *
 * Per-owner rate limiting for agent execution.
 * Uses Laravel's built-in RateLimiter backed by cache.
 */
class AgentRateLimiter
{
    /**
     * Attempt an agent execution under the rate limit.
     *
     * @throws \RuntimeException when rate limit is exceeded
     */
    public function attempt(Agent $agent): void
    {
        $key = $this->getKey($agent);
        $limit = $agent->rate_limit_per_minute ?? 60;

        $executed = RateLimiter::attempt(
            $key,
            $limit,
            fn() => true,
            60 // decay in seconds
        );

        if (!$executed) {
            $seconds = RateLimiter::availableIn($key);
            throw new \RuntimeException(
                "Agent [{$agent->name}] rate limit exceeded. Try again in {$seconds} seconds."
            );
        }
    }

    /**
     * Check if the agent is within rate limits without consuming a slot.
     */
    public function check(Agent $agent): array
    {
        $key = $this->getKey($agent);
        $limit = $agent->rate_limit_per_minute ?? 60;

        return [
            'remaining'     => RateLimiter::remaining($key, $limit),
            'limit'         => $limit,
            'available_in'  => RateLimiter::availableIn($key),
        ];
    }

    /**
     * Clear rate limit for an agent (e.g., after unquarantine).
     */
    public function clear(Agent $agent): void
    {
        RateLimiter::clear($this->getKey($agent));
    }

    protected function getKey(Agent $agent): string
    {
        $ownerId = $agent->owner_id ?? 'global';
        return "agent_exec:{$ownerId}:{$agent->id}";
    }
}
