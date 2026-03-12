<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('wa_message_id', 255)->nullable()->index()->after('message_type');
            $table->string('status', 20)->nullable()->after('wa_message_id'); // sent, delivered, read
            $table->timestamp('status_updated_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn(['wa_message_id', 'status', 'status_updated_at']);
        });
    }
};
