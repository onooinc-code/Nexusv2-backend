<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\Proactive\NlpParserService;

class ProactiveAIController extends Controller
{
    protected NlpParserService $nlpParser;

    public function __construct(NlpParserService $nlpParser)
    {
        $this->nlpParser = $nlpParser;
    }

    // ─── ECA Rules ────────────────────────────────────────────────────────────

    public function indexRules()
    {
        $rules = DB::table('eca_rules')->orderByDesc('created_at')->get();
        return response()->json(['success' => true, 'data' => $rules]);
    }

    public function storeRule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'natural_language_rule' => 'required|string|max:1000',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Parse NLP rule into structured conditions/actions
            $parsed = $this->nlpParser->parseRule($request->natural_language_rule);

            $id = DB::table('eca_rules')->insertGetId([
                'name' => $request->name ?? substr($request->natural_language_rule, 0, 60),
                'natural_language_rule' => $request->natural_language_rule,
                'event_type' => $parsed['event_type'],
                'conditions' => json_encode($parsed['conditions']),
                'actions' => json_encode($parsed['actions']),
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // If it's time-based, auto-create a trigger
            if ($parsed['type'] === 'time_based' && $parsed['next_run_at']) {
                DB::table('proactive_triggers')->insert([
                    'eca_rule_id' => $id,
                    'trigger_type' => 'time_based',
                    'next_run_at' => $parsed['next_run_at'],
                    'context_payload' => json_encode($parsed['actions']),
                    'status' => 'pending',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            $rule = DB::table('eca_rules')->find($id);
            return response()->json(['success' => true, 'data' => $rule], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create ECA rule: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleRule(int $id)
    {
        $rule = DB::table('eca_rules')->find($id);
        if (!$rule) {
            return response()->json(['success' => false, 'message' => 'Rule not found'], 404);
        }

        DB::table('eca_rules')->where('id', $id)->update([
            'is_active' => !$rule->is_active,
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['success' => true, 'is_active' => !$rule->is_active]);
    }

    public function destroyRule(int $id)
    {
        DB::table('proactive_triggers')->where('eca_rule_id', $id)->delete();
        DB::table('eca_rules')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ─── Triggers ─────────────────────────────────────────────────────────────

    public function indexTriggers()
    {
        $triggers = DB::table('proactive_triggers')->orderByDesc('created_at')->limit(50)->get();
        return response()->json(['success' => true, 'data' => $triggers]);
    }

    // ─── Autonomous Logs ──────────────────────────────────────────────────────

    public function indexLogs()
    {
        $logs = DB::table('autonomous_logs')->orderByDesc('created_at')->limit(100)->get();
        return response()->json(['success' => true, 'data' => $logs]);
    }
}

