<?php

namespace App\Services\Engines;

use Illuminate\Support\Facades\Log;

class PersonaToneEngine
{
    protected array $personas = [
        'default' => [
            'name' => 'Default',
            'description' => 'Standard helpful assistant',
            'tone' => 'professional',
            'traits' => ['helpful', 'clear', 'concise'],
        ],
        'expert' => [
            'name' => 'Expert',
            'description' => 'Deep domain knowledge, authoritative',
            'tone' => 'technical',
            'traits' => ['authoritative', 'detailed', 'precise'],
        ],
        'friendly' => [
            'name' => 'Friendly',
            'description' => 'Warm, approachable assistant',
            'tone' => 'friendly',
            'traits' => ['warm', 'encouraging', 'conversational'],
        ],
        'concise' => [
            'name' => 'Concise',
            'description' => 'Brief, to-the-point responses',
            'tone' => 'concise',
            'traits' => ['brief', 'direct', 'efficient'],
        ],
        'empathetic' => [
            'name' => 'Empathetic',
            'description' => 'Understanding and supportive',
            'tone' => 'empathetic',
            'traits' => ['empathetic', 'supportive', 'patient'],
        ],
    ];

    protected array $contactPersonaPreferences = [];

    public function setPersonaForContact(string $contactId, string $persona): void
    {
        if (!isset($this->personas[$persona])) {
            throw new \InvalidArgumentException("Unknown persona: {$persona}");
        }
        $this->contactPersonaPreferences[$contactId] = $persona;
    }

    public function getPersonaForContact(string $contactId): string
    {
        return $this->contactPersonaPreferences[$contactId] ?? 'default';
    }

    public function select(array $context = []): array
    {
        $contactId = $context['contact_id'] ?? null;
        $explicitPersona = $context['persona'] ?? null;
        $toneContext = $context['tone'] ?? null;

        $persona = $explicitPersona ?? ($contactId ? $this->getPersonaForContact($contactId) : 'default');

        if (!isset($this->personas[$persona])) {
            return [
                'success' => false,
                'error' => "Unknown persona: {$persona}",
                'available_personas' => array_keys($this->personas),
            ];
        }

        $personaConfig = $this->personas[$persona];

        $tone = $toneContext ?? $personaConfig['tone'];

        return [
            'success' => true,
            'persona' => $persona,
            'persona_config' => $personaConfig,
            'tone' => $tone,
            'system_prompt' => $this->buildSystemPrompt($personaConfig),
        ];
    }

    protected function buildSystemPrompt(array $personaConfig): string
    {
        $parts = [
            "You are a {$personaConfig['name']} assistant.",
            $personaConfig['description'],
            "Traits: " . implode(', ', $personaConfig['traits']),
        ];

        return implode(" ", $parts);
    }

    public function getAvailablePersonas(): array
    {
        $result = [];
        foreach ($this->personas as $name => $config) {
            $result[$name] = [
                'name' => $config['name'],
                'description' => $config['description'],
                'tone' => $config['tone'],
                'traits' => $config['traits'],
            ];
        }
        return $result;
    }
}
