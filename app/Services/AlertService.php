<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * AlertService
 *
 * Manages alert rules and triggers for failures and security events.
 * Evaluates conditions against recent logs and dispatches notifications.
 */
class AlertService
{
    /**
     * The cache key prefix for alert state.
     *
     * @var string
     */
    protected string $cachePrefix = 'alerts.';

    /**
     * The default alert rules.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $defaultRules = [
        [
            'id' => 'high_error_rate',
            'name' => 'High Error Rate',
            'description' => 'Alert when error rate exceeds threshold',
            'condition' => 'error_rate',
            'threshold' => 10,
            'window_minutes' => 5,
            'level' => Log::LEVEL_CRITICAL,
            'category' => Log::CATEGORY_SYSTEM,
            'enabled' => true,
        ],
        [
            'id' => 'security_failure',
            'name' => 'Security Failure',
            'description' => 'Alert on authentication or authorization failures',
            'condition' => 'category_level',
            'category' => Log::CATEGORY_SECURITY,
            'level' => Log::LEVEL_WARNING,
            'window_minutes' => 1,
            'enabled' => true,
        ],
        [
            'id' => 'ai_provider_down',
            'name' => 'AI Provider Down',
            'description' => 'Alert when AI provider errors spike',
            'condition' => 'category_level',
            'category' => Log::CATEGORY_AI,
            'level' => Log::LEVEL_ERROR,
            'window_minutes' => 3,
            'enabled' => true,
        ],
        [
            'id' => 'workflow_failure',
            'name' => 'Workflow Failure',
            'description' => 'Alert on workflow execution failures',
            'condition' => 'category_level',
            'category' => Log::CATEGORY_WORKFLOW,
            'level' => Log::LEVEL_ERROR,
            'window_minutes' => 5,
            'enabled' => true,
        ],
    ];

    /**
     * Evaluate all alert rules against recent logs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function evaluateAll(): array
    {
        $rules = $this->getRules();
        $triggered = [];

        foreach ($rules as $rule) {
            if (! $rule['enabled']) {
                continue;
            }

            if ($this->evaluateRule($rule)) {
                $triggered[] = $rule;
                $this->recordAlert($rule);
            }
        }

        return $triggered;
    }

    /**
     * Evaluate a single alert rule.
     *
     * @param array<string, mixed> $rule
     * @return bool
     */
    protected function evaluateRule(array $rule): bool
    {
        $window = $rule['window_minutes'] ?? 5;
        $since = now()->subMinutes($window);

        return match ($rule['condition']) {
            'error_rate' => $this->checkErrorRate($since, (int) $rule['threshold']),
            'category_level' => $this->checkCategoryLevel($since, $rule['category'], $rule['level']),
            default => false,
        };
    }

    /**
     * Check if error rate exceeds threshold.
     *
     * @param \Carbon\Carbon $since
     * @param int $threshold
     * @return bool
     */
    protected function checkErrorRate(\Carbon\Carbon $since, int $threshold): bool
    {
        $total = Log::where('created_at', '>=', $since)->count();
        if ($total === 0) {
            return false;
        }

        $errors = Log::where('created_at', '>=', $since)
            ->byLevel([
                Log::LEVEL_ERROR,
                Log::LEVEL_CRITICAL,
                Log::LEVEL_ALERT,
                Log::LEVEL_EMERGENCY,
            ])
            ->count();

        $rate = ($errors / $total) * 100;
        return $rate >= $threshold;
    }

    /**
     * Check if logs in a category at a level exceed threshold.
     *
     * @param \Carbon\Carbon $since
     * @param string $category
     * @param string $level
     * @return bool
     */
    protected function checkCategoryLevel(\Carbon\Carbon $since, string $category, string $level): bool
    {
        return Log::where('created_at', '>=', $since)
            ->byCategory($category)
            ->byLevel($level)
            ->exists();
    }

    /**
     * Record that an alert was triggered.
     *
     * @param array<string, mixed> $rule
     * @return void
     */
    protected function recordAlert(array $rule): void
    {
        $key = $this->cachePrefix . $rule['id'];
        Cache::put($key, [
            'rule_id' => $rule['id'],
            'name' => $rule['name'],
            'triggered_at' => now()->toIso8601String(),
            'level' => $rule['level'],
            'category' => $rule['category'],
        ], now()->addHour());
    }

    /**
     * Get all alert rules.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRules(): array
    {
        return Config::get('alerts.rules', $this->defaultRules);
    }

    /**
     * Get a specific alert rule by ID.
     *
     * @param string $ruleId
     * @return array<string, mixed>|null
     */
    public function getRule(string $ruleId): ?array
    {
        foreach ($this->getRules() as $rule) {
            if ($rule['id'] === $ruleId) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * Add a new alert rule.
     *
     * @param array<string, mixed> $rule
     * @return array<string, mixed>
     */
    public function addRule(array $rule): array
    {
        $rules = $this->getRules();
        $rule['id'] = $rule['id'] ?? uniqid('rule_');
        $rule['enabled'] = $rule['enabled'] ?? true;
        $rules[] = $rule;

        Config::set('alerts.rules', $rules);
        return $rule;
    }

    /**
     * Update an existing alert rule.
     *
     * @param string $ruleId
     * @param array<string, mixed> $updates
     * @return array<string, mixed>|null
     */
    public function updateRule(string $ruleId, array $updates): ?array
    {
        $rules = $this->getRules();
        foreach ($rules as &$rule) {
            if ($rule['id'] === $ruleId) {
                $rule = Arr::replace($rule, $updates);
                Config::set('alerts.rules', $rules);
                return $rule;
            }
        }
        return null;
    }

    /**
     * Delete an alert rule.
     *
     * @param string $ruleId
     * @return bool
     */
    public function deleteRule(string $ruleId): bool
    {
        $rules = $this->getRules();
        $filtered = array_filter($rules, fn ($r) => $r['id'] !== $ruleId);

        if (count($filtered) === count($rules)) {
            return false;
        }

        Config::set('alerts.rules', array_values($filtered));
        Cache::forget($this->cachePrefix . $ruleId);
        return true;
    }

    /**
     * Get recently triggered alerts.
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function getRecentAlerts(int $limit = 50): array
    {
        $keys = Cache::get($this->cachePrefix . '*', []);
        // Collect all alert keys from cache
        $allKeys = collect(Cache::getRedis()->keys($this->cachePrefix . '*'))
            ->map(fn ($k) => str_replace($this->cachePrefix, '', $k))
            ->values()
            ->all();

        $alerts = [];
        foreach ($allKeys as $key) {
            $alert = Cache::get($this->cachePrefix . $key);
            if ($alert) {
                $alerts[] = $alert;
            }
        }

        usort($alerts, fn ($a, $b) => strcmp($b['triggered_at'], $a['triggered_at']));
        return array_slice($alerts, 0, $limit);
    }
}
