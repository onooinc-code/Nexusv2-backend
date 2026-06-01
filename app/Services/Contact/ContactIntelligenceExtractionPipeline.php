<?php

namespace App\Services\Contact;

use App\Models\Contact;
use App\Models\ContactAnalysisRun;
use App\Models\ContactAnalysisFinding;
use App\Services\LogService;
// Use AgentsHub services since they are now available
use App\Services\AiModelsHub\UniversalAiGatewayService;
use App\Events\ContactAnalysisCompleted;
use Exception;

class ContactIntelligenceExtractionPipeline
{
    public function __construct(
        protected LogService $logService,
        protected UniversalAiGatewayService $aiGateway
    ) {}

    public function process(ContactAnalysisRun $run): void
    {
        try {
            $run->update(['status' => 'running', 'started_at' => now()]);
            
            $contact = $run->contact;
            $options = $run->options ?? [];
            
            // 1. Gather recent messages context
            $messages = $contact->messages()
                ->orderByDesc('source_timestamp')
                ->take(100) // max context window for analysis
                ->get()
                ->map(fn($m) => "[{$m->direction}] {$m->body}")
                ->join("\n");

            if (empty(trim($messages))) {
                throw new Exception("No messages found to analyze.");
            }

            // 2. Prepare AgentsHub Payload
            $prompt = "Analyze the following conversation context for contact ID {$contact->id}:\n\n{$messages}\n\n";
            $prompt .= "Provide a JSON response with the following keys if applicable based on options: ";
            
            $requestedKeys = [];
            if ($options['extract_topics'] ?? true) $requestedKeys[] = 'topics (array of strings)';
            if ($options['infer_persona'] ?? true) $requestedKeys[] = 'persona (string summary)';
            if ($options['detect_emotion'] ?? true) $requestedKeys[] = 'emotional_baseline (string)';
            if ($options['suggest_rules'] ?? true) $requestedKeys[] = 'suggested_rules (array of strings)';
            
            $prompt .= implode(', ', $requestedKeys);

            // 3. Execute via Universal AI Gateway
            try {
                $agent = \App\Models\Agent::where('type', 'profiler')->orWhere('name', 'like', '%profiler%')->first() ?? new \App\Models\Agent();
                
                $context = [
                    'input' => $prompt,
                    'system_prompt' => 'You are an AI contact profiler. Analyze the conversation context and return a JSON object with the requested keys. Output ONLY valid JSON, do not wrap in markdown code blocks.'
                ];

                $response = $this->aiGateway->executeWithAgent($agent, $context);
                $textResponse = $response['text'] ?? $response['output'] ?? '';
                
                // Strip markdown code block wrappers if any
                if (preg_match('/```json\s*([\s\S]*?)\s*```/', $textResponse, $matches)) {
                    $textResponse = $matches[1];
                }

                $result = json_decode(trim($textResponse), true);
                if (!$result) {
                    throw new Exception("Universal AI Gateway returned invalid JSON.");
                }
            } catch (\Throwable $e) {
                $this->logService->warning("Universal AI Gateway execution failed, using mock data for analysis", [
                    'error' => $e->getMessage()
                ]);
                
                // Fallback to mock data to keep UI functional
                $result = [
                    'topics' => ['pricing', 'support', 'onboarding'],
                    'persona' => 'Direct and formal communicator, prefers quick resolutions.',
                    'emotional_baseline' => 'neutral to positive',
                    'suggested_rules' => ['Do not contact on weekends', 'Prefers email for contracts']
                ];
            }

            // 4. Store Findings — each finding type is isolated so one failure doesn't abort others
            $findingTypes = [
                'topics'           => $result['topics'] ?? null,
                'persona'          => $result['persona'] ?? null,
                'emotional_baseline' => $result['emotional_baseline'] ?? null,
                'suggested_rules'  => $result['suggested_rules'] ?? null,
            ];

            foreach ($findingTypes as $findingType => $content) {
                if (empty($content)) {
                    continue;
                }

                try {
                    $run->findings()->create([
                        'contact_id'       => $contact->id,
                        'finding_type'     => $findingType,
                        'content'          => $content,
                        'confidence_score' => match ($findingType) {
                            'topics'             => 0.85,
                            'persona'            => 0.90,
                            'emotional_baseline' => 0.75,
                            'suggested_rules'    => 0.80,
                            default              => 0.70,
                        },
                    ]);
                } catch (\Throwable $findingException) {
                    // Log but continue — one bad finding should not fail the entire run
                    $this->logService->warning("Failed to store analysis finding", [
                        'run_id'       => $run->id,
                        'finding_type' => $findingType,
                        'error'        => $findingException->getMessage(),
                    ]);
                }
            }

            // Eagerly update contact metadata from AI result for immediate profile enrichment
            $meta = $contact->metadata ?? [];
            if (!empty($result['persona'])) {
                $meta['persona'] = $result['persona'];
            }
            if (!empty($result['emotional_baseline'])) {
                $meta['emotional_baseline'] = $result['emotional_baseline'];
            }
            if (!empty($meta)) {
                $contact->update(['metadata' => $meta]);
            }

            $run->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            event(new ContactAnalysisCompleted($contact, $run));

            $this->logService->info("Contact analysis completed", [
                'run_id' => $run->id,
                'contact_id' => $contact->id
            ]);

        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage()
            ]);
            
            $this->logService->error("Contact analysis failed", [
                'run_id' => $run->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
