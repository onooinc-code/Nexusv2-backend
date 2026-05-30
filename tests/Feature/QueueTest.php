<?php

namespace Tests\Feature;

use App\Jobs\SyncMemoryJob;
use App\Models\Contact;
use App\Models\Memory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_memory_job_is_dispatched_on_memory_create(): void
    {
        Queue::fake([SyncMemoryJob::class]);

        $contact = Contact::factory()->create();
        $memory = Memory::factory()->create([
            'contact_id' => $contact->id,
            'type' => 'episodic',
        ]);

        Queue::assertPushed(SyncMemoryJob::class, function ($job) use ($memory) {
            return $job->memory->id === $memory->id;
        });
    }

    public function test_sync_memory_job_handles_failure_gracefully(): void
    {
        $contact = Contact::factory()->create();
        $memory = Memory::factory()->create([
            'contact_id' => $contact->id,
            'type' => 'episodic',
        ]);

        $job = new SyncMemoryJob($memory);
        $result = $job->handle(new \App\Services\Memory\SemanticMemoryService());

        $this->assertTrue($result);
    }

    public function test_queue_connection_is_sync_in_testing(): void
    {
        $this->assertEquals('sync', config('queue.default'));
    }

    public function test_job_can_be_pushed_to_queue(): void
    {
        Queue::fake();

        SyncMemoryJob::dispatch(
            Memory::factory()->create()
        );

        Queue::assertPushed(SyncMemoryJob::class, 1);
    }
}
