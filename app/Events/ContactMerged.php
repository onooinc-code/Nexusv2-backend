<?php

namespace App\Events;

use App\Models\Contact;
use Illuminate\Broadcasting\PrivateChannel;

class ContactMerged extends BroadcastableEvent
{
    public function __construct(public Contact $target, public int $sourceId, public array $metadata = [])
    { 
        parent::__construct(); 
    }

    public function broadcastOn(): array
    {
        if ($this->target->user_id) {
            return [new PrivateChannel('user.' . $this->target->user_id)];
        }
        return [new PrivateChannel('system.events')];
    }
}
