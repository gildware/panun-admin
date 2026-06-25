<?php

namespace Modules\WhatsAppModule\Support;

use Illuminate\Support\Facades\Cache;
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
        return Cache::remember('whatsapp_admin_unread_counts', 15, function () {
            return self::countsUncached();
        });
    }

    /**
     * @return array{0: int, 1: int} [unread_chats, unread_messages]
     */
    private static function countsUncached(): array
    {
        try {
            $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
            if (! is_string($table) || $table === '') {
                return [0, 0];
            }

            static $tableExists = null;
            if ($tableExists === null) {
                $tableExists = Schema::hasTable($table);
            }
            if (! $tableExists) {
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
