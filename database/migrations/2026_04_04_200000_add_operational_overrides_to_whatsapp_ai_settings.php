<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_ai_settings', 'db_ai_support_enabled')) {
                $table->boolean('db_ai_support_enabled')->nullable();
            }
            if (! Schema::hasColumn('whatsapp_ai_settings', 'db_gemini_model')) {
                $table->string('db_gemini_model', 255)->nullable();
            }
            if (! Schema::hasColumn('whatsapp_ai_settings', 'db_greeting_buttons')) {
                $table->boolean('db_greeting_buttons')->nullable();
            }
            if (! Schema::hasColumn('whatsapp_ai_settings', 'db_support_hours_start')) {
                $table->string('db_support_hours_start', 16)->nullable();
            }
            if (! Schema::hasColumn('whatsapp_ai_settings', 'db_support_hours_end')) {
                $table->string('db_support_hours_end', 16)->nullable();
            }
            if (! Schema::hasColumn('whatsapp_ai_settings', 'db_support_timezone')) {
                $table->string('db_support_timezone', 64)->nullable();
            }
            if (! Schema::hasColumn('whatsapp_ai_settings', 'db_support_phone_display')) {
                $table->string('db_support_phone_display', 512)->nullable();
            }
            if (! Schema::hasColumn('whatsapp_ai_settings', 'db_ai_dispatch_sync')) {
                $table->boolean('db_ai_dispatch_sync')->nullable();
            }
            if (! Schema::hasColumn('whatsapp_ai_settings', 'db_queue_connection')) {
                $table->string('db_queue_connection', 64)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_ai_settings', function (Blueprint $table) {
            foreach ([
                'db_ai_support_enabled',
                'db_gemini_model',
                'db_greeting_buttons',
                'db_support_hours_start',
                'db_support_hours_end',
                'db_support_timezone',
                'db_support_phone_display',
                'db_ai_dispatch_sync',
                'db_queue_connection',
            ] as $c) {
                if (Schema::hasColumn('whatsapp_ai_settings', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
