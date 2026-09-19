<?php

namespace Modules\WhatsAppModule\Support;

/**
 * WhatsApp thread assignee keys used by the inbox and dashboard widgets.
 */
final class WhatsAppChatHandler
{
    public const AI = 'AI';

    /** Message thread with no matching whatsapp_users row. Not unassigned. */
    public const UNMATCHED = '__unmatched__';

    public static function isUnassigned(?string $handledBy): bool
    {
        $key = trim((string) $handledBy);
        if ($key === self::UNMATCHED) {
            return false;
        }

        return $key === '' || strcasecmp($key, self::AI) === 0;
    }

    public static function normalizeKey(?string $handledBy): string
    {
        $key = trim((string) $handledBy);
        if ($key === '' || strcasecmp($key, self::AI) === 0) {
            return self::AI;
        }

        return $key;
    }

    /**
     * @param  list<string>  $handlerFilters  "ai" and/or admin user ids
     */
    public static function matchesFilters(?string $handledByKey, array $handlerFilters): bool
    {
        if ($handlerFilters === []) {
            return true;
        }

        $key = trim((string) $handledByKey);
        foreach ($handlerFilters as $hf) {
            $wanted = strtolower(trim((string) $hf));
            if ($wanted === 'ai') {
                if (self::isUnassigned($key)) {
                    return true;
                }

                continue;
            }
            if ($key !== '' && $key !== self::UNMATCHED && $key === (string) $hf) {
                return true;
            }
        }

        return false;
    }
}
