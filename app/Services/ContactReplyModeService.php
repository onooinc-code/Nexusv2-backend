<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactAuditEvent;
use App\Services\LogService;
use Illuminate\Support\Facades\Cache;

/**
 * Manages global and per-contact reply modes.
 *
 * Valid modes: manual | copilot | autopilot
 */
class ContactReplyModeService
{
    public const GLOBAL_KEY    = 'contact_hub:global_reply_mode';
    public const AUDIT_CHANNEL = 'contact_reply_mode';

    public const MODE_MANUAL   = 'manual';
    public const MODE_COPILOT  = 'copilot';
    public const MODE_AUTOPILOT = 'autopilot';

    public const VALID_MODES = [
        self::MODE_MANUAL,
        self::MODE_COPILOT,
        self::MODE_AUTOPILOT,
    ];

    public function __construct(protected LogService $logService) {}

    // -------------------------------------------------------------------------
    // Global mode
    // -------------------------------------------------------------------------

    /**
     * Return the current hub-wide reply mode (stored in cache / settings).
     */
    public function getGlobal(): array
    {
        $mode = Cache::get(self::GLOBAL_KEY, self::MODE_MANUAL);

        return [
            'mode'       => $mode,
            'is_autopilot_active' => $mode === self::MODE_AUTOPILOT,
        ];
    }

    /**
     * Update the hub-wide reply mode and log the change.
     *
     * @param  string      $mode     One of the VALID_MODES constants.
     * @param  int|null    $actorId  User ID making the change.
     */
    public function setGlobal(string $mode, ?int $actorId = null): array
    {
        $this->assertValidMode($mode);

        $previous = Cache::get(self::GLOBAL_KEY, self::MODE_MANUAL);
        Cache::put(self::GLOBAL_KEY, $mode, now()->addYears(1));

        $this->logService->info('Global reply mode changed', [
            'channel'  => self::AUDIT_CHANNEL,
            'type'     => 'global_mode_change',
            'user_id'  => $actorId,
            'context'  => ['from' => $previous, 'to' => $mode],
        ]);

        return [
            'mode'       => $mode,
            'previous'   => $previous,
            'changed_at' => now()->toIso8601String(),
        ];
    }

    // -------------------------------------------------------------------------
    // Per-contact mode
    // -------------------------------------------------------------------------

    /**
     * Return the effective reply mode for a contact.
     * If the contact has an override, use it; otherwise fall back to global.
     */
    public function getForContact(Contact $contact): array
    {
        $global   = Cache::get(self::GLOBAL_KEY, self::MODE_MANUAL);
        $override = $contact->reply_mode_override;
        $effective = $override ?? $global;

        return [
            'contact_id'  => $contact->id,
            'global_mode' => $global,
            'override'    => $override,
            'effective'   => $effective,
            'is_autopilot_active' => $effective === self::MODE_AUTOPILOT,
        ];
    }

    /**
     * Set (or clear) the per-contact reply-mode override and write an audit event.
     *
     * @param  string|null  $mode   Pass null to remove the override.
     * @param  int|null     $actorId
     */
    public function setForContact(Contact $contact, ?string $mode, ?int $actorId = null): array
    {
        if ($mode !== null) {
            $this->assertValidMode($mode);
        }

        $previous = $contact->reply_mode_override;
        $contact->reply_mode_override = $mode;
        $contact->save();

        // Write an audit event for traceability
        ContactAuditEvent::create([
            'contact_id'   => $contact->id,
            'actor_type'   => $actorId ? 'user' : 'system',
            'actor_id'     => $actorId,
            'action'       => 'reply_mode.changed',
            'before_state' => ['reply_mode_override' => $previous],
            'after_state'  => ['reply_mode_override' => $mode],
        ]);

        $this->logService->info('Contact reply mode changed', [
            'channel'  => self::AUDIT_CHANNEL,
            'type'     => 'contact_mode_change',
            'user_id'  => $actorId,
            'related_id'   => $contact->id,
            'related_type' => Contact::class,
            'context'  => ['from' => $previous, 'to' => $mode],
        ]);

        return $this->getForContact($contact->fresh());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function assertValidMode(string $mode): void
    {
        if (!in_array($mode, self::VALID_MODES, true)) {
            throw new \InvalidArgumentException(
                "Invalid reply mode '{$mode}'. Must be one of: " . implode(', ', self::VALID_MODES)
            );
        }
    }
}
