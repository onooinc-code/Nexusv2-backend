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
        Schema::table('agent_tasks', function (Blueprint $table) {
            // Add missing fields per TaskHub spec
            $table->enum('type', ['manual', 'agent', 'system'])->default('agent')->after('id');
            $table->foreignId('contact_id')->nullable()->constrained()->after('type');
            $table->foreignId('conversation_id')->nullable()->constrained()->after('contact_id');
            $table->json('payload_data')->nullable()->after('conversation_id');
            $table->json('result_data')->nullable()->after('payload_data');
            $table->timestamp('deleted_at')->nullable()->after('result_data');
            
            // Rename due_at to due_date for API spec compatibility
            if (Schema::hasColumn('agent_tasks', 'due_at')) {
                $table->renameColumn('due_at', 'due_date');
            }
            
            // Update status enum to match spec (todo/in-progress/blocked/completed/failed/cancelled)
            // Note: We'll handle the actual values in the model with mutators/mapping
            // For now, we'll keep the existing values but update the model to map them
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            // Drop added columns
            $table->dropColumn(['type', 'contact_id', 'conversation_id', 'payload_data', 'result_data', 'deleted_at']);
            
            // Rename due_date back to due_at
            if (Schema::hasColumn('agent_tasks', 'due_date')) {
                $table->renameColumn('due_date', 'due_at');
            }
            
            // Note: We're not reverting the status enum change as it would require data migration
        });
    }
};