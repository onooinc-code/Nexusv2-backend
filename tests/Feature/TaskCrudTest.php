<?php

namespace Tests\Feature;

use App\Models\AgentTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_crud_and_transition_actions_work()
    {
        file_put_contents('/tmp/taskcrud_progress.log', "test started\n", FILE_APPEND);
        $user = User::factory()->create();
        file_put_contents('/tmp/taskcrud_progress.log', "user created\n", FILE_APPEND);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks', [
                'title' => 'Test task',
                'description' => 'A task description',
                'priority' => 5,
            ]);

        file_put_contents('/tmp/taskcrud_response_post.json', $response->getContent() . "\n", FILE_APPEND);
        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test task')
            ->assertJsonPath('data.status', 'pending');
        file_put_contents('/tmp/taskcrud_progress.log', "post completed\n", FILE_APPEND);

        $task = AgentTask::first();
        file_put_contents('/tmp/taskcrud_progress.log', "task id=" . ($task?->id ?? 'null') . " count=" . AgentTask::count() . "\n", FILE_APPEND);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}", [
                'title' => 'Updated title',
                'priority' => 8,
            ]);

        file_put_contents('/tmp/taskcrud_response_patch.json', $response->getContent() . "\n", FILE_APPEND);
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.priority', 8);
        file_put_contents('/tmp/taskcrud_progress.log', "patch completed\n", FILE_APPEND);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/cancel");

        file_put_contents('/tmp/taskcrud_response_cancel.json', $response->getContent() . "\n", FILE_APPEND);
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $task->id);
        file_put_contents('/tmp/taskcrud_progress.log', "cancel completed\n", FILE_APPEND);
        $this->assertNotNull($task);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/tasks/{$task->id}", [
                'title' => 'Updated title',
                'priority' => 8,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.priority', 8);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $task->id);

        $this->assertDatabaseHas('agent_tasks', ['id' => $task->id]);
    }
}
