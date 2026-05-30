<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MemoryIndexed;
use App\Jobs\ExtractMemoryJob;
use App\Jobs\SyncMemoryJob;
use App\Models\Contact;
use App\Models\Conversation;
use App\Services\LogService;
use App\Services\Memory\WorkingMemoryService;
use App\Services\Memory\EpisodicMemoryService;
use App\Services\Memory\SemanticMemoryService;
use App\Services\Memory\StructuredMemoryService;
use App\Services\Memory\GraphMemoryService;
use App\Services\Memory\MemoryRouter;
use App\Services\Memory\MemoryMaintenanceService;
use App\Services\Memory\MemorySummaryService;
use App\Integrations\Mem0Integration;

class MemoryController extends Controller
{
    protected $workingMemoryService;
    protected $episodicMemoryService;
    protected $semanticMemoryService;
    protected $structuredMemoryService;
    protected $graphMemoryService;
    protected $memoryRouter;
    protected $memoryMaintenanceService;
    protected $memorySummaryService;
    protected $mem0Integration;
    protected LogService $logService;

    public function __construct(
        WorkingMemoryService $workingMemoryService,
        EpisodicMemoryService $episodicMemoryService,
        SemanticMemoryService $semanticMemoryService = null,
        StructuredMemoryService $structuredMemoryService = null,
        GraphMemoryService $graphMemoryService = null,
        MemoryRouter $memoryRouter = null,
        MemoryMaintenanceService $memoryMaintenanceService = null,
        MemorySummaryService $memorySummaryService = null,
        Mem0Integration $mem0Integration = null,
        LogService $logService
    ) {
        $this->workingMemoryService = $workingMemoryService;
        $this->episodicMemoryService = $episodicMemoryService;
        $this->semanticMemoryService = $semanticMemoryService;
        $this->structuredMemoryService = $structuredMemoryService;
        $this->graphMemoryService = $graphMemoryService;
        $this->memoryRouter = $memoryRouter ?? new MemoryRouter(
            $workingMemoryService,
            $episodicMemoryService,
            $semanticMemoryService,
            $structuredMemoryService,
            $graphMemoryService
        );
        $this->memoryMaintenanceService = $memoryMaintenanceService ?? new MemoryMaintenanceService();
        $this->memorySummaryService = $memorySummaryService ?? new MemorySummaryService();
        $this->mem0Integration = $mem0Integration ?? new Mem0Integration();
        $this->logService = $logService;
    }

    /**
     * Display a listing of memories.
     */
    public function index()
    {
        return response()->json(['data' => []]);
    }

