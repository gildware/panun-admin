<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_conversations', function (Blueprint $table) {
            $table->uuid('reply_to_conversation_id')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('channel_conversations', function (Blueprint $table) {
            $table->dropColumn('reply_to_conversation_id');
        });
    }
};
