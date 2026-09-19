<?php

namespace Modules\WhatsAppModule\Support;

/**
 * Open/closed bucket for a WhatsApp thread status payload.
 */
final class WhatsAppChatStatusBucket
{
    public const OPEN = 'open';

    public const CLOSED = 'closed';

    /**
     * @param  array<string, mixed>|null  $chatStatus
     */
    public static function fromChatStatus(?array $chatStatus): string
    {
        $bucket = is_array($chatStatus) ? strtolower(trim((string) ($chatStatus['bucket'] ?? ''))) : '';

        return $bucket === self::CLOSED ? self::CLOSED : self::OPEN;
    }

    /**
     * @param  array<string, mixed>|null  $chatStatus
     * @param  list<string>  $wantedBuckets
     */
    public static function matches(?array $chatStatus, array $wantedBuckets): bool
    {
        if ($wantedBuckets === []) {
            return true;
        }

        return in_array(self::fromChatStatus($chatStatus), $wantedBuckets, true);
    }
}
