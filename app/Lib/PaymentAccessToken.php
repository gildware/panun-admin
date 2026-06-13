<?php

namespace App\Lib;

use Illuminate\Support\Facades\Crypt;

class PaymentAccessToken
{
    public static function issue(string $subjectId, int $ttlMinutes = 60): string
    {
        $payload = json_encode([
            'sub' => $subjectId,
            'exp' => now()->addMinutes($ttlMinutes)->timestamp,
        ]);

        return rtrim(strtr(base64_encode(Crypt::encryptString($payload)), '+/', '-_'), '=');
    }

    public static function resolve(?string $token): ?string
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

            $subjectId = $payload['sub'] ?? null;

            return is_string($subjectId) && $subjectId !== '' ? $subjectId : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
