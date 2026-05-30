<?php

namespace App\Repositories;

use App\Services\Memory\WorkingMemoryService;
use App\Services\Memory\EpisodicMemoryService;
use App\Services\Memory\SemanticMemoryService;
use App\Services\Memory\StructuredMemoryService;
use App\Services\Memory\GraphMemoryService;

class MemoryRepository
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
     * Create a memory in the appropriate storage layer
     *
     * @param string $type Memory type (working, episodic, semantic, structured, graph)
     * @param mixed $data Data to store
     * @return mixed
     */
    public function create(string $type, $data)
    {
        return match ($type) {
            'working' => $this->workingMemoryService->store($data),
            'episodic' => $this->episodicMemoryService->store($data),
            'semantic' => $this->semanticMemoryService ? $this->semanticMemoryService->store($data) : null,
            'structured' => $this->structuredMemoryService ? $this->structuredMemoryService->store($data) : null,
            'graph' => $this->graphMemoryService ? $this->graphMemoryService->store($data) : null,
            default => throw new \InvalidArgumentException("Invalid memory type: {$type}"),
        };
    }

    /**
     * Read a memory from the appropriate storage layer
     *
     * @param string $type Memory type
     * @param mixed $identifier Identifier for the memory
     * @return mixed
     */
    public function read(string $type, $identifier)
    {
        return match ($type) {
            'working' => $this->workingMemoryService->get($identifier),
            'episodic' => $this->episodicMemoryService->get($identifier),
            'semantic' => $this->semanticMemoryService ? $this->semanticMemoryService->get($identifier) : null,
            'structured' => $this->structuredMemoryService ? $this->structuredMemoryService->get($identifier) : null,
            'graph' => $this->graphMemoryService ? $this->graphMemoryService->get($identifier) : null,
            default => throw new \InvalidArgumentException("Invalid memory type: {$type}"),
        };
    }

    /**
     * Update a memory in the appropriate storage layer
     *
     * @param string $type Memory type
     * @param mixed $identifier Identifier for the memory
     * @param mixed $data New data
     * @return mixed
     */
    public function update(string $type, $identifier, $data)
    {
        return match ($type) {
            'working' => $this->workingMemoryService->update($identifier, $data),
            'episodic' => $this->episodicMemoryService->update($identifier, $data),
            'semantic' => $this->semanticMemoryService ? $this->semanticMemoryService->update($identifier, $data) : null,
            'structured' => $this->structuredMemoryService ? $this->structuredMemoryService->update($identifier, $data) : null,
            'graph' => $this->graphMemoryService ? $this->graphMemoryService->update($identifier, $data) : null,
            default => throw new \InvalidArgumentException("Invalid memory type: {$type}"),
        };
    }

    /**
     * Delete a memory from the appropriate storage layer
     *
     * @param string $type Memory type
     * @param mixed $identifier Identifier for the memory
     * @return bool
     */
    public function delete(string $type, $identifier): bool
    {
        return match ($type) {
            'working' => $this->workingMemoryService->delete($identifier),
            'episodic' => $this->episodicMemoryService->delete($identifier),
            'semantic' => $this->semanticMemoryService ? $this->semanticMemoryService->delete($identifier) : false,
            'structured' => $this->structuredMemoryService ? $this->structuredMemoryService->delete($identifier) : false,
            'graph' => $this->graphMemoryService ? $this->graphMemoryService->delete($identifier) : false,
            default => throw new \InvalidArgumentException("Invalid memory type: {$type}"),
        };
    }

    /**
     * Search for memories across storage layers
     *
     * @param string $query Search query
     * @param array $types Memory types to search in (optional)
     * @return array
     */
    public function search(string $query, array $types = []): array
    {
        $results = [];
        $typesToSearch = $types ?: ['working', 'episodic', 'semantic', 'structured', 'graph'];

        foreach ($typesToSearch as $type) {
            switch ($type) {
                case 'working':
                    $results[$type] = $this->workingMemoryService->search($query);
                    break;
                case 'episodic':
                    $results[$type] = $this->episodicMemoryService->search($query);
                    break;
                case 'semantic':
                    if ($this->semanticMemoryService) {
                        $results[$type] = $this->semanticMemoryService->search($query);
                    }
                    break;
                case 'structured':
                    if ($this->structuredMemoryService) {
                        $results[$type] = $this->structuredMemoryService->search($query);
                    }
                    break;
                case 'graph':
                    if ($this->graphMemoryService) {
                        $results[$type] = $this->graphMemoryService->search($query);
                    }
                    break;
            }
        }

        return $results;
    }
}