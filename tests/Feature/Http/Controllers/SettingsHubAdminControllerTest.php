<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SettingsHubAdminControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $regularUser;
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->adminUser = User::factory()->create(['role' => 'super_admin']);
        $this->regularUser = User::factory()->create(['role' => 'user']);
        $this->workspace = Workspace::factory()->create();
    }

    public function test_dashboard_overview_returns_statistics(): void
    {
        // Create some test settings
        Setting::factory()->count(5)->create(['scope' => 'global']);
        Setting::factory()->count(3)->create([
            'scope' => 'workspace',
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/settings/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'statistics' => [
                        'total_settings',
                        'total_encrypted',
                        'total_public',
                        'total_private',
                        'by_group',
                        'by_scope',
                    ],
                    'health' => [
                        'credential_validation',
                        'last_health_check',
                    ],
                ],
            ]);

        $this->assertEquals(true, $response->json('success'));
    }

    public function test_dashboard_overview_requires_authorization(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/v1/settings/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_audit_trail_returns_logs(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/settings/admin/audit-trail?limit=50');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'count',
            ]);
    }

    public function test_audit_trail_filters_by_type(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/settings/admin/audit-trail?type=health_check');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_compliance_status_checks_critical_settings(): void
    {
        // Create required critical settings
        Setting::factory()->create(['key' => 'system.global_agent_pause']);
        Setting::factory()->create(['key' => 'system.maintenance_mode']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/settings/admin/compliance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'critical_settings',
                    'encryption',
                    'maintenance',
                ],
            ]);
    }

    public function test_multi_tenancy_status_shows_distribution(): void
    {
        Setting::factory()->count(3)->create(['scope' => 'global']);
        Setting::factory()->count(2)->create([
            'scope' => 'workspace',
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/settings/admin/multi-tenancy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'by_scope',
                    'workspace_distribution',
                    'user_distribution',
                ],
            ]);
    }

    public function test_performance_metrics_returns_stats(): void
    {
        Setting::factory()->count(10)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/settings/admin/performance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_settings',
                    'recent_changes',
                    'by_type',
                    'cache_efficiency',
                ],
            ]);
    }

    public function test_export_settings_as_json(): void
    {
        Setting::factory()->count(5)->create();

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/settings/admin/export', [
                'format' => 'json',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'format' => 'json',
            ])
            ->assertJsonStructure([
                'data',
                'count',
            ]);
    }

    public function test_export_settings_as_csv(): void
    {
        Setting::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/settings/admin/export', [
                'format' => 'csv',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'format' => 'csv',
            ]);

        // Verify CSV format
        $data = $response->json('data');
        $this->assertStringContainsString('key,type,group,scope', $data);
    }

    public function test_export_settings_by_scope(): void
    {
        Setting::factory()->count(2)->create(['scope' => 'global']);
        Setting::factory()->count(3)->create(['scope' => 'workspace']);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/settings/admin/export', [
                'format' => 'json',
                'scope' => 'global',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(2, $response->json('count'));
    }

    public function test_export_settings_masks_encrypted_by_default(): void
    {
        Setting::factory()->create([
            'key' => 'integrations.openai_key',
            'value' => 'secret-key-12345',
            'is_encrypted' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/settings/admin/export', [
                'format' => 'json',
                'include_encrypted' => false,
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('[ENCRYPTED]', $data[0]['value']);
    }

    public function test_export_settings_includes_encrypted_when_requested(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'integrations.openai_key',
            'is_encrypted' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/settings/admin/export', [
                'format' => 'json',
                'include_encrypted' => true,
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEquals('[ENCRYPTED]', $data[0]['value']);
    }

    public function test_all_admin_routes_require_authorization(): void
    {
        $routes = [
            'GET' => [
                '/api/v1/settings/admin/dashboard',
                '/api/v1/settings/admin/audit-trail',
                '/api/v1/settings/admin/compliance',
                '/api/v1/settings/admin/multi-tenancy',
                '/api/v1/settings/admin/performance',
            ],
            'POST' => [
                '/api/v1/settings/admin/export',
            ],
        ];

        foreach ($routes['GET'] as $route) {
            $response = $this->actingAs($this->regularUser)->getJson($route);
            $this->assertEquals(403, $response->status(), "GET {$route} should be forbidden");
        }

        foreach ($routes['POST'] as $route) {
            $response = $this->actingAs($this->regularUser)->postJson($route);
            $this->assertEquals(403, $response->status(), "POST {$route} should be forbidden");
        }
    }
}
