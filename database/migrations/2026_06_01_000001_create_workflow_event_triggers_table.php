<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_event_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->string('event_name');
            $table->json('condition_payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['event_name', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_event_triggers');
    }
};
