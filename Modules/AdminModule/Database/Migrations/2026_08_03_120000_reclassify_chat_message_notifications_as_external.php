<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\AdminModule\Entities\UserNotification;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_notifications')
            ->where('type', UserNotification::TYPE_CHAT_MESSAGE)
            ->update(['category' => UserNotification::CATEGORY_EXTERNAL]);
    }

    public function down(): void
    {
        DB::table('user_notifications')
            ->where('type', UserNotification::TYPE_CHAT_MESSAGE)
            ->update(['category' => UserNotification::CATEGORY_INTERNAL]);
    }
};
