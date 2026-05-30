<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Str;

/**
 * SettingPolicy
 *
 * Determines who can view, create, update, and delete settings.
 * Critical settings (prefixed with 'system.') require super-admin access.
 */
class SettingPolicy
{
    /**
     * Determine whether the user can view the setting.
     */
    public function view(User $user, Setting $setting): bool
    {
        // Public settings can be viewed by anyone
        if ($setting->is_public) {
            return true;
        }

        // Only admins can view private settings
        return $user->is_admin;
    }

    /**
     * Determine whether the user can create settings.
     */
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can update the setting.
     */
    public function update(User $user, Setting $setting): bool
    {
        // Critical system settings require super-admin
        if (Str::startsWith($setting->key, 'system.')) {
            return $user->is_super_admin ?? false;
        }

        // Integration settings require super-admin
        if (Str::startsWith($setting->key, 'integrations.')) {
            return $user->is_super_admin ?? false;
        }

        // Other settings require admin
        return $user->is_admin;
    }

    /**
     * Determine whether the user can delete the setting.
     */
    public function delete(User $user, Setting $setting): bool
    {
        // Only super-admins can delete settings
        return $user->is_super_admin ?? false;
    }

    /**
     * Determine if user can view masked credentials.
     */
    public function viewMasked(User $user, Setting $setting): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine if user can toggle emergency controls.
     */
    public function toggleEmergency(User $user): bool
    {
        return $user->is_super_admin ?? false;
    }

    /**
     * Determine if user can run seeders.
     */
    public function runSeeder(User $user): bool
    {
        return $user->is_super_admin ?? false;
    }
}
