<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Sandbox WhatsApp numbers: AI runs for real (DB + Gemini) but outbound Cloud API sends are skipped.
 * Use for admin playground and automated tests — no customer phone required.
 */
final class WhatsAppAiPlayground
{
    /**
     * Default isolated "phone" key stored in whatsapp_messages.phone for test threads.
     */
    public static function defaultSandboxPhone(): string
    {
        return (string) config('whatsappmodule.ai_playground_phone', 'AI_TEST_SANDBOX');
    }

    /**
     * True when outbound WhatsApp Cloud API must not be called (no Meta send).
     */
    public static function skipCloudApi(string $phone): bool
    {
        $phone = trim($phone);
        if ($phone === '') {
            return false;
        }
        if (str_starts_with($phone, 'AI_TEST_')) {
            return true;
        }
        $extra = config('whatsappmodule.ai_playground_phones_extra');
        if (is_array($extra)) {
            return in_array($phone, $extra, true);
        }

        return false;
    }

    /**
     * Strip the internal "[Quick actions]" marker persisted on outbound rows when buttons were attached.
     */
    public static function stripPersistedQuickActionsMarker(string $text): string
    {
        $t = preg_replace('/\n\[Quick actions\]\s*$/u', '', $text) ?? $text;

        return trim($t);
    }

    /**
     * Inbound text Meta sends when a customer taps a quick-reply (matches WhatsAppGraphInboundHandler).
     */
    public static function simulatedButtonInboundText(string $title, string $id): string
    {
        return trim($title).' ['.$id.']';
    }

    private static function snapshotCacheKey(string $phone): string
    {
        return 'wa_ai_playground_snap_v1_'.md5(trim($phone));
    }

    /**
     * Remember button rows for the latest sandbox outbound (for playground UI). Only when Cloud API is skipped.
     *
     * @param  array<int, array<string, mixed>>  $metaButtons
     * @param  list<string>|null  $quickReplyIds
     */
    public static function storeOutboundSnapshot(string $phone, string $visibleBody, array $metaButtons, ?array $quickReplyIds = null): void
    {
        if (! self::skipCloudApi($phone)) {
            return;
        }
        $part = WhatsAppSessionInteractiveSequence::snapshotFromMeta($metaButtons, $quickReplyIds);
        Cache::put(self::snapshotCacheKey($phone), array_merge([
            'visible_body' => $visibleBody,
            'captured_at' => now()->toIso8601String(),
        ], $part), now()->addHours(4));
    }

    public static function storePlainOutbound(string $phone, string $visibleBody): void
    {
        if (! self::skipCloudApi($phone)) {
            return;
        }
        Cache::put(self::snapshotCacheKey($phone), [
            'visible_body' => $visibleBody,
            'captured_at' => now()->toIso8601String(),
            'quick_replies' => [],
            'urls' => [],
            'phones' => [],
        ], now()->addHours(4));
    }

    /**
     * @return array{visible_body?: string, captured_at?: string, quick_replies: list, urls: list, phones: list}|null
     */
    public static function getOutboundSnapshot(string $phone): ?array
    {
        $v = Cache::get(self::snapshotCacheKey($phone));

        return is_array($v) ? $v : null;
    }

    public static function clearOutboundSnapshot(string $phone): void
    {
        Cache::forget(self::snapshotCacheKey($phone));
    }
}
