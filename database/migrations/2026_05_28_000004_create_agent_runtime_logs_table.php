<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('agent_runtime_logs')) {
            Schema::create('agent_runtime_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
                $table->uuid('task_id')->nullable();
                $table->uuid('trace_id')->nullable();
                $table->string('step');
                $table->json('input')->nullable();
                $table->json('output')->nullable();
                $table->integer('duration_ms')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_runtime_logs');
    }
};
