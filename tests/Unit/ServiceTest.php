<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\AgentTool;
use App\Models\AgentSkill;
use App\Models\Setting;
use App\Services\AgentLifecycleService;
use App\Services\AgentConfigurationService;
use App\Services\AgentRegistry;
use App\Services\AgentToolRegistry;
use App\Services\AgentToolExecutor;
use App\Services\AgentSkillLibrary;
use App\Services\MCPIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    // ─── AgentLifecycleService Tests ─────────────────────────────────────

    public function test_agent_lifecycle_initialize_sets_running_status(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_IDLE]);
        $service = new AgentLifecycleService();
        
        $result = $service->initialize($agent);
        
        $this->assertEquals(Agent::STATUS_RUNNING, $result->status);
        $this->assertEquals(1, $result->execution_count);
        $this->assertNotNull($result->last_executed_at);
    }

    public function test_agent_lifecycle_transition_to_valid_status(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_IDLE]);
        $service = new AgentLifecycleService();
        
        $result = $service->transition($agent, Agent::STATUS_RUNNING);
        
        $this->assertEquals(Agent::STATUS_RUNNING, $result->status);
    }

    public function test_agent_lifecycle_transition_to_invalid_status_throws_exception(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_IDLE]);
        $service = new AgentLifecycleService();
        
        $this->expectException(\InvalidArgumentException::class);
        $service->transition($agent, Agent::STATUS_PAUSED);
    }

    public function test_agent_lifecycle_idle_sets_idle_status(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_RUNNING]);
        $service = new AgentLifecycleService();
        
        $result = $service->idle($agent);
        
        $this->assertEquals(Agent::STATUS_IDLE, $result->status);
    }

    public function test_agent_lifecycle_pause_sets_paused_status(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_RUNNING]);
        $service = new AgentLifecycleService();
        
        $result = $service->pause($agent);
        
        $this->assertEquals(Agent::STATUS_PAUSED, $result->status);
    }

    public function test_agent_lifecycle_resume_sets_running_status(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_PAUSED]);
        $service = new AgentLifecycleService();
        
        $result = $service->resume($agent);
        
        $this->assertEquals(Agent::STATUS_RUNNING, $result->status);
    }

    public function test_agent_lifecycle_complete_records_success(): void
    {
        $agent = Agent::factory()->create([
            'status' => Agent::STATUS_RUNNING,
            'success_count' => 2,
        ]);
        $service = new AgentLifecycleService();
        
        $result = $service->complete($agent);
        
        $this->assertEquals(Agent::STATUS_IDLE, $result->status);
        $this->assertEquals(3, $result->success_count);
    }

    public function test_agent_lifecycle_fail_records_error(): void
    {
        $agent = Agent::factory()->create([
            'status' => Agent::STATUS_RUNNING,
            'error_count' => 1,
        ]);
        $service = new AgentLifecycleService();
        
        $result = $service->fail($agent, 'Test error');
        
        $this->assertEquals(Agent::STATUS_ERROR, $result->status);
        $this->assertEquals(2, $result->error_count);
    }

    public function test_agent_lifecycle_can_transition_returns_true_for_valid(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_IDLE]);
        $service = new AgentLifecycleService();
        
        $this->assertTrue($service->canTransition($agent, Agent::STATUS_RUNNING));
    }

    public function test_agent_lifecycle_can_transition_returns_false_for_invalid(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_IDLE]);
        $service = new AgentLifecycleService();
        
        $this->assertFalse($service->canTransition($agent, Agent::STATUS_PAUSED));
    }

    public function test_agent_lifecycle_get_available_transitions(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_IDLE]);
        $service = new AgentLifecycleService();
        
        $transitions = $service->getAvailableTransitions($agent);
        
        $this->assertContains(Agent::STATUS_RUNNING, $transitions);
    }

    public function test_agent_lifecycle_get_lifecycle_state(): void
    {
        $service = new AgentLifecycleService();
        $state = $service->getLifecycleState();
        
        $this->assertIsArray($state);
        $this->assertArrayHasKey(Agent::STATUS_IDLE, $state);
    }

    // ─── AgentConfigurationService Tests ────────────────────────────────

    public function test_agent_configuration_load_returns_merged_config(): void
    {
        $agent = Agent::factory()->create(['settings' => ['timeout' => 60]]);
        $service = new AgentConfigurationService();
        
        $config = $service->load($agent);
        
        $this->assertEquals(60, $config['timeout']);
        $this->assertEquals(300, $config['max_execution_time']);
    }

    public function test_agent_configuration_get_returns_specific_value(): void
    {
        $agent = Agent::factory()->create(['settings' => ['retry_count' => 5]]);
        $service = new AgentConfigurationService();
        
        $value = $service->get($agent, 'retry_count');
        
        $this->assertEquals(5, $value);
    }

    public function test_agent_configuration_get_returns_default_for_missing(): void
    {
        $agent = Agent::factory()->create(['settings' => []]);
        $service = new AgentConfigurationService();
        
        $value = $service->get($agent, 'nonexistent', 'default');
        
        $this->assertEquals('default', $value);
    }

    public function test_agent_configuration_set_updates_setting(): void
    {
        $agent = Agent::factory()->create(['settings' => []]);
        $service = new AgentConfigurationService();
        
        $result = $service->set($agent, 'timeout', 120);
        
        $this->assertEquals(120, $result->settings['timeout']);
    }

    public function test_agent_configuration_update_bulk_updates_settings(): void
    {
        $agent = Agent::factory()->create(['settings' => []]);
        $service = new AgentConfigurationService();
        
        $result = $service->update($agent, ['timeout' => 120, 'retry_count' => 5]);
        
        $this->assertEquals(120, $result->settings['timeout']);
        $this->assertEquals(5, $result->settings['retry_count']);
    }

    public function test_agent_configuration_reset_restores_defaults(): void
    {
        $agent = Agent::factory()->create(['settings' => ['timeout' => 999]]);
        $service = new AgentConfigurationService();
        
        $result = $service->reset($agent);
        
        $this->assertEquals(30, $result->settings['timeout']);
    }

    public function test_agent_configuration_validate_returns_errors_for_missing_required(): void
    {
        $agent = Agent::factory()->create(['settings' => []]);
        $service = new AgentConfigurationService();

        $errors = $service->validate($agent, ['nonexistent_key' => 'required']);

        $this->assertContains('Missing required config: nonexistent_key', $errors);
    }

    public function test_agent_configuration_validate_returns_errors_for_wrong_type(): void
    {
        $agent = Agent::factory()->create(['settings' => ['custom_field' => 'not_int']]);
        $service = new AgentConfigurationService();

        $errors = $service->validate($agent, ['custom_field' => 'integer']);

        $this->assertContains('Config custom_field must be integer', $errors);
    }

    public function test_agent_configuration_get_default_config(): void
    {
        $service = new AgentConfigurationService();
        $config = $service->getDefaultConfig();
        
        $this->assertArrayHasKey('max_execution_time', $config);
        $this->assertArrayHasKey('retry_count', $config);
    }

    // ─── AgentRegistry Tests ────────────────────────────────────────────

    public function test_agent_registry_resolve_returns_agent_instance(): void
    {
        $agent = Agent::factory()->create(['type' => Agent::TYPE_REFLECTION]);
        $registry = new AgentRegistry();
        
        $instance = $registry->resolve($agent);
        
        $this->assertInstanceOf(\App\Agents\ReflectionAgent::class, $instance);
    }

    public function test_agent_registry_resolve_throws_for_unknown_type(): void
    {
        $agent = Agent::factory()->create(['type' => 'unknown']);
        $registry = new AgentRegistry();
        
        $this->expectException(\InvalidArgumentException::class);
        $registry->resolve($agent);
    }

    public function test_agent_registry_has_returns_true_for_registered_type(): void
    {
        $registry = new AgentRegistry();
        
        $this->assertTrue($registry->has(Agent::TYPE_REFLECTION));
    }

    public function test_agent_registry_has_returns_false_for_unknown_type(): void
    {
        $registry = new AgentRegistry();
        
        $this->assertFalse($registry->has('unknown'));
    }

    public function test_agent_registry_register_adds_new_type(): void
    {
        $registry = new AgentRegistry();
        $registry->register('custom', \App\Agents\ReflectionAgent::class);
        
        $this->assertTrue($registry->has('custom'));
    }

    public function test_agent_registry_all_returns_all_types(): void
    {
        $registry = new AgentRegistry();
        $all = $registry->all();
        
        $this->assertArrayHasKey(Agent::TYPE_REFLECTION, $all);
        $this->assertArrayHasKey(Agent::TYPE_TEAM, $all);
    }

    public function test_agent_registry_get_registered_types(): void
    {
        $registry = new AgentRegistry();
        $types = $registry->getRegisteredTypes();
        
        $this->assertContains(Agent::TYPE_REFLECTION, $types);
    }

    public function test_agent_registry_get_agent_class(): void
    {
        $registry = new AgentRegistry();
        $class = $registry->getAgentClass(Agent::TYPE_TEAM);
        
        $this->assertEquals(\App\Agents\TeamAgent::class, $class);
    }

    public function test_agent_registry_clear_cache_resets_instances(): void
    {
        $agent = Agent::factory()->create(['type' => Agent::TYPE_REFLECTION]);
        $registry = new AgentRegistry();
        
        $instance1 = $registry->resolve($agent);
        $registry->clearCache();
        $instance2 = $registry->resolve($agent);
        
        $this->assertNotSame($instance1, $instance2);
    }

    // ─── AgentToolRegistry Tests ────────────────────────────────────────

    public function test_agent_tool_registry_register_adds_tool(): void
    {
        $registry = new AgentToolRegistry();
        $registry->register('test_tool', ['description' => 'Test tool']);
        
        $this->assertTrue($registry->has('test_tool'));
    }

    public function test_agent_tool_registry_get_returns_tool_definition(): void
    {
        $registry = new AgentToolRegistry();
        $registry->register('test_tool', ['description' => 'Test tool']);
        
        $tool = $registry->get('test_tool');
        
        $this->assertEquals('Test tool', $tool['description']);
    }

    public function test_agent_tool_registry_get_returns_null_for_missing(): void
    {
        $registry = new AgentToolRegistry();
        
        $this->assertNull($registry->get('nonexistent'));
    }

    public function test_agent_tool_registry_execute_with_callback(): void
    {
        $registry = new AgentToolRegistry();
        $executed = false;
        
        $registry->register('test_tool', [], function () use (&$executed) {
            $executed = true;
            return ['result' => 'callback executed'];
        });
        
        $result = $registry->execute('test_tool', []);
        
        $this->assertTrue($executed);
        $this->assertEquals('callback executed', $result['result']);
    }

    public function test_agent_tool_registry_execute_without_callback_uses_default(): void
    {
        $registry = new AgentToolRegistry();
        $registry->register('test_tool', []);
        
        $result = $registry->execute('test_tool', []);
        
        $this->assertEquals('test_tool', $result['tool']);
        $this->assertEquals('executed', $result['status']);
    }

    public function test_agent_tool_registry_execute_throws_for_missing_tool(): void
    {
        $registry = new AgentToolRegistry();
        
        $this->expectException(\InvalidArgumentException::class);
        $registry->execute('nonexistent', []);
    }

    public function test_agent_tool_registry_validate_parameters_throws_for_missing_required(): void
    {
        $registry = new AgentToolRegistry();
        $registry->register('test_tool', ['required' => ['param1']]);
        
        $this->expectException(\InvalidArgumentException::class);
        $registry->execute('test_tool', []);
    }

    public function test_agent_tool_registry_register_from_model(): void
    {
        $agent = Agent::factory()->create();
        $tool = AgentTool::factory()->create(['agent_id' => $agent->id]);
        $registry = new AgentToolRegistry();
        
        $registry->registerFromModel($tool);
        
        $this->assertTrue($registry->has($tool->name));
    }

    public function test_agent_tool_registry_unregister_removes_tool(): void
    {
        $registry = new AgentToolRegistry();
        $registry->register('test_tool', []);
        
        $result = $registry->unregister('test_tool');
        
        $this->assertTrue($result);
        $this->assertFalse($registry->has('test_tool'));
    }

    public function test_agent_tool_registry_clear_removes_all_tools(): void
    {
        $registry = new AgentToolRegistry();
        $registry->register('tool1', []);
        $registry->register('tool2', []);
        
        $registry->clear();
        
        $this->assertEmpty($registry->getAll());
    }

    // ─── AgentToolExecutor Tests ────────────────────────────────────────

    public function test_agent_tool_executor_execute_tool_success(): void
    {
        $agent = Agent::factory()->create();
        $registry = new AgentToolRegistry();
        $registry->register('test_tool', []);
        $executor = new AgentToolExecutor($registry);
        
        $result = $executor->executeTool($agent, 'test_tool', ['param' => 'value']);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('test_tool', $result['tool']);
        $this->assertArrayHasKey('duration_ms', $result);
    }

    public function test_agent_tool_executor_execute_tool_not_found(): void
    {
        $agent = Agent::factory()->create();
        $registry = new AgentToolRegistry();
        $executor = new AgentToolExecutor($registry);
        
        $result = $executor->executeTool($agent, 'nonexistent', []);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_agent_tool_executor_execute_tools_multiple(): void
    {
        $agent = Agent::factory()->create();
        $registry = new AgentToolRegistry();
        $registry->register('tool1', []);
        $registry->register('tool2', []);
        $executor = new AgentToolExecutor($registry);
        
        $results = $executor->executeTools($agent, [
            ['tool' => 'tool1'],
            ['tool' => 'tool2'],
        ]);
        
        $this->assertCount(2, $results);
        $this->assertTrue($results[0]['success']);
        $this->assertTrue($results[1]['success']);
    }

    public function test_agent_tool_executor_execute_tools_missing_name(): void
    {
        $agent = Agent::factory()->create();
        $registry = new AgentToolRegistry();
        $executor = new AgentToolExecutor($registry);
        
        $results = $executor->executeTools($agent, [
            ['params' => []],
        ]);
        
        $this->assertFalse($results[0]['success']);
        $this->assertEquals('Missing tool name in tool call', $results[0]['error']);
    }

    public function test_agent_tool_executor_get_execution_history(): void
    {
        $agent = Agent::factory()->create();
        $registry = new AgentToolRegistry();
        $registry->register('test_tool', []);
        $executor = new AgentToolExecutor($registry);
        
        $executor->executeTool($agent, 'test_tool', []);
        $history = $executor->getExecutionHistory();
        
        $this->assertCount(1, $history);
        $this->assertEquals('test_tool', $history[0]['tool']);
    }

    public function test_agent_tool_executor_clear_history(): void
    {
        $agent = Agent::factory()->create();
        $registry = new AgentToolRegistry();
        $registry->register('test_tool', []);
        $executor = new AgentToolExecutor($registry);
        
        $executor->executeTool($agent, 'test_tool', []);
        $executor->clearHistory();
        
        $this->assertEmpty($executor->getExecutionHistory());
    }

    public function test_agent_tool_executor_get_success_rate(): void
    {
        $agent = Agent::factory()->create();
        $registry = new AgentToolRegistry();
        $registry->register('test_tool', []);
        $executor = new AgentToolExecutor($registry);
        
        $executor->executeTool($agent, 'test_tool', []);
        $rate = $executor->getSuccessRate();
        
        $this->assertEquals(100.0, $rate);
    }

    // ─── AgentSkillLibrary Tests ────────────────────────────────────────

    public function test_agent_skill_library_register_adds_skill(): void
    {
        $library = new AgentSkillLibrary();
        $library->register('test_skill', ['description' => 'Test skill']);
        
        $this->assertTrue($library->has('test_skill'));
    }

    public function test_agent_skill_library_get_returns_skill_definition(): void
    {
        $library = new AgentSkillLibrary();
        $library->register('test_skill', ['description' => 'Test skill']);
        
        $skill = $library->get('test_skill');
        
        $this->assertEquals('Test skill', $skill['description']);
    }

    public function test_agent_skill_library_get_by_category_filters_correctly(): void
    {
        $library = new AgentSkillLibrary();
        $library->register('skill1', ['category' => 'reasoning']);
        $library->register('skill2', ['category' => 'coding']);
        
        $reasoning = $library->getByCategory('reasoning');
        
        $this->assertCount(1, $reasoning);
    }

    public function test_agent_skill_library_execute_with_callback(): void
    {
        $library = new AgentSkillLibrary();
        $executed = false;
        
        $library->register('test_skill', [], function () use (&$executed) {
            $executed = true;
            return ['result' => 'skill executed'];
        });
        
        $result = $library->execute('test_skill', []);
        
        $this->assertTrue($executed);
        $this->assertEquals('skill executed', $result['result']);
    }

    public function test_agent_skill_library_execute_throws_for_missing_skill(): void
    {
        $library = new AgentSkillLibrary();
        
        $this->expectException(\InvalidArgumentException::class);
        $library->execute('nonexistent', []);
    }

    public function test_agent_skill_library_register_from_model(): void
    {
        $agent = Agent::factory()->create();
        $skill = AgentSkill::factory()->create(['agent_id' => $agent->id]);
        $library = new AgentSkillLibrary();
        
        $library->registerFromModel($skill);
        
        $this->assertTrue($library->has($skill->name));
    }

    public function test_agent_skill_library_search_finds_skill_by_name(): void
    {
        $library = new AgentSkillLibrary();
        $library->register('reasoning_skill', ['description' => 'Advanced reasoning']);
        
        $results = $library->search('reasoning');
        
        $this->assertCount(1, $results);
    }

    public function test_agent_skill_library_clear_removes_all_skills(): void
    {
        $library = new AgentSkillLibrary();
        $library->register('skill1', []);
        $library->register('skill2', []);
        
        $library->clear();
        
        $this->assertEmpty($library->getAll());
    }

    // ─── MCPIntegrationService Tests ───────────────────────────────────

    public function test_mcp_integration_service_register_server(): void
    {
        $service = new MCPIntegrationService();
        $service->registerServer('test_server', ['url' => 'http://localhost:3000']);

        $this->assertTrue($service->getServer('test_server') !== null);
    }

    public function test_mcp_integration_service_get_server(): void
    {
        $service = new MCPIntegrationService();
        $service->registerServer('test_server', ['url' => 'http://localhost:3000']);
        
        $server = $service->getServer('test_server');
        
        $this->assertEquals('test_server', $server['name']);
    }

    public function test_mcp_integration_service_connect_returns_success(): void
    {
        $service = new MCPIntegrationService();
        $service->registerServer('test_server', ['url' => 'http://localhost:3000']);
        
        $result = $service->connect('test_server');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('test_server', $result['server']);
    }

    public function test_mcp_integration_service_connect_throws_for_disabled_server(): void
    {
        $service = new MCPIntegrationService();
        $service->registerServer('test_server', ['enabled' => false]);

        $this->expectException(\RuntimeException::class);
        $service->connect('test_server');
    }

    public function test_mcp_integration_service_disconnect(): void
    {
        $service = new MCPIntegrationService();
        $service->registerServer('test_server', []);
        $service->connect('test_server');
        
        $result = $service->disconnect('test_server');
        
        $this->assertTrue($result);
        $this->assertFalse($service->isConnected('test_server'));
    }

    public function test_mcp_integration_service_list_tools(): void
    {
        $service = new MCPIntegrationService();
        $service->registerServer('test_server', [
            'tools' => [
                ['name' => 'tool1', 'description' => 'Test tool 1'],
            ],
        ]);
        
        $result = $service->listTools('test_server');
        
        $this->assertArrayHasKey('tools', $result);
        $this->assertCount(1, $result['tools']);
    }

    public function test_mcp_integration_service_call_tool(): void
    {
        $service = new MCPIntegrationService();
        $service->registerServer('test_server', []);
        
        $result = $service->callTool('test_server', 'test_tool', ['param' => 'value']);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('test_tool', $result['tool']);
    }

    public function test_mcp_integration_service_attach_to_agent(): void
    {
        $agent = Agent::factory()->create();
        $service = new MCPIntegrationService();
        $service->registerServer('test_server', []);
        
        $result = $service->attachToAgent($agent, 'test_server');
        
        $this->assertTrue($result['success']);
        $agent->refresh();
        $this->assertContains('test_server', $agent->metadata['mcp_servers'] ?? []);
    }

    public function test_mcp_integration_service_detach_from_agent(): void
    {
        $agent = Agent::factory()->create(['metadata' => ['mcp_servers' => ['test_server']]]);
        $service = new MCPIntegrationService();
        $service->registerServer('test_server', []);
        
        $result = $service->detachFromAgent($agent, 'test_server');
        
        $this->assertTrue($result['success']);
        $agent->refresh();
        $this->assertNotContains('test_server', $agent->metadata['mcp_servers'] ?? []);
    }

    public function test_mcp_integration_service_get_agent_servers(): void
    {
        $agent = Agent::factory()->create(['metadata' => ['mcp_servers' => ['server1', 'server2']]]);
        $service = new MCPIntegrationService();
        $service->registerServer('server1', []);
        $service->registerServer('server2', []);
        
        $servers = $service->getAgentServers($agent);
        
        $this->assertCount(2, $servers);
    }

    public function test_mcp_integration_service_unregister(): void
    {
        $service = new MCPIntegrationService();
        $service->registerServer('test_server', []);
        
        $result = $service->unregister('test_server');
        
        $this->assertTrue($result);
        $this->assertNull($service->getServer('test_server'));
    }

    public function test_mcp_integration_service_clear(): void
    {
        $service = new MCPIntegrationService();
        $service->registerServer('server1', []);
        $service->registerServer('server2', []);
        
        $service->clear();
        
        $this->assertEmpty($service->getAllServers());
    }
}
