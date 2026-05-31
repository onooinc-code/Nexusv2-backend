<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            if (! Schema::hasColumn('workflows', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('workflows', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('description');
            }
            if (! Schema::hasColumn('workflows', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete()->after('is_system');
            }
            if (! Schema::hasColumn('workflows', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('status');
            }

            $table->index(['status', 'is_active']);
            $table->index(['trigger_type', 'is_active']);
        });

        Schema::create('workflow_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('definition');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('change_summary')->nullable();
            $table->timestamps();

            $table->unique(['workflow_id', 'version_number']);
            $table->index('created_at');
        });

        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->uuid('workflow_version_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trigger_source')->default('manual');
            $table->string('run_mode')->default('async');
            $table->string('status')->default('pending');
            $table->json('input_payload')->nullable();
            $table->json('runtime_state')->nullable();
            $table->json('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('workflow_version_id')->references('id')->on('workflow_versions')->nullOnDelete();
            $table->index(['workflow_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('workflow_step_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('execution_id');
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->string('step_id');
            $table->string('step_name')->nullable();
            $table->string('step_type')->nullable();
            $table->string('status')->default('pending');
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('attempt')->default(1);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('execution_id')->references('id')->on('workflow_executions')->cascadeOnDelete();
            $table->index(['execution_id', 'step_id']);
            $table->index(['workflow_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_step_logs');
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflow_versions');

        Schema::table('workflows', function (Blueprint $table) {
            $columns = ['uuid', 'is_system', 'owner_id', 'version'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('workflows', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
