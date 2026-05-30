<?php

namespace Tests\Feature;

use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_returns_logs(): void
    {
        Log::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/logs');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'pagination']);
    }

    public function test_index_filters_by_level(): void
    {
        Log::factory()->count(2)->create(['level' => Log::LEVEL_INFO]);
        Log::factory()->count(3)->create(['level' => Log::LEVEL_ERROR]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/logs?level=error');

        $response->assertOk();
        $this->assertEquals(3, $response->json('pagination.total'));
    }

    public function test_index_filters_by_channel(): void
    {
        Log::factory()->count(2)->create(['channel' => 'auth']);
        Log::factory()->count(3)->create(['channel' => 'api']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/logs?channel=auth');

        $response->assertOk();
        $this->assertEquals(2, $response->json('pagination.total'));
    }

    public function test_show_returns_log(): void
    {
        $log = Log::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/logs/{$log->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $log->id);
    }

    public function test_show_returns_404_for_missing_log(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/logs/99999');

        $response->assertNotFound();
    }

    public function test_destroy_deletes_log(): void
    {
        $log = Log::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/logs/{$log->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('logs', ['id' => $log->id]);
    }

    public function test_stats_returns_statistics(): void
    {
        Log::factory()->count(5)->create(['level' => Log::LEVEL_INFO]);
        Log::factory()->count(3)->create(['level' => Log::LEVEL_ERROR]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/logs/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['total', 'by_level', 'by_channel'],
            ]);

        $this->assertEquals(8, $response->json('data.total'));
    }

    public function test_levels_returns_available_levels(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/logs/levels');

        $response->assertOk()
            ->assertJsonCount(8, 'data');
    }

    public function test_channels_returns_available_channels(): void
    {
        Log::factory()->create(['channel' => 'auth']);
        Log::factory()->create(['channel' => 'api']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/logs/channels');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_categories_alias_returns_channels(): void
    {
        Log::factory()->create(['channel' => 'auth']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/logs/categories');

        $response->assertOk();
    }

    public function test_errors_returns_error_logs(): void
    {
        Log::factory()->count(3)->create(['level' => Log::LEVEL_ERROR]);
        Log::factory()->count(2)->create(['level' => Log::LEVEL_CRITICAL]);
        Log::factory()->count(5)->create(['level' => Log::LEVEL_INFO]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/logs/errors');

        $response->assertOk();
        $this->assertEquals(5, $response->json('pagination.total'));
    }

    public function test_clear_removes_old_logs(): void
    {
        // Create an old log (30 days ago)
        $oldLog = Log::factory()->create();
        $oldLog->update(['created_at' => now()->subDays(30)]);

        // Create a recent log
        $recentLog = Log::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/logs/clear', ['older_than_days' => 7]);

        $response->assertOk();
        $this->assertDatabaseMissing('logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('logs', ['id' => $recentLog->id]);
    }

    public function test_clear_without_days_parameter_clears_all(): void
    {
        Log::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/logs/clear');

        $response->assertOk();
        $this->assertEquals(0, Log::count());
    }

    public function test_unauthenticated_user_cannot_access_logs(): void
    {
        $response = $this->getJson('/api/v1/logs');

        $response->assertUnauthorized();
    }
}
