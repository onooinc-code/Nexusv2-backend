<?php

namespace App\Events;

use App\Models\Contact;
use App\Models\ContactAnalysisRun;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactAnalysisCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Contact $contact,
        public ContactAnalysisRun $run
    ) {}
}
