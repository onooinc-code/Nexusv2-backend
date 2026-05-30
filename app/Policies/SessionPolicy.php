<?php

namespace App\Policies;

use App\Models\ConversationSession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SessionPolicy
{
    use HandlesAuthorization;

    /**
     * Grant administrators blanket access.
     */
    public function before(User $user, string $ability): bool|null
    {
        if (in_array($user->email, config('broadcasting.admin_emails', []), true)) {
            return true;
        }

        return null;
    }

    public function view(User $user, ConversationSession $session): bool
    {
        return $this->ownsSession($user, $session);
    }

    public function update(User $user, ConversationSession $session): bool
    {
        return $this->ownsSession($user, $session);
    }

    public function subscribe(User $user, ConversationSession $session): bool
    {
        return $this->ownsSession($user, $session);
    }

    protected function ownsSession(User $user, ConversationSession $session): bool
    {
        return $session->conversation?->contact?->user_id === $user->id;
    }
}
