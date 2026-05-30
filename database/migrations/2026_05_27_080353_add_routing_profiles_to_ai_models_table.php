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
        Schema::table('ai_models', function (Blueprint $table) {
            $table->string('quality_tier')->nullable()->comment('basic, standard, premium');
            $table->string('cost_profile')->nullable()->comment('low, medium, high');
            $table->string('latency_profile')->nullable()->comment('fast, balanced, safe');
            $table->string('security_class')->nullable()->comment('standard, sensitive, restricted');
            $table->json('language_support')->nullable()->comment('Supported languages e.g., ["en", "ar"]');
            $table->string('version_tag')->nullable();
            $table->json('presets')->nullable()->comment('Custom model parameter presets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->dropColumn([
                'quality_tier',
                'cost_profile',
                'latency_profile',
                'security_class',
                'language_support',
                'version_tag',
                'presets'
            ]);
        });
    }
};
