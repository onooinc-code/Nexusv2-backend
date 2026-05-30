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
        Schema::table('settings', function (Blueprint $table) {
            // Add multi-tenancy support
            $table->string('scope')->default('global')->after('is_encrypted');
            $table->unsignedBigInteger('workspace_id')->nullable()->after('scope');
            $table->unsignedBigInteger('user_id')->nullable()->after('workspace_id');

            // Add indexes for performance
            $table->index(['scope', 'key']);
            $table->index(['workspace_id', 'key']);
            $table->index(['user_id', 'key']);
            $table->index(['scope', 'workspace_id', 'key']);

            // Add foreign key for workspace (if exists)
            if (Schema::hasTable('workspaces')) {
                $table->foreign('workspace_id')
                    ->references('id')
                    ->on('workspaces')
                    ->onDelete('cascade');
            }

            // Add foreign key for user
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Drop foreign keys safely
            try {
                $table->dropForeign('settings_workspace_id_foreign');
            } catch (\Exception $e) {
                // Key doesn't exist, continue
            }

            try {
                $table->dropForeign('settings_user_id_foreign');
            } catch (\Exception $e) {
                // Key doesn't exist, continue
            }

            // Drop indexes
            if (Schema::hasColumn('settings', 'scope')) {
                try {
                    $table->dropIndex(['scope', 'key']);
                } catch (\Exception $e) {
                    // Index doesn't exist, continue
                }
            }

            if (Schema::hasColumn('settings', 'workspace_id')) {
                try {
                    $table->dropIndex(['workspace_id', 'key']);
                } catch (\Exception $e) {
                    // Index doesn't exist, continue
                }
            }

            if (Schema::hasColumn('settings', 'user_id')) {
                try {
                    $table->dropIndex(['user_id', 'key']);
                } catch (\Exception $e) {
                    // Index doesn't exist, continue
                }
            }

            try {
                $table->dropIndex(['scope', 'workspace_id', 'key']);
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }

            // Drop columns
            $table->dropColumn(['scope', 'workspace_id', 'user_id']);
        });
    }
};
