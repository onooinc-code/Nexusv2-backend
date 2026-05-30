<?php

namespace App\Services;

use App\Models\Contact;

class RelationshipGraphService
{
    public function buildGraph(Contact $contact): array
    {
        $nodes = [
            ['id' => $contact->id, 'label' => $contact->name],
        ];
        $edges = [];

        foreach ($contact->rules as $rule) {
            if (!empty($rule->metadata['related_contact_id'])) {
                $target = (int) $rule->metadata['related_contact_id'];
                $edges[] = [
                    'source' => $contact->id,
                    'target' => $target,
                    'relationship' => $rule->metadata['relationship'] ?? 'related',
                ];
                $nodes[] = ['id' => $target, 'label' => $rule->metadata['related_contact_name'] ?? 'Related'];
            }
        }

        return [
            'contact' => $contact->id,
            'nodes' => collect($nodes)->unique('id')->values()->all(),
            'edges' => $edges,
        ];
    }
}
