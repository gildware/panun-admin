<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Unknown intent + clear domain → summary handler (not generic fallback).
 */
class MobileAppAiDomainFallbackHandler
{
    /**
     * @return MobileAppAiIntentClassification|null
     */
    public function inferIntent(string $text, string $domain): ?MobileAppAiIntentClassification
    {
        if ($summary = MobileAppAiSummaryIntentDetector::detect($text)) {
            return new MobileAppAiIntentClassification(
                $summary['intent'],
                max(0.72, $summary['confidence']),
                'domain_fallback',
                ['mode' => $summary['mode'], 'domain' => $domain]
            );
        }

        return match ($domain) {
            MobileAppAiIntentDomainCatalog::CART => new MobileAppAiIntentClassification(
                MobileAppAiIntentCatalog::CART_SUMMARY,
                0.75,
                'domain_fallback',
                ['mode' => 'items', 'domain' => $domain]
            ),
            MobileAppAiIntentDomainCatalog::BOOKING => new MobileAppAiIntentClassification(
                MobileAppAiIntentCatalog::BOOKING_SUMMARY,
                0.75,
                'domain_fallback',
                ['mode' => MobileAppAiBookingMessageDetector::looksLikeBookingCountQuery($text) ? 'count' : 'list', 'domain' => $domain]
            ),
            MobileAppAiIntentDomainCatalog::BIDDING => new MobileAppAiIntentClassification(
                MobileAppAiIntentCatalog::BIDDING_SUMMARY,
                0.75,
                'domain_fallback',
                ['mode' => 'list', 'domain' => $domain]
            ),
            MobileAppAiIntentDomainCatalog::ACCOUNT => new MobileAppAiIntentClassification(
                MobileAppAiIntentCatalog::ADDRESS_SUMMARY,
                0.75,
                'domain_fallback',
                ['mode' => 'list', 'domain' => $domain]
            ),
            MobileAppAiIntentDomainCatalog::SUPPORT => new MobileAppAiIntentClassification(
                MobileAppAiIntentCatalog::HUMAN_SUPPORT,
                0.72,
                'domain_fallback',
                ['domain' => $domain]
            ),
            MobileAppAiIntentDomainCatalog::CATALOG => new MobileAppAiIntentClassification(
                MobileAppAiIntentCatalog::SERVICE_TRIAGE,
                0.72,
                'domain_fallback',
                ['service_query' => MobileAppAiServiceQueryNormalizer::normalize($text), 'domain' => $domain]
            ),
            default => null,
        };
    }
}
