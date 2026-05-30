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
        Schema::create('intent_routing', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('intent_name')->unique();
            $table->uuid('default_provider_id')->nullable();
            $table->uuid('default_model_id')->nullable();
            $table->uuid('fallback_provider_id')->nullable();
            $table->uuid('fallback_model_id')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('default_provider_id')->references('id')->on('ai_providers')->onDelete('set null');
            $table->foreign('default_model_id')->references('id')->on('ai_models')->onDelete('set null');
            $table->foreign('fallback_provider_id')->references('id')->on('ai_providers')->onDelete('set null');
            $table->foreign('fallback_model_id')->references('id')->on('ai_models')->onDelete('set null');
            
            // Indexes
            $table->index(['intent_name']);
            $table->index(['default_provider_id']);
            $table->index(['default_model_id']);
            $table->index(['fallback_provider_id']);
            $table->index(['fallback_model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intent_routing');
    }
};