<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_conversations', function (Blueprint $table) {
            $table->index(['channel_id', 'created_at'], 'channel_conversations_channel_created_idx');
        });

        Schema::table('channel_users', function (Blueprint $table) {
            $table->index(['user_id', 'channel_id'], 'channel_users_user_channel_idx');
            $table->index(['channel_id', 'user_id'], 'channel_users_channel_user_idx');
        });

        Schema::table('channel_lists', function (Blueprint $table) {
            $table->index('updated_at', 'channel_lists_updated_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('channel_conversations', function (Blueprint $table) {
            $table->dropIndex('channel_conversations_channel_created_idx');
        });

        Schema::table('channel_users', function (Blueprint $table) {
            $table->dropIndex('channel_users_user_channel_idx');
            $table->dropIndex('channel_users_channel_user_idx');
        });

        Schema::table('channel_lists', function (Blueprint $table) {
            $table->dropIndex('channel_lists_updated_at_idx');
        });
    }
};
