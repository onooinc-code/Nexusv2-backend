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
        // Drop old tables from Phase 02 to recreate with new vNext schema
        Schema::dropIfExists('contact_aliases');
        Schema::dropIfExists('contact_preferences');
        Schema::dropIfExists('contact_relationships');
        Schema::dropIfExists('contact_identifiers');
        Schema::dropIfExists('contact_channels');

        Schema::create('contact_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contact_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('value');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['type', 'value']);
        });

        Schema::create('contact_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('contact_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('status')->default('pending');
            $table->integer('total_records')->default(0);
            $table->integer('imported_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contact_message_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_thread_id')->nullable();
            $table->string('channel');
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['channel', 'source_thread_id']);
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('thread_id')->nullable()->constrained('contact_message_threads')->nullOnDelete();
            $table->string('channel');
            $table->string('source');
            $table->string('external_id')->nullable();
            $table->string('sender_identifier')->nullable();
            $table->string('direction')->nullable();
            $table->text('body')->nullable();
            $table->string('language')->nullable();
            $table->json('attachments_metadata')->nullable();
            $table->json('raw_metadata')->nullable();
            $table->foreignId('import_batch_id')->nullable()->constrained('contact_import_batches')->nullOnDelete();
            $table->string('dedupe_hash')->nullable()->unique();
            $table->timestamp('source_timestamp')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['contact_id', 'channel']);
            $table->index('external_id');
        });

        Schema::create('contact_analysis_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->json('options')->nullable();
            $table->json('results')->nullable();
            $table->string('trace_id')->nullable();
            $table->json('cost_metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_analysis_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('analysis_run_id')->nullable()->constrained('contact_analysis_runs')->nullOnDelete();
            $table->string('type');
            $table->text('content');
            $table->decimal('confidence', 5, 2)->nullable();
            $table->json('evidence_refs')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contact_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->decimal('confidence', 5, 2)->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('last_validated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('contact_memory_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memory_id')->constrained('contact_memories')->cascadeOnDelete();
            $table->integer('version');
            $table->text('content');
            $table->json('changes')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_memory_maintenance_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('pending');
            $table->string('operation');
            $table->json('scope')->nullable();
            $table->json('results')->nullable();
            $table->text('error_log')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('target_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('type');
            $table->string('direction')->nullable();
            $table->decimal('strength', 5, 2)->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->text('evidence')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contact_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['contact_id', 'key']);
        });

        Schema::create('contact_reply_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->text('rule');
            $table->boolean('is_active')->default(true);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contact_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('topic');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contact_topic_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('contact_topics')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('contact_messages')->nullOnDelete();
            $table->foreignId('analysis_run_id')->nullable()->constrained('contact_analysis_runs')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('contact_profile_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->json('snapshot_data');
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('trace_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            $table->index(['actor_type', 'actor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_audit_events');
        Schema::dropIfExists('contact_profile_snapshots');
        Schema::dropIfExists('contact_topic_mentions');
        Schema::dropIfExists('contact_topics');
        Schema::dropIfExists('contact_reply_rules');
        Schema::dropIfExists('contact_preferences');
        Schema::dropIfExists('contact_relationships');
        Schema::dropIfExists('contact_memory_maintenance_runs');
        Schema::dropIfExists('contact_memory_versions');
        Schema::dropIfExists('contact_memories');
        Schema::dropIfExists('contact_analysis_findings');
        Schema::dropIfExists('contact_analysis_runs');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('contact_message_threads');
        Schema::dropIfExists('contact_import_batches');
        Schema::dropIfExists('contact_aliases');
        Schema::dropIfExists('contact_identifiers');
        Schema::dropIfExists('contact_channels');
    }
};
