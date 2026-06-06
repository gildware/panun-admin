<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_app_ai_conversations', function (Blueprint $table) {
            $table->json('booking_draft')->nullable()->after('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::table('mobile_app_ai_conversations', function (Blueprint $table) {
            $table->dropColumn('booking_draft');
        });
    }
};
