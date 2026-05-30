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
        Schema::create('eca_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('natural_language_rule');
            $table->string('event_type')->nullable(); // e.g. ContactMessageReceived
            $table->json('conditions')->nullable(); // structured parsed conditions
            $table->json('actions')->nullable(); // structured actions to take
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('proactive_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eca_rule_id')->nullable()->constrained('eca_rules')->nullOnDelete();
            $table->string('trigger_type'); // time_based, event_based
            $table->timestamp('next_run_at')->nullable();
            $table->json('context_payload')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('autonomous_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action_taken');
            $table->text('reasoning')->nullable();
            $table->json('context')->nullable();
            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autonomous_logs');
        Schema::dropIfExists('proactive_triggers');
        Schema::dropIfExists('eca_rules');
    }
};
