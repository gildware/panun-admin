<?php

namespace Modules\WhatsAppModule\Support;

/**
 * Current admin / webhook channel: whatsapp | instagram | facebook.
 */
final class SocialInboxChannel
{
    public const WHATSAPP = 'whatsapp';

    public const INSTAGRAM = 'instagram';

    public const FACEBOOK = 'facebook';

    /** @var list<string> */
    public const CHANNELS = [self::WHATSAPP, self::INSTAGRAM, self::FACEBOOK];

    private static ?string $override = null;

    public static function setOverride(?string $channel): void
    {
        self::$override = $channel !== null && self::isValid($channel) ? $channel : null;
    }

    public static function using(string $channel, callable $callback): mixed
    {
        if (!self::isValid($channel)) {
            $channel = self::WHATSAPP;
        }
        $prev = self::$override;
        self::$override = $channel;
        try {
            return $callback();
        } finally {
            self::$override = $prev;
        }
    }

    public static function current(): string
    {
        if (self::$override !== null) {
            return self::$override;
        }
        if (app()->bound('social_inbox_channel')) {
            $v = app('social_inbox_channel');
            if (is_string($v) && self::isValid($v)) {
                return $v;
            }
        }

        return self::WHATSAPP;
    }

    public static function isValid(string $channel): bool
    {
        return in_array($channel, self::CHANNELS, true);
    }

    /**
     * @return array<string, string>
     */
    public static function routeDefaults(): array
    {
        return ['channel' => self::current()];
    }
}
