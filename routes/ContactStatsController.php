<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactMessage;
use App\Models\Setting; // Assuming Setting model exists in SettingsHub
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContactStatsController extends Controller
{
    /**
     * Get high-level stats for the ContactHub dashboard.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total_contacts' => Contact::count(),
            'total_messages' => ContactMessage::count(),
            'active_today' => Contact::whereHas('conversations', function($q) {
                $q->where('updated_at', '>=', now()->startOfDay());
            })->count(),
            'avg_engagement' => (int) Contact::avg('engagement_score')
        ]);
    }

    /**
     * Global AI Reply settings.
     */
    public function getGlobalReplyMode(): JsonResponse
    {
        $mode = Setting::get('contact_reply_mode', 'manual');
        return response()->json(['mode' => $mode]);
    }

    public function setGlobalReplyMode(Request $request): JsonResponse
    {
        $request->validate(['mode' => 'required|in:manual,assist,auto']);
        Setting::updateOrCreate(['key' => 'contact_reply_mode'], ['value' => $request->mode]);

        return response()->json(['success' => true]);
    }

    /**
     * Per-contact reply overrides.
     */
    public function getContactReplyMode(Contact $contact): JsonResponse
    {
        return response()->json(['mode' => $contact->metadata['reply_mode'] ?? 'global']);
    }

    public function setContactReplyMode(Request $request, Contact $contact): JsonResponse
    {
        $contact->update(['metadata->reply_mode' => $request->mode]);
        return response()->json(['success' => true]);
    }
}
