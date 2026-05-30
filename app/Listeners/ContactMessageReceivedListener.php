<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ContactMessageReceivedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        // Example event structure assumption: $event->contactName, $event->messageTopic, $event->messageContent
        $contactName = $event->contactName ?? '';
        $topic = $event->messageTopic ?? '';

        Log::info('Evaluating ECA rules for ContactMessageReceived', ['contact' => $contactName]);

        $ecaRules = DB::table('eca_rules')
            ->where('event_type', 'ContactMessageReceived')
            ->where('is_active', true)
            ->get();

        foreach ($ecaRules as $rule) {
            $conditions = json_decode($rule->conditions, true) ?? [];
            $match = true;

            // Simple condition evaluation
            if (isset($conditions['contact_name']) && strtolower($conditions['contact_name']) !== strtolower($contactName)) {
                $match = false;
            }

            if (isset($conditions['topic']) && !Str::contains(strtolower($topic), strtolower($conditions['topic']))) {
                $match = false;
            }

            if ($match) {
                Log::info('ECA Rule Matched', ['rule_id' => $rule->id]);
                
                // Instead of executing immediately, queue it as an autonomous trigger
                // or execute directly depending on the logic
                $actions = json_decode($rule->actions, true) ?? [];

                if (isset($actions['reply'])) {
                    // Trigger a reply action (simulate)
                    Log::info('Executing autonomous reply: ' . $actions['reply']['message']);
                }

                if (isset($actions['notify'])) {
                    DB::table('notification_logs')->insert([
                        'channel' => 'system',
                        'recipient' => 'Hedra',
                        'body' => $actions['notify']['message'] ?? 'Autonomous action completed.',
                        'status' => 'pending',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }

                DB::table('autonomous_logs')->insert([
                    'action_taken' => 'Executed event-based ECA rule',
                    'reasoning' => 'Event matched ECA rule: ' . $rule->name,
                    'status' => 'completed',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }
        }
    }
}
