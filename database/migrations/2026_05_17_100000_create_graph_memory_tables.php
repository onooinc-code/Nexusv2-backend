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
        Schema::create('graph_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('label')->index();
            $table->string('type')->index(); // e.g., 'contact', 'topic', 'concept'
            $table->foreignId('related_id')->nullable()->index(); // ID of the related entity (e.g., contact_id)
            $table->string('related_type')->nullable(); // e.g., 'App\Models\Contact'
            $table->json('properties')->nullable(); // Additional properties
            $table->timestamps();
        });

        Schema::create('graph_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_node')->constrained('graph_nodes')->cascadeOnDelete();
            $table->foreignId('to_node')->constrained('graph_nodes')->cascadeOnDelete();
            $table->string('label')->index(); // e.g., 'knows', 'related_to', 'works_at'
            $table->json('properties')->nullable(); // Edge properties (weight, etc.)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('graph_edges');
        Schema::dropIfExists('graph_nodes');
    }
};