<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\ContactStatsService;
use App\Services\ContactReplyModeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Phase 2 — Contact Cards And Topbar Controls
 *
 * Handles:
 *   GET  /api/v1/contacts/stats
 *   GET  /api/v1/contacts/reply-mode            (global)
 *   PATCH /api/v1/contacts/reply-mode           (global)
 *   GET  /api/v1/contacts/{contact}/reply-mode  (per-contact)
 *   PATCH /api/v1/contacts/{contact}/reply-mode (per-contact)
 */
class ContactStatsController extends Controller
{
    public function __construct(
        protected ContactStatsService $statsService,
        protected ContactReplyModeService $replyModeService,
    ) {}

    // =========================================================================
    // Stats
    // =========================================================================

    /**
     * GET /api/v1/contacts/stats
     * Returns operational hub-level counters for the topbar strip.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => $this->statsService->getHubStats(),
        ]);
    }

    // =========================================================================
    // Global reply-mode
    // =========================================================================

    /**
     * GET /api/v1/contacts/reply-mode
     */
    public function getGlobalReplyMode(): JsonResponse
    {
        return response()->json([
            'data' => $this->replyModeService->getGlobal(),
        ]);
    }

    /**
     * PATCH /api/v1/contacts/reply-mode
     */
    public function setGlobalReplyMode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'string', Rule::in(ContactReplyModeService::VALID_MODES)],
        ]);

        $result = $this->replyModeService->setGlobal($data['mode'], $request->user()?->id);

        return response()->json(['data' => $result]);
    }

    // =========================================================================
    // Per-contact reply-mode
    // =========================================================================

    /**
     * GET /api/v1/contacts/{contact}/reply-mode
     */
    public function getContactReplyMode(int $contact): JsonResponse
    {
        $contact = Contact::findOrFail($contact);

        return response()->json([
            'data' => $this->replyModeService->getForContact($contact),
        ]);
    }

    /**
     * PATCH /api/v1/contacts/{contact}/reply-mode
     * Pass {"mode": null} to clear the per-contact override.
     */
    public function setContactReplyMode(Request $request, int $contact): JsonResponse
    {
        $data = $request->validate([
            'mode' => ['nullable', 'string', Rule::in(ContactReplyModeService::VALID_MODES)],
        ]);

        $contactModel = Contact::findOrFail($contact);
        $result = $this->replyModeService->setForContact(
            $contactModel,
            $data['mode'] ?? null,
            $request->user()?->id
        );

        return response()->json(['data' => $result]);
    }
}
