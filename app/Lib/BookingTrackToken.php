<?php

namespace App\Lib;

use Illuminate\Support\Facades\Crypt;

class BookingTrackToken
{
    public static function issue(string $readableId, string $phone, int $ttlMinutes = 1440): string
    {
        $payload = json_encode([
            'rid' => $readableId,
            'phone' => self::normalizePhone($phone),
            'exp' => now()->addMinutes($ttlMinutes)->timestamp,
        ]);

        return rtrim(strtr(base64_encode(Crypt::encryptString($payload)), '+/', '-_'), '=');
    }

    public static function validate(?string $token, string $readableId, string $phone): bool
    {
        $payload = self::decode($token);
        if ($payload === null) {
            return false;
        }

        if (($payload['rid'] ?? '') !== $readableId) {
            return false;
        }

        if (!hash_equals((string) ($payload['phone'] ?? ''), self::normalizePhone($phone))) {
            return false;
        }

        return true;
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    public static function phoneMatches(?string $stored, string $provided): bool
    {
        if ($stored === null || $stored === '') {
            return false;
        }

        return hash_equals(self::normalizePhone($stored), self::normalizePhone($provided));
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decode(?string $token): ?array
    {
        if ($token === null || $token === '') {
            return null;
        }

        try {
            $normalized = strtr($token, '-_', '+/');
            $padding = strlen($normalized) % 4;
            if ($padding > 0) {
                $normalized .= str_repeat('=', 4 - $padding);
            }

            $payload = json_decode(Crypt::decryptString(base64_decode($normalized, true)), true);
            if (!is_array($payload) || ($payload['exp'] ?? 0) < time()) {
                return null;
            }

            return $payload;
        } catch (\Throwable) {
            return null;
        }
    }
}
