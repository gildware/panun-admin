<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GuestSessionService
{
    private const CACHE_PREFIX = 'guest_session:';

    private const TTL_DAYS = 90;

    public static function resolveGuestId(Request $request): string
    {
        $guestId = $request->input('guest_id') ?? $request->header('guest_id');

        return is_string($guestId) ? trim($guestId) : '';
    }

    public static function rejectIfInvalid(Request $request, bool $isLoggedIn, mixed $guestId): ?JsonResponse
    {
        if ($isLoggedIn) {
            return null;
        }

        $guestId = (string) $guestId;
        $secret = (string) $request->header('X-Guest-Secret', '');

        if ($guestId === '' || ! self::validate($guestId, $secret)) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        return null;
    }

    /**
     * Register a guest session. Returns true when registered or already registered with the same secret.
     */
    public static function register(string $guestId, string $secret): bool
    {
        if ($guestId === '' || strlen($secret) < 32) {
            return false;
        }

        $stored = Cache::get(self::cacheKey($guestId));
        $hashed = self::hashSecret($secret);

        if ($stored !== null) {
            return hash_equals((string) $stored, $hashed);
        }

        Cache::put(self::cacheKey($guestId), $hashed, now()->addDays(self::TTL_DAYS));

        return true;
    }

    public static function validate(string $guestId, ?string $secret): bool
    {
        if ($guestId === '' || $secret === null || strlen($secret) < 32) {
            return false;
        }

        $stored = Cache::get(self::cacheKey($guestId));
        if ($stored === null) {
            return false;
        }

        return hash_equals((string) $stored, self::hashSecret($secret));
    }

    public static function invalidate(string $guestId): void
    {
        Cache::forget(self::cacheKey($guestId));
    }

    private static function cacheKey(string $guestId): string
    {
        return self::CACHE_PREFIX . $guestId;
    }

    private static function hashSecret(string $secret): string
    {
        return hash('sha256', $secret);
    }
}
