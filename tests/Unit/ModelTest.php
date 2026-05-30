<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\Workflow;
use App\Models\Setting;
use App\Models\Contact;
use App\Models\Memory;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\BaseModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    // ─── Agent Model Tests ───────────────────────────────────────────────

    public function test_agent_has_correct_fillable_attributes(): void
    {
        $agent = new Agent();
        $this->assertEquals([
            'name', 'key', 'description', 'type', 'provider', 'status',
            'settings', 'metadata', 'is_active', 'last_executed_at',
            'execution_count', 'success_count', 'error_count',
        ], $agent->getFillable());
    }

    public function test_agent_has_correct_casts(): void
    {
        $agent = new Agent();
        $casts = $agent->getCasts();
        $this->assertEquals('json', $casts['settings']);
        $this->assertEquals('json', $casts['metadata']);
        $this->assertEquals('boolean', $casts['is_active']);
        $this->assertEquals('datetime', $casts['last_executed_at']);
        $this->assertEquals('integer', $casts['execution_count']);
    }

    public function test_agent_default_status_is_idle(): void
    {
        $agent = new Agent();
        $this->assertEquals(Agent::STATUS_IDLE, $agent->status);
    }

    public function test_agent_default_is_active_is_true(): void
    {
        $agent = new Agent();
        $this->assertTrue($agent->is_active);
    }

    public function test_agent_type_constants_exist(): void
    {
        $this->assertEquals('reflection', Agent::TYPE_REFLECTION);
        $this->assertEquals('team', Agent::TYPE_TEAM);
        $this->assertEquals('autonomous', Agent::TYPE_AUTONOMOUS);
        $this->assertEquals('specialized', Agent::TYPE_SPECIALIZED);
        $this->assertEquals('supervisor', Agent::TYPE_SUPERVISOR);
    }

    public function test_agent_status_constants_exist(): void
    {
        $this->assertEquals('idle', Agent::STATUS_IDLE);
        $this->assertEquals('running', Agent::STATUS_RUNNING);
        $this->assertEquals('paused', Agent::STATUS_PAUSED);
        $this->assertEquals('error', Agent::STATUS_ERROR);
        $this->assertEquals('completed', Agent::STATUS_COMPLETED);
    }

    public function test_agent_can_be_created_with_factory(): void
    {
        $agent = Agent::factory()->create();
        $this->assertDatabaseHas('agents', ['id' => $agent->id]);
        $this->assertNotNull($agent->name);
        $this->assertNotNull($agent->key);
    }

    public function test_agent_has_tools_relationship(): void
    {
        $agent = Agent::factory()->create();
        $tool = \App\Models\AgentTool::factory()->create(['agent_id' => $agent->id]);
        $this->assertTrue($agent->tools->contains($tool));
    }

    public function test_agent_has_skills_relationship(): void
    {
        $agent = Agent::factory()->create();
        $skill = \App\Models\AgentSkill::factory()->create(['agent_id' => $agent->id]);
        $this->assertTrue($agent->skills->contains($skill));
    }

    public function test_agent_has_tasks_relationship(): void
    {
        $agent = Agent::factory()->create();
        $task = \App\Models\AgentTask::factory()->create(['agent_id' => $agent->id]);
        $this->assertTrue($agent->tasks->contains($task));
    }

    public function test_agent_active_tools_returns_only_active(): void
    {
        $agent = Agent::factory()->create();
        $activeTool = \App\Models\AgentTool::factory()->create(['agent_id' => $agent->id, 'is_active' => true]);
        $inactiveTool = \App\Models\AgentTool::factory()->create(['agent_id' => $agent->id, 'is_active' => false]);
        
        $activeTools = $agent->activeTools();
        $this->assertCount(1, $activeTools);
        $this->assertTrue($activeTools->first()->is_active);
    }

    public function test_agent_is_running_returns_true_when_running(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_RUNNING]);
        $this->assertTrue($agent->isRunning());
    }

    public function test_agent_is_idle_returns_true_when_idle(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_IDLE]);
        $this->assertTrue($agent->isIdle());
    }

    public function test_agent_has_error_returns_true_when_error(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_ERROR]);
        $this->assertTrue($agent->hasError());
    }

    public function test_agent_get_success_rate_returns_zero_when_no_executions(): void
    {
        $agent = Agent::factory()->create(['execution_count' => 0]);
        $this->assertEquals(0.0, $agent->getSuccessRate());
    }

    public function test_agent_get_success_rate_calculates_correctly(): void
    {
        $agent = Agent::factory()->create([
            'execution_count' => 10,
            'success_count' => 8,
        ]);
        $this->assertEquals(80.0, $agent->getSuccessRate());
    }

    public function test_agent_increment_execution_updates_counters(): void
    {
        $agent = Agent::factory()->create(['execution_count' => 5]);
        $agent->incrementExecution();
        $agent->refresh();
        $this->assertEquals(6, $agent->execution_count);
        $this->assertNotNull($agent->last_executed_at);
    }

    public function test_agent_record_success_updates_status(): void
    {
        $agent = Agent::factory()->create([
            'status' => Agent::STATUS_RUNNING,
            'success_count' => 3,
        ]);
        $agent->recordSuccess();
        $agent->refresh();
        $this->assertEquals(4, $agent->success_count);
        $this->assertEquals(Agent::STATUS_IDLE, $agent->status);
    }

    public function test_agent_record_error_updates_status(): void
    {
        $agent = Agent::factory()->create([
            'status' => Agent::STATUS_RUNNING,
            'error_count' => 2,
        ]);
        $agent->recordError();
        $agent->refresh();
        $this->assertEquals(3, $agent->error_count);
        $this->assertEquals(Agent::STATUS_ERROR, $agent->status);
    }

    public function test_agent_scope_by_type_filters_correctly(): void
    {
        Agent::factory()->create(['type' => Agent::TYPE_REFLECTION]);
        Agent::factory()->create(['type' => Agent::TYPE_TEAM]);
        
        $reflectionAgents = Agent::byType(Agent::TYPE_REFLECTION)->get();
        $this->assertCount(1, $reflectionAgents);
        $this->assertEquals(Agent::TYPE_REFLECTION, $reflectionAgents->first()->type);
    }

    public function test_agent_scope_active_filters_correctly(): void
    {
        Agent::factory()->create(['is_active' => true]);
        Agent::factory()->create(['is_active' => false]);
        
        $activeAgents = Agent::active()->get();
        $this->assertCount(1, $activeAgents);
        $this->assertTrue($activeAgents->first()->is_active);
    }

    public function test_agent_type_label_attribute(): void
    {
        $agent = Agent::factory()->create(['type' => Agent::TYPE_REFLECTION]);
        $this->assertEquals('Reflection Agent', $agent->type_label);
        
        $agent->type = 'unknown';
        $this->assertEquals('Unknown Agent', $agent->type_label);
    }

    public function test_agent_status_label_attribute(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_RUNNING]);
        $this->assertEquals('Running', $agent->status_label);
        
        $agent->status = 'unknown';
        $this->assertEquals('Unknown', $agent->status_label);
    }

    // ─── Workflow Model Tests ────────────────────────────────────────────

    public function test_workflow_has_correct_fillable_attributes(): void
    {
        $workflow = new Workflow();
        $this->assertEquals([
            'name', 'key', 'description', 'steps', 'trigger_type', 'trigger_config',
            'status', 'settings', 'metadata', 'is_active', 'last_executed_at',
            'execution_count', 'success_count', 'error_count',
        ], $workflow->getFillable());
    }

    public function test_workflow_has_correct_casts(): void
    {
        $workflow = new Workflow();
        $casts = $workflow->getCasts();
        $this->assertEquals('json', $casts['steps']);
        $this->assertEquals('json', $casts['trigger_config']);
        $this->assertEquals('json', $casts['settings']);
        $this->assertEquals('json', $casts['metadata']);
        $this->assertEquals('boolean', $casts['is_active']);
        $this->assertEquals('datetime', $casts['last_executed_at']);
    }

    public function test_workflow_default_status_is_draft(): void
    {
        $workflow = new Workflow();
        $this->assertEquals(Workflow::STATUS_DRAFT, $workflow->status);
    }

    public function test_workflow_default_trigger_type_is_manual(): void
    {
        $workflow = new Workflow();
        $this->assertEquals(Workflow::TRIGGER_MANUAL, $workflow->trigger_type);
    }

    public function test_workflow_status_constants_exist(): void
    {
        $this->assertEquals('draft', Workflow::STATUS_DRAFT);
        $this->assertEquals('active', Workflow::STATUS_ACTIVE);
        $this->assertEquals('running', Workflow::STATUS_RUNNING);
        $this->assertEquals('paused', Workflow::STATUS_PAUSED);
        $this->assertEquals('completed', Workflow::STATUS_COMPLETED);
        $this->assertEquals('failed', Workflow::STATUS_FAILED);
        $this->assertEquals('cancelled', Workflow::STATUS_CANCELLED);
    }

    public function test_workflow_trigger_constants_exist(): void
    {
        $this->assertEquals('manual', Workflow::TRIGGER_MANUAL);
        $this->assertEquals('scheduled', Workflow::TRIGGER_SCHEDULED);
        $this->assertEquals('event', Workflow::TRIGGER_EVENT);
        $this->assertEquals('webhook', Workflow::TRIGGER_WEBHOOK);
    }

    public function test_workflow_can_be_created_with_factory(): void
    {
        $workflow = Workflow::factory()->create();
        $this->assertDatabaseHas('workflows', ['id' => $workflow->id]);
        $this->assertNotNull($workflow->name);
    }

    public function test_workflow_has_tasks_relationship(): void
    {
        $workflow = Workflow::factory()->create();
        $task = \App\Models\AgentTask::factory()->create(['workflow_id' => $workflow->id]);
        $this->assertTrue($workflow->tasks->contains($task));
    }

    public function test_workflow_is_running_returns_true_when_running(): void
    {
        $workflow = Workflow::factory()->create(['status' => Workflow::STATUS_RUNNING]);
        $this->assertTrue($workflow->isRunning());
    }

    public function test_workflow_is_draft_returns_true_when_draft(): void
    {
        $workflow = Workflow::factory()->create(['status' => Workflow::STATUS_DRAFT]);
        $this->assertTrue($workflow->isDraft());
    }

    public function test_workflow_can_execute_returns_true_for_draft_active_workflow(): void
    {
        $workflow = Workflow::factory()->create([
            'status' => Workflow::STATUS_DRAFT,
            'is_active' => true,
        ]);
        $this->assertTrue($workflow->canExecute());
    }

    public function test_workflow_can_execute_returns_false_when_inactive(): void
    {
        $workflow = Workflow::factory()->create([
            'status' => Workflow::STATUS_DRAFT,
            'is_active' => false,
        ]);
        $this->assertFalse($workflow->canExecute());
    }

    public function test_workflow_get_success_rate_calculates_correctly(): void
    {
        $workflow = Workflow::factory()->create([
            'execution_count' => 20,
            'success_count' => 15,
        ]);
        $this->assertEquals(75.0, $workflow->getSuccessRate());
    }

    public function test_workflow_progress_attribute_calculates_correctly(): void
    {
        $workflow = Workflow::factory()->create([
            'steps' => [
                ['status' => 'completed'],
                ['status' => 'completed'],
                ['status' => 'pending'],
            ],
        ]);
        $this->assertEquals(67, $workflow->progress); // 2/3 = 66.67 rounded to 67
    }

    public function test_workflow_progress_returns_zero_for_empty_steps(): void
    {
        $workflow = Workflow::factory()->create(['steps' => []]);
        $this->assertEquals(0, $workflow->progress);
    }

    public function test_workflow_total_steps_attribute(): void
    {
        $workflow = Workflow::factory()->create([
            'steps' => [
                ['status' => 'completed'],
                ['status' => 'pending'],
                ['status' => 'pending'],
            ],
        ]);
        $this->assertEquals(3, $workflow->total_steps);
    }

    public function test_workflow_completed_steps_attribute(): void
    {
        $workflow = Workflow::factory()->create([
            'steps' => [
                ['status' => 'completed'],
                ['status' => 'completed'],
                ['status' => 'pending'],
            ],
        ]);
        $this->assertEquals(2, $workflow->completed_steps);
    }

    public function test_workflow_scope_by_status_filters_correctly(): void
    {
        Workflow::factory()->create(['status' => Workflow::STATUS_DRAFT]);
        Workflow::factory()->create(['status' => Workflow::STATUS_ACTIVE]);
        
        $drafts = Workflow::byStatus(Workflow::STATUS_DRAFT)->get();
        $this->assertCount(1, $drafts);
    }

    public function test_workflow_scope_active_filters_correctly(): void
    {
        Workflow::factory()->create(['is_active' => true]);
        Workflow::factory()->create(['is_active' => false]);
        
        $active = Workflow::active()->get();
        $this->assertCount(1, $active);
    }

    public function test_workflow_status_label_attribute(): void
    {
        $workflow = Workflow::factory()->create(['status' => Workflow::STATUS_RUNNING]);
        $this->assertEquals('Running', $workflow->status_label);
    }

    public function test_workflow_trigger_type_label_attribute(): void
    {
        $workflow = Workflow::factory()->create(['trigger_type' => Workflow::TRIGGER_SCHEDULED]);
        $this->assertEquals('Scheduled', $workflow->trigger_type_label);
    }

    // ─── Setting Model Tests ─────────────────────────────────────────────

    public function test_setting_has_correct_fillable_attributes(): void
    {
        $setting = new Setting();
        $this->assertEquals([
            'key', 'value', 'type', 'group', 'is_public', 'description',
        ], $setting->getFillable());
    }

    public function test_setting_has_correct_casts(): void
    {
        $setting = new Setting();
        $casts = $setting->getCasts();
        $this->assertEquals('json', $casts['value']);
        $this->assertEquals('boolean', $casts['is_public']);
        $this->assertEquals('datetime', $casts['created_at']);
    }

    public function test_setting_type_constants_exist(): void
    {
        $this->assertEquals('string', Setting::TYPE_STRING);
        $this->assertEquals('integer', Setting::TYPE_INTEGER);
        $this->assertEquals('boolean', Setting::TYPE_BOOLEAN);
        $this->assertEquals('json', Setting::TYPE_JSON);
        $this->assertEquals('text', Setting::TYPE_TEXT);
    }

    public function test_setting_group_constants_exist(): void
    {
        $this->assertEquals('general', Setting::GROUP_GENERAL);
        $this->assertEquals('security', Setting::GROUP_SECURITY);
        $this->assertEquals('ai', Setting::GROUP_AI);
        $this->assertEquals('notifications', Setting::GROUP_NOTIFICATIONS);
        $this->assertEquals('integrations', Setting::GROUP_INTEGRATIONS);
        $this->assertEquals('ui', Setting::GROUP_UI);
    }

    public function test_setting_can_be_created_with_factory(): void
    {
        $setting = Setting::factory()->create();
        $this->assertDatabaseHas('settings', ['id' => $setting->id]);
        $this->assertNotNull($setting->key);
    }

    public function test_setting_get_typed_value_returns_boolean(): void
    {
        $setting = Setting::factory()->create([
            'type' => Setting::TYPE_BOOLEAN,
            'value' => 'true',
        ]);
        $this->assertTrue($setting->getTypedValue());
    }

    public function test_setting_get_typed_value_returns_integer(): void
    {
        $setting = Setting::factory()->create([
            'type' => Setting::TYPE_INTEGER,
            'value' => '42',
        ]);
        $this->assertEquals(42, $setting->getTypedValue());
    }

    public function test_setting_get_typed_value_returns_json(): void
    {
        $setting = Setting::factory()->create([
            'type' => Setting::TYPE_JSON,
            'value' => json_encode(['key' => 'value']),
        ]);
        $this->assertEquals(['key' => 'value'], $setting->getTypedValue());
    }

    public function test_setting_scope_by_group_filters_correctly(): void
    {
        Setting::factory()->create(['group' => Setting::GROUP_GENERAL]);
        Setting::factory()->create(['group' => Setting::GROUP_SECURITY]);
        
        $general = Setting::byGroup(Setting::GROUP_GENERAL)->get();
        $this->assertCount(1, $general);
    }

    public function test_setting_scope_public_filters_correctly(): void
    {
        Setting::factory()->create(['is_public' => true]);
        Setting::factory()->create(['is_public' => false]);
        
        $public = Setting::public()->get();
        $this->assertCount(1, $public);
        $this->assertTrue($public->first()->is_public);
    }

    public function test_setting_group_label_attribute(): void
    {
        $setting = Setting::factory()->create(['group' => Setting::GROUP_AI]);
        $this->assertEquals('AI Configuration', $setting->group_label);
        
        $setting->group = 'custom';
        $this->assertEquals('Custom', $setting->group_label);
    }

    // ─── Contact Model Tests ─────────────────────────────────────────────

    public function test_contact_has_correct_fillable_attributes(): void
    {
        $contact = new Contact();
        $this->assertEquals([
            'uuid', 'user_id', 'phone', 'name', 'email', 'type', 'title',
            'company', 'avatar_url', 'metadata', 'attributes', 'is_active', 'last_seen_at',
        ], $contact->getFillable());
    }

    public function test_contact_type_constants_exist(): void
    {
        $this->assertEquals('contact', Contact::TYPE_CONTACT);
        $this->assertEquals('client', Contact::TYPE_CLIENT);
        $this->assertEquals('family', Contact::TYPE_FAMILY);
        $this->assertEquals('friend', Contact::TYPE_FRIEND);
        $this->assertEquals('fiancée', Contact::TYPE_FIANCEE);
        $this->assertEquals('partner', Contact::TYPE_PARTNER);
        $this->assertEquals('prospect', Contact::TYPE_PROSPECT);
        $this->assertEquals('vendor', Contact::TYPE_VENDOR);
    }

    public function test_contact_can_be_created_with_factory(): void
    {
        $contact = Contact::factory()->create();
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
        $this->assertNotNull($contact->name);
    }

    public function test_contact_has_conversations_relationship(): void
    {
        $contact = Contact::factory()->create();
        $conversation = Conversation::factory()->create(['contact_id' => $contact->id]);
        $this->assertTrue($contact->conversations->contains($conversation));
    }

    public function test_contact_has_notes_relationship(): void
    {
        $contact = Contact::factory()->create();
        $note = \App\Models\ContactNote::factory()->create(['contact_id' => $contact->id]);
        $this->assertTrue($contact->notes->contains($note));
    }

    public function test_contact_has_memories_relationship(): void
    {
        $contact = Contact::factory()->create();
        $memory = Memory::factory()->create(['contact_id' => $contact->id]);
        $this->assertTrue($contact->memories->contains($memory));
    }

    public function test_contact_scope_of_type_filters_correctly(): void
    {
        Contact::factory()->create(['type' => Contact::TYPE_CLIENT]);
        Contact::factory()->create(['type' => Contact::TYPE_FAMILY]);
        
        $clients = Contact::ofType(Contact::TYPE_CLIENT)->get();
        $this->assertCount(1, $clients);
        $this->assertEquals(Contact::TYPE_CLIENT, $clients->first()->type);
    }

    public function test_contact_scope_search_filters_by_name(): void
    {
        Contact::factory()->create(['name' => 'Alice Wonderland']);
        Contact::factory()->create(['name' => 'Bob Builder']);
        
        $results = Contact::search('Alice')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Alice Wonderland', $results->first()->name);
    }

    public function test_contact_scope_search_filters_by_email(): void
    {
        Contact::factory()->create(['email' => 'alice@example.com']);
        Contact::factory()->create(['email' => 'bob@example.com']);
        
        $results = Contact::search('alice@example.com')->get();
        $this->assertCount(1, $results);
    }

    public function test_contact_type_label_attribute(): void
    {
        $contact = Contact::factory()->create(['type' => Contact::TYPE_CLIENT]);
        $this->assertEquals('Client', $contact->type_label);
        
        $contact->type = 'unknown';
        $this->assertEquals('Unknown', $contact->type_label);
    }

    public function test_contact_get_available_types_returns_all_types(): void
    {
        $types = Contact::getAvailableTypes();
        $this->assertContains(Contact::TYPE_CLIENT, $types);
        $this->assertContains(Contact::TYPE_FAMILY, $types);
        $this->assertCount(8, $types);
    }

    // ─── Memory Model Tests ──────────────────────────────────────────────

    public function test_memory_has_correct_fillable_attributes(): void
    {
        $memory = new Memory();
        $this->assertEquals([
            'contact_id', 'conversation_id', 'source', 'type', 'title',
            'content', 'vector', 'metadata', 'tags', 'expires_at',
        ], $memory->getFillable());
    }

    public function test_memory_has_correct_casts(): void
    {
        $memory = new Memory();
        $casts = $memory->getCasts();
        $this->assertEquals('json', $casts['vector']);
        $this->assertEquals('json', $casts['metadata']);
        $this->assertEquals('json', $casts['tags']);
        $this->assertEquals('datetime', $casts['expires_at']);
    }

    public function test_memory_belongs_to_contact(): void
    {
        $contact = Contact::factory()->create();
        $memory = Memory::factory()->create(['contact_id' => $contact->id]);
        $this->assertTrue($memory->contact->is($contact));
    }

    public function test_memory_belongs_to_conversation(): void
    {
        $contact = Contact::factory()->create();
        $conversation = Conversation::factory()->create(['contact_id' => $contact->id]);
        $memory = Memory::factory()->create([
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
        ]);
        $this->assertTrue($memory->conversation->is($conversation));
    }

    // ─── Message Model Tests ─────────────────────────────────────────────

    public function test_message_has_correct_fillable_attributes(): void
    {
        $message = new Message();
        $this->assertEquals([
            'conversation_id', 'sender_type', 'sender_id', 'direction',
            'content_type', 'content', 'metadata', 'status', 'sent_at', 'received_at',
        ], $message->getFillable());
    }

    public function test_message_has_correct_casts(): void
    {
        $message = new Message();
        $casts = $message->getCasts();
        $this->assertEquals('json', $casts['metadata']);
        $this->assertEquals('datetime', $casts['sent_at']);
        $this->assertEquals('datetime', $casts['received_at']);
    }

    public function test_message_belongs_to_conversation(): void
    {
        $contact = Contact::factory()->create();
        $conversation = Conversation::factory()->create(['contact_id' => $contact->id]);
        $message = Message::factory()->create(['conversation_id' => $conversation->id]);
        $this->assertTrue($message->conversation->is($conversation));
    }

    // ─── Conversation Model Tests ────────────────────────────────────────

    public function test_conversation_has_correct_fillable_attributes(): void
    {
        $conversation = new Conversation();
        $this->assertEquals([
            'contact_id', 'topic_id', 'title', 'status', 'metadata', 'last_message_at',
        ], $conversation->getFillable());
    }

    public function test_conversation_has_correct_casts(): void
    {
        $conversation = new Conversation();
        $casts = $conversation->getCasts();
        $this->assertEquals('json', $casts['metadata']);
        $this->assertEquals('datetime', $casts['last_message_at']);
    }

    public function test_conversation_belongs_to_contact(): void
    {
        $contact = Contact::factory()->create();
        $conversation = Conversation::factory()->create(['contact_id' => $contact->id]);
        $this->assertTrue($conversation->contact->is($contact));
    }

    public function test_conversation_belongs_to_topic(): void
    {
        $contact = Contact::factory()->create();
        $topic = \App\Models\Topic::factory()->create();
        $conversation = Conversation::factory()->create([
            'contact_id' => $contact->id,
            'topic_id' => $topic->id,
        ]);
        $this->assertTrue($conversation->topic->is($topic));
    }

    public function test_conversation_has_messages_relationship(): void
    {
        $contact = Contact::factory()->create();
        $conversation = Conversation::factory()->create(['contact_id' => $contact->id]);
        $message = Message::factory()->create(['conversation_id' => $conversation->id]);
        $this->assertTrue($conversation->messages->contains($message));
    }

    public function test_conversation_has_sessions_relationship(): void
    {
        $contact = Contact::factory()->create();
        $conversation = Conversation::factory()->create(['contact_id' => $contact->id]);
        $session = \App\Models\ConversationSession::factory()->create(['conversation_id' => $conversation->id]);
        $this->assertTrue($conversation->sessions->contains($session));
    }

    // ─── BaseModel Tests ────────────────────────────────────────────────

    public function test_base_model_has_common_casts(): void
    {
        $model = new BaseModel();
        $casts = $model->getCasts();
        $this->assertEquals('json', $casts['metadata']);
        $this->assertEquals('json', $casts['attributes']);
        $this->assertEquals('json', $casts['settings']);
        $this->assertEquals('json', $casts['config']);
    }

    public function test_base_model_generates_uuid(): void
    {
        $uuid = BaseModel::generateUuid();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    public function test_base_model_scope_by_status_single_status(): void
    {
        Agent::factory()->create(['status' => Agent::STATUS_IDLE]);
        Agent::factory()->create(['status' => Agent::STATUS_RUNNING]);
        
        $idle = Agent::byStatus(Agent::STATUS_IDLE)->get();
        $this->assertCount(1, $idle);
    }

    public function test_base_model_scope_by_status_array_status(): void
    {
        Agent::factory()->create(['status' => Agent::STATUS_IDLE]);
        Agent::factory()->create(['status' => Agent::STATUS_RUNNING]);
        Agent::factory()->create(['status' => Agent::STATUS_ERROR]);
        
        $results = Agent::byStatus([Agent::STATUS_IDLE, Agent::STATUS_RUNNING])->get();
        $this->assertCount(2, $results);
    }

    public function test_base_model_scope_active_filters_correctly(): void
    {
        Agent::factory()->create(['is_active' => true]);
        Agent::factory()->create(['is_active' => false]);
        
        $active = Agent::active()->get();
        $this->assertCount(1, $active);
    }

    public function test_base_model_scope_inactive_filters_correctly(): void
    {
        Agent::factory()->create(['is_active' => true]);
        Agent::factory()->create(['is_active' => false]);
        
        $inactive = Agent::inactive()->get();
        $this->assertCount(1, $inactive);
    }
}
