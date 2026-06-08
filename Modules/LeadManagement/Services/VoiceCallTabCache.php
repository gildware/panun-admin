<?php

namespace Modules\LeadManagement\Services;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Short-lived HTML cache for Voice Calls admin tab AJAX loads.
 */
class VoiceCallTabCache
{
    public const TAB_PLACED = 'placed';

    public const TAB_BULK = 'bulk';

    public const TAB_BULK_DETAILS = 'bulk_details';

    public const TAB_WHATSAPP_FOLLOWUP = 'whatsapp_followup';

    public const TAB_VOICE_CRON = 'voice_cron';

    public const TAB_HISTORY = 'history';

    public const TAB_FORWARDED = 'forwarded';

    public const TAB_CALLBACK = 'callback';

    public const TAB_API_LOGS = 'api_logs';

    /**
     * @param  array<string, mixed>  $cacheContext
     */
    public function respond(Request $request, string $tab, Closure $render, array $cacheContext = []): Response
    {
        if (!$this->isEnabled() || !$request->ajax()) {
            return response($render());
        }

        $version = $this->versionFor($tab);
        $cacheKey = $this->cacheKey($tab, $version, array_merge($request->query(), $cacheContext));
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return response($cached)->header('X-Voice-Tab-Cache', 'HIT');
        }

        $html = $render();
        Cache::put($cacheKey, $html, $this->ttlFor($tab));

        return response($html)->header('X-Voice-Tab-Cache', 'MISS');
    }

    public function forget(string $tab): void
    {
        Cache::increment($this->versionKey($tab));
    }

    public function forgetMany(array $tabs): void
    {
        foreach (array_unique($tabs) as $tab) {
            $this->forget((string) $tab);
        }
    }

    public function forgetCallLogTabs(): void
    {
        $this->forgetMany([
            self::TAB_PLACED,
            self::TAB_HISTORY,
            self::TAB_FORWARDED,
            self::TAB_CALLBACK,
            self::TAB_BULK,
            self::TAB_BULK_DETAILS,
        ]);
    }

    private function isEnabled(): bool
    {
        return (bool) config('services.omnidimension.tab_cache_enabled', true);
    }

    private function versionFor(string $tab): int
    {
        return (int) Cache::get($this->versionKey($tab), 0);
    }

    private function versionKey(string $tab): string
    {
        return 'voice_call_tab:v:' . $tab;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function cacheKey(string $tab, int $version, array $query): string
    {
        ksort($query);

        return 'voice_call_tab:' . $tab . ':v' . $version . ':' . md5(json_encode($query) ?: '');
    }

    private function ttlFor(string $tab): int
    {
        $map = [
            self::TAB_PLACED => 'cache_tab_placed_ttl',
            self::TAB_BULK => 'cache_tab_bulk_ttl',
            self::TAB_BULK_DETAILS => 'cache_tab_bulk_details_ttl',
            self::TAB_WHATSAPP_FOLLOWUP => 'cache_tab_whatsapp_followup_ttl',
            self::TAB_VOICE_CRON => 'cache_tab_voice_cron_ttl',
            self::TAB_HISTORY => 'cache_tab_call_logs_ttl',
            self::TAB_FORWARDED => 'cache_tab_call_logs_ttl',
            self::TAB_CALLBACK => 'cache_tab_call_logs_ttl',
            self::TAB_API_LOGS => 'cache_tab_api_logs_ttl',
        ];

        $configKey = $map[$tab] ?? 'cache_tab_call_logs_ttl';

        return (int) config('services.omnidimension.' . $configKey, 45);
    }
}
