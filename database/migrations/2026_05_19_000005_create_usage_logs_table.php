<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('provider_id')->nullable();
            $table->uuid('model_id')->nullable();
            $table->string('intent_name')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('input_cost', 14, 6)->default(0);
            $table->decimal('output_cost', 14, 6)->default(0);
            $table->decimal('total_cost', 14, 6)->default(0);
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();

            $table->index(['provider_id']);
            $table->index(['model_id']);
            $table->index(['intent_name']);
            $table->index(['timestamp']);

            $table->foreign('provider_id')->references('id')->on('ai_providers')->onDelete('set null');
            $table->foreign('model_id')->references('id')->on('ai_models')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_logs');
    }
};
