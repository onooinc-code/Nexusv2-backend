<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->string('alternate_name')->nullable()->after('display_name');
            $table->string('gender')->nullable()->after('type');
            $table->string('whatsapp_number')->nullable()->after('phone');
            $table->string('primary_identifier')->nullable()->after('email');
            $table->string('reply_mode_override')->nullable()->after('attributes');
            $table->integer('profile_confidence')->default(0)->after('reply_mode_override');
            $table->timestamp('memory_freshness')->nullable()->after('profile_confidence');
            $table->timestamp('last_interaction_at')->nullable()->after('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'alternate_name',
                'gender',
                'whatsapp_number',
                'primary_identifier',
                'reply_mode_override',
                'profile_confidence',
                'memory_freshness',
                'last_interaction_at',
            ]);
        });
    }
};
