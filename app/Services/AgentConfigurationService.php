<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class AgentConfigurationService
{
    protected array $defaultConfig = [
        'max_execution_time' => 300,
        'retry_count' => 3,
        'retry_delay' => 60,
        'timeout' => 30,
        'memory_limit' => '128M',
        'log_level' => 'info',
        'enable_monitoring' => true,
        'enable_audit' => true,
    ];

    public function load(Agent $agent): array
    {
        $agentSettings = $agent->settings ?? [];
        $globalSettings = $this->getGlobalAgentSettings();

        return array_merge($this->defaultConfig, $globalSettings, $agentSettings);
    }

    public function get(Agent $agent, string $key, mixed $default = null): mixed
    {
        $config = $this->load($agent);
        return Arr::get($config, $key, $default);
    }

    public function set(Agent $agent, string $key, mixed $value): Agent
    {
        $settings = $agent->settings ?? [];
        Arr::set($settings, $key, $value);
        $agent->update(['settings' => $settings]);
        Log::info("Agent config updated: {$agent->name} - {$key} = " . json_encode($value));
        return $agent;
    }

    public function update(Agent $agent, array $config): Agent
    {
        $currentSettings = $agent->settings ?? [];
        $merged = array_merge($currentSettings, $config);
        $agent->update(['settings' => $merged]);
        Log::info("Agent config bulk updated: {$agent->name}");
        return $agent;
    }

    public function reset(Agent $agent): Agent
    {
        $agent->update(['settings' => $this->defaultConfig]);
        Log::info("Agent config reset to defaults: {$agent->name}");
        return $agent;
    }

    public function validate(Agent $agent, array $rules = []): array
    {
        $config = $this->load($agent);
        $errors = [];

        foreach ($rules as $key => $rule) {
            $value = Arr::get($config, $key);
            if ($rule === 'required' && is_null($value)) {
                $errors[] = "Missing required config: {$key}";
            }
            if ($rule === 'integer' && !is_int($value)) {
                $errors[] = "Config {$key} must be integer";
            }
            if ($rule === 'boolean' && !is_bool($value)) {
                $errors[] = "Config {$key} must be boolean";
            }
        }

        return $errors;
    }

    protected function getGlobalAgentSettings(): array
    {
        try {
            $settings = Setting::where('key', 'like', 'agent.%')->get();
            $result = [];
            foreach ($settings as $setting) {
                $configKey = str_replace('agent.', '', $setting->key);
                $result[$configKey] = $setting->value;
            }
            return $result;
        } catch (\Throwable $e) {
            Log::warning('Failed to load global agent settings: ' . $e->getMessage());
            return [];
        }
    }

    public function getDefaultConfig(): array
    {
        return $this->defaultConfig;
    }
}
