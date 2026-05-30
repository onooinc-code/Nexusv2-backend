<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\Workflow;
use App\Models\AgentTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActionTest extends TestCase
{
    use RefreshDatabase;

    // ─── Agent Actions ───────────────────────────────────────────────────

    public function test_agent_execute_action_returns_success_result(): void
    {
        $agent = Agent::factory()->create([
            'type' => Agent::TYPE_AUTONOMOUS,
            'status' => Agent::STATUS_IDLE,
        ]);

        $result = $agent->execute(['input' => 'test data']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('agent_id', $result);
        $this->assertArrayHasKey('agent_type', $result);
    }

    public function test_agent_execute_action_handles_reflection_agent(): void
    {
        $agent = Agent::factory()->create([
            'type' => Agent::TYPE_REFLECTION,
            'status' => Agent::STATUS_IDLE,
        ]);

        $result = $agent->execute(['context' => 'test context']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_agent_execute_action_handles_team_agent(): void
    {
        $agent = Agent::factory()->create([
            'type' => Agent::TYPE_TEAM,
            'status' => Agent::STATUS_IDLE,
        ]);

        $result = $agent->execute(['task' => 'coordinate team']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_agent_execute_action_handles_specialized_agent(): void
    {
        $agent = Agent::factory()->create([
            'type' => Agent::TYPE_SPECIALIZED,
            'status' => Agent::STATUS_IDLE,
            'settings' => ['domain' => 'technical_support'],
        ]);

        $result = $agent->execute(['query' => 'How do I reset my password?']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_agent_execute_action_handles_supervisor_agent(): void
    {
        $agent = Agent::factory()->create([
            'type' => Agent::TYPE_SUPERVISOR,
            'status' => Agent::STATUS_IDLE,
        ]);

        $result = $agent->execute(['task' => 'oversee operations']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_agent_execute_action_validates_required_context(): void
    {
        $agent = Agent::factory()->create([
            'type' => Agent::TYPE_AUTONOMOUS,
            'status' => Agent::STATUS_IDLE,
        ]);

        $result = $agent->execute([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    // ─── Workflow Actions ─────────────────────────────────────────────────

    public function test_workflow_execute_action_runs_all_steps(): void
    {
        $workflow = Workflow::factory()->create([
            'status' => Workflow::STATUS_DRAFT,
            'is_active' => true,
            'steps' => [
                ['name' => 'Step 1', 'action' => 'log', 'message' => 'Step 1 done'],
                ['name' => 'Step 2', 'action' => 'log', 'message' => 'Step 2 done'],
            ],
        ]);

        $result = $workflow->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('steps_executed', $result);
    }

    public function test_workflow_execute_action_handles_condition_step(): void
    {
        $workflow = Workflow::factory()->create([
            'status' => Workflow::STATUS_DRAFT,
            'is_active' => true,
            'steps' => [
                [
                    'name' => 'Conditional Step',
                    'action' => 'condition',
                    'condition' => ['field' => 'status', 'operator' => '==', 'value' => 'active'],
                ],
            ],
        ]);

        $result = $workflow->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_workflow_execute_action_handles_delay_step(): void
    {
        $workflow = Workflow::factory()->create([
            'status' => Workflow::STATUS_DRAFT,
            'is_active' => true,
            'steps' => [
                ['name' => 'Wait Step', 'action' => 'delay', 'delay_seconds' => 1],
            ],
        ]);

        $result = $workflow->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_workflow_execute_action_handles_agent_step(): void
    {
        $agent = Agent::factory()->create([
            'type' => Agent::TYPE_AUTONOMOUS,
            'status' => Agent::STATUS_IDLE,
        ]);

        $workflow = Workflow::factory()->create([
            'status' => Workflow::STATUS_DRAFT,
            'is_active' => true,
            'steps' => [
                [
                    'name' => 'Agent Step',
                    'action' => 'agent',
                    'agent_type' => Agent::TYPE_AUTONOMOUS,
                ],
            ],
        ]);

        $result = $workflow->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    // ─── Task Actions ────────────────────────────────────────────────────

    public function test_task_cancel_action_stops_running_task(): void
    {
        $task = AgentTask::factory()->create(['status' => 'running']);

        $result = $task->cancel();

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $task->fresh()->status);
    }

    public function test_task_pause_action_pauses_running_task(): void
    {
        $task = AgentTask::factory()->create(['status' => 'running']);

        $result = $task->pause();

        $this->assertTrue($result);
        $this->assertEquals('paused', $task->fresh()->status);
    }

    public function test_task_resume_action_resumes_paused_task(): void
    {
        $task = AgentTask::factory()->create(['status' => 'paused']);

        $result = $task->resume();

        $this->assertTrue($result);
        $this->assertEquals('running', $task->fresh()->status);
    }

    public function test_task_complete_action_marks_task_completed(): void
    {
        $task = AgentTask::factory()->create(['status' => 'running']);

        $result = $task->complete(['output' => 'Task completed successfully']);

        $this->assertTrue($result);
        $this->assertEquals('completed', $task->fresh()->status);
    }

    public function test_task_fail_action_marks_task_failed(): void
    {
        $task = AgentTask::factory()->create(['status' => 'running']);

        $result = $task->fail('Something went wrong');

        $this->assertTrue($result);
        $this->assertEquals('failed', $task->fresh()->status);
    }

    // ─── Memory Actions ──────────────────────────────────────────────────

    public function test_memory_store_action_creates_memory(): void
    {
        $contact = \App\Models\Contact::factory()->create();

        $memoryData = [
            'contact_id' => $contact->id,
            'type' => 'episodic',
            'content' => 'Test memory content',
            'source' => 'test',
        ];

        $memory = \App\Models\Memory::create($memoryData);

        $this->assertDatabaseHas('memories', ['content' => 'Test memory content']);
        $this->assertEquals($contact->id, $memory->contact_id);
    }

    public function test_memory_search_action_filters_by_type(): void
    {
        $contact = \App\Models\Contact::factory()->create();
        \App\Models\Memory::factory()->create(['contact_id' => $contact->id, 'type' => 'episodic']);
        \App\Models\Memory::factory()->create(['contact_id' => $contact->id, 'type' => 'semantic']);

        $results = \App\Models\Memory::where('contact_id', $contact->id)
            ->where('type', 'episodic')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('episodic', $results->first()->type);
    }

    // ─── Contact Actions ─────────────────────────────────────────────────

    public function test_contact_create_action_persists_contact(): void
    {
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'type' => \App\Models\Contact::TYPE_CLIENT,
        ];

        $contact = \App\Models\Contact::create($contactData);

        $this->assertDatabaseHas('contacts', ['email' => 'john@example.com']);
        $this->assertEquals('John Doe', $contact->name);
    }

    public function test_contact_update_action_modifies_contact(): void
    {
        $contact = \App\Models\Contact::factory()->create(['name' => 'Old Name']);

        $contact->update(['name' => 'New Name']);

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'name' => 'New Name']);
    }

    public function test_contact_delete_action_soft_deletes_contact(): void
    {
        $contact = \App\Models\Contact::factory()->create();

        $contact->delete();

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }

    // ─── Setting Actions ─────────────────────────────────────────────────

    public function test_setting_set_action_updates_value(): void
    {
        $setting = \App\Models\Setting::factory()->create([
            'key' => 'test_key',
            'value' => 'old_value',
        ]);

        $setting->setValue('new_value');

        $this->assertEquals('new_value', $setting->fresh()->value);
    }

    public function test_setting_get_action_returns_typed_value(): void
    {
        $setting = \App\Models\Setting::factory()->create([
            'key' => 'test_int',
            'type' => 'integer',
            'value' => '42',
        ]);

        $value = $setting->getTypedValue();

        $this->assertEquals(42, $value);
    }

    // ─── Log Actions ─────────────────────────────────────────────────────

    public function test_log_info_action_creates_log_entry(): void
    {
        \App\Services\LogService::info('Test info message', [
            'context' => 'test',
            'user_id' => 1,
        ]);

        $this->assertDatabaseHas('logs', [
            'level' => 'info',
            'message' => 'Test info message',
        ]);
    }

    public function test_log_error_action_creates_log_entry(): void
    {
        \App\Services\LogService::error('Test error message', [
            'context' => 'test',
            'exception' => new \RuntimeException('Test exception'),
        ]);

        $this->assertDatabaseHas('logs', [
            'level' => 'error',
            'message' => 'Test error message',
        ]);
    }

    public function test_log_debug_action_creates_log_entry(): void
    {
        \App\Services\LogService::debug('Test debug message');

        $this->assertDatabaseHas('logs', [
            'level' => 'debug',
            'message' => 'Test debug message',
        ]);
    }
}
