<?php

namespace App\Services;

use App\Models\Contact;

class EmotionBaselineService
{
    public function calculateBaseline(Contact $contact): string
    {
        $notes = $contact->notes()->pluck('note')->toArray();

        if (count($notes) === 0) {
            return 'neutral';
        }

        $score = collect($notes)->reduce(function ($carry, $note) {
            $note = strtolower($note);

            if (str_contains($note, 'happy') || str_contains($note, 'good') || str_contains($note, 'positive')) {
                return $carry + 1;
            }

            if (str_contains($note, 'angry') || str_contains($note, 'sad') || str_contains($note, 'frustrated') || str_contains($note, 'negative')) {
                return $carry - 1;
            }

            return $carry;
        }, 0);

        return match (true) {
            $score > 0 => 'positive',
            $score < 0 => 'negative',
            default => 'neutral',
        };
    }

    public function persistBaseline(Contact $contact): void
    {
        $baseline = $this->calculateBaseline($contact);
        $contact->metadata = array_merge($contact->metadata ?? [], [
            'emotional_baseline' => $baseline,
            'emotion_updated_at' => now()->toDateTimeString(),
        ]);
        $contact->save();
    }
}
