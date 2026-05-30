<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Workflow;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    // ─── Response Time Tests ─────────────────────────────────────────────

    public function test_agent_index_response_time_under_200ms(): void
    {
        Agent::factory()->count(10)->create();

        $start = microtime(true);
        $response = $this->getJson('/api/v1/agents');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $duration, "Agent index took {$duration}ms");
    }

    public function test_contact_index_response_time_under_200ms(): void
    {
        Contact::factory()->count(10)->create();

        $start = microtime(true);
        $response = $this->getJson('/api/v1/contacts');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $duration, "Contact index took {$duration}ms");
    }

    public function test_workflow_index_response_time_under_200ms(): void
    {
        Workflow::factory()->count(10)->create();

        $start = microtime(true);
        $response = $this->getJson('/api/v1/workflows');
        $duration = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $duration, "Workflow index took {$duration}ms");
    }

    // ─── Database Query Performance ──────────────────────────────────────

    public function test_database_queries_use_eager_loading(): void
    {
        $agent = Agent::factory()->create();
        \App\Models\AgentTool::factory()->count(3)->create(['agent_id' => $agent->id]);
        \App\Models\AgentSkill::factory()->count(3)->create(['agent_id' => $agent->id]);

        $response = $this->getJson("/api/v1/agents/{$agent->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['tools', 'skills']]);
    }

    // ─── Pagination Performance ──────────────────────────────────────────

    public function test_pagination_limits_results_per_page(): void
    {
        Agent::factory()->count(100)->create();

        $response = $this->getJson('/api/v1/agents?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data');
    }

    // ─── Memory Usage ────────────────────────────────────────────────────

    public function test_memory_usage_stays_within_reasonable_limits(): void
    {
        Contact::factory()->count(50)->create();
        Agent::factory()->count(20)->create();
        Workflow::factory()->count(10)->create();

        $startMemory = memory_get_usage();

        $this->getJson('/api/v1/contacts');
        $this->getJson('/api/v1/agents');
        $this->getJson('/api/v1/workflows');

        $endMemory = memory_get_usage();
        $memoryIncrease = $endMemory - $startMemory;

        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, "Memory usage increased by " . round($memoryIncrease / 1024 / 1024, 2) . "MB");
    }
}
