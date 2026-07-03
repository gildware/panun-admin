<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Monotonic revision for customer home content. Bumped when home-affecting data changes.
 */
class CustomerHomeContentVersion
{
    private const GLOBAL_KEY = 'customer_home_content_version:global';

    private const PERSONAL_PREFIX = 'customer_home_content_version:user:';

    public static function global(): string
    {
        return (string) Cache::get(self::GLOBAL_KEY, '0');
    }

    public static function personal(int|string $userId): string
    {
        return (string) Cache::get(self::PERSONAL_PREFIX.$userId, '0');
    }

    public static function bumpGlobal(): void
    {
        Cache::forever(self::GLOBAL_KEY, (string) ((int) self::global() + 1));
    }

    public static function bumpPersonal(int|string $userId): void
    {
        Cache::forever(self::PERSONAL_PREFIX.$userId, (string) ((int) self::personal($userId) + 1));
    }

    public static function resolveForRequest(
        string $layoutHash,
        ?int $userId = null,
    ): string {
        $parts = [self::global(), $layoutHash];
        if ($userId !== null) {
            $parts[] = self::personal($userId);
        }

        return implode(':', $parts);
    }
}
