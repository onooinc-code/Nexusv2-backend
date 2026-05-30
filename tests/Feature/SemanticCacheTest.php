<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\AiModelsHub\SemanticCache;
use Illuminate\Support\Facades\Cache;

class SemanticCacheTest extends TestCase
{
    protected SemanticCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = app(SemanticCache::class);
        Cache::flush(); // Start clean
    }

    /** @test */
    public function it_returns_null_for_cache_miss()
    {
        $result = $this->cache->get('test_intent', 'What is the weather today?', []);
        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_cached_result_after_put()
    {
        $payload = ['success' => true, 'data' => ['text' => 'It is sunny.']];
        $this->cache->put('test_intent', 'What is the weather today?', [], $payload);

        $result = $this->cache->get('test_intent', 'What is the weather today?', []);
        $this->assertNotNull($result);
        $this->assertEquals($payload, $result);
    }

    /** @test */
    public function it_distinguishes_different_prompts()
    {
        $payload1 = ['success' => true, 'data' => ['text' => 'Sunny.']];
        $payload2 = ['success' => true, 'data' => ['text' => 'Rainy.']];

        $this->cache->put('test_intent', 'Weather today?', [], $payload1);
        $this->cache->put('test_intent', 'Weather tomorrow?', [], $payload2);

        $r1 = $this->cache->get('test_intent', 'Weather today?', []);
        $r2 = $this->cache->get('test_intent', 'Weather tomorrow?', []);

        $this->assertEquals('Sunny.', $r1['data']['text']);
        $this->assertEquals('Rainy.', $r2['data']['text']);
    }

    /** @test */
    public function it_returns_same_cache_key_for_identical_params_different_order()
    {
        $payload = ['success' => true, 'data' => ['text' => 'Response.']];

        $this->cache->put('test_intent', 'Hello', ['b' => 2, 'a' => 1], $payload);
        $result = $this->cache->get('test_intent', 'Hello', ['a' => 1, 'b' => 2]);

        $this->assertNotNull($result);
        $this->assertEquals($payload, $result);
    }
}
