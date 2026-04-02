<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_messages', 'reply_to_wa_message_id')) {
                $table->string('reply_to_wa_message_id', 255)->nullable()->after('wa_message_id');
            }
            if (!Schema::hasColumn('whatsapp_messages', 'reactions')) {
                $table->json('reactions')->nullable()->after('reply_to_wa_message_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_messages', 'reactions')) {
                $table->dropColumn('reactions');
            }
            if (Schema::hasColumn('whatsapp_messages', 'reply_to_wa_message_id')) {
                $table->dropColumn('reply_to_wa_message_id');
            }
        });
    }
};
