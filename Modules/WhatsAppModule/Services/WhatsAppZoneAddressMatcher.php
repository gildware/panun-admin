<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Log;
use Modules\ZoneManagement\Entities\Zone;

/**
 * Matches customer address text to an active zone using zone name + zone description (area lists).
 * Conservative: only returns high confidence when one zone clearly wins.
 */
final class WhatsAppZoneAddressMatcher
{
    /**
     * @return array{
     *   confidence: 'high'|'low'|'none',
     *   zone_id: string|null,
     *   zone_name: string|null,
     *   district_for_booking_row: string|null,
     *   score: float,
     *   note: string,
     *   top_candidates?: list<array{zone_name: string, score: float}>
     * }
     */
    public function match(string $rawAddress): array
    {
        $norm = $this->normalize($rawAddress);
        if ($norm === '') {
            return [
                'confidence' => 'none',
                'zone_id' => null,
                'zone_name' => null,
                'district_for_booking_row' => null,
                'score' => 0.0,
                'note' => 'Empty address — cannot match a zone.',
            ];
        }

        try {
            $zones = Zone::query()
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'description']);
        } catch (\Throwable $e) {
            Log::debug('WhatsAppZoneAddressMatcher: zones query failed', ['message' => $e->getMessage()]);

            return [
                'confidence' => 'none',
                'zone_id' => null,
                'zone_name' => null,
                'district_for_booking_row' => null,
                'score' => 0.0,
                'note' => 'Zones could not be loaded.',
            ];
        }

        $scored = [];
        foreach ($zones as $z) {
            $s = $this->scoreZone($norm, $z);
            if ($s > 0.0001) {
                $scored[] = ['zone' => $z, 'score' => $s];
            }
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        if ($scored === []) {
            return [
                'confidence' => 'none',
                'zone_id' => null,
                'zone_name' => null,
                'district_for_booking_row' => null,
                'score' => 0.0,
                'note' => 'No zone name or area from zone descriptions matched this address.',
            ];
        }

        $minHigh = (float) config('whatsappmodule.ai_zone_match_min_score_high', 10.0);
        $ambiguityRatio = (float) config('whatsappmodule.ai_zone_match_ambiguity_ratio', 0.88);
        if ($ambiguityRatio <= 0 || $ambiguityRatio > 1) {
            $ambiguityRatio = 0.88;
        }

        $top = $scored[0];
        $second = $scored[1] ?? null;
        $ambiguous = $second !== null && $second['score'] > 0 && ($second['score'] >= $top['score'] * $ambiguityRatio);

        if ($ambiguous || $top['score'] < $minHigh) {
            $candidates = [];
            foreach (array_slice($scored, 0, 3) as $row) {
                $candidates[] = [
                    'zone_name' => (string) ($row['zone']->name ?? ''),
                    'score' => round($row['score'], 2),
                ];
            }

            return [
                'confidence' => 'low',
                'zone_id' => null,
                'zone_name' => null,
                'district_for_booking_row' => null,
                'score' => round($top['score'], 2),
                'note' => $ambiguous
                    ? 'Several zones partially matched — do not set zone; staff will pick in admin.'
                    : 'Match is weak — leave zone and district empty; do not ask the customer.',
                'top_candidates' => $candidates,
            ];
        }

        /** @var Zone $z */
        $z = $top['zone'];
        $zName = trim((string) ($z->name ?? ''));

        return [
            'confidence' => 'high',
            'zone_id' => (string) $z->id,
            'zone_name' => $zName,
            'district_for_booking_row' => $zName,
            'score' => round($top['score'], 2),
            'note' => 'Strong match from zone name and/or areas listed in the zone description.',
        ];
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/[^\p{L}\p{N}\s,]/u', ' ', $s) ?? $s;

        return trim(preg_replace('/\s+/', ' ', $s) ?? $s);
    }

    private function scoreZone(string $addressNorm, Zone $z): float
    {
        $score = 0.0;
        $name = mb_strtolower(trim((string) ($z->name ?? '')));
        if ($name !== '') {
            if (str_contains($addressNorm, $name)) {
                $score += 14.0;
            }
            foreach (preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $w) {
                if (mb_strlen($w) < 3) {
                    continue;
                }
                if (str_contains($addressNorm, $w)) {
                    $score += 5.0;
                }
            }
        }

        $desc = trim((string) ($z->description ?? ''));
        foreach ($this->areaChunksFromDescription($desc) as $chunk) {
            $c = mb_strtolower(trim($chunk));
            if (mb_strlen($c) < 4) {
                continue;
            }
            if ($this->isGenericChunk($c)) {
                continue;
            }
            if (str_contains($addressNorm, $c)) {
                $score += 8.0;
            }
        }

        return $score;
    }

    /**
     * @return list<string>
     */
    private function areaChunksFromDescription(string $description): array
    {
        if ($description === '') {
            return [];
        }
        $parts = preg_split('/[\r\n,;|]+/', $description) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $out[] = $p;
            }
        }

        return $out;
    }

    private function isGenericChunk(string $chunkLower): bool
    {
        $generic = ['near', 'road', 'street', 'st.', 'lane', 'nagar', 'colony', 'sector', 'house', 'house no', 'floor', 'flat', 'plot'];
        foreach ($generic as $g) {
            if ($chunkLower === $g) {
                return true;
            }
        }

        return false;
    }
}
