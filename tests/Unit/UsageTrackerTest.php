<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\AiModelsHub\UsageTracker;
use App\Models\AIProvider;
use App\Models\AIModel;
use Illuminate\Support\Facades\Cache;

class UsageTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // Clear cache before each test
    }

    /** @test */
    public function it_tracks_usage_for_a_provider()
    {
        $provider = AIProvider::factory()->create();
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
            'input_cost_per_m' => 0.001,
            'output_cost_per_m' => 0.002,
        ]);

        $tracker = new UsageTracker();

        $tracker->trackUsage(
            $provider->id,
            $model->id,
            100, // input tokens
            50   // output tokens
        );

        // Check that usage was tracked in cache
        $key = "usage:{$provider->id}:{$model->id}";
        $cachedData = Cache::get($key);

        $this->assertNotNull($cachedData);
        $this->assertEquals(100, $cachedData['input_tokens']);
        $this->assertEquals(50, $cachedData['output_tokens']);
        $this->assertEquals(150, $cachedData['total_tokens']);
        $this->assertEquals(0.0002, $cachedData['total_cost']); // (100/1000)*0.001 + (50/1000)*0.002
    }

    /** @test */
    public function it_gets_usage_stats_for_a_provider()
    {
        $provider = AIProvider::factory()->create();
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
            'input_cost_per_m' => 0.001,
            'output_cost_per_m' => 0.002,
        ]);

        $tracker = new UsageTracker();

        // Track multiple usage events
        $tracker->trackUsage($provider->id, $model->id, 100, 50);
        $tracker->trackUsage($provider->id, $model->id, 50, 25);

        $stats = $tracker->getUsageStats($provider->id, $model->id);

        $this->assertEquals(150, $stats['input_tokens']);
        $this->assertEquals(75, $stats['output_tokens']);
        $this->assertEquals(225, $stats['total_tokens']);
        $this->assertEquals(0.0003, $stats['total_cost']);
    }

    /** @test */
    public function it_gets_usage_stats_for_all_models_of_a_provider()
    {
        $provider = AIProvider::factory()->create();
        $model1 = AIModel::factory()->create([
            'provider_id' => $provider->id,
            'input_cost_per_m' => 0.001,
            'output_cost_per_m' => 0.002,
        ]);
        $model2 = AIModel::factory()->create([
            'provider_id' => $provider->id,
            'input_cost_per_m' => 0.002,
            'output_cost_per_m' => 0.004,
        ]);

        $tracker = new UsageTracker();

        // Track usage for both models
        $tracker->trackUsage($provider->id, $model1->id, 100, 50);
        $tracker->trackUsage($provider->id, $model2->id, 50, 25);

        $stats = $tracker->getUsageStats($provider->id);

        $this->assertArrayHasKey($model1->id->toString(), $stats);
        $this->assertArrayHasKey($model2->id->toString(), $stats);
        
        $this->assertEquals(100, $stats[$model1->id->toString()]['input_tokens']);
        $this->assertEquals(50, $stats[$model1->id->toString()]['output_tokens']);
        $this->assertEquals(50, $stats[$model2->id->toString()]['input_tokens']);
        $this->assertEquals(25, $stats[$model2->id->toString()]['output_tokens']);
    }

    /** @test */
    public function it_calculates_cost_correctly()
    {
        $provider = AIProvider::factory()->create();
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
            'input_cost_per_m' => 0.001, // $0.001 per 1k tokens = $0.000001 per token
            'output_cost_per_m' => 0.002, // $0.002 per 1k tokens = $0.000002 per token
        ]);

        $tracker = new UsageTracker();

        $cost = $tracker->calculateCost($model->id, 1000, 500);

        // Expected: (1000 * 0.000001) + (500 * 0.000002) = 0.001 + 0.001 = 0.002
        $this->assertEquals(0.002, $cost);
    }

    /** @test */
    public function it_resets_usage_stats()
    {
        $provider = AIProvider::factory()->create();
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
        ]);

        $tracker = new UsageTracker();

        // Track some usage
        $tracker->trackUsage($provider->id, $model->id, 100, 50);

        // Verify usage exists
        $stats = $tracker->getUsageStats($provider->id, $model->id);
        $this->assertEquals(100, $stats['input_tokens']);

        // Reset usage
        $tracker->resetUsage($provider->id, $model->id);

        // Verify usage is reset
        $stats = $tracker->getUsageStats($provider->id, $model->id);
        $this->assertEquals(0, $stats['input_tokens']);
        $this->assertEquals(0, $stats['output_tokens']);
        $this->assertEquals(0, $stats['total_tokens']);
        $this->assertEquals(0, $stats['total_cost']);
    }
}