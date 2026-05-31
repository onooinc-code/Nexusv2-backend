<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix: Widen the `key_hash` column from VARCHAR(255) to TEXT so it can
 * hold the full AES-256-CBC encrypted ciphertext produced by Crypt::encryptString().
 * Also drops the index on key_hash — MySQL cannot index a TEXT column without
 * a prefix length, and we don't need to query by ciphertext directly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_api_keys', function (Blueprint $table) {
            // Drop the index on key_hash (cannot index TEXT without prefix)
            $table->dropIndex(['key_hash']);

            // Widen from VARCHAR(255) → TEXT to accommodate encrypted payloads
            $table->text('key_hash')->change();
        });
    }

    public function down(): void
    {
        Schema::table('ai_api_keys', function (Blueprint $table) {
            $table->string('key_hash')->change();
            $table->index(['key_hash']);
        });
    }
};
