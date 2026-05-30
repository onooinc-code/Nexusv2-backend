<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_api_keys', function (Blueprint $table) {
            $table->string('status')->default('active')->after('is_active')->comment('active, expiring, expired, rotated');
            $table->timestamp('expires_at')->nullable()->after('status');
            $table->timestamp('last_rotated_at')->nullable()->after('expires_at');
            $table->uuid('workspace_id')->nullable()->after('last_rotated_at')->comment('For per-tenant key scoping');
        });
    }

    public function down(): void
    {
        Schema::table('ai_api_keys', function (Blueprint $table) {
            $table->dropColumn(['status', 'expires_at', 'last_rotated_at', 'workspace_id']);
        });
    }
};
