<?php

namespace Tests\Unit;

use App\Models\Log;
use App\Services\LogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LogService $logService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logService = app(LogService::class);
    }

    public function test_log_creates_record(): void
    {
        $log = $this->logService->info('Test message', ['channel' => 'test']);

        $this->assertInstanceOf(Log::class, $log);
        $this->assertEquals('info', $log->level);
        $this->assertEquals('Test message', $log->message);
        $this->assertEquals('test', $log->channel);
    }

    public function test_log_with_all_levels(): void
    {
        $levels = [
            'debug', 'info', 'notice', 'warning',
            'error', 'critical', 'alert', 'emergency',
        ];

        foreach ($levels as $level) {
            $log = $this->logService->log($level, "Test {$level} message", ['channel' => 'test']);
            $this->assertEquals($level, $log->level);
            $this->assertEquals("Test {$level} message", $log->message);
        }
    }

    public function test_log_with_related_entity(): void
    {
        $log = $this->logService->info('Related log', [
            'channel' => 'test',
            'related_id' => 1,
            'related_type' => 'App\Models\User',
        ]);

        $this->assertEquals(1, $log->related_id);
        $this->assertEquals('App\Models\User', $log->related_type);
    }

    public function test_log_with_context(): void
    {
        $context = ['key' => 'value', 'nested' => ['a' => 1]];
        $log = $this->logService->info('Context test', [
            'channel' => 'test',
            'context' => $context,
        ]);

        $this->assertEquals($context, $log->context);
    }

    public function test_get_stats(): void
    {
        Log::factory()->count(5)->create(['level' => Log::LEVEL_INFO]);
        Log::factory()->count(3)->create(['level' => Log::LEVEL_ERROR]);

        $stats = $this->logService->getStats();

        $this->assertEquals(8, $stats['total']);
        $this->assertEquals(5, $stats['by_level'][Log::LEVEL_INFO]);
        $this->assertEquals(3, $stats['by_level'][Log::LEVEL_ERROR]);
    }

    public function test_get_levels(): void
    {
        $levels = $this->logService->getLevels();

        $this->assertCount(8, $levels);
        $this->assertEquals('debug', $levels[0]['value']);
        $this->assertEquals('emergency', $levels[7]['value']);
    }

    public function test_get_channels(): void
    {
        Log::factory()->create(['channel' => 'auth']);
        Log::factory()->create(['channel' => 'api']);

        $channels = $this->logService->getChannels();

        $this->assertContains('auth', $channels);
        $this->assertContains('api', $channels);
    }

    public function test_get_errors(): void
    {
        Log::factory()->count(3)->create(['level' => Log::LEVEL_ERROR]);
        Log::factory()->count(2)->create(['level' => Log::LEVEL_CRITICAL]);
        Log::factory()->count(5)->create(['level' => Log::LEVEL_INFO]);

        $errors = $this->logService->getErrors();

        $this->assertEquals(5, $errors->count());
    }

    public function test_get_by_id(): void
    {
        $log = Log::factory()->create();

        $found = $this->logService->getById($log->id);

        $this->assertEquals($log->id, $found->id);
    }

    public function test_delete(): void
    {
        $log = Log::factory()->create();

        $result = $this->logService->delete($log->id);

        $this->assertTrue($result);
        $this->assertNull(Log::find($log->id));
    }

    public function test_delete_nonexistent_returns_false(): void
    {
        $result = $this->logService->delete(99999);

        $this->assertFalse($result);
    }

    public function test_by_channel(): void
    {
        Log::factory()->count(3)->create(['channel' => 'auth']);
        Log::factory()->count(2)->create(['channel' => 'api']);

        $authLogs = $this->logService->byChannel('auth');

        $this->assertEquals(3, $authLogs->count());
    }

    public function test_clear_old_logs(): void
    {
        // Create an old log (30 days ago)
        $oldLog = Log::factory()->create();
        $oldLog->update(['created_at' => now()->subDays(30)]);

        // Create a recent log
        $recentLog = Log::factory()->create();

        $deleted = $this->logService->clearOldLogs(7);

        $this->assertEquals(1, $deleted);
        $this->assertNull(Log::find($oldLog->id));
        $this->assertNotNull(Log::find($recentLog->id));
    }

    public function test_log_related_helper(): void
    {
        $log = $this->logService->logRelated(
            Log::LEVEL_INFO,
            'Related entity log',
            'test',
            'App\Models\User',
            1,
            ['action' => 'update']
        );

        $this->assertEquals(1, $log->related_id);
        $this->assertEquals('App\Models\User', $log->related_type);
        $this->assertEquals('Related entity log', $log->message);
    }
}
