<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AdminModule\Entities\UserNotification;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->string('category', 16)->default(UserNotification::CATEGORY_EXTERNAL)->after('type');
            $table->index(['user_id', 'category', 'read_at']);
        });

        $internalTypes = [
            UserNotification::TYPE_CHAT_MESSAGE,
            UserNotification::TYPE_LEAD_COMMENT,
            UserNotification::TYPE_TICKET_ASSIGNED,
            UserNotification::TYPE_LEAD_ASSIGNED,
            UserNotification::TYPE_BOOKING_ASSIGNED,
            UserNotification::TYPE_WHATSAPP_ASSIGNED,
        ];

        DB::table('user_notifications')
            ->whereIn('type', $internalTypes)
            ->update(['category' => UserNotification::CATEGORY_INTERNAL]);
    }

    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'category', 'read_at']);
            $table->dropColumn('category');
        });
    }
};
