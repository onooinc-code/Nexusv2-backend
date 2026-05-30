<?php

namespace App\Events;

use App\Models\Contact;
use Illuminate\Broadcasting\PrivateChannel;

class ContactDeleted extends BroadcastableEvent
{
    public function __construct(public Contact $contact, public array $metadata = [])
    { 
        parent::__construct(); 
    }

    public function broadcastOn(): array
    {
        if ($this->contact->user_id) {
            return [new PrivateChannel('user.' . $this->contact->user_id)];
        }
        return [new PrivateChannel('system.events')];
    }
}
