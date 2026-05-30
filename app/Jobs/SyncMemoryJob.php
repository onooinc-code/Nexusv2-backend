<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\LogService;
use App\Services\Memory\WorkingMemoryService;
use App\Services\Memory\EpisodicMemoryService;
use App\Services\Memory\SemanticMemoryService;
use App\Services\Memory\StructuredMemoryService;
use App\Services\Memory\GraphMemoryService;

class SyncMemoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contactId;
    protected $memoryType;
    protected LogService $logService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $contactId,
        string $memoryType = 'all',
        LogService $logService
    ) {
        $this->contactId = $contactId;
        $this->memoryType = $memoryType;
        $this->logService = $logService;
    }

    /**
     * Execute the job.
     */
    public function handle(
        WorkingMemoryService $workingMemoryService,
        EpisodicMemoryService $episodicMemoryService,
        SemanticMemoryService $semanticMemoryService,
        StructuredMemoryService $structuredMemoryService,
        GraphMemoryService $graphMemoryService
    ) {
        try {
            $this->logService->info('Starting internal memory sync job', [
                'channel' => 'memory',
                'type' => 'sync',
                'related_id' => $this->contactId,
                'related_type' => 'App\Models\Contact',
                'context' => ['memoryType' => $this->memoryType],
            ]);

            $typesToSync = $this->memoryType === 'all' 
                ? ['working', 'episodic', 'semantic', 'structured', 'graph'] 
                : [$this->memoryType];

            foreach ($typesToSync as $type) {
                switch ($type) {
                    case 'working':
                        $this->syncWorkingMemory($workingMemoryService);
                        break;
                    case 'episodic':
                        $this->syncEpisodicMemory($episodicMemoryService);
                        break;
                    case 'semantic':
                        if ($semanticMemoryService) {
                            $this->syncSemanticMemory($semanticMemoryService);
                        }
                        break;
                    case 'structured':
                        if ($structuredMemoryService) {
                            $this->syncStructuredMemory($structuredMemoryService);
                        }
                        break;
                    case 'graph':
                        if ($graphMemoryService) {
                            $this->syncGraphMemory($graphMemoryService);
                        }
                        break;
                }
            }

            $this->logService->info('Internal memory sync job completed successfully', [
                'channel' => 'memory',
                'type' => 'sync',
                'related_id' => $this->contactId,
                'related_type' => 'App\Models\Contact',
                'context' => ['memoryType' => $this->memoryType],
            ]);
        } catch (\Exception $e) {
            $this->logService->error('Internal memory sync job failed', [
                'channel' => 'memory',
                'type' => 'sync',
                'related_id' => $this->contactId,
                'related_type' => 'App\Models\Contact',
                'context' => ['memoryType' => $this->memoryType, 'error' => $e->getMessage()],
            ]);

            // Optionally, you could re-throw the exception to trigger retry logic
            // throw $e;
        }
    }

    /**
     * Sync working memory internally.
     */
    protected function syncWorkingMemory(WorkingMemoryService $workingMemoryService)
    {
        $this->logService->debug('Performing internal working memory maintenance', [
            'channel' => 'memory',
            'type' => 'sync',
        ]);
        // Add internal sync/maintenance logic here
    }

    /**
     * Sync episodic memory internally.
     */
    protected function syncEpisodicMemory(EpisodicMemoryService $episodicMemoryService)
    {
        $this->logService->debug('Performing internal episodic memory maintenance', [
            'channel' => 'memory',
            'type' => 'sync',
        ]);
        // Add internal sync/maintenance logic here
    }

    /**
     * Sync semantic memory internally.
     */
    protected function syncSemanticMemory(SemanticMemoryService $semanticMemoryService)
    {
        $this->logService->debug('Performing internal semantic memory maintenance', [
            'channel' => 'memory',
            'type' => 'sync',
        ]);
        // Add internal sync/maintenance logic here
    }

    /**
     * Sync structured memory internally.
     */
    protected function syncStructuredMemory(StructuredMemoryService $structuredMemoryService)
    {
        $this->logService->debug('Performing internal structured memory maintenance', [
            'channel' => 'memory',
            'type' => 'sync',
        ]);
        // Add internal sync/maintenance logic here
    }

    /**
     * Sync graph memory internally.
     */
    protected function syncGraphMemory(GraphMemoryService $graphMemoryService)
    {
        $this->logService->debug('Performing internal graph memory maintenance', [
            'channel' => 'memory',
            'type' => 'sync',
        ]);
        // Add internal sync/maintenance logic here
    }
}