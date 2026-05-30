<?php

namespace App\Services;

use App\Models\AgentTask;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Log;

class TaskLogService
{
    protected array $logs = [];
    protected int $maxInMemoryLogs = 1000;

    public function log(AgentTask $task, string $level, string $message, array $context = []): void
    {
        $logEntry = [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        $this->logs[] = $logEntry;

        if (count($this->logs) > $this->maxInMemoryLogs) {
            array_shift($this->logs);
        }

        Log::log($level, "[Task {$task->id}] {$message}", $context);

        $this->persistLog($task, $level, $message, $context);
    }

    protected function persistLog(AgentTask $task, string $level, string $message, array $context = []): void
    {
        try {
            SystemLog::create([
                'level' => $level,
                'message' => $message,
                'context' => array_merge($context, [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                ]),
                'source' => 'task',
            ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to persist task log: " . $e->getMessage());
        }
    }

    public function info(AgentTask $task, string $message, array $context = []): void
    {
        $this->log($task, 'info', $message, $context);
    }

    public function warning(AgentTask $task, string $message, array $context = []): void
    {
        $this->log($task, 'warning', $message, $context);
    }

    public function error(AgentTask $task, string $message, array $context = []): void
    {
        $this->log($task, 'error', $message, $context);
    }

    public function debug(AgentTask $task, string $message, array $context = []): void
    {
        $this->log($task, 'debug', $message, $context);
    }

    public function getLogs(int $taskId, int $limit = 100): array
    {
        return array_filter($this->logs, fn($log) => $log['task_id'] === $taskId);
    }

    public function getLogsByLevel(string $level, int $limit = 100): array
    {
        return array_filter($this->logs, fn($log) => $log['level'] === $level);
    }

    public function getRecentLogs(int $limit = 100): array
    {
        return array_slice($this->logs, -$limit);
    }

    public function clearLogs(int $taskId = null): void
    {
        if ($taskId) {
            $this->logs = array_filter($this->logs, fn($log) => $log['task_id'] !== $taskId);
        } else {
            $this->logs = [];
        }
    }

    public function getStats(): array
    {
        $levels = [
            'emergency' => 0,
            'alert' => 0,
            'critical' => 0,
            'error' => 0,
            'warning' => 0,
            'notice' => 0,
            'info' => 0,
            'debug' => 0,
        ];

        foreach ($this->logs as $log) {
            $level = $log['level'];
            if (isset($levels[$level])) {
                $levels[$level]++;
            }
        }

        return [
            'total' => count($this->logs),
            'by_level' => $levels,
            'max_capacity' => $this->maxInMemoryLogs,
        ];
    }
}
