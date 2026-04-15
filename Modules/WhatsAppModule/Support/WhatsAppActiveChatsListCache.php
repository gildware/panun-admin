<?php

namespace Modules\WhatsAppModule\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Active chat list is cached for admin; bump {@see LEGACY_KEY} when row shape changes.
 */
final class WhatsAppActiveChatsListCache
{
    public const LEGACY_KEY = 'whatsapp_active_chats_list';

    public static function listCacheKey(): string
    {
        return 'whatsapp_active_chats_list_v3_' . SocialInboxChannel::current();
    }

    public static function chatFullCacheKey(string $phone): string
    {
        return 'whatsapp_chat_full_v3_' . md5(SocialInboxChannel::current() . '|' . $phone);
    }

    public static function forgetAll(): void
    {
        Cache::forget(self::LEGACY_KEY);
        foreach (SocialInboxChannel::CHANNELS as $ch) {
            Cache::forget('whatsapp_active_chats_list_v2');
            Cache::forget('whatsapp_active_chats_list_v3_' . $ch);
        }
    }

    public static function forgetChatFull(?string $phone): void
    {
        if ($phone === null || $phone === '') {
            return;
        }
        Cache::forget(self::chatFullCacheKey($phone));
    }
}
