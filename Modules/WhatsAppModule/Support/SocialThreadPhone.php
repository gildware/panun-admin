<?php

namespace Modules\WhatsAppModule\Support;

/**
 * Canonical `whatsapp_messages.phone` / thread keys for non-WhatsApp channels (avoids collisions with E.164).
 */
final class SocialThreadPhone
{
    public static function forFacebook(string $psid): string
    {
        return 'FB_' . preg_replace('/\D+/', '', $psid);
    }

    public static function forInstagram(string $scopedId): string
    {
        return 'IG_' . preg_replace('/\D+/', '', $scopedId);
    }

    public static function stripChannelPrefix(string $storedPhone, string $channel): string
    {
        if ($channel === SocialInboxChannel::FACEBOOK && str_starts_with($storedPhone, 'FB_')) {
            return substr($storedPhone, 3);
        }
        if ($channel === SocialInboxChannel::INSTAGRAM && str_starts_with($storedPhone, 'IG_')) {
            return substr($storedPhone, 3);
        }

        return $storedPhone;
    }
}
