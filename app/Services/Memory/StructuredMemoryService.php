<?php

namespace App\Services\Memory;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StructuredMemoryService
{
    /**
     * Store a structured fact or relationship
     *
     * @param int $contactId
     * @param string $factType
     * @param mixed $data
     * @param array $metadata
     * @return bool
     */
    public function store(int $contactId, string $factType, $data, array $metadata = []): bool
    {
        try {
            // Validate contact exists (optional, but good practice)
            // $contact = Contact::find($contactId);
            // if (!$contact) {
            //     Log::warning('StructuredMemoryService::store - Contact not found', [
            //         'contactId' => $contactId
            //     ]);
            //     return false;
            // }

            // Store in structured_memories table
            DB::table('structured_memories')->insert([
                'contact_id' => $contactId,
                'fact_type' => $factType,
                'data' => is_array($data) ? json_encode($data) : $data,
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('StructuredMemoryService::store failed', [
                'contactId' => $contactId,
                'factType' => $factType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Retrieve structured memories for a contact
     *
     * @param int $contactId
     * @param string|null $factType
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function retrieve(int $contactId, string $factType = null, int $limit = 50, int $offset = 0): array
    {
        try {
            $query = DB::table('structured_memories')
                ->where('contact_id', $contactId);

            if ($factType) {
                $query->where('fact_type', $factType);
            }

            $results = $query->offset($offset)
                ->limit($limit)
                ->orderBy('created_at', 'desc')
                ->get();

            // Decode JSON data and metadata
            $memories = [];
            foreach ($results as $row) {
                $memories[] = [
                    'id' => $row->id,
                    'fact_type' => $row->fact_type,
                    'data' => json_decode($row->data, true),
                    'metadata' => json_decode($row->metadata, true),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            }

            return $memories;
        } catch (\Exception $e) {
            Log::error('StructuredMemoryService::retrieve failed', [
                'contactId' => $contactId,
                'factType' => $factType,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Update a structured memory
     *
     * @param int $id
     * @param string|null $factType
     * @param mixed $data
     * @param array $metadata
     * @return bool
     */
    public function update(int $id, string $factType = null, $data = null, array $metadata = []): bool
    {
        try {
            $updateData = [
                'updated_at' => now(),
            ];

            if ($factType !== null) {
                $updateData['fact_type'] = $factType;
            }

            if ($data !== null) {
                $updateData['data'] = is_array($data) ? json_encode($data) : $data;
            }

            if (!empty($metadata)) {
                $updateData['metadata'] = json_encode($metadata);
            }

            $affected = DB::table('structured_memories')
                ->where('id', $id)
                ->update($updateData);

            return $affected > 0;
        } catch (\Exception $e) {
            Log::error('StructuredMemoryService::update failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete a structured memory
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $affected = DB::table('structured_memories')
                ->where('id', $id)
                ->delete();

            return $affected > 0;
        } catch (\Exception $e) {
            Log::error('StructuredMemoryService::delete failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Search structured memories by content
     *
     * @param int $contactId
     * @param string $searchTerm
     * @param int $limit
     * @return array
     */
    public function search(int $contactId, string $searchTerm, int $limit = 50): array
    {
        try {
            // Simple search in data field (for more advanced search, consider using full-text search)
            $results = DB::table('structured_memories')
                ->where('contact_id', $contactId)
                ->where('data', 'like', "%{$searchTerm}%")
                ->orWhere('fact_type', 'like', "%{$searchTerm}%")
                ->limit($limit)
                ->orderBy('created_at', 'desc')
                ->get();

            $memories = [];
            foreach ($results as $row) {
                $memories[] = [
                    'id' => $row->id,
                    'fact_type' => $row->fact_type,
                    'data' => json_decode($row->data, true),
                    'metadata' => json_decode($row->metadata, true),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            }

            return $memories;
        } catch (\Exception $e) {
            Log::error('StructuredMemoryService::search failed', [
                'contactId' => $contactId,
                'searchTerm' => $searchTerm,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}