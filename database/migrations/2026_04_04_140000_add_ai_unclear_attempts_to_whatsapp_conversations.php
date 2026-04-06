<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_conversations', 'ai_unclear_attempts')) {
                $table->unsignedTinyInteger('ai_unclear_attempts')->default(0)->after('active_lead_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_conversations', 'ai_unclear_attempts')) {
                $table->dropColumn('ai_unclear_attempts');
            }
        });
    }
};
