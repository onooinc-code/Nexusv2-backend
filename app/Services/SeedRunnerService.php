<?php

namespace App\Services;

use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * SeedRunnerService
 *
 * Orchestrates database seeder execution.
 * Allows running seeders via API endpoints.
 */
class SeedRunnerService
{
    /**
     * Available seeders with metadata.
     */
    private array $availableSeeds = [
        'phase02' => [
            'name' => 'Phase 02 Test Data',
            'description' => 'Creates test contacts, conversations, agents, and relationships',
            'class' => 'Database\Seeders\Phase02Seeder',
            'data_count' => 'Contacts: 8, Conversations: 8, Messages: 32, Memories: 16, Agents: 4',
        ],
        'workflows' => [
            'name' => 'Workflow Templates',
            'description' => 'Imports standard workflow templates for common automations',
            'class' => 'Database\Seeders\WorkflowTemplateSeeder',
            'data_count' => 'Templates: 4',
        ],
        'demo-users' => [
            'name' => 'Demo Users',
            'description' => 'Creates demo admin, user, and test accounts',
            'class' => 'Database\Seeders\DemoUserSeeder',
            'data_count' => 'Users: 3',
        ],
        'settings' => [
            'name' => 'Application Settings',
            'description' => 'Populates default system settings and integration configuration keys',
            'class' => 'Database\Seeders\SettingSeeder',
            'data_count' => 'Settings: 33',
        ],
    ];

    /**
     * Get list of available seeders.
     */
    public function listAvailableSeeds(): array
    {
        return array_map(function ($seed, $id) {
            return array_merge(['id' => $id], $seed);
        }, $this->availableSeeds, array_keys($this->availableSeeds));
    }

    /**
     * Get single seeder info.
     */
    public function getSeed(string $seedId): ?array
    {
        $seed = $this->availableSeeds[$seedId] ?? null;
        if (!$seed) {
            return null;
        }
        return array_merge(['id' => $seedId], $seed);
    }

    /**
     * Run a single seeder.
     */
    public function runSeed(string $seedId, bool $force = false): array
    {
        $seed = $this->availableSeeds[$seedId] ?? null;
        if (!$seed) {
            throw new Exception("Seeder not found: {$seedId}");
        }

        $seedClass = $seed['class'];

        try {
            Log::info("Starting seeder execution", ['seed_id' => $seedId, 'class' => $seedClass]);

            // Resolve and call seeder
            $seeder = app($seedClass);
            $seeder->run();

            Log::info("Seeder completed successfully", ['seed_id' => $seedId]);

            return [
                'success' => true,
                'message' => "Seeder {$seedId} completed successfully",
                'seed_id' => $seedId,
                'seed_name' => $seed['name'],
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            Log::error("Seeder failed", [
                'seed_id' => $seedId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'seed_id' => $seedId,
                'seed_name' => $seed['name'],
            ];
        }
    }

    /**
     * Run multiple seeders.
     */
    public function runMultiple(array $seedIds, bool $force = false): array
    {
        $results = [];
        $allSuccess = true;

        foreach ($seedIds as $seedId) {
            $result = $this->runSeed($seedId, $force);
            $results[] = $result;
            if (!$result['success']) {
                $allSuccess = false;
            }
        }

        return [
            'success' => $allSuccess,
            'results' => $results,
            'total' => count($results),
            'successful' => collect($results)->filter(fn($r) => $r['success'])->count(),
            'failed' => collect($results)->filter(fn($r) => !$r['success'])->count(),
        ];
    }
}
