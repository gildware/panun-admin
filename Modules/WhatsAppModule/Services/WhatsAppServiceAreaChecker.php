<?php

namespace Modules\WhatsAppModule\Services;

/**
 * Heuristic check that a free-text address is plausibly inside the Kashmir (J&K) service area.
 * Uses zone high-confidence match first; otherwise keyword signals from config.
 */
final class WhatsAppServiceAreaChecker
{
    /**
     * @param  array<string, mixed>|null  $zoneMatch  Result of {@see WhatsAppZoneAddressMatcher::match()}
     * @return array{in_service_area: bool, reason: string}
     */
    public function assess(string $rawAddress, ?array $zoneMatch = null): array
    {
        $trimmed = trim($rawAddress);
        if ($trimmed === '') {
            return ['in_service_area' => true, 'reason' => 'empty_address_skipped'];
        }

        if ($zoneMatch !== null && ($zoneMatch['confidence'] ?? '') === 'high' && !empty($zoneMatch['zone_id'])) {
            return ['in_service_area' => true, 'reason' => 'zone_high_confidence'];
        }

        $norm = mb_strtolower($trimmed);

        $inside = config('whatsapp_ai_support.service_area_inside_keywords', []);
        $outside = config('whatsapp_ai_support.service_area_outside_keywords', []);
        if (!is_array($inside)) {
            $inside = [];
        }
        if (!is_array($outside)) {
            $outside = [];
        }

        $hasInside = false;
        foreach ($inside as $k) {
            $k = trim((string) $k);
            if ($k === '') {
                continue;
            }
            if (str_contains($norm, mb_strtolower($k))) {
                $hasInside = true;
                break;
            }
        }

        $hasOutside = false;
        foreach ($outside as $k) {
            $k = trim((string) $k);
            if ($k === '') {
                continue;
            }
            if (str_contains($norm, mb_strtolower($k))) {
                $hasOutside = true;
                break;
            }
        }

        if ($hasOutside && !$hasInside) {
            return ['in_service_area' => false, 'reason' => 'outside_keywords_without_local_signal'];
        }

        return ['in_service_area' => true, 'reason' => 'no_outside_conflict'];
    }
}
