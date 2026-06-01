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
        Schema::create('dead_letter_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('agent_tasks')->onDelete('cascade');
            $table->string('queue')->default('agent-tasks');
            $table->text('exception_message')->nullable();
            $table->longText('exception_trace')->nullable();
            $table->timestamp('failed_at')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->index('task_id');
            $table->index('failed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dead_letter_tasks');
    }
};
