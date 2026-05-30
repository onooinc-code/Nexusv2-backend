<?php

namespace App\Services\Memory;

use App\Models\Contact;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class EpisodicMemoryService
{
    /**
     * Store an event as episodic memory
     *
     * @param int $contactId
     * @param string $eventType
     * @param array $data
     * @return bool
     */
    public function storeEvent(int $contactId, string $eventType, array $data): bool
    {
        try {
            // Validate contact exists
            $contact = Contact::find($contactId);
            if (!$contact) {
                Log::warning('EpisodicMemoryService::storeEvent - Contact not found', [
                    'contactId' => $contactId
                ]);
                return false;
            }

            // Store episodic memory (using messages table for now)
            $message = Message::create([
                'contact_id' => $contactId,
                'content' => json_encode([
                    'event_type' => $eventType,
                    'data' => $data,
                    'timestamp' => now()->toDateTimeString()
                ]),
                'sender' => 'system',
                'metadata' => [
                    'memory_type' => 'episodic',
                    'event_type' => $eventType
                ]
            ]);

            return $message !== null;
        } catch (\Exception $e) {
            Log::error('EpisodicMemoryService::storeEvent failed', [
                'contactId' => $contactId,
                'eventType' => $eventType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Store a message as episodic memory
     *
     * @param int $contactId
     * @param string $content
     * @param string $sender
     * @param array $metadata
     * @return bool
     */
    public function storeMessage(int $contactId, string $content, string $sender = 'user', array $metadata = []): bool
    {
        try {
            // Validate contact exists
            $contact = Contact::find($contactId);
            if (!$contact) {
                Log::warning('EpisodicMemoryService::storeMessage - Contact not found', [
                    'contactId' => $contactId
                ]);
                return false;
            }

            // Store message as episodic memory
            $message = Message::create([
                'contact_id' => $contactId,
                'content' => $content,
                'sender' => $sender,
                'metadata' => array_merge([
                    'memory_type' => 'episodic',
                    'stored_at' => now()->toDateTimeString()
                ], $metadata)
            ]);

            return $message !== null;
        } catch (\Exception $e) {
            Log::error('EpisodicMemoryService::storeMessage failed', [
                'contactId' => $contactId,
                'sender' => $sender,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Retrieve episodic memories for a contact
     *
     * @param int $contactId
     * @param int $limit
     * @param int $offset
     * @return \Illuminate\Support\Collection
     */
    public function retrieve(int $contactId, int $limit = 50, int $offset = 0)
    {
        try {
            return Message::where('contact_id', $contactId)
                ->whereJsonContains('metadata->memory_type', 'episodic')
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('EpisodicMemoryService::retrieve failed', [
                'contactId' => $contactId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Retrieve episodic memories by event type
     *
     * @param int $contactId
     * @param string $eventType
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function retrieveByEventType(int $contactId, string $eventType, int $limit = 50)
    {
        try {
            return Message::where('contact_id', $contactId)
                ->whereJsonContains('metadata->memory_type', 'episodic')
                ->whereJsonContains('metadata->event_type', $eventType)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('EpisodicMemoryService::retrieveByEventType failed', [
                'contactId' => $contactId,
                'eventType' => $eventType,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Delete episodic memory
     *
     * @param int $messageId
     * @return bool
     */
    public function delete(int $messageId): bool
    {
        try {
            $message = Message::find($messageId);
            if (!$message) {
                return false;
            }

            // Verify it's an episodic memory before deleting
            if (!isset($message->metadata['memory_type']) || $message->metadata['memory_type'] !== 'episodic') {
                Log::warning('EpisodicMemoryService::delete - Attempted to delete non-episodic memory', [
                    'messageId' => $messageId
                ]);
                return false;
            }

            return $message->delete();
        } catch (\Exception $e) {
            Log::error('EpisodicMemoryService::delete failed', [
                'messageId' => $messageId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Count episodic memories for a contact
     *
     * @param int $contactId
     * @return int
     */
    public function count(int $contactId): int
    {
        try {
            return Message::where('contact_id', $contactId)
                ->whereJsonContains('metadata->memory_type', 'episodic')
                ->count();
        } catch (\Exception $e) {
            Log::error('EpisodicMemoryService::count failed', [
                'contactId' => $contactId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
