<?php

namespace App\Services\Memory;

use App\Services\Memory\WorkingMemoryService;
use App\Services\Memory\EpisodicMemoryService;
use App\Services\Memory\SemanticMemoryService;
use App\Services\Memory\StructuredMemoryService;
use App\Services\Memory\GraphMemoryService;

class MemoryRouter
{
    protected $workingMemoryService;
    protected $episodicMemoryService;
    protected $semanticMemoryService;
    protected $structuredMemoryService;
    protected $graphMemoryService;

    public function __construct(
        WorkingMemoryService $workingMemoryService,
        EpisodicMemoryService $episodicMemoryService,
        SemanticMemoryService $semanticMemoryService = null,
        StructuredMemoryService $structuredMemoryService = null,
        GraphMemoryService $graphMemoryService = null
    ) {
        $this->workingMemoryService = $workingMemoryService;
        $this->episodicMemoryService = $episodicMemoryService;
        $this->semanticMemoryService = $semanticMemoryService;
        $this->structuredMemoryService = $structuredMemoryService;
        $this->graphMemoryService = $graphMemoryService;
    }

    /**
     * Route a memory operation to the appropriate storage service.
     *
     * @param string $type Memory type (working, episodic, semantic, structured, graph)
     * @return object The service instance for the given memory type
     * @throws \InvalidArgumentException
     */
    public function route(string $type)
    {
        return match ($type) {
            'working' => $this->workingMemoryService,
            'episodic' => $this->episodicMemoryService,
            'semantic' => $this->semanticMemoryService,
            'structured' => $this->structuredMemoryService,
            'graph' => $this->graphMemoryService,
            default => throw new \InvalidArgumentException("Invalid memory type: {$type}"),
        };
    }

    /**
     * Store a memory in the appropriate storage.
     *
     * @param string $type Memory type
     * @param mixed $data Data to store
     * @return mixed
     */
    public function store(string $type, $data)
    {
        $service = $this->route($type);
        // Assuming each service has a store method that accepts $data
        // We'll need to adjust based on the actual service method signatures
        if (method_exists($service, 'store')) {
            return $service->store($data);
        }

        // Fallback for services with different method signatures
        return match ($type) {
            'working' => $this->workingMemoryService->store(
                $data['key'] ?? uniqid(),
                $data['value'] ?? $data,
                $data['ttl'] ?? null
            ),
            'episodic' => $this->episodicMemoryService->storeMessage(
                $data['contactId'],
                $data['content'],
                $data['sender'] ?? 'user',
                $data['metadata'] ?? []
            ),
            'semantic' => $this->semanticMemoryService ? 
                $this->semanticMemoryService->store(
                    $data['contactId'],
                    $data['content'],
                    $data['metadata'] ?? []
                ) : null,
            'structured' => $this->structuredMemoryService ? 
                $this->structuredMemoryService->store(
                    $data['contactId'],
                    $data['factType'],
                    $data['data'],
                    $data['metadata'] ?? []
                ) : null,
            'graph' => $this->graphMemoryService ? 
                $this->graphMemoryService->addNode(
                    $data['label'],
                    $data['type'],
                    $data['relatedId'] ?? null,
                    $data['relatedType'] ?? null,
                    $data['properties'] ?? []
                ) : null,
        };
    }

    /**
     * Retrieve a memory from the appropriate storage.
     *
     * @param string $type Memory type
     * @param mixed $identifier Identifier for the memory
     * @return mixed
     */
    public function retrieve(string $type, $identifier)
    {
        $service = $this->route($type);
        if (method_exists($service, 'get') || method_exists($service, 'retrieve')) {
            if (method_exists($service, 'get')) {
                return $service->get($identifier);
            }
            return $service->retrieve($identifier);
        }

        // Fallback for services with different method signatures
        return match ($type) {
            'working' => $this->workingMemoryService->get($identifier),
            'episodic' => $this->episodicMemoryService->retrieve($identifier),
            'semantic' => $this->semanticMemoryService ? 
                $this->semanticMemoryService->retrieve(
                    $identifier['contactId'] ?? 0,
                    $identifier['query'] ?? '',
                    $identifier['limit'] ?? 10
                ) : null,
            'structured' => $this->structuredMemoryService ? 
                $this->structuredMemoryService->retrieve(
                    $identifier['contactId'] ?? 0,
                    $identifier['factType'] ?? null,
                    $identifier['limit'] ?? 50,
                    $identifier['offset'] ?? 0
                ) : null,
            'graph' => $this->graphMemoryService ? 
                $this->graphMemoryService->getNodes(
                    $identifier['type'] ?? null,
                    $identifier['relatedId'] ?? null,
                    $identifier['relatedType'] ?? null,
                    $identifier['limit'] ?? 50
                ) : null,
        };
    }

    /**
     * Update a memory in the appropriate storage.
     *
     * @param string $type Memory type
     * @param mixed $identifier Identifier for the memory
     * @param mixed $data New data
     * @return mixed
     */
    public function update(string $type, $identifier, $data)
    {
        $service = $this->route($type);
        if (method_exists($service, 'update')) {
            return $service->update($identifier, $data);
        }

        // Fallback for services with different method signatures
        return match ($type) {
            'working' => $this->workingMemoryService->update(
                $identifier,
                $data['value'] ?? $data,
                $data['ttl'] ?? null
            ),
            'episodic' => $this->episodicMemoryService->update(
                $identifier,
                $data['content'] ?? null,
                $data['sender'] ?? null,
                $data['metadata'] ?? []
            ),
            'semantic' => $this->semanticMemoryService ? 
                $this->semanticMemoryService->update(
                    $identifier['vectorId'] ?? null,
                    $identifier['contactId'] ?? null,
                    $identifier['content'] ?? null,
                    $identifier['metadata'] ?? []
                ) : null,
            'structured' => $this->structuredMemoryService ? 
                $this->structuredMemoryService->update(
                    $identifier['id'] ?? 0,
                    $identifier['factType'] ?? null,
                    $identifier['data'] ?? null,
                    $identifier['metadata'] ?? []
                ) : null,
            'graph' => $this->graphMemoryService ? 
                // For graph, update might be more complex; we'll skip for now
                null : null,
        };
    }

    /**
     * Delete a memory from the appropriate storage.
     *
     * @param string $type Memory type
     * @param mixed $identifier Identifier for the memory
     * @return bool
     */
    public function delete(string $type, $identifier): bool
    {
        $service = $this->route($type);
        if (method_exists($service, 'delete')) {
            return $service->delete($identifier);
        }

        // Fallback for services with different method signatures
        return match ($type) {
            'working' => $this->workingMemoryService->delete($identifier),
            'episodic' => $this->episodicMemoryService->delete($identifier),
            'semantic' => $this->semanticMemoryService ? 
                $this->semanticMemoryService->delete(
                    $identifier['contactId'] ?? '',
                    is_array($identifier) ? $identifier : [$identifier]
                ) : false,
            'structured' => $this->structuredMemoryService ? 
                $this->structuredMemoryService->delete(
                    $identifier['id'] ?? 0
                ) : false,
            'graph' => $this->graphMemoryService ? 
                $this->graphMemoryService->deleteNode(
                    $identifier['nodeId'] ?? 0
                ) : false,
        };
    }
}