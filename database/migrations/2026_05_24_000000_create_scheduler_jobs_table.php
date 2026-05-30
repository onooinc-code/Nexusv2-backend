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
        Schema::create('scheduler_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // command, job, webhook
            $table->text('payload')->nullable(); // JSON configuration
            $table->string('cron_expression');
            $table->string('status')->default('active'); // active, paused
            $table->boolean('is_running')->default(false);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduler_jobs');
    }
};
