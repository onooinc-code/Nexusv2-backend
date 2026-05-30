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
       Schema::table('agents', function (Blueprint $table) {
             $table->string('type')->default('reflection')->after('provider');
             $table->timestamp('last_executed_at')->nullable()->after('is_active');
             $table->integer('execution_count')->default(0)->after('last_executed_at');
             $table->integer('success_count')->default(0)->after('execution_count');
             $table->integer('error_count')->default(0)->after('success_count');
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            //
        });
    }
};
