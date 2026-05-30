<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\AiModelsHub\IntentRoutingEngine;
use App\Models\AIProvider;
use App\Models\AIModel;
use App\Models\IntentRouting;

class IntentRoutingEngineTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_resolves_intent_to_provider_and_model()
    {
        $provider = AIProvider::factory()->create();
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
        ]);

        $intent = IntentRouting::factory()->create([
            'intent_name' => 'test-intent',
            'default_provider_id' => $provider->id,
            'default_model_id' => $model->id,
        ]);

        $engine = new IntentRoutingEngine();

        $result = $engine->resolveIntent('test-intent');

        $this->assertNotNull($result);
        $this->assertEquals($provider->id, $result['provider_id']);
        $this->assertEquals($model->id, $result['model_id']);
    }

    /** @test */
    public function it_returns_null_for_unknown_intent()
    {
        $engine = new IntentRoutingEngine();

        $result = $engine->resolveIntent('unknown-intent');

        $this->assertNull($result);
    }

    /** @test */
    public function it_gets_fallback_options()
    {
        $provider = AIProvider::factory()->create();
        $fallbackProvider = AIProvider::factory()->create();
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
        ]);
        $fallbackModel = AIModel::factory()->create([
            'provider_id' => $fallbackProvider->id,
        ]);

        $intent = IntentRouting::factory()->create([
            'intent_name' => 'test-intent',
            'default_provider_id' => $provider->id,
            'default_model_id' => $model->id,
            'fallback_provider_id' => $fallbackProvider->id,
            'fallback_model_id' => $fallbackModel->id,
        ]);

        $engine = new IntentRoutingEngine();

        $result = $engine->getFallbackOptions('test-intent');

        $this->assertNotNull($result);
        $this->assertEquals($fallbackProvider->id, $result['provider_id']);
        $this->assertEquals($fallbackModel->id, $result['model_id']);
    }

    /** @test */
    public function it_creates_or_updates_intent_routing()
    {
        $provider = AIProvider::factory()->create();
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
        ]);

        $engine = new IntentRoutingEngine();

        $result = $engine->upsertRouting(
            'test-intent',
            $provider->id,
            $model->id
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('intent_routing', [
            'intent_name' => 'test-intent',
            'default_provider_id' => $provider->id,
            'default_model_id' => $model->id,
        ]);

        // Test update
        $provider2 = AIProvider::factory()->create();
        $model2 = AIModel::factory()->create([
            'provider_id' => $provider2->id,
        ]);

        $result = $engine->upsertRouting(
            'test-intent',
            $provider2->id,
            $model2->id
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('intent_routing', [
            'intent_name' => 'test-intent',
            'default_provider_id' => $provider2->id,
            'default_model_id' => $model2->id,
        ]);
        $this->assertDatabaseCount('intent_routing', 1);
    }

    /** @test */
    public function it_deletes_intent_routing()
    {
        $provider = AIProvider::factory()->create();
        $model = AIModel::factory()->create([
            'provider_id' => $provider->id,
        ]);

        $intent = IntentRouting::factory()->create([
            'intent_name' => 'test-intent',
            'default_provider_id' => $provider->id,
            'default_model_id' => $model->id,
        ]);

        $engine = new IntentRoutingEngine();

        $result = $engine->deleteRouting('test-intent');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('intent_routing', [
            'intent_name' => 'test-intent',
        ]);
    }
}