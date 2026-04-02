<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_conversation_templates')) {
            return;
        }
        Schema::table('whatsapp_conversation_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_conversation_templates', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('whatsapp_conversation_templates')) {
            return;
        }
        Schema::table('whatsapp_conversation_templates', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_conversation_templates', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
