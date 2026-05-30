<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AiAuditTrail;

class CostAnalyticsTest extends TestCase
{
    /** @test */
    public function forecast_endpoint_returns_valid_structure()
    {
        $response = $this->getJson('/api/v1/ai/cost/forecast');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'current_spend',
                         'monthly_limit',
                         'remaining_budget',
                         'forecasted_total',
                         'daily_average',
                         'status',
                     ]
                 ]);
    }

    /** @test */
    public function forecast_status_is_healthy_when_no_budget_set()
    {
        $response = $this->getJson('/api/v1/ai/cost/forecast');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertContains($data['status'], ['healthy', 'over_budget_predicted', 'budget_exceeded']);
    }

    /** @test */
    public function set_budget_endpoint_requires_monthly_limit()
    {
        $response = $this->postJson('/api/v1/ai/cost/budget', []);
        $response->assertStatus(422);
    }

    /** @test */
    public function set_budget_endpoint_creates_budget()
    {
        $response = $this->postJson('/api/v1/ai/cost/budget', [
            'monthly_limit' => 100.00,
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    /** @test */
    public function audit_trail_endpoint_returns_list()
    {
        // Seed a few records
        AiAuditTrail::create([
            'event_type' => 'route_executed',
            'status' => 'success',
            'latency_ms' => 350,
            'fallback_triggered' => false,
            'input_tokens' => 100,
            'output_tokens' => 200,
        ]);

        $response = $this->getJson('/api/v1/ai/audit-trail');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'event_type',
                             'status',
                             'latency_ms',
                         ]
                     ]
                 ]);
    }
}
