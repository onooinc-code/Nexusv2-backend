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
        // 1. Update contacts table with canonical_name and soft deletes
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'canonical_name')) {
                $table->string('canonical_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('contacts', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // 2. Create contact_identifiers table
        Schema::create('contact_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('type'); // email, phone, external_id
            $table->string('value')->index();
            $table->boolean('trusted')->default(true);
            $table->timestamps();
        });

        // 3. Create contact_relationships table
        Schema::create('contact_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('related_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('relationship_type'); // family, work, social, vendor, partner
            $table->integer('mention_count')->default(1);
            $table->float('confidence')->default(1.0);
            $table->timestamps();
            
            $table->unique(['contact_id', 'related_contact_id', 'relationship_type'], 'contact_relationship_unique');
        });

        // 4. Create contact_preferences table
        Schema::create('contact_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('preference_type'); // channel, tone, timezone, opt_out
            $table->string('value');
            $table->float('confidence')->default(1.0);
            $table->integer('inferred_from_count')->default(0);
            $table->timestamps();

            $table->unique(['contact_id', 'preference_type'], 'contact_preference_unique');
        });

        // 5. Create contact_aliases table
        Schema::create('contact_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('primary_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('alias_name')->index();
            $table->float('confidence')->default(1.0);
            $table->text('created_context')->nullable();
            $table->timestamps();

            $table->unique(['primary_contact_id', 'alias_name'], 'contact_alias_unique');
        });

        // 6. Create notification_templates table
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('channels'); // JSON array: e.g. ["email", "sms", "whatsapp"]
            $table->timestamps();
        });

        // 7. Create notification_logs table
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('channel'); // email, sms, whatsapp, push
            $table->string('recipient');
            $table->string('template_key')->nullable();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('payload')->nullable();
            $table->string('status')->default('pending'); // pending, sent, delivered, failed
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('contact_aliases');
        Schema::dropIfExists('contact_preferences');
        Schema::dropIfExists('contact_relationships');
        Schema::dropIfExists('contact_identifiers');
        
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'canonical_name']);
        });
    }
};
