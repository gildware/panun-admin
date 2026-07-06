<?php

namespace Modules\WhatsAppModule\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unread counts for admin header (IN messages not yet marked seen).
 */
final class WhatsAppAdminUnread
{
    private const CACHE_KEY = 'whatsapp_admin_unread_counts';

    /**
     * @return array{0: int, 1: int} [unread_chats, unread_messages]
     */
    public static function counts(): array
    {
        return Cache::remember(self::CACHE_KEY, 15, function () {
            return self::countsUncached();
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
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

            $unreadMessages = (int) self::unreadQuery($table)->count();

            $unreadChats = (int) self::unreadQuery($table)
                ->distinct()
                ->count('phone');

            return [$unreadChats, $unreadMessages];
        } catch (\Throwable) {
            return [0, 0];
        }
    }

    private static function unreadQuery(string $table): Builder
    {
        $query = DB::table($table)
            ->whereRaw("UPPER(COALESCE(direction, '')) = 'IN'")
            ->whereNull('admin_seen_at');

        static $hasChannelColumn = null;
        if ($hasChannelColumn === null) {
            $hasChannelColumn = Schema::hasColumn($table, 'channel');
        }

        if ($hasChannelColumn) {
            $query->where(function (Builder $inner) {
                $inner->where('channel', SocialInboxChannel::WHATSAPP)
                    ->orWhereNull('channel');
            });
        }

        return $query;
    }
}
