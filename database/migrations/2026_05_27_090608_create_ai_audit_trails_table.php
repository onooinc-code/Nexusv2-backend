<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_audit_trails', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->comment('route_executed, fallback_triggered, key_accessed, budget_exceeded, rate_limited');
            $table->uuid('provider_id')->nullable();
            $table->uuid('model_id')->nullable();
            $table->string('intent')->nullable();
            $table->string('status')->comment('success, failed, fallback');
            $table->integer('latency_ms')->nullable();
            $table->boolean('fallback_triggered')->default(false);
            $table->integer('fallback_sequence')->nullable()->comment('Which fallback index was used');
            $table->decimal('estimated_cost', 12, 6)->nullable();
            $table->integer('input_tokens')->nullable();
            $table->integer('output_tokens')->nullable();
            $table->string('error_type')->nullable();
            $table->text('error_message')->nullable();
            $table->uuid('workspace_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->json('metadata')->nullable()->comment('Extra context: profiles used, cache hit, etc.');
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['provider_id', 'created_at']);
            $table->index(['workspace_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_audit_trails');
    }
};
