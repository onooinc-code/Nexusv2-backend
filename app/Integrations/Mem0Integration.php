<?php

namespace App\Integrations;

class Mem0Integration
{
    public function store(array $data): bool
    {
        return true;
    }

    public function search(string $query, int $contactId, int $limit = 20): array
    {
        return [];
    }

    public function delete(int $memoryId): bool
    {
        return true;
    }
}
