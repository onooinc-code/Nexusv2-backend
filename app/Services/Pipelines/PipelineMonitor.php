<?php

namespace App\Services\Pipelines;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PipelineMonitor
{
    protected string $cachePrefix = 'pipeline_metrics_';
    protected int $ttlSeconds = 3600;
    protected array $metrics = [];

    public function recordStageExecution(string $pipeline, string $stage, float $durationMs, bool $success): void
    {
        $key = $this->getMetricKey($pipeline, $stage);
        $current = Cache::get($key, [
            'count' => 0,
            'success_count' => 0,
            'failure_count' => 0,
            'total_duration_ms' => 0,
            'min_duration_ms' => null,
            'max_duration_ms' => null,
        ]);

        $current['count']++;
        if ($success) {
            $current['success_count']++;
        } else {
            $current['failure_count']++;
        }
        $current['total_duration_ms'] += $durationMs;
        $current['min_duration_ms'] = $current['min_duration_ms'] !== null
            ? min($current['min_duration_ms'], $durationMs)
            : $durationMs;
        $current['max_duration_ms'] = $current['max_duration_ms'] !== null
            ? max($current['max_duration_ms'], $durationMs)
            : $durationMs;
        $current['last_executed'] = now()->toISOString();

        Cache::put($key, $current, $this->ttlSeconds);
    }

    public function getStageMetrics(string $pipeline, string $stage): array
    {
        $key = $this->getMetricKey($pipeline, $stage);
        $metrics = Cache::get($key, [
            'count' => 0,
            'success_count' => 0,
            'failure_count' => 0,
            'total_duration_ms' => 0,
            'min_duration_ms' => null,
            'max_duration_ms' => null,
        ]);

        $metrics['pipeline'] = $pipeline;
        $metrics['stage'] = $stage;
        $metrics['avg_duration_ms'] = $metrics['count'] > 0
            ? round($metrics['total_duration_ms'] / $metrics['count'], 2)
            : 0;
        $metrics['success_rate'] = $metrics['count'] > 0
            ? round(($metrics['success_count'] / $metrics['count']) * 100, 2)
            : 0;

        return $metrics;
    }

    public function getPipelineMetrics(string $pipeline): array
    {
        $allKeys = Cache::getRedis()->keys($this->cachePrefix . $pipeline . '_*');
        $stages = [];

        foreach ($allKeys as $key) {
            $stageKey = str_replace($this->cachePrefix, '', $key);
            $parts = explode('_', $stageKey, 2);
            $stageName = $parts[1] ?? $stageKey;
            $stages[] = $this->getStageMetrics($pipeline, $stageName);
        }

        return [
            'pipeline' => $pipeline,
            'stages' => $stages,
            'total_executions' => array_sum(array_column($stages, 'count')),
        ];
    }

    public function getAllPipelineMetrics(): array
    {
        $patterns = ['context_assembly', 'memory_extraction', 'response_delivery'];
        $allMetrics = [];

        foreach ($patterns as $pipeline) {
            $allMetrics[$pipeline] = $this->getPipelineMetrics($pipeline);
        }

        return $allMetrics;
    }

    public function trace(string $pipeline, string $stage, array $data = []): void
    {
        Log::debug("[Pipeline Trace] {$pipeline}::{$stage}", $data);
    }

    protected function getMetricKey(string $pipeline, string $stage): string
    {
        $safeStage = str_replace([' ', '-'], '_', strtolower($stage));
        return $this->cachePrefix . $pipeline . '_' . $safeStage;
    }
}
