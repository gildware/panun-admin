<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\UserManagement\Entities\User;

/**
 * AI-primary routing: understand → entity resolution gates → execute or clarify.
 */
class MobileAppAiIntentRoutingPolicy
{
    public function __construct(
        protected MobileAppAiIntentClassifier $classifier,
        protected MobileAppAiDomainFallbackHandler $domainFallback,
    ) {}

    public function classifier(): MobileAppAiIntentClassifier
    {
        return $this->classifier;
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    public function buildTurnPlan(User $user, string $text, array $draft, ?MobileAppAiConversation $conversation = null): MobileAppAiTurnPlan
    {
        $executeAt = (float) config('mobile_app_ai_production.confidence.execute', 0.85);
        $clarifyMin = (float) config('mobile_app_ai_production.confidence.clarify_min', 0.60);

        // Every message: AI understanding (via classifier → UnderstandingService).
        $primary = $this->classifier->classify($user, $text, $draft, $conversation);
        $aiOnly = (bool) config('mobile_app_ai_intent_classification.ai_only_understanding', true);

        if (! $aiOnly && $primary->intent === MobileAppAiIntentCatalog::UNKNOWN
            && MobileAppAiAmbiguityDetector::isVagueTargetPhrase($text)) {
            $domain = MobileAppAiDomainDetector::detect($text) ?? '';
            $clarify = MobileAppAiAmbiguityDetector::clarificationFor($text, $domain !== '' ? $domain : null);

            return new MobileAppAiTurnPlan(
                $primary,
                [],
                MobileAppAiTurnPlan::ROUTE_CLARIFY,
                $clarify,
                $domain
            );
        }

        if (! $aiOnly && $primary->intent === MobileAppAiIntentCatalog::UNKNOWN) {
            $domain = MobileAppAiDomainDetector::detect($text);
            if ($domain !== null) {
                $inferred = $this->domainFallback->inferIntent($text, $domain);
                if ($inferred !== null) {
                    $primary = $inferred;
                }
            }
        }

        $intents = $this->detectMultiIntents($user, $text, $draft, $primary);
        $ordered = $this->orderIntents($intents);
        $head = $ordered[0] ?? $primary;

        $domain = MobileAppAiIntentDomainCatalog::domainForIntent($head->intent);
        if ($head->entityString('domain') !== '') {
            $domain = $head->entityString('domain');
        }

        $clarification = $this->clarificationForLowConfidence($head, $text);

        if ($head->intent === MobileAppAiIntentCatalog::UNKNOWN || $head->confidence < $clarifyMin) {
            $clarify = $clarification !== ''
                ? $clarification
                : MobileAppAiCustomerSnapshotService::softClarifyFallback();

            return new MobileAppAiTurnPlan(
                $head,
                $ordered,
                MobileAppAiTurnPlan::ROUTE_CLARIFY,
                $clarify,
                $domain
            );
        }

        if ($head->confidence < $executeAt && $clarification !== '') {
            return new MobileAppAiTurnPlan(
                $head,
                $ordered,
                MobileAppAiTurnPlan::ROUTE_CLARIFY,
                $clarification,
                $domain
            );
        }

        return new MobileAppAiTurnPlan(
            $head,
            $ordered,
            MobileAppAiTurnPlan::ROUTE_EXECUTE,
            '',
            $domain
        );
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return list<MobileAppAiIntentClassification>
     */
    private function detectMultiIntents(
        User $user,
        string $text,
        array $draft,
        MobileAppAiIntentClassification $primary,
    ): array {
        if ((bool) config('mobile_app_ai_intent_classification.ai_only_understanding', true)
            || ! config('mobile_app_ai_production.multi_intent.enabled', true)) {
            return [$primary];
        }

        $max = (int) config('mobile_app_ai_production.multi_intent.max_per_message', 3);
        $found = [$primary];
        $t = mb_strtolower($text);

        if (preg_match('/\b(and|aur|also|plus|then)\b/iu', $t)) {
            $summaryDetect = MobileAppAiSummaryIntentDetector::detect($text);
            $wantsCart = MobileAppAiCartRequestParser::looksLikeViewCart($text)
                || ($summaryDetect !== null && $summaryDetect['intent'] === MobileAppAiIntentCatalog::CART_SUMMARY);
            if (! in_array($primary->intent, [MobileAppAiIntentCatalog::CART_SUMMARY, MobileAppAiIntentCatalog::VIEW_CART], true)
                && $wantsCart) {
                $found[] = new MobileAppAiIntentClassification(
                    MobileAppAiIntentCatalog::CART_SUMMARY,
                    0.88,
                    'multi_intent',
                    ['mode' => 'items']
                );
            }
            if ($primary->intent !== MobileAppAiIntentCatalog::CART_REMOVE_ITEM
                && MobileAppAiCartRequestParser::parse($text) !== null) {
                $parsed = MobileAppAiCartRequestParser::parse($text);
                $found[] = new MobileAppAiIntentClassification(
                    MobileAppAiIntentCatalog::CART_REMOVE_ITEM,
                    0.85,
                    'multi_intent',
                    ['remove_target' => (string) ($parsed['target'] ?? '')]
                );
            }
        }

        $unique = [];
        $seen = [];
        foreach ($found as $c) {
            if (isset($seen[$c->intent])) {
                continue;
            }
            $seen[$c->intent] = true;
            $unique[] = $c;
            if (count($unique) >= $max) {
                break;
            }
        }

        return $unique;
    }

    /**
     * @param  list<MobileAppAiIntentClassification>  $intents
     * @return list<MobileAppAiIntentClassification>
     */
    private function orderIntents(array $intents): array
    {
        $readonly = [
            MobileAppAiIntentCatalog::VIEW_CART,
            MobileAppAiIntentCatalog::CART_SUMMARY,
            MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY,
            MobileAppAiIntentCatalog::PRICING_QUERY,
            MobileAppAiIntentCatalog::BOOKING_STATUS,
            MobileAppAiIntentCatalog::BOOKING_SUMMARY,
            MobileAppAiIntentCatalog::BIDDING_SUMMARY,
            MobileAppAiIntentCatalog::ADDRESS_SUMMARY,
            MobileAppAiIntentCatalog::SERVICE_DETAILS,
        ];

        usort($intents, static function (MobileAppAiIntentClassification $a, MobileAppAiIntentClassification $b) use ($readonly): int {
            $aRead = in_array($a->intent, $readonly, true) ? 0 : 1;
            $bRead = in_array($b->intent, $readonly, true) ? 0 : 1;
            if ($aRead !== $bRead) {
                return $aRead <=> $bRead;
            }

            return $b->confidence <=> $a->confidence;
        });

        return $intents;
    }

    private function clarificationForLowConfidence(MobileAppAiIntentClassification $c, string $text): string
    {
        if ($c->confidence >= (float) config('mobile_app_ai_production.confidence.clarify_min', 0.60)) {
            return '';
        }

        if (MobileAppAiAmbiguityDetector::isVagueTargetPhrase($text)) {
            return MobileAppAiAmbiguityDetector::clarificationFor($text, $c->entityString('domain'));
        }

        return match ($c->intent) {
            MobileAppAiIntentCatalog::CART_REMOVE_ITEM,
            MobileAppAiIntentCatalog::CART_RESCHEDULE => 'Which cart item should I change? Name the service (e.g. AC repair) or ask to see your cart.',
            MobileAppAiIntentCatalog::BOOKING_CANCEL => 'Which booking should I cancel? Share your booking reference from the app.',
            MobileAppAiIntentCatalog::BOOKING_START,
            MobileAppAiIntentCatalog::SERVICE_TRIAGE => 'What service do you need — plumbing, AC, electrical, or something else?',
            default => '',
        };
    }
}
