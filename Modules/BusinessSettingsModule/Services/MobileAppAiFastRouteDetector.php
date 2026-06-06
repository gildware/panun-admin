<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Very high-confidence shortcuts — no Gemini (low latency).
 */
final class MobileAppAiFastRouteDetector
{
    /**
     * @return MobileAppAiIntentClassification|null
     */
    public static function detect(string $text): ?MobileAppAiIntentClassification
    {
        $t = mb_strtolower(trim(MobileAppAiInputNormalizer::forMatching($text)));
        if ($t === '') {
            return null;
        }

        if (MobileAppAiConversationalResponder::isGreeting($t)) {
            return new MobileAppAiIntentClassification(MobileAppAiIntentCatalog::GREETING, 0.99, 'fast_route', []);
        }
        if (MobileAppAiConversationalResponder::isThanks($t)) {
            return new MobileAppAiIntentClassification(MobileAppAiIntentCatalog::THANKS, 0.99, 'fast_route', []);
        }

        if (in_array($t, ['cart', 'my cart', 'the cart', 'basket', 'my basket'], true)) {
            return new MobileAppAiIntentClassification(
                MobileAppAiIntentCatalog::CART_SUMMARY,
                0.99,
                'fast_route',
                ['mode' => 'items']
            );
        }

        if (in_array($t, ['my bookings', 'bookings', 'booking status', 'my booking'], true)) {
            return new MobileAppAiIntentClassification(
                MobileAppAiIntentCatalog::BOOKING_SUMMARY,
                0.99,
                'fast_route',
                ['mode' => 'list']
            );
        }

        if (in_array($t, ['help', 'support', 'help me', 'customer support'], true)) {
            return new MobileAppAiIntentClassification(MobileAppAiIntentCatalog::HUMAN_SUPPORT, 0.97, 'fast_route', []);
        }

        if ($summary = MobileAppAiSummaryIntentDetector::detect($text)) {
            if ($summary['confidence'] >= 0.92) {
                return new MobileAppAiIntentClassification(
                    $summary['intent'],
                    $summary['confidence'],
                    'fast_route',
                    ['mode' => $summary['mode']]
                );
            }
        }

        return null;
    }
}
