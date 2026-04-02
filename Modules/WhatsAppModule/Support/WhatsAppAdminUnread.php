<?php

namespace Modules\WhatsAppModule\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unread counts for admin header (IN messages not yet marked seen).
 */
final class WhatsAppAdminUnread
{
    /**
     * @return array{0: int, 1: int} [unread_chats, unread_messages]
     */
    public static function counts(): array
    {
        try {
            $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
            if (!is_string($table) || $table === '' || !Schema::hasTable($table)) {
                return [0, 0];
            }

            $unreadMessages = (int) DB::table($table)
                ->where('direction', 'IN')
                ->whereNull('admin_seen_at')
                ->count();

            $unreadChats = (int) DB::table($table)
                ->where('direction', 'IN')
                ->whereNull('admin_seen_at')
                ->distinct()
                ->count('phone');

            return [$unreadChats, $unreadMessages];
        } catch (\Throwable) {
            return [0, 0];
        }
    }
}
