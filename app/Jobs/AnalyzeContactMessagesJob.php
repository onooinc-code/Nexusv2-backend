<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ContactAnalysisRun;
use App\Services\Contact\ContactIntelligenceExtractionPipeline;

class AnalyzeContactMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes

    public function __construct(
        public ContactAnalysisRun $run
    ) {}

    public function handle(ContactIntelligenceExtractionPipeline $pipeline): void
    {
        $pipeline->process($this->run);
    }
}