    /**
     * Store a newly created memory in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:working,episodic,semantic,structured,graph',
            'contactId' => 'required_if:type,episodic,semantic,structured|integer|exists:contacts,id',
            'content' => 'required_if:type,episodic,semantic|string',
            'data' => 'sometimes|array',
            'metadata' => 'sometimes|array',
            'factType' => 'required_if:type,structured|string',
            'label' => 'required_if:type,graph|string',
            'nodeType' => 'required_if:type,graph|string',
        ]);

        try {
            $result = null;
            $type = $validated['type'];

            switch ($type) {
                case 'working':
                    $result = $this->workingMemoryService->store(
                        $validated['key'] ?? uniqid(),
                        $validated['value'] ?? $validated['content'] ?? $validated['data'],
                        $validated['ttl'] ?? null
                    );
                    break;
                case 'episodic':
                    $result = $this->episodicMemoryService->storeMessage(
                        $validated['contactId'],
                        $validated['content'],
                        $validated['sender'] ?? 'user',
                        $validated['metadata'] ?? []
                    );

                    if ($result && $this->semanticMemoryService) {
                        $this->semanticMemoryService->store(
                            $validated['contactId'],
                            $validated['content'],
                            array_merge($validated['metadata'] ?? [], ['source' => 'episodic'])
                        );
                    }

                    if ($result && $contact = Contact::find($validated['contactId'])) {
                        // Memory stored successfully; Pinecone indexing will emit MemoryIndexed when complete.
                    }
                    break;
                case 'semantic':
                    if ($this->semanticMemoryService) {
                        $result = $this->semanticMemoryService->store(
                            $validated['contactId'],
                            $validated['content'],
                            $validated['metadata'] ?? []
                        );

                        if ($result && $contact = Contact::find($validated['contactId'])) {
                            // Memory stored successfully; indexing will emit MemoryIndexed when available.
                        }
                    }
                    break;
                case 'structured':
                    if ($this->structuredMemoryService) {
                        $result = $this->structuredMemoryService->store(
                            $validated['contactId'],
                            $validated['factType'],
                            $validated['data'] ?? [],
                            $validated['metadata'] ?? []
                        );

                        if ($result && $contact = Contact::find($validated['contactId'])) {
                            // Structured memory stored successfully; SyncMemoryJob will keep memory services in sync.
                            SyncMemoryJob::dispatch($validated['contactId'], 'structured');
                        }
                    }
                    break;
                case 'graph':
                    if ($this->graphMemoryService) {
                        $result = $this->graphMemoryService->addNode(
                            $validated['label'],
                            $validated['nodeType'],
                            $validated['relatedId'] ?? null,
                            $validated['relatedType'] ?? null,
                            $validated['properties'] ?? []
                        );
                    }
                    break;
            }

            if ($result !== null && $result !== false) {
                $this->logService->info('Memory created', [
                    'channel' => 'memory',
                    'type' => 'create',
                    'related_id' => $result,
                    'related_type' => 'App\Models\Memory',
                    'user_id' => $request->user()?->id,
                    'context' => ['memory_type' => $type],
                ]);

                return response()->json([
                    'message' => "Memory created successfully",
                    'id' => is_int($result) ? $result : null,
                    'type' => $type
                ], 201);
            }

            return response()->json([
                'message' => 'Failed to create memory'
            ], 500);
        } catch (\Exception $e) {
            $this->logService->error('Memory creation failed', [
                'channel' => 'memory',
                'type' => 'create',
                'context' => ['error' => $e->getMessage(), 'request' => $request->all()],
            ]);

            return response()->json([
                'message' => 'An error occurred while creating the memory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified memory.
     */
    public function show($id)
    {
        // For simplicity, we'll assume this is an episodic memory ID
        // In a real implementation, we might need to determine the type from context
        try {
            $memory = $this->episodicMemoryService->retrieve((int) $id, 1)->first();

            if ($memory) {
                return response()->json(['data' => $memory]);
            }

            return response()->json([
                'message' => 'Memory not found'
            ], 404);
        } catch (\Exception $e) {
            $this->logService->error('Memory retrieval failed', [
                'channel' => 'memory',
                'type' => 'retrieve',
                'context' => ['id' => $id, 'error' => $e->getMessage()],
            ]);

            return response()->json([
                'message' => 'An error occurred while retrieving the memory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified memory in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:working,episodic,semantic,structured,graph',
            'contactId' => 'required_if:type,episodic,semantic,structured|integer|exists:contacts,id',
            'content' => 'sometimes|string',
            'data' => 'sometimes|array',
            'metadata' => 'sometimes|array',
            'factType' => 'sometimes|string',
            'label' => 'sometimes|string',
            'nodeType' => 'sometimes|string',
            'ttl' => 'sometimes|integer',
        ]);

        try {
            $result = false;
            $type = $validated['type'];

            switch ($type) {
                case 'working':
                    $result = $this->workingMemoryService->update(
                        $id,
                        $validated['value'] ?? $validated['content'] ?? $validated['data'],
                        $validated['ttl'] ?? null
                    );
                    break;
                case 'episodic':
                    // For episodic, we might update the message content
                    $message = $this->episodicMemoryService->retrieve($id, 1)->first();
                    if ($message) {
                        $result = $this->episodicMemoryService->update(
                            $id,
                            $validated['content'] ?? $message->content,
                            $validated['sender'] ?? 'user',
                            array_merge(
                                json_decode($message->metadata ?: '{}', true),
                                $validated['metadata'] ?? []
                            )
                        );
                    }
                    break;
                case 'semantic':
                    // Semantic updates would require deleting and re-adding
                    // For simplicity, we'll mark as not implemented
                    if ($this->semanticMemoryService) {
                        // This would need a proper update method in the service
                        return response()->json([
                            'message' => 'Semantic memory update not implemented'
                        ], 501);
                    }
                    break;
                case 'structured':
                    if ($this->structuredMemoryService) {
                        $result = $this->structuredMemoryService->update(
                            $id,
                            $validated['factType'] ?? null,
                            $validated['data'] ?? null,
                            $validated['metadata'] ?? []
                        );
                    }
                    break;
                case 'graph':
                    // Graph updates would be more complex (update node/edge properties)
                    // For simplicity, we'll mark as not implemented
                    if ($this->graphMemoryService) {
                        return response()->json([
                            'message' => 'Graph memory update not implemented'
                        ], 501);
                    }
                    break;
            }

            if ($result) {
                $this->logService->info('Memory updated', [
                    'channel' => 'memory',
                    'type' => 'update',
                    'related_id' => $id,
                    'related_type' => 'App\Models\Memory',
                    'user_id' => $request->user()?->id,
                    'context' => ['memory_type' => $type],
                ]);

                return response()->json([
                    'message' => 'Memory updated successfully',
                    'id' => $id
                ]);
            }

            return response()->json([
                'message' => 'Failed to update memory'
            ], 400);
        } catch (\Exception $e) {
            $this->logService->error('Memory update failed', [
                'channel' => 'memory',
                'type' => 'update',
                'context' => ['id' => $id, 'error' => $e->getMessage()],
            ]);

            return response()->json([
                'message' => 'An error occurred while updating the memory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified memory from storage.
     */
    public function destroy($id)
    {
        // For simplicity, we'll assume this is an episodic memory ID
        // In a real implementation, we might need to determine the type from context
        try {
            $result = $this->episodicMemoryService->delete($id);

            if ($result) {
                $this->logService->info('Memory deleted', [
                    'channel' => 'memory',
                    'type' => 'delete',
                    'related_id' => $id,
                    'related_type' => 'App\Models\Memory',
                    'user_id' => request()->user()?->id,
                ]);

                return response()->json([
                    'message' => 'Memory deleted successfully',
                    'id' => $id
                ]);
            }

            return response()->json([
                'message' => 'Memory not found or could not be deleted'
            ], 404);
        } catch (\Exception $e) {
            $this->logService->error('Memory deletion failed', [
                'channel' => 'memory',
                'type' => 'delete',
                'context' => ['id' => $id, 'error' => $e->getMessage()],
            ]);

            return response()->json([
                'message' => 'An error occurred while deleting the memory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search for memories.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string',
            'contactId' => 'sometimes|integer|exists:contacts,id',
            'types' => 'sometimes|array',
            'types.*' => 'string|in:working,episodic,semantic,structured,graph',
            'limit' => 'sometimes|integer|min:1|max:100',
            'offset' => 'sometimes|integer|min:0',
        ]);

        try {
            $results = [];
            $limit = $validated['limit'] ?? 20;
            $offset = $validated['offset'] ?? 0;
            $types = $validated['types'] ?? ['working', 'episodic', 'semantic', 'structured', 'graph'];
            $contactId = $validated['contactId'] ?? null;
            $query = $validated['query'];

            // Search each memory type
            foreach ($types as $type) {
                switch ($type) {
                    case 'working':
                        // Working memory search is limited since Redis doesn't have good search
                        // We would need to scan all keys or use a different approach
                        // For now, we'll skip working memory search in this implementation
                        break;
                    case 'episodic':
                        if ($contactId !== null) {
                            $episodicResults = $this->episodicMemoryService->retrieve(
                                $contactId,
                                $limit,
                                $offset
                            );

                            // Filter by query content (simple search)
                            $filtered = $episodicResults->filter(function ($memory) use ($query) {
                                return stripos($memory->content ?? '', $query) !== false;
                            });

                            $results['episodic'] = $filtered->all();
                        }
                        break;
                    case 'semantic':
                        if ($this->semanticMemoryService && $contactId !== null) {
                            $semanticResults = $this->semanticMemoryService->retrieve(
                                $contactId,
                                $query,
                                $limit
                            );
                            $results['semantic'] = $semanticResults;
                        }
                        break;
                    case 'structured':
                        if ($this->structuredMemoryService && $contactId !== null) {
                            $structuredResults = $this->structuredMemoryService->search(
                                $contactId,
                                $query,
                                $limit
                            );
                            $results['structured'] = $structuredResults;
                        }
                        break;
                    case 'graph':
                        // Graph search would be more complex (search node labels, properties, etc.)
                        // For simplicity, we'll search node labels
                        if ($this->graphMemoryService) {
                            $graphResults = $this->graphMemoryService->getNodes(
                                null, // type
                                null, // relatedId
                                null, // relatedType
                                $limit
                            );

                            // Filter by query in label or properties
                            $filtered = array_filter($graphResults, function ($node) use ($query) {
                                return stripos($node['label'] ?? '', $query) !== false ||
                                       (is_array($node['properties']) &&
                                        array_filter($node['properties'], function ($value) use ($query) {
                                            return is_string($value) && stripos($value, $query) !== false;
                                        }) !== []
                                       );
                            });

                            $results['graph'] = array_values($filtered);
                        }
                        break;
                }
            }

            return response()->json([
                'query' => $query,
                'contactId' => $contactId,
                'results' => $results,
                'totalResults' => array_sum(array_map('count', $results))
            ]);
        } catch (\Exception $e) {
            $this->logService->error('Memory search failed', [
                'channel' => 'memory',
                'type' => 'search',
                'context' => ['error' => $e->getMessage(), 'request' => $request->all()],
            ]);

            return response()->json([
                'message' => 'An error occurred while searching memories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Index a memory for semantic search.
     */
    public function indexMemory(Request $request, $id)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:episodic,structured',
            'contactId' => 'required|integer|exists:contacts,id',
        ]);

        $conversation = Conversation::find($id);
        if (! $conversation) {
            return response()->json([
                'message' => 'Conversation not found for memory extraction',
            ], 404);
        }

        ExtractMemoryJob::dispatch($conversation->id);

        $this->logService->info('Memory extraction queued', [
            'channel' => 'memory',
            'type' => 'extract',
            'related_id' => $conversation->id,
            'related_type' => 'App\Models\Conversation',
            'user_id' => $request->user()?->id,
            'context' => ['memory_type' => $validated['type']],
        ]);

        return response()->json([
            'message' => 'Memory extraction dispatched',
            'conversation_id' => $conversation->id,
            'status' => 'queued',
        ], 202);
    }
}
