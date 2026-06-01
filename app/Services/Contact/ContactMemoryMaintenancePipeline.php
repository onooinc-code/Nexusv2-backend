<?php

namespace App\Services\Contact;

use App\Models\ContactMemoryMaintenanceRun;
use App\Models\ContactMemory;
use App\Models\Contact;
use App\Services\LogService;
use Exception;

class ContactMemoryMaintenancePipeline
{
    public function __construct(
        protected LogService $logService
    ) {}

    public function process(ContactMemoryMaintenanceRun $run): void
    {
        try {
            $run->update(['status' => 'running', 'started_at' => now()]);
            
            $operation = $run->operation;
            $scope = $run->scope ?? [];
            $contactId = $scope['contact_id'] ?? null;
            $isDryRun = $run->results['dry_run'] ?? false;
            
            $results = [
                'dry_run' => $isDryRun,
                'items_processed' => 0,
                'items_modified' => 0,
                'items_deleted' => 0,
                'errors' => [],
            ];

            if ($contactId) {
                $contact = Contact::find($contactId);
                if (!$contact) {
                    throw new Exception("Contact not found for maintenance run.");
                }

                switch ($operation) {
                    case 'prune_stale':
                        // Delete memories older than 1 year or not validated recently
                        $query = $contact->memories()->where('created_at', '<', now()->subYear());
                        $results['items_processed'] = $query->count();
                        if (!$isDryRun) {
                            $staleMemories = $query->get();
                            foreach ($staleMemories as $stale) {
                                try {
                                    /** @var \App\Services\Memory\SemanticMemoryService|null $semanticMemory */
                                    $semanticMemory = app()->bound(\App\Services\Memory\SemanticMemoryService::class)
                                        ? app(\App\Services\Memory\SemanticMemoryService::class)
                                        : null;

                                    if ($semanticMemory) {
                                        $semanticMemory->delete($contact->id, [$stale->id]);
                                    }
                                } catch (\Throwable $e) {
                                    $this->logService->warning('SemanticMemoryService delete failed during prune; continuing DB delete.', [
                                        'memory_id' => $stale->id,
                                        'error'     => $e->getMessage(),
                                    ]);
                                }

                                $stale->delete();
                                $results['items_deleted']++;
                            }
                        } else {
                            $results['items_deleted'] = $results['items_processed']; // simulate
                        }
                        break;
                        
                    case 'resolve_duplicates':
                        // Duplicate check and Pinecone indexing rebuild
                        $memories = $contact->memories;
                        $results['items_processed'] = $memories->count();
                        foreach ($memories as $memory) {
                            if (empty($memory->vector)) {
                                \App\Jobs\VectorizeMemoryJob::dispatch($memory->id, $memory->content);
                                $results['items_modified']++;
                            } else {
                                \App\Jobs\SaveToPineconeJob::dispatch($memory->id, $memory->vector);
                                $results['items_modified']++;
                            }
                        }
                        break;
                        
                    case 'erase_data':
                        $results['items_processed'] = 1;
                        if (!$isDryRun) {
                            // Attempt to clear Pinecone vectors; degrade gracefully if unavailable
                            try {
                                /** @var \App\Services\Memory\SemanticMemoryService|null $semanticMemory */
                                $semanticMemory = app()->bound(\App\Services\Memory\SemanticMemoryService::class)
                                    ? app(\App\Services\Memory\SemanticMemoryService::class)
                                    : null;

                                if ($semanticMemory) {
                                    $semanticMemory->delete($contact->id);
                                }
                            } catch (\Throwable $e) {
                                $this->logService->warning('SemanticMemoryService delete failed during erase; continuing DB erase.', [
                                    'contact_id' => $contact->id,
                                    'error'      => $e->getMessage(),
                                ]);
                            }

                            // Safely clear the contact's interaction data
                            $contact->messages()->delete();
                            $contact->memories()->delete();
                            $contact->notificationLogs()->delete();
                            $contact->analysisFindings()->delete();
                            $results['items_deleted'] = 1;
                        }
                        break;

                    default:
                        throw new Exception("Unknown maintenance operation: {$operation}");
                }
            } else {
                throw new Exception("Global maintenance runs are currently disabled.");
            }

            $run->update([
                'status' => 'completed',
                'completed_at' => now(),
                'results' => array_merge($run->results ?? [], $results)
            ]);

            $this->logService->info("Memory Maintenance completed", [
                'run_id' => $run->id,
                'operation' => $operation
            ]);

        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
                'results' => array_merge($run->results ?? [], ['error' => $e->getMessage()])
            ]);
            
            $this->logService->error("Memory Maintenance failed", [
                'run_id' => $run->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
