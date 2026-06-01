<?php

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AgentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Any authenticated user can view the list of agents (could be scoped by workspace/owner in controller)
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Agent $agent): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Agent $agent): bool
    {
        // For now, allow owners or admins to update. But since owner_id might be missing right now, default to true or check if owner_id matches.
        return $agent->owner_id === null || $agent->owner_id === $user->id || $user->is_super_admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Agent $agent): bool
    {
        if ($agent->is_system) {
            return false;
        }
        return $agent->owner_id === null || $agent->owner_id === $user->id || $user->is_super_admin;
    }

    public function run(User $user, Agent $agent): bool
    {
        return true; // Allow any user to run for now, unless restricted by owner.
    }

    public function quarantine(User $user, Agent $agent): bool
    {
        return $user->is_super_admin || $agent->owner_id === null || $agent->owner_id === $user->id;
    }
}
