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
        Schema::create('provider_health_metrics', function (Blueprint $table) {
            $table->id();
            $table->uuid('provider_id');
            $table->string('status')->comment('healthy, degraded, offline');
            $table->integer('latency_ms')->nullable();
            $table->integer('rate_limit_limit')->nullable();
            $table->integer('rate_limit_remaining')->nullable();
            $table->integer('rate_limit_reset')->nullable();
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('ai_providers')->onDelete('cascade');
        });

        Schema::create('cost_budgets', function (Blueprint $table) {
            $table->id();
            $table->uuid('workspace_id')->nullable()->comment('Nullable for global limits');
            $table->decimal('monthly_limit', 12, 4);
            $table->decimal('current_spend', 12, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_health_metrics');
        Schema::dropIfExists('cost_budgets');
    }
};
