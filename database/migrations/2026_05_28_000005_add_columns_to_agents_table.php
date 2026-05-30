<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('persona_id')->nullable()->constrained('agent_personas')->onDelete('set null');
            $table->boolean('is_system')->default(false);
            $table->integer('rate_limit_per_minute')->default(60);
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['persona_id']);
            $table->dropColumn(['owner_id', 'persona_id', 'is_system', 'rate_limit_per_minute']);
        });
    }
};
