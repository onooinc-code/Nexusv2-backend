<?php

namespace App\Services\Contact;

use App\Models\Contact;
use App\Models\ContactImportBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContactImportPipeline
{
    protected WhatsAppImportParser $whatsappParser;
    protected FacebookImportParser $facebookParser;
    protected ContactMessageNormalizer $normalizer;
    protected WahaImportService $wahaService;

    public function __construct()
    {
        $this->whatsappParser = new WhatsAppImportParser();
        $this->facebookParser = new FacebookImportParser();
        $this->normalizer = new ContactMessageNormalizer();
        $this->wahaService = new WahaImportService();
    }

    /**
     * Preview import without committing
     *
     * @param Contact $contact
     * @param string $source 'whatsapp' or 'facebook'
     * @param string $content File content or pasted text
     * @param string $format 'txt' or 'json'
     * @param string|null $timezone
     * @return array
     */
    public function preview(
        Contact $contact,
        string $source,
        string $content,
        string $format,
        ?string $timezone = 'UTC'
    ): array {
        try {
            $parsedMessages = $this->parse($source, $content, $format, $contact, $timezone);

            return [
                'success' => true,
                'source' => $source,
                'format' => $format,
                'total_messages' => count($parsedMessages),
                'message_sample' => array_slice($parsedMessages, 0, 3),
                'inbound_count' => count(array_filter($parsedMessages, fn($m) => $m['direction'] === 'inbound')),
                'outbound_count' => count(array_filter($parsedMessages, fn($m) => $m['direction'] === 'outbound')),
                'date_range' => $this->getDateRange($parsedMessages),
                'unique_senders' => count(array_unique(array_column($parsedMessages, 'sender_identifier'))),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Import and commit messages
     *
     * @param Contact $contact
     * @param string $source
     * @param string $content
     * @param string $format
     * @param string|null $timezone
     * @return array
     */
    public function commit(
        Contact $contact,
        string $source,
        string $content,
        string $format,
        ?string $timezone = 'UTC'
    ): array {
        return DB::transaction(function () use ($contact, $source, $content, $format, $timezone) {
            $batch = null;

            try {
                // Create import batch record
                $batch = ContactImportBatch::create([
                    'contact_id' => $contact->id,
                    'source' => $source,
                    'status' => 'processing',
                    'total_records' => 0,
                    'imported_records' => 0,
                    'failed_records' => 0,
                ]);

                // Parse messages
                $parsedMessages = $this->parse($source, $content, $format, $contact, $timezone);

                // Normalize and create
                $result = $this->normalizer->normalizeAndCreate(
                    $parsedMessages,
                    $contact,
                    $source,
                    $batch->id
                );

                // Update batch record
                $batch->update([
                    'total_records' => count($parsedMessages),
                    'imported_records' => $result['created'],
                    'failed_records' => $result['duplicates'] + count($result['errors']),
                    'status' => count($result['errors']) > 0 ? 'completed_with_errors' : 'completed',
                ]);

                return [
                    'success' => true,
                    'batch_id' => $batch->id,
                    'created' => $result['created'],
                    'duplicates' => $result['duplicates'],
                    'errors' => $result['errors'],
                    'message' => "Successfully imported {$result['created']} messages",
                ];

            } catch (\Exception $e) {
                $batch?->update([
                    'status' => 'failed',
                    'metadata' => array_merge($batch->metadata ?? [], ['error' => $e->getMessage()]),
                ]);

                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'batch_id' => $batch?->id,
                ];
            }
        });
    }

    /**
     * Rollback an import batch
     *
     * @param ContactImportBatch $batch
     * @return array
     */
    public function rollback(ContactImportBatch $batch): array
    {
        return DB::transaction(function () use ($batch) {
            try {
                // Count messages to delete
                $messageCount = $batch->messages()->count();

                // Delete all messages from this batch
                $batch->messages()->delete();

                // Mark batch as rolled back
                $batch->update(['status' => 'rolled_back']);

                return [
                    'success' => true,
                    'batch_id' => $batch->id,
                    'deleted' => $messageCount,
                    'message' => "Rolled back {$messageCount} messages",
                ];

            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Parse import content based on source and format
     *
     * @param string $source
     * @param string $content
     * @param string $format
     * @param Contact $contact
     * @param string|null $timezone
     * @return array
     */
    private function parse(
        string $source,
        string $content,
        string $format,
        Contact $contact,
        ?string $timezone
    ): array {
        return match($source) {
            'whatsapp' => $this->parseWhatsApp($content, $format, $contact, $timezone),
            'whatsapp_waha' => $this->parseWaha($content),
            'facebook' => $this->parseFacebook($content, $format, $timezone),
            default => throw new \InvalidArgumentException("Unknown source: {$source}"),
        };
    }

    /**
     * Parse WAHA live sync format
     */
    private function parseWaha(string $content): array
    {
        $data = json_decode($content, true);
        return $this->wahaService->fetchAndParseMessages($data['session'], $data['chatId'], $data['limit'] ?? 100);
    }

    /**
     * Parse WhatsApp export
     *
     * @param string $content
     * @param string $format
     * @param Contact $contact
     * @param string $timezone
     * @return array
     */
    private function parseWhatsApp(
        string $content,
        string $format,
        Contact $contact,
        string $timezone
    ): array {
        $phoneNumber = $contact->whatsapp_number ?? $contact->phone ?? '';

        return match($format) {
            'txt' => $this->whatsappParser->parseTxt($content, $phoneNumber, $timezone),
            'json' => $this->whatsappParser->parseJson($content, $phoneNumber, $timezone),
            default => throw new \InvalidArgumentException("Unknown WhatsApp format: {$format}"),
        };
    }

    /**
     * Parse Facebook export
     *
     * @param string $content
     * @param string $format
     * @param string $timezone
     * @return array
     */
    private function parseFacebook(
        string $content,
        string $format,
        string $timezone
    ): array {
        return match($format) {
            'txt' => $this->facebookParser->parseTxt($content, $timezone),
            'json' => $this->facebookParser->parseJson($content, $timezone),
            default => throw new \InvalidArgumentException("Unknown Facebook format: {$format}"),
        };
    }

    /**
     * Get date range from messages
     *
     * @param array $messages
     * @return array|null
     */
    private function getDateRange(array $messages): ?array
    {
        if (empty($messages)) {
            return null;
        }

        $timestamps = array_map(fn($m) => strtotime($m['timestamp']), $messages);

        return [
            'earliest' => date('Y-m-d', min($timestamps)),
            'latest' => date('Y-m-d', max($timestamps)),
        ];
    }
}
