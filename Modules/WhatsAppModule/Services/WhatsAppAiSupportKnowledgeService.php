<?php

namespace Modules\WhatsAppModule\Services;

/**
 * Config-driven FAQs and troubleshooting for the search_support_knowledge tool.
 */
class WhatsAppAiSupportKnowledgeService
{
    /**
     * @return array<string, mixed>
     */
    public function search(string $query): array
    {
        $q = mb_strtolower(trim($query));
        $faqs = config('whatsapp_ai_support.faqs', []);
        $troubleshooting = config('whatsapp_ai_support.troubleshooting', []);
        $tips = config('whatsapp_ai_support.general_tips', []);

        $matchedFaqs = [];
        if (is_array($faqs) && $q !== '') {
            foreach ($faqs as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $fq = mb_strtolower((string) ($row['q'] ?? ''));
                $fa = mb_strtolower((string) ($row['a'] ?? ''));
                if ($fq === '' && $fa === '') {
                    continue;
                }
                if (str_contains($fq, $q) || str_contains($fa, $q)
                    || $this->tokenOverlap($q, $fq.' '.$fa) >= 1) {
                    $matchedFaqs[] = [
                        'question' => (string) ($row['q'] ?? ''),
                        'answer' => (string) ($row['a'] ?? ''),
                    ];
                }
            }
        }

        $matchedTroubleshooting = [];
        if (is_array($troubleshooting) && $q !== '') {
            foreach ($troubleshooting as $needle => $pack) {
                if (!is_string($needle) || $needle === '' || !is_array($pack)) {
                    continue;
                }
                if (!str_contains($q, mb_strtolower($needle))) {
                    continue;
                }
                $title = (string) ($pack['title'] ?? $needle);
                $steps = $pack['steps'] ?? [];
                $steps = is_array($steps) ? array_values(array_filter(array_map('strval', $steps))) : [];
                $matchedTroubleshooting[] = [
                    'topic' => $title,
                    'steps' => $steps,
                ];
            }
        }

        $providerUrl = trim((string) config('whatsapp_ai_support.provider_onboarding_form_url', ''));
        $providerHint = $providerUrl !== ''
            ? 'Provider self-service link (share only when customer wants to join as provider): '.$providerUrl
            : null;

        return [
            'ok' => true,
            'query' => $query,
            'faqs' => array_slice($matchedFaqs, 0, 5),
            'troubleshooting' => array_slice($matchedTroubleshooting, 0, 3),
            'general_tips' => is_array($tips) ? array_slice($tips, 0, 6) : [],
            'provider_onboarding_hint' => $providerHint,
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
