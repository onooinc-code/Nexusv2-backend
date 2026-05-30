<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log as LaravelLog;

/**
 * LogService
 *
 * Centralized application logging service.
 * Wraps Laravel's logging with structured context,
 * categorization, and database persistence.
 */
class LogService
{
    /**
     * The default log channel to use.
     *
     * @var string
     */
    protected string $channel;

    /**
     * Create a new LogService instance.
     *
     * @param string|null $channel
     * @return void
     */
    public function __construct(?string $channel = null)
    {
        $this->channel = $channel ?? config('logging.default', 'stack');
    }

    /**
     * Log a debug message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return Log
     */
    public function debug(string $message, array $context = []): Log
    {
        return $this->log(Log::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return Log
     */
    public function info(string $message, array $context = []): Log
    {
        return $this->log(Log::LEVEL_INFO, $message, $context);
    }

    /**
     * Log a notice message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return Log
     */
    public function notice(string $message, array $context = []): Log
    {
        return $this->log(Log::LEVEL_NOTICE, $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return Log
     */
    public function warning(string $message, array $context = []): Log
    {
        return $this->log(Log::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return Log
     */
    public function error(string $message, array $context = []): Log
    {
        return $this->log(Log::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log a critical message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return Log
     */
    public function critical(string $message, array $context = []): Log
    {
        return $this->log(Log::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Log an alert message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return Log
     */
    public function alert(string $message, array $context = []): Log
    {
        return $this->log(Log::LEVEL_ALERT, $message, $context);
    }

    /**
     * Log an emergency message.
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return Log
     */
    public function emergency(string $message, array $context = []): Log
    {
        return $this->log(Log::LEVEL_EMERGENCY, $message, $context);
    }

    /**
     * Write a log entry at the specified level.
     *
     * @param string $level
     * @param string $message
     * @param array<string, mixed> $context
     * @return Log
     */
    public function log(string $level, string $message, array $context = []): Log
    {
        // Write to Laravel's log channels
        LaravelLog::stack([$this->channel])->log($level, $message, $context);

        // Persist to database
        $log = Log::create([
            'level' => $level,
            'channel' => $context['channel'] ?? 'app',
            'message' => $message,
            'context' => Arr::except($context, ['channel', 'type', 'user_id', 'related_id', 'related_type']),
            'type' => $context['type'] ?? Log::TYPE_APPLICATION,
            'user_id' => $context['user_id'] ?? null,
            'related_id' => $context['related_id'] ?? null,
            'related_type' => $context['related_type'] ?? null,
        ]);

        return $log;
    }

    /**
     * Log a message for a related entity.
     *
     * @param string $level
     * @param string $message
     * @param mixed $model
     * @param array<string, mixed> $context
     * @return Log
     */
    public function logRelated(string $level, string $message, $model, array $context = []): Log
    {
        $context['related_id'] = $model->getKey();
        $context['related_type'] = $model->getMorphClass();
        return $this->log($level, $message, $context);
    }

    /**
     * Get recent logs.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection<int, Log>
     */
    public function recent(int $limit = 100)
    {
        return Log::latest()->limit($limit)->get();
    }

    /**
     * Get logs by level.
     *
     * @param string|array $levels
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection<int, Log>
     */
    public function byLevel($levels, int $limit = 100)
    {
        return Log::byLevel($levels)->latest()->limit($limit)->get();
    }

    /**
     * Get logs by channel.
     *
     * @param string|array $channels
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection<int, Log>
     */
    public function byChannel($channels, int $limit = 100)
    {
        return Log::byChannel($channels)->latest()->limit($limit)->get();
    }

    /**
     * Get log statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total' => Log::count(),
            'by_level' => Log::selectRaw('level, count(*) as count')
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray(),
            'by_channel' => Log::selectRaw('channel, count(*) as count')
                ->groupBy('channel')
                ->pluck('count', 'channel')
                ->toArray(),
            'today' => Log::whereDate('created_at', today())->count(),
            'errors_today' => Log::whereDate('created_at', today())
                ->whereIn('level', [
                    Log::LEVEL_ERROR,
                    Log::LEVEL_CRITICAL,
                    Log::LEVEL_ALERT,
                    Log::LEVEL_EMERGENCY,
                ])
                ->count(),
        ];
    }

    /**
     * Get available log levels.
     *
     * @return array
     */
    public function getLevels(): array
    {
        return [
            ['value' => Log::LEVEL_DEBUG, 'label' => 'Debug'],
            ['value' => Log::LEVEL_INFO, 'label' => 'Info'],
            ['value' => Log::LEVEL_NOTICE, 'label' => 'Notice'],
            ['value' => Log::LEVEL_WARNING, 'label' => 'Warning'],
            ['value' => Log::LEVEL_ERROR, 'label' => 'Error'],
            ['value' => Log::LEVEL_CRITICAL, 'label' => 'Critical'],
            ['value' => Log::LEVEL_ALERT, 'label' => 'Alert'],
            ['value' => Log::LEVEL_EMERGENCY, 'label' => 'Emergency'],
        ];
    }

    /**
     * Get available log channels.
     *
     * @return array
     */
    public function getChannels(): array
    {
        return [
            ['value' => Log::CHANNEL_AUTH, 'label' => 'Authentication'],
            ['value' => Log::CHANNEL_SECURITY, 'label' => 'Security'],
            ['value' => Log::CHANNEL_API, 'label' => 'API'],
            ['value' => Log::CHANNEL_WORKFLOW, 'label' => 'Workflow'],
            ['value' => Log::CHANNEL_AGENT, 'label' => 'Agent'],
            ['value' => Log::CHANNEL_AI, 'label' => 'AI'],
            ['value' => Log::CHANNEL_SYSTEM, 'label' => 'System'],
            ['value' => Log::CHANNEL_DATABASE, 'label' => 'Database'],
            ['value' => Log::CHANNEL_CACHE, 'label' => 'Cache'],
            ['value' => Log::CHANNEL_QUEUE, 'label' => 'Queue'],
        ];
    }

    /**
     * Get error-level logs.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection<int, Log>
     */
    public function getErrors(int $limit = 100)
    {
        return Log::errors()->latest()->limit($limit)->get();
    }

    /**
     * Get a log by ID.
     *
     * @param int $id
     * @return Log|null
     */
    public function getById(int $id): ?Log
    {
        return Log::find($id);
    }

    /**
     * Delete a log by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $log = Log::find($id);
        if (!$log) {
            return false;
        }
        return $log->delete();
    }

    /**
     * Clear logs older than specified days.
     *
     * @param int $days
     * @return int Number of deleted records
     */
    public function clearOldLogs(int $days): int
    {
        return Log::where('created_at', '<', now()->subDays($days))->delete();
    }
}