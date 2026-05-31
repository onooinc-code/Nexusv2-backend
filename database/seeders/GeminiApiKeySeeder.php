<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * GeminiApiKeySeeder
 *
 * Stores the two Gemini API keys in the ai_api_keys table,
 * encrypted with Laravel's AES-256-CBC encrypter.
 * The first key is set as active (primary); the second is set as
 * a fallback (is_active = false, ready to be rotated in via the
 * platform's key-rotation scheduler).
 */
class GeminiApiKeySeeder extends Seeder
{
    // ────────────────────────────────────────────────────────────────────
    //  ⚠️  These keys are stored ONLY as encrypted ciphertext in the DB.
    //  The plaintext is never persisted anywhere on disk after seeding.
    // ────────────────────────────────────────────────────────────────────
    private const KEYS = [
        [
            'value'     => 'AIzaSyCtm0pi342VTr95ypicdJ5y7Sl1fqTG4mA',
            'name'      => 'Gemini API Key — Primary',
            'is_active' => true,
        ],
        [
            'value'     => 'AIzaSyCxD8TZHpYfconRw4pxLAjQwbbbuwsH7FU',
            'name'      => 'Gemini API Key — Fallback',
            'is_active' => false,
        ],
    ];

    public function run(): void
    {
        // Resolve the Gemini provider ID from the DB
        $provider = DB::table('ai_providers')
            ->where('name', 'Google Gemini')
            ->first();

        if (! $provider) {
            $this->command->error(
                'Google Gemini provider not found. Run AiProvidersSeeder first.'
            );
            return;
        }

        $now = Carbon::now();

        foreach (self::KEYS as $keyData) {
            // Skip if a key with the same name already exists for this provider
            $exists = DB::table('ai_api_keys')
                ->where('provider_id', $provider->id)
                ->where('name', $keyData['name'])
                ->exists();

            if ($exists) {
                $this->command->warn("Skipping (already exists): {$keyData['name']}");
                continue;
            }

            DB::table('ai_api_keys')->insert([
                'id'          => Str::uuid()->toString(),
                'provider_id' => $provider->id,
                'key_hash'    => Crypt::encryptString($keyData['value']),
                'name'        => $keyData['name'],
                'is_active'   => $keyData['is_active'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $active = $keyData['is_active'] ? '✅ active' : '💤 fallback';
            $this->command->info("Stored {$active}: {$keyData['name']}");
        }

        $this->command->newLine();
        $this->command->info('🔐 GeminiApiKeySeeder complete. Both keys encrypted and stored.');
    }
}
