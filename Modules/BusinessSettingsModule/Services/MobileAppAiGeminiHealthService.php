<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\WhatsAppModule\Services\WhatsAppGeminiSupportClient;

/**
 * Probes Gemini availability — chat is blocked when unhealthy (no fake fallbacks).
 */
final class MobileAppAiGeminiHealthService
{
    private const CACHE_KEY = 'mobile_app_ai_gemini_health_v1';

    private const TTL_OK_SECONDS = 180;

    private const TTL_FAIL_SECONDS = 60;

    public function __construct(
        protected MobileAppAiRuntimeResolver $runtime,
        protected WhatsAppGeminiSupportClient $gemini,
    ) {}

    public function isHealthy(bool $forceRefresh = false): bool
    {
        if (! $this->runtime->enabled()) {
            return false;
        }

        if (trim((string) config('services.gemini.api_key', '')) === '') {
            return false;
        }

        if (! $forceRefresh) {
            $cached = Cache::get(self::CACHE_KEY);
            if ($cached !== null) {
                return (bool) $cached;
            }
        }

        $ok = $this->probe();
        Cache::put(self::CACHE_KEY, $ok, $ok ? self::TTL_OK_SECONDS : self::TTL_FAIL_SECONDS);

        return $ok;
    }

    public function markUnhealthy(): void
    {
        Cache::put(self::CACHE_KEY, false, self::TTL_FAIL_SECONDS);
    }

    public function markHealthy(): void
    {
        Cache::put(self::CACHE_KEY, true, self::TTL_OK_SECONDS);
    }

    private function probe(): bool
    {
        try {
            $text = $this->gemini->generatePlainText(
                'You are a health check. Reply with exactly the word OK and nothing else.',
                'ping'
            );
            $ok = $text !== null && trim($text) !== '';

            if (! $ok) {
                Log::warning('Mobile app AI Gemini health probe failed', ['reason' => 'empty_response']);
            }

            return $ok;
        } catch (\Throwable $e) {
            Log::warning('Mobile app AI Gemini health probe failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
