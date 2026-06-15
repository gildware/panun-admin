<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_conversations', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(0)->after('reply_to_conversation_id');
            $table->timestamp('pinned_at')->nullable()->after('is_pinned');
            $table->uuid('pinned_by')->nullable()->after('pinned_at');
            $table->index(['channel_id', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::table('channel_conversations', function (Blueprint $table) {
            $table->dropIndex(['channel_id', 'is_pinned']);
            $table->dropColumn(['is_pinned', 'pinned_at', 'pinned_by']);
        });
    }
};
