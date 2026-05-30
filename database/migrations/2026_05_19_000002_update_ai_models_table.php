<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::rename('ai_models', 'ai_models_old');

            Schema::create('ai_models', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->uuid('provider_id')->nullable();
                $table->integer('context_window')->nullable();
                $table->decimal('input_cost_per_m', 10, 6)->nullable();
                $table->decimal('output_cost_per_m', 10, 6)->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->index(['provider_id']);
                $table->index(['context_window']);
            });

            $oldRows = DB::table('ai_models_old')->get();
            foreach ($oldRows as $row) {
                DB::table('ai_models')->insert([
                    'id' => (string) Str::uuid(),
                    'name' => $row->name,
                    'provider_id' => null,
                    'context_window' => null,
                    'input_cost_per_m' => null,
                    'output_cost_per_m' => null,
                    'last_synced_at' => null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }

            Schema::dropIfExists('ai_models_old');

            return;
        }

        Schema::table('ai_models', function (Blueprint $table) {
            // Drop existing columns that will be replaced
            $table->dropColumn(['provider', 'external_id', 'description', 'capabilities', 'metadata', 'status']);

            // Change id to UUID
            $table->uuid('id')->change();

            // Add new columns
            $table->uuid('provider_id')->nullable()->after('id');
            $table->integer('context_window')->nullable()->after('provider_id');
            $table->decimal('input_cost_per_m', 10, 6)->nullable()->after('context_window');
            $table->decimal('output_cost_per_m', 10, 6)->nullable()->after('input_cost_per_m');
            $table->timestamp('last_synced_at')->nullable()->after('output_cost_per_m');

            // Add foreign key constraint
            $table->foreign('provider_id')->references('id')->on('ai_providers')->onDelete('set null');

            // Add indexes
            $table->index(['provider_id']);
            $table->index(['context_window']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['provider_id']);

            // Drop new columns
            $table->dropColumn(['provider_id', 'context_window', 'input_cost_per_m', 'output_cost_per_m', 'last_synced_at']);

            // Change id back to auto-incrementing integer
            $table->id()->change();

            // Add back the original columns
            $table->string('provider')->nullable()->after('id');
            $table->string('external_id')->nullable()->unique()->after('provider');
            $table->text('description')->nullable()->after('external_id');
            $table->json('capabilities')->nullable()->after('description');
            $table->json('metadata')->nullable()->after('capabilities');
            $table->string('status')->default('active')->after('metadata');
        });
    }
};
