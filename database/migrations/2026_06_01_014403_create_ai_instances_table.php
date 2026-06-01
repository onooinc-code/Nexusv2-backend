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
        Schema::create('ai_instances', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider'); // openai, anthropic, ollama, google
            $table->string('model_name'); // e.g. gpt-4o
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('ready');
            $table->json('config')->nullable(); // temperature, max_tokens defaults
            $table->string('routing_tag')->nullable(); // e.g. "reasoning", "fast", "cheap"
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_instances');
    }
};
