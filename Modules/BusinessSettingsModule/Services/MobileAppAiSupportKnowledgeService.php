<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\WhatsAppModule\Services\WhatsAppAiSupportKnowledgeService;

/**
 * Merged WhatsApp + mobile app support knowledge for in-app AI.
 */
class MobileAppAiSupportKnowledgeService
{
    public function __construct(
        protected WhatsAppAiSupportKnowledgeService $whatsappKnowledge,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function search(string $query): array
    {
        $base = $this->whatsappKnowledge->search($query);
        $q = mb_strtolower(trim($query));

        $mobileFaqs = config('mobile_app_ai_support.faqs', []);
        $mobileTrouble = config('mobile_app_ai_support.troubleshooting', []);
        $mobileTips = config('mobile_app_ai_support.general_tips', []);

        $extraFaqs = [];
        if (is_array($mobileFaqs) && $q !== '') {
            foreach ($mobileFaqs as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $fq = mb_strtolower((string) ($row['q'] ?? ''));
                $fa = mb_strtolower((string) ($row['a'] ?? ''));
                if ($fq === '' && $fa === '') {
                    continue;
                }
                if (str_contains($fq, $q) || str_contains($fa, $q) || $this->tokenOverlap($q, $fq.' '.$fa) >= 1) {
                    $extraFaqs[] = [
                        'question' => (string) ($row['q'] ?? ''),
                        'answer' => (string) ($row['a'] ?? ''),
                    ];
                }
            }
        }

        $extraTrouble = [];
        if (is_array($mobileTrouble) && $q !== '') {
            foreach ($mobileTrouble as $needle => $pack) {
                if (! is_string($needle) || $needle === '' || ! is_array($pack)) {
                    continue;
                }
                if (! str_contains($q, mb_strtolower($needle))) {
                    continue;
                }
                $steps = $pack['steps'] ?? [];
                $extraTrouble[] = [
                    'topic' => (string) ($pack['title'] ?? $needle),
                    'steps' => is_array($steps) ? array_values(array_filter(array_map('strval', $steps))) : [],
                ];
            }
        }

        $faqs = array_merge($base['faqs'] ?? [], $extraFaqs);
        $troubleshooting = array_merge($base['troubleshooting'] ?? [], $extraTrouble);
        $tips = array_merge($base['general_tips'] ?? [], is_array($mobileTips) ? $mobileTips : []);

        return [
            'ok' => true,
            'query' => $query,
            'faqs' => array_slice($faqs, 0, 8),
            'troubleshooting' => array_slice($troubleshooting, 0, 4),
            'general_tips' => array_slice(array_values(array_unique($tips)), 0, 8),
            'provider_onboarding_hint' => $base['provider_onboarding_hint'] ?? null,
        ];
    }

    private function tokenOverlap(string $query, string $haystack): int
    {
        $qt = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hit = 0;
        foreach ($qt as $t) {
            if (mb_strlen($t) < 3) {
                continue;
            }
            if (str_contains($haystack, $t)) {
                $hit++;
            }
        }

        return $hit;
    }
}
