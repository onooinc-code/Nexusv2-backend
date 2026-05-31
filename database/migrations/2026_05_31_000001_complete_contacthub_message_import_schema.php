<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_import_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('contact_import_batches', 'contact_id')) {
                $table->foreignId('contact_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('contacts')
                    ->nullOnDelete();
            }
        });

        Schema::table('contact_message_threads', function (Blueprint $table) {
            if (! Schema::hasColumn('contact_message_threads', 'source')) {
                $table->string('source')->nullable()->after('contact_id');
            }
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('contact_messages', 'sender_contact_id')) {
                $table->foreignId('sender_contact_id')
                    ->nullable()
                    ->after('thread_id')
                    ->constrained('contacts')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('contact_messages', 'sender_name')) {
                $table->string('sender_name')->nullable()->after('sender_identifier');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            if (Schema::hasColumn('contact_messages', 'sender_contact_id')) {
                $table->dropConstrainedForeignId('sender_contact_id');
            }

            if (Schema::hasColumn('contact_messages', 'sender_name')) {
                $table->dropColumn('sender_name');
            }
        });

        Schema::table('contact_message_threads', function (Blueprint $table) {
            if (Schema::hasColumn('contact_message_threads', 'source')) {
                $table->dropColumn('source');
            }
        });

        Schema::table('contact_import_batches', function (Blueprint $table) {
            if (Schema::hasColumn('contact_import_batches', 'contact_id')) {
                $table->dropConstrainedForeignId('contact_id');
            }
        });
    }
};
