<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store the full Meta Cloud API inbound context per message for future features.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_messages')) {
            return;
        }

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_messages', 'meta_payload')) {
                $table->json('meta_payload')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('whatsapp_messages')) {
            return;
        }

        if (Schema::hasColumn('whatsapp_messages', 'meta_payload')) {
            Schema::table('whatsapp_messages', function (Blueprint $table) {
                $table->dropColumn('meta_payload');
            });
        }
    }
};
