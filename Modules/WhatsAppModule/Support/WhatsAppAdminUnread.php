<?php

namespace Modules\WhatsAppModule\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unread counts for admin header (IN messages not yet marked seen).
 * Uses the same channel-scoped rules as the WhatsApp active chats list.
 */
final class WhatsAppAdminUnread
{
    private const CACHE_KEY = 'whatsapp_admin_unread_counts';

    /**
     * @return array{0: int, 1: int} [unread_chats, unread_messages]
     */
    public static function counts(): array
    {
        $stats = self::channelStats(SocialInboxChannel::WHATSAPP);

        return [$stats['unread_chats'], $stats['unread_messages']];
    }

    /**
     * @return array{total: int, unread_chats: int, unread_messages: int, read: int}
     */
    public static function channelStats(string $channel = SocialInboxChannel::WHATSAPP): array
    {
        if (! SocialInboxChannel::isValid($channel)) {
            $channel = SocialInboxChannel::WHATSAPP;
        }

        return Cache::remember(self::CACHE_KEY.':'.$channel, 15, function () use ($channel) {
            return self::channelStatsUncached($channel);
        });
    }

    public static function forgetCache(): void
    {
        foreach (SocialInboxChannel::CHANNELS as $channel) {
            Cache::forget(self::CACHE_KEY.':'.$channel);
        }
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{total: int, unread_chats: int, unread_messages: int, read: int}
     */
    private static function channelStatsUncached(string $channel): array
    {
        try {
            $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
            if (! is_string($table) || $table === '') {
                return self::emptyStats();
            }

            static $tableExists = null;
            if ($tableExists === null) {
                $tableExists = Schema::hasTable($table);
            }
            if (! $tableExists) {
                return self::emptyStats();
            }

            $total = (int) DB::table($table)
                ->where('channel', $channel)
                ->selectRaw('COUNT(DISTINCT phone) AS aggregate_count')
                ->value('aggregate_count');

            $unreadChats = (int) DB::table($table)
                ->where('channel', $channel)
                ->where('direction', 'IN')
                ->whereNull('admin_seen_at')
                ->selectRaw('COUNT(DISTINCT phone) AS aggregate_count')
                ->value('aggregate_count');

            $unreadMessages = (int) DB::table($table)
                ->where('channel', $channel)
                ->where('direction', 'IN')
                ->whereNull('admin_seen_at')
                ->count();

            return [
                'total' => $total,
                'unread_chats' => $unreadChats,
                'unread_messages' => $unreadMessages,
                'read' => max(0, $total - $unreadChats),
            ];
        } catch (\Throwable) {
            return self::emptyStats();
        }
    }

    /**
     * @return array{total: int, unread_chats: int, unread_messages: int, read: int}
     */
    private static function emptyStats(): array
    {
        return [
            'total' => 0,
            'unread_chats' => 0,
            'unread_messages' => 0,
            'read' => 0,
        ];
    }
}
