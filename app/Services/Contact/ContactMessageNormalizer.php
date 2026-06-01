<?php

namespace App\Services\Contact;

use App\Models\Contact;
use App\Models\ContactMessage;
use App\Models\ContactMessageThread;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContactMessageNormalizer
{
    /**
     * Normalize and create messages from parsed data
     *
     * @param array $parsedMessages
     * @param Contact $contact
     * @param string $source
     * @param int|null $importBatchId
     * @return array ['created' => int, 'duplicates' => int, 'errors' => array]
     */
    public function normalizeAndCreate(
        array $parsedMessages,
        Contact $contact,
        string $source,
        ?int $importBatchId = null
    ): array {
        $result = [
            'created' => 0,
            'duplicates' => 0,
            'errors' => [],
        ];

        // Get or create message thread
        $thread = $this->getOrCreateThread($contact, $source, $parsedMessages);

        foreach ($parsedMessages as $parsedMsg) {
            try {
                // Calculate dedupe hash
                $dedupeHash = $this->calculateHash($parsedMsg);

                // Check if message already exists for this contact (per-contact duplicate detection)
                if ($this->messageExists($dedupeHash, $contact->id)) {
                    $result['duplicates']++;
                    continue;
                }

                // Resolve sender contact (if this is an inbound message from another person)
                $senderContact = null;
                if ($parsedMsg['direction'] === 'inbound') {
                    $senderContact = $this->resolveSenderContact($parsedMsg, $contact);
                }

                // Detect language
                $language = $this->detectLanguage($parsedMsg['body']);

                // Create the message
                $message = ContactMessage::create([
                    'contact_id' => $contact->id,
                    'thread_id' => $thread?->id,
                    'sender_contact_id' => $senderContact?->id,
                    'channel' => $parsedMsg['channel'] ?? 'unknown',
                    'source' => $source,
                    'external_id' => $parsedMsg['source_id'],
                    'sender_identifier' => $parsedMsg['sender_identifier'],
                    'sender_name' => $parsedMsg['sender_name'],
                    'direction' => $parsedMsg['direction'] ?? 'inbound',
                    'body' => $parsedMsg['body'],
                    'language' => $language,
                    'attachments_metadata' => $parsedMsg['attachments_metadata'] ?? [],
                    'raw_metadata' => $parsedMsg['raw_metadata'] ?? [],
                    'dedupe_hash' => $dedupeHash,
                    'source_timestamp' => $parsedMsg['timestamp'],
                    'import_batch_id' => $importBatchId,
                ]);

                $result['created']++;

            } catch (\Exception $e) {
                $result['errors'][] = [
                    'message' => $parsedMsg['body'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Update contact's last interaction timestamp
        if ($result['created'] > 0) {
            $latestMessage = ContactMessage::where('contact_id', $contact->id)
                ->orderBy('source_timestamp', 'desc')
                ->first();

            if ($latestMessage) {
                $contact->update(['last_interaction_at' => $latestMessage->source_timestamp]);
            }
        }

        return $result;
    }

    /**
     * Get or create message thread
     *
     * @param Contact $contact
     * @param string $source
     * @param array $messages
     * @return ContactMessageThread|null
     */
    private function getOrCreateThread(Contact $contact, string $source, array $messages): ?ContactMessageThread
    {
        if (empty($messages)) {
            return null;
        }

        // Use source thread ID if available
        $sourceThreadId = $messages[0]['source_thread_id'] ?? null;

        // For single-contact conversations, use contact ID as thread ID
        $threadName = match($source) {
            'whatsapp' => 'WhatsApp - ' . ($contact->whatsapp_number ?? $contact->name),
            'facebook' => 'Facebook - ' . $contact->name,
            default => $contact->name,
        };

        return ContactMessageThread::firstOrCreate(
            [
                'contact_id' => $contact->id,
                'source' => $source,
                'source_thread_id' => $sourceThreadId,
            ],
            [
                'channel' => match($source) {
                    'whatsapp' => 'whatsapp',
                    'facebook' => 'facebook_messenger',
                    default => 'unknown',
                },
                'name' => $threadName,
            ]
        );
    }

    /**
     * Resolve sender to a Contact record
     *
     * @param array $parsedMsg
     * @param Contact $mainContact
     * @return Contact|null
     */
    private function resolveSenderContact(array $parsedMsg, Contact $mainContact): ?Contact
    {
        $senderName = $parsedMsg['sender_name'];
        $senderIdentifier = $parsedMsg['sender_identifier'];

        if (empty($senderName) || empty($senderIdentifier)) {
            return null;
        }

        // Try to find existing contact by identifier
        $contact = Contact::whereHas('identifiers', function ($q) use ($senderIdentifier) {
            $q->where('value', 'LIKE', '%' . $senderIdentifier . '%');
        })->first();

        // If found, return it
        if ($contact) {
            return $contact;
        }

        // Try to find by name similarity
        $contact = Contact::where('name', 'LIKE', '%' . $senderName . '%')->first();
        if ($contact) {
            return $contact;
        }

        // Create new contact for sender if it's likely a new contact
        // (only if not just a variation of main contact's name)
        if (!$this->isSameContact($senderName, $mainContact->name)) {
            try {
                return Contact::create([
                    'name' => $senderName,
                    'primary_identifier' => $senderIdentifier,
                    'type' => 'individual',
                    'display_name' => $senderName,
                ]);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Check if two names represent the same contact
     *
     * @param string $name1
     * @param string $name2
     * @return bool
     */
    private function isSameContact(string $name1, string $name2): bool
    {
        $clean1 = strtolower(preg_replace('/[^a-z0-9]/', '', $name1));
        $clean2 = strtolower(preg_replace('/[^a-z0-9]/', '', $name2));

        // Exact match or high similarity
        if ($clean1 === $clean2) {
            return true;
        }

        // Check if one is contained in the other
        if (str_contains($clean1, $clean2) || str_contains($clean2, $clean1)) {
            return true;
        }

        // Levenshtein similarity > 80%
        $maxLen = max(strlen($clean1), strlen($clean2));
        if ($maxLen === 0) {
            return true;
        }

        $similarity = 1 - (levenshtein($clean1, $clean2) / $maxLen);
        return $similarity > 0.8;
    }

    /**
     * Calculate dedupe hash for a message
     *
     * @param array $message
     * @return string
     */
    private function calculateHash(array $message): string
    {
        $data = [
            $message['timestamp'],
            $message['sender_identifier'],
            $message['body'],
            $message['source_id'] ?? null,
        ];

        return hash('sha256', implode('|', $data));
    }

    /**
     * Check if message already exists for the given contact.
     * Scoped to contact_id to prevent cross-contact hash collisions
     * (e.g. the same message text in a group chat imported for two contacts).
     *
     * @param string $dedupeHash
     * @param int    $contactId
     * @return bool
     */
    private function messageExists(string $dedupeHash, int $contactId): bool
    {
        return ContactMessage::where('contact_id', $contactId)
            ->where('dedupe_hash', $dedupeHash)
            ->exists();
    }

    /**
     * Detect language of message body
     *
     * @param string $body
     * @return string
     */
    private function detectLanguage(string $body): string
    {
        // Simple detection based on common patterns
        // In production, use a proper language detection library like langdetect

        if (preg_match('/[\x{0600}-\x{06FF}]/u', $body)) {
            return 'ar'; // Arabic
        }

        if (preg_match('/[\x{4E00}-\x{9FFF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $body)) {
            return 'ja'; // Japanese/Chinese
        }

        if (preg_match('/[\x{0400}-\x{04FF}]/u', $body)) {
            return 'ru'; // Russian/Cyrillic
        }

        if (preg_match('/[\x{0E00}-\x{0E7F}]/u', $body)) {
            return 'th'; // Thai
        }

        // Default to English
        return 'en';
    }
}
