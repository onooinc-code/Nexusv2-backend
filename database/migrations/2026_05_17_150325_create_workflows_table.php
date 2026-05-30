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
       Schema::create('workflows', function (Blueprint $table) {
             $table->id();
             $table->string('name');
             $table->string('key')->unique();
             $table->text('description')->nullable();
             $table->json('steps')->nullable();
             $table->string('trigger_type')->default('manual');
             $table->json('trigger_config')->nullable();
             $table->string('status')->default('draft');
             $table->json('settings')->nullable();
             $table->json('metadata')->nullable();
             $table->boolean('is_active')->default(true);
             $table->timestamp('last_executed_at')->nullable();
             $table->integer('execution_count')->default(0);
             $table->integer('success_count')->default(0);
             $table->integer('error_count')->default(0);
             $table->timestamps();
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
