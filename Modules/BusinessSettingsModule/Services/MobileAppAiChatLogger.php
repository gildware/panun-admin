<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\Log;

final class MobileAppAiChatLogger
{
    public static function route(string|int $userId, string $message, string $action, string $step): void
    {
        if (! config('mobile_app_ai_behavior.log_routing', true)) {
            return;
        }

        Log::info('mobile_app_ai.route', [
            'user_id' => $userId,
            'action' => $action,
            'step' => $step,
            'message_preview' => mb_substr(trim($message), 0, 120),
        ]);
    }

    public static function intentRoute(
        string|int $userId,
        string $message,
        string $intent,
        float $confidence,
        string $source,
        string $handler,
    ): void {
        if (! config('mobile_app_ai_behavior.log_routing', true)) {
            return;
        }

        Log::info('mobile_app_ai.intent_route', [
            'user_id' => $userId,
            'message' => mb_substr(trim($message), 0, 500),
            'intent' => $intent,
            'confidence' => round($confidence, 3),
            'source' => $source,
            'handler' => $handler,
        ]);
    }

    public static function turn(
        string|int $userId,
        string $message,
        string $intent,
        float $confidence,
        string $source,
        string $domain,
        string $handler,
        string $routingMode,
        bool $geminiUsed,
        bool $fallbackUsed,
    ): void {
        if (! config('mobile_app_ai_behavior.log_routing', true)) {
            return;
        }

        Log::info('mobile_app_ai.turn', [
            'user_id' => $userId,
            'message' => mb_substr(trim($message), 0, 500),
            'intent' => $intent,
            'confidence' => round($confidence, 3),
            'source' => $source,
            'domain' => $domain,
            'handler' => $handler,
            'routing_mode' => $routingMode,
            'gemini_used' => $geminiUsed,
            'fallback_used' => $fallbackUsed,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function pick(string|int $userId, string $serviceName, array $context = []): void
    {
        if (! config('mobile_app_ai_behavior.log_routing', true)) {
            return;
        }

        Log::info('mobile_app_ai.service_pick', array_merge([
            'user_id' => $userId,
            'service' => $serviceName,
        ], $context));
    }
}
