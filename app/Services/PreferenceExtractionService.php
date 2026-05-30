<?php

namespace App\Services;

use App\Models\Contact;

class PreferenceExtractionService
{
    public function extractPreferences(Contact $contact): array
    {
        $attributes = $contact->attributes ?? [];

        $preferences = $attributes['preferences'] ?? [];

        if (empty($preferences)) {
            $preferences = [
                'communication' => 'default',
                'timezone' => $attributes['timezone'] ?? 'UTC',
                'language' => $attributes['language'] ?? 'en',
            ];

            $attributes['preferences'] = $preferences;
            $contact->attributes = $attributes;
            $contact->save();
        }

        return $preferences;
    }
}
