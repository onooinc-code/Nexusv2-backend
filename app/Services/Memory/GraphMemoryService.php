<?php

namespace App\Services\Memory;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GraphMemoryService
{
    /**
     * Add a node to the graph
     *
     * @param string $label
     * @param string $type
     * @param int|null $relatedId
     * @param string|null $relatedType
     * @param array $properties
     * @return int|null Node ID
     */
    public function addNode(string $label, string $type, int $relatedId = null, string $relatedType = null, array $properties = []): ?int
    {
        try {
            $nodeId = DB::table('graph_nodes')->insertGetId([
                'label' => $label,
                'type' => $type,
                'related_id' => $relatedId,
                'related_type' => $relatedType,
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $nodeId;
        } catch (\Exception $e) {
            Log::error('GraphMemoryService::addNode failed', [
                'label' => $label,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Add an edge between two nodes
     *
     * @param int $fromNodeId
     * @param int $toNodeId
     * @param string $label
     * @param array $properties
     * @return int|null Edge ID
     */
    public function addEdge(int $fromNodeId, int $toNodeId, string $label, array $properties = []): ?int
    {
        try {
            // Validate that both nodes exist
            $fromNode = DB::table('graph_nodes')->where('id', $fromNodeId)->first();
            $toNode = DB::table('graph_nodes')->where('id', $toNodeId)->first();

            if (!$fromNode || !$toNode) {
                Log::warning('GraphMemoryService::addEdge - One or both nodes not found', [
                    'fromNodeId' => $fromNodeId,
                    'toNodeId' => $toNodeId
                ]);
                return null;
            }

            $edgeId = DB::table('graph_edges')->insertGetId([
                'from_node' => $fromNodeId,
                'to_node' => $toNodeId,
                'label' => $label,
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $edgeId;
        } catch (\Exception $e) {
            Log::error('GraphMemoryService::addEdge failed', [
                'fromNodeId' => $fromNodeId,
                'toNodeId' => $toNodeId,
                'label' => $label,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get nodes by type and/or related entity
     *
     * @param string|null $type
     * @param int|null $relatedId
     * @param string|null $relatedType
     * @param int $limit
     * @return array
     */
    public function getNodes(string $type = null, int $relatedId = null, string $relatedType = null, int $limit = 50): array
    {
        try {
            $query = DB::table('graph_nodes');

            if ($type) {
                $query->where('type', $type);
            }

            if ($relatedId) {
                $query->where('related_id', $relatedId);
            }

            if ($relatedType) {
                $query->where('related_type', $relatedType);
            }

            $results = $query->limit($limit)
                ->orderBy('created_at', 'desc')
                ->get();

            $nodes = [];
            foreach ($results as $row) {
                $nodes[] = [
                    'id' => $row->id,
                    'label' => $row->label,
                    'type' => $row->type,
                    'related_id' => $row->related_id,
                    'related_type' => $row->related_type,
                    'properties' => json_decode($row->properties, true),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            }

            return $nodes;
        } catch (\Exception $e) {
            Log::error('GraphMemoryService::getNodes failed', [
                'type' => $type,
                'relatedId' => $relatedId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get edges for a node
     *
     * @param int $nodeId
     * @param string|null $direction 'in', 'out', or null for both
     * @param int $limit
     * @return array
     */
    public function getEdges(int $nodeId, string $direction = null, int $limit = 50): array
    {
        try {
            $query = DB::table('graph_edges');

            if ($direction === 'in') {
                $query->where('to_node', $nodeId);
            } elseif ($direction === 'out') {
                $query->where('from_node', $nodeId);
            } else {
                // Both directions
                $query->where('from_node', $nodeId)
                    ->orWhere('to_node', $nodeId);
            }

            $results = $query->limit($limit)
                ->orderBy('created_at', 'desc')
                ->get();

            $edges = [];
            foreach ($results as $row) {
                $edges[] = [
                    'id' => $row->id,
                    'from_node' => $row->from_node,
                    'to_node' => $row->to_node,
                    'label' => $row->label,
                    'properties' => json_decode($row->properties, true),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            }

            return $edges;
        } catch (\Exception $e) {
            Log::error('GraphMemoryService::getEdges failed', [
                'nodeId' => $nodeId,
                'direction' => $direction,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Delete a node and its associated edges
     *
     * @param int $nodeId
     * @return bool
     */
    public function deleteNode(int $nodeId): bool
    {
        try {
            // Delete associated edges first
            DB::table('graph_edges')
                ->where('from_node', $nodeId)
                ->orWhere('to_node', $nodeId)
                ->delete();

            // Delete the node
            $affected = DB::table('graph_nodes')
                ->where('id', $nodeId)
                ->delete();

            return $affected > 0;
        } catch (\Exception $e) {
            Log::error('GraphMemoryService::deleteNode failed', [
                'nodeId' => $nodeId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete an edge
     *
     * @param int $edgeId
     * @return bool
     */
    public function deleteEdge(int $edgeId): bool
    {
        try {
            $affected = DB::table('graph_edges')
                ->where('id', $edgeId)
                ->delete();

            return $affected > 0;
        } catch (\Exception $e) {
            Log::error('GraphMemoryService::deleteEdge failed', [
                'edgeId' => $edgeId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Find shortest path between two nodes (simplified BFS)
     *
     * @param int $startNodeId
     * @param int $endNodeId
     * @param int $maxDepth
     * @return array|null Path as array of node IDs, or null if no path
     */
    public function findShortestPath(int $startNodeId, int $endNodeId, int $maxDepth = 10): ?array
    {
        try {
            if ($startNodeId === $endNodeId) {
                return [$startNodeId];
            }

            // Simple BFS implementation
            $queue = [[$startNodeId]]; // Each element is a path (array of node IDs)
            $visited = [$startNodeId => true];

            while (!empty($queue) && count($queue[0]) <= $maxDepth) {
                $path = array_shift($queue);
                $nodeId = end($path);

                // Get neighbors (both incoming and outgoing edges)
                $neighbors = [];
                $edges = DB::table('graph_edges')
                    ->where('from_node', $nodeId)
                    ->orWhere('to_node', $nodeId)
                    ->get();

                foreach ($edges as $edge) {
                    $neighborId = ($edge->from_node == $nodeId) ? $edge->to_node : $edge->from_node;
                    if (!isset($visited[$neighborId])) {
                        $neighbors[] = $neighborId;
                        $visited[$neighborId] = true;
                    }
                }

                foreach ($neighbors as $neighborId) {
                    $newPath = [...$path, $neighborId];
                    if ($neighborId === $endNodeId) {
                        return $newPath;
                    }
                    $queue[] = $newPath;
                }
            }

            return null; // No path found within maxDepth
        } catch (\Exception $e) {
            Log::error('GraphMemoryService::findShortestPath failed', [
                'startNodeId' => $startNodeId,
                'endNodeId' => $endNodeId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}