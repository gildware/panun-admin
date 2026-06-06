<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\Log;
use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Services\WhatsAppGeminiSupportClient;

/**
 * Primary language-understanding layer — AI interprets; backend executes.
 */
class MobileAppAiUnderstandingService
{
    public function __construct(
        protected MobileAppAiRuntimeResolver $runtime,
        protected WhatsAppGeminiSupportClient $gemini,
        protected MobileAppAiCustomerStateService $customerState,
        protected MobileAppAiCartService $cartService,
        protected MobileAppAiCustomerAgentService $customerAgent,
    ) {}

    /**
     * @param  array<string, mixed>  $draft
     */
    public function understand(
        User $user,
        string $text,
        array $draft = [],
        ?MobileAppAiConversation $conversation = null,
    ): MobileAppAiIntentClassification {
        $text = MobileAppAiInputNormalizer::forMatching($text);

        $state = $this->customerState->build($user, $conversation);
        $context = $this->buildContext($user, $draft, $state, $conversation);
        $aiOnly = $this->aiOnlyUnderstanding();

        $ai = null;
        $useAi = (bool) config('mobile_app_ai_intent_classification.ai_primary', true)
            && (bool) config('mobile_app_ai_intent_classification.use_gemini', true)
            && $this->runtime->enabled();

        if ($useAi) {
            $ai = $this->understandWithAi($text, $context, $state);
            Log::info('mobile_app_ai.understanding', [
                'user_id' => $user->id,
                'ai_only' => $aiOnly,
                'ai_called' => true,
                'ai_ok' => $ai !== null,
                'ai_intent' => $ai?->intent,
                'ai_confidence' => $ai !== null ? round($ai->confidence, 3) : null,
                'source' => $ai?->source,
                'message_preview' => mb_substr($text, 0, 120),
            ]);
            if ($ai === null) {
                Log::warning('mobile_app_ai.gemini_understanding_unavailable', [
                    'user_id' => $user->id,
                    'ai_only' => $aiOnly,
                    'hint' => 'Check GEMINI_API_KEY, Generative Language API, and Google Cloud billing (403 PERMISSION_DENIED = project billing denied).',
                ]);
            }
        }

        if ($aiOnly) {
            $chosen = $this->resolveAiOnly($ai);
            $chosen = $this->applyIdleWizardRemap($chosen, $context);
            $chosen = $this->applyWizardContextGuard($chosen, $context);
        } else {
            $rules = $this->understandWithRules($user, $text, $context);
            $chosen = $this->mergeUnderstandingAiFirst($ai, $rules);
            $chosen = $this->applyCartBookingGuard($user, $text, $chosen, $context);
            $chosen = $this->applyWizardContextGuard($chosen, $context, $text);
        }

        $min = (float) config('mobile_app_ai_intent_classification.min_confidence', 0.45);
        if ($chosen->confidence < $min) {
            return new MobileAppAiIntentClassification(
                MobileAppAiIntentCatalog::UNKNOWN,
                $chosen->confidence,
                $chosen->source,
                $chosen->entities
            );
        }

        return $chosen;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function understandWithRules(User $user, string $text, array $context): MobileAppAiIntentClassification
    {
        $candidates = [];

        if (MobileAppAiConversationalResponder::isGreeting($text)) {
            $candidates[] = [MobileAppAiIntentCatalog::GREETING, 0.98, ['domain' => 'support']];
        }
        if (MobileAppAiConversationalResponder::isThanks($text)) {
            $candidates[] = [MobileAppAiIntentCatalog::THANKS, 0.98, ['domain' => 'support']];
        }

        $convState = is_array($context['conversation_state'] ?? null) ? $context['conversation_state'] : [];
        $activeService = trim((string) ($convState['active_service'] ?? ''));

        if (MobileAppAiFrustrationDetector::looksLikeFrustration($text)) {
            if ($activeService !== '' || MobileAppAiBookingMessageDetector::isActiveBookingWizardStep((string) ($context['wizard_step'] ?? ''))) {
                $candidates[] = [
                    MobileAppAiIntentCatalog::BOOKING_START,
                    0.9,
                    ['domain' => 'booking', 'service_query' => $activeService, 'recovery' => 'frustration'],
                ];
            } elseif ((int) ($context['cart_count'] ?? 0) > 0) {
                $candidates[] = [
                    MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY,
                    0.86,
                    ['domain' => 'cart', 'recovery' => 'frustration'],
                ];
            }
        }

        if ($summary = MobileAppAiSummaryIntentDetector::detect($text)) {
            $candidates[] = [
                $summary['intent'],
                $summary['confidence'],
                ['mode' => $summary['mode'], 'domain' => MobileAppAiIntentDomainCatalog::domainForIntent($summary['intent'])],
            ];
        }

        if (MobileAppAiCartRequestParser::looksLikeViewCart($text)) {
            $candidates[] = [MobileAppAiIntentCatalog::CART_SUMMARY, 0.9, ['mode' => 'items', 'domain' => 'cart']];
        }
        if (MobileAppAiCartScheduleReply::looksLikeCartScheduleQuery($text)) {
            $scheduleConf = (int) ($context['cart_count'] ?? 0) > 0 ? 0.96 : 0.92;
            $candidates[] = [MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY, $scheduleConf, ['domain' => 'cart']];
        }
        if (MobileAppAiPricingReply::looksLikePricingQuery($text)) {
            $candidates[] = [MobileAppAiIntentCatalog::PRICING_QUERY, 0.88, ['domain' => 'pricing']];
        }

        $parsed = MobileAppAiCartRequestParser::parse($text);
        if ($parsed !== null) {
            $op = (string) ($parsed['op'] ?? '');
            match ($op) {
                'view' => $candidates[] = [MobileAppAiIntentCatalog::CART_SUMMARY, 0.93, ['mode' => 'items', 'domain' => 'cart']],
                'clear_all' => $candidates[] = [MobileAppAiIntentCatalog::CART_CLEAR, 0.9, ['domain' => 'cart']],
                'remove' => $candidates[] = [
                    MobileAppAiIntentCatalog::CART_REMOVE_ITEM,
                    $this->cartRemoveRuleConfidence(
                        (string) ($parsed['target'] ?? ''),
                        $context,
                        (string) ($parsed['keep_target'] ?? ''),
                    ),
                    array_filter([
                        'remove_target' => (string) ($parsed['target'] ?? ''),
                        'keep_target' => (string) ($parsed['keep_target'] ?? ''),
                        'domain' => 'cart',
                    ], static fn (string $v): bool => $v !== ''),
                ],
                'keep_only' => $candidates[] = [
                    MobileAppAiIntentCatalog::CART_REMOVE_ITEM,
                    0.88,
                    ['keep_target' => (string) ($parsed['target'] ?? ''), 'domain' => 'cart'],
                ],
                'reschedule' => $candidates[] = [
                    MobileAppAiIntentCatalog::CART_RESCHEDULE,
                    0.9,
                    ['schedule_text' => (string) ($parsed['schedule_text'] ?? ''), 'domain' => 'cart'],
                ],
                default => null,
            };
        }

        if (preg_match('/\b(change|update|reschedule|move)\b.*\b(?:date|time|schedule|visit)\b/iu', $text)
            || preg_match('/\bkal\s+wala\b/iu', $text)) {
            $candidates[] = [MobileAppAiIntentCatalog::CART_RESCHEDULE, 0.82, ['domain' => 'cart']];
        }

        if (preg_match('/\b(?:hata|hatao|hata do|nikal|remove)\b/iu', $text)
            && preg_match('/\b(ac|a\/c|inverter|cart)\b/iu', $text)) {
            $candidates[] = [MobileAppAiIntentCatalog::CART_REMOVE_ITEM, 0.8, ['domain' => 'cart']];
        }

        if (preg_match('/\b(coupon|promo|voucher)\b/iu', $text)) {
            if (MobileAppAiCouponService::wantsRemoveCoupon($text)) {
                $candidates[] = [MobileAppAiIntentCatalog::COUPON_REMOVE, 0.88, ['domain' => 'cart']];
            } elseif (preg_match('/\b(list|show|my)\s+coupons?\b/iu', $text)) {
                $candidates[] = [MobileAppAiIntentCatalog::COUPON_LIST, 0.85, ['domain' => 'cart']];
            } else {
                $code = MobileAppAiCouponService::extractCouponCode($text);
                $candidates[] = [
                    MobileAppAiIntentCatalog::COUPON_APPLY,
                    $code !== '' ? 0.86 : 0.7,
                    ['coupon_code' => $code, 'domain' => 'cart'],
                ];
            }
        }

        if (MobileAppAiBiddingService::looksLikeAcceptBid($text)) {
            $candidates[] = [MobileAppAiIntentCatalog::BIDDING_ACCEPT, 0.9, array_merge($this->extractProviderHint($text), ['domain' => 'bidding'])];
        } elseif (MobileAppAiBiddingService::looksLikeDenyBid($text)) {
            $candidates[] = [MobileAppAiIntentCatalog::BIDDING_DECLINE, 0.9, array_merge($this->extractProviderHint($text), ['domain' => 'bidding'])];
        } elseif (MobileAppAiBiddingService::looksLikeBiddingIntent($text)) {
            if (preg_match('/\b(create|post|new)\b.*\b(bid|bidding)\b/iu', $text)) {
                $candidates[] = [MobileAppAiIntentCatalog::BIDDING_CREATE, 0.85, ['domain' => 'bidding']];
            } else {
                $candidates[] = [MobileAppAiIntentCatalog::BIDDING_LIST, 0.82, ['domain' => 'bidding']];
            }
        }

        if (MobileAppAiBookingManageService::looksLikeCancelBooking($text)) {
            $candidates[] = [MobileAppAiIntentCatalog::BOOKING_CANCEL, 0.95, ['domain' => 'booking']];
        }
        if (MobileAppAiBookingManageService::looksLikeRebook($text)) {
            $candidates[] = [MobileAppAiIntentCatalog::BOOKING_REBOOK, 0.88, ['domain' => 'booking']];
        }
        if (preg_match('/\b(pk[a-z0-9]{6,})\b/i', $text)) {
            $candidates[] = [MobileAppAiIntentCatalog::BOOKING_STATUS, 0.92, ['domain' => 'booking']];
        }

        if (MobileAppAiServiceDetailsService::looksLikeServiceDetailsIntent($text)) {
            $candidates[] = [
                MobileAppAiIntentCatalog::SERVICE_DETAILS,
                0.8,
                ['service_query' => MobileAppAiServiceQueryNormalizer::normalize($text), 'domain' => 'catalog'],
            ];
        }

        $qty = $this->customerAgent->parseQuantityChangeForUser($user, $text);
        if ($qty !== null) {
            $candidates[] = [MobileAppAiIntentCatalog::CART_QTY_CHANGE, 0.87, array_merge($qty, ['domain' => 'cart'])];
        }

        if (preg_match('/\b(human|agent|call\s+support|talk\s+to)\b/iu', $text)) {
            $candidates[] = [MobileAppAiIntentCatalog::HUMAN_SUPPORT, 0.85, ['domain' => 'support']];
        }
        if (MobileAppAiBookingMessageDetector::looksLikeAppTroubleshoot($text)) {
            $candidates[] = [MobileAppAiIntentCatalog::APP_TROUBLESHOOT, 0.8, ['domain' => 'support']];
        }

        if (MobileAppAiServiceQueryNormalizer::looksLikeProblemOrService($text)
            && ! MobileAppAiServiceQueryNormalizer::isGenericBookingPhrase($text)) {
            $candidates[] = [
                MobileAppAiIntentCatalog::SERVICE_TRIAGE,
                0.78,
                ['service_query' => MobileAppAiServiceQueryNormalizer::normalize($text), 'domain' => 'catalog'],
            ];
        }

        if (MobileAppAiBookingMessageDetector::looksLikeServiceBookingRequest($text)) {
            $candidates[] = [MobileAppAiIntentCatalog::BOOKING_START, 0.94, ['domain' => 'booking']];
        } elseif (MobileAppAiBookingMessageDetector::hasBookingIntent($text)
            || MobileAppAiServiceQueryNormalizer::isGenericBookingPhrase($text)
            || MobileAppAiBookingMessageDetector::looksLikeTechnicianRequest($text)) {
            $candidates[] = [MobileAppAiIntentCatalog::BOOKING_START, 0.88, ['domain' => 'booking']];
        }

        if ($candidates === []) {
            return new MobileAppAiIntentClassification(MobileAppAiIntentCatalog::UNKNOWN, 0.2, 'rules', []);
        }

        usort($candidates, static fn (array $a, array $b): int => $b[1] <=> $a[1]);
        [$intent, $confidence, $entities] = $candidates[0];

        return new MobileAppAiIntentClassification(
            MobileAppAiIntentCatalog::isValid($intent) ? $intent : MobileAppAiIntentCatalog::UNKNOWN,
            (float) $confidence,
            'rules',
            is_array($entities) ? $entities : []
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $state
     */
    private function understandWithAi(string $text, array $context, array $state): ?MobileAppAiIntentClassification
    {
        $intents = implode(', ', MobileAppAiIntentCatalog::all());
        $convState = is_array($state['conversation_state'] ?? null) ? $state['conversation_state'] : [];

        $system = <<<PROMPT
You are the language-understanding layer for Panun Kaergar (home-services app).
Customers write in English, Hinglish, Roman Urdu, with typos and incomplete sentences.

Return ONLY one JSON object, no markdown:
{"intent":"<one of: {$intents}>","confidence":0.0-1.0,"domain":"cart|booking|bidding|account|support|pricing|catalog","entities":{}}

Entity keys when relevant:
- mode: count|list|latest|items (for summaries)
- remove_target, keep_target: service name strings
- remove, keep: arrays of service name strings (also map to remove_target/keep_target)
- schedule_text: natural language date/time change
- coupon_code, service_query, provider_hint
- cart_line_ids: array of ids from catalog
- cart_filter: visit_before_now|visit_after_now|no_schedule

Understanding rules (you classify ONLY — never execute actions):
- Cart contents / count / total / dikhao / mera cart → cart_summary
- Cart visit dates / schedule of each service → cart_schedule_query (incl. unka kya schedule date hai, visit date kya hai, kab hai visit)
- Cart price / total / charges → pricing_query or cart_summary
- Remove/keep cart items (incl. Hinglish hata do) → cart_remove_item with entities
- Reschedule cart visit (kal wala next week) → cart_reschedule
- How many bookings / my bookings → booking_summary
- Cancel booking → booking_cancel
- Book service / service chahiye / service karni hai / nahi service hi chahiye mujhay → booking_start (not unknown)
- Short service name answers (AC ki, AC ka, plumber, tap) when wizard_step=service_query → booking_wizard_continue with service_query entity
- Short follow-ups (bola na, haan, yes) during triage/booking → booking_start using active_service from conversation_state
- Need technician / want technician → booking_start
- Home problem (leak, AC not cooling, tap leak) → service_triage
- Bids → bidding_summary or bidding_list
- Addresses → address_summary
- App/payment issues → app_troubleshoot
- Human support → human_support
- Greeting / thanks → greeting or thanks
- If unsure → unknown

booking_wizard_continue is ONLY valid when wizard_step is one of: service_query, service_triage, service_confirm, service, variation, schedule, address, provider, ready.
When wizard_step is idle or empty, never use booking_wizard_continue — use booking_start or service_triage instead.

When wizard_step is service_query (we asked "what service?"):
- Short answers naming a trade (AC ki, AC ka, plumber, geyser) → booking_wizard_continue with entities.service_query set

When wizard_step is active otherwise:
- Customer is continuing the wizard → booking_wizard_continue
- Unless they clearly switch topic to cart, bookings list, pricing, or human support

Use conversation_state, wizard_choices, and recent_messages for short follow-ups (e.g. bola na, haan, service karni hai after AC triage).
Never invent cart contents, booking counts, or prices — only classify intent.
PROMPT;

        $stateJson = json_encode([
            'wizard_step' => $context['wizard_step'] ?? 'idle',
            'wizard_choices' => $context['wizard_choices'] ?? [],
            'cart' => $context['cart_summary'] ?? '',
            'bookings_count' => count((array) ($state['bookings'] ?? [])),
            'conversation_state' => $convState,
            'recent_messages' => $context['recent_messages'] ?? [],
            'pending_confirmation' => $state['pending_confirmation'] ?? null,
        ], JSON_UNESCAPED_UNICODE);

        $userPrompt = "Server now: ".($context['server_now'] ?? now()->format('Y-m-d H:i:s'))."\nCustomer state: {$stateJson}\nCustomer message: {$text}";

        try {
            $raw = $this->gemini->generatePlainText($system, $userPrompt);
            if ($raw === null) {
                return null;
            }
            $json = $this->extractJson($raw);
            if ($json === null) {
                return null;
            }

            $intent = (string) ($json['intent'] ?? MobileAppAiIntentCatalog::UNKNOWN);
            if (! MobileAppAiIntentCatalog::isValid($intent)) {
                $intent = MobileAppAiIntentCatalog::UNKNOWN;
            }

            $entities = $this->normalizeEntitiesFromAi($json);

            return new MobileAppAiIntentClassification(
                $intent,
                min(1.0, max(0.0, (float) ($json['confidence'] ?? 0.5))),
                'ai',
                $entities
            );
        } catch (\Throwable $e) {
            Log::warning('mobile_app_ai.understanding_failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    private function normalizeEntitiesFromAi(array $json): array
    {
        $raw = is_array($json['entities'] ?? null) ? $json['entities'] : $json;
        $entities = [];

        $domain = (string) ($json['domain'] ?? ($raw['domain'] ?? ''));
        if ($domain !== '') {
            $entities['domain'] = $domain;
        }

        foreach (['mode', 'remove_target', 'keep_target', 'schedule_text', 'coupon_code', 'service_query', 'provider_hint', 'cart_filter'] as $key) {
            if (isset($raw[$key]) && is_string($raw[$key]) && trim($raw[$key]) !== '') {
                $entities[$key] = trim($raw[$key]);
            }
        }

        if (isset($json['remove_target']) && is_string($json['remove_target'])) {
            $entities['remove_target'] = trim($json['remove_target']);
        }
        if (isset($json['keep_target']) && is_string($json['keep_target'])) {
            $entities['keep_target'] = trim($json['keep_target']);
        }

        $removeList = $this->entityList($raw, 'remove');
        $keepList = $this->entityList($raw, 'keep');
        if ($removeList !== []) {
            $entities['remove'] = $removeList;
            if (($entities['remove_target'] ?? '') === '') {
                $entities['remove_target'] = $removeList[0];
            }
        }
        if ($keepList !== []) {
            $entities['keep'] = $keepList;
            if (($entities['keep_target'] ?? '') === '') {
                $entities['keep_target'] = $keepList[0];
            }
        }

        if (isset($raw['cart_line_ids']) || isset($json['cart_line_ids'])) {
            $entities['cart_line_ids'] = $this->normalizeIdList($raw['cart_line_ids'] ?? $json['cart_line_ids'] ?? []);
        }

        $filter = (string) ($raw['cart_filter'] ?? $json['cart_filter'] ?? '');
        $normalizedFilter = $this->normalizeCartFilter($filter);
        if ($normalizedFilter !== '') {
            $entities['cart_filter'] = $normalizedFilter;
        }

        return $entities;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return list<string>
     */
    private function entityList(array $raw, string $key): array
    {
        $v = $raw[$key] ?? [];
        if (! is_array($v)) {
            return is_string($v) && trim($v) !== '' ? [trim($v)] : [];
        }

        $out = [];
        foreach ($v as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return array_values(array_unique($out));
    }

    private function aiOnlyUnderstanding(): bool
    {
        return (bool) config('mobile_app_ai_intent_classification.ai_only_understanding', true);
    }

    private function resolveAiOnly(?MobileAppAiIntentClassification $ai): MobileAppAiIntentClassification
    {
        if ($ai !== null) {
            return $ai;
        }

        return new MobileAppAiIntentClassification(
            MobileAppAiIntentCatalog::UNKNOWN,
            0.0,
            'ai_unavailable',
            []
        );
    }

    /**
     * Gemini sometimes returns booking_wizard_continue on idle — remap to booking_start.
     *
     * @param  array<string, mixed>  $context
     */
    private function applyIdleWizardRemap(
        MobileAppAiIntentClassification $chosen,
        array $context,
    ): MobileAppAiIntentClassification {
        $step = trim((string) ($context['wizard_step'] ?? ''));
        if ($step !== '' && $step !== 'idle'
            && MobileAppAiBookingMessageDetector::isActiveBookingWizardStep($step)) {
            return $chosen;
        }

        if ($chosen->intent !== MobileAppAiIntentCatalog::BOOKING_WIZARD_CONTINUE) {
            return $chosen;
        }

        return new MobileAppAiIntentClassification(
            MobileAppAiIntentCatalog::BOOKING_START,
            max($chosen->confidence, 0.88),
            'ai+idle_remap',
            array_merge($chosen->entities, ['domain' => 'booking', 'remapped_from' => 'booking_wizard_continue'])
        );
    }

    /**
     * Legacy merge path when ai_only_understanding is disabled.
     */
    private function mergeUnderstandingAiFirst(
        ?MobileAppAiIntentClassification $ai,
        MobileAppAiIntentClassification $rules,
    ): MobileAppAiIntentClassification {
        if ($ai === null) {
            return new MobileAppAiIntentClassification(
                $rules->intent,
                $rules->confidence,
                'rules_fallback',
                $rules->entities
            );
        }

        $min = (float) config('mobile_app_ai_intent_classification.min_confidence', 0.45);

        if ($ai->intent !== MobileAppAiIntentCatalog::UNKNOWN) {
            $rulesStrong = (float) config('mobile_app_ai_intent_classification.rule_wins_over_gemini', 0.92);
            if ($rules->confidence >= $rulesStrong
                && $rules->intent !== $ai->intent
                && in_array($rules->intent, [
                    MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY,
                    MobileAppAiIntentCatalog::CART_SUMMARY,
                    MobileAppAiIntentCatalog::BOOKING_SUMMARY,
                ], true)) {
                return new MobileAppAiIntentClassification(
                    $rules->intent,
                    $rules->confidence,
                    'rules_safety',
                    array_merge($ai->entities, $rules->entities)
                );
            }

            return new MobileAppAiIntentClassification(
                $ai->intent,
                $ai->confidence >= $min ? $ai->confidence : max($ai->confidence, $rules->confidence),
                $rules->intent === $ai->intent ? 'ai+rules' : 'ai',
                array_merge($ai->entities, $rules->entities)
            );
        }

        if ($rules->intent !== MobileAppAiIntentCatalog::UNKNOWN && $rules->confidence >= $min) {
            return new MobileAppAiIntentClassification(
                $rules->intent,
                $rules->confidence,
                'rules_fallback',
                array_merge($ai->entities, $rules->entities)
            );
        }

        return $ai;
    }

    /**
     * Structural wizard routing only — no message-regex reclassification.
     *
     * @param  array<string, mixed>  $context
     */
    private function applyWizardContextGuard(
        MobileAppAiIntentClassification $chosen,
        array $context,
        ?string $text = null,
    ): MobileAppAiIntentClassification {
        $step = (string) ($context['wizard_step'] ?? 'idle');
        if (! MobileAppAiBookingMessageDetector::isActiveBookingWizardStep($step)) {
            return $chosen;
        }

        if ($this->aiOnlyUnderstanding()) {
            if (in_array($chosen->intent, [
                MobileAppAiIntentCatalog::BOOKING_WIZARD_CONTINUE,
                MobileAppAiIntentCatalog::SERVICE_TRIAGE,
                MobileAppAiIntentCatalog::BOOKING_START,
                MobileAppAiIntentCatalog::VIEW_CART,
                MobileAppAiIntentCatalog::CART_SUMMARY,
                MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY,
                MobileAppAiIntentCatalog::PRICING_QUERY,
                MobileAppAiIntentCatalog::BOOKING_SUMMARY,
                MobileAppAiIntentCatalog::BOOKING_STATUS,
                MobileAppAiIntentCatalog::HUMAN_SUPPORT,
            ], true)) {
                return $chosen;
            }

            return new MobileAppAiIntentClassification(
                MobileAppAiIntentCatalog::BOOKING_WIZARD_CONTINUE,
                max($chosen->confidence, 0.85),
                'wizard_step',
                array_merge($chosen->entities, ['wizard_step' => $step, 'domain' => MobileAppAiIntentDomainCatalog::BOOKING])
            );
        }

        $text = trim((string) $text);
        if ($text !== '' && $this->looksLikeCartEscape($text)) {
            return $chosen;
        }

        if ($text !== ''
            && (MobileAppAiBookingMessageDetector::looksLikeServiceBookingRequest($text)
                || $chosen->intent === MobileAppAiIntentCatalog::BOOKING_START)) {
            return new MobileAppAiIntentClassification(
                MobileAppAiIntentCatalog::BOOKING_START,
                max($chosen->confidence, 0.9),
                $chosen->usedAi() ? 'ai+booking_escape' : 'booking_escape',
                ['domain' => 'booking', 'escaped_wizard' => $step]
            );
        }

        if (in_array($chosen->intent, [
            MobileAppAiIntentCatalog::BOOKING_WIZARD_CONTINUE,
            MobileAppAiIntentCatalog::SERVICE_TRIAGE,
        ], true)) {
            return $chosen;
        }

        if ($text !== ''
            && (MobileAppAiFrustrationDetector::looksLikeFrustration($text)
                || MobileAppAiCartScheduleReply::looksLikeCartScheduleQuery($text))) {
            return $chosen;
        }

        return new MobileAppAiIntentClassification(
            MobileAppAiIntentCatalog::BOOKING_WIZARD_CONTINUE,
            max($chosen->confidence, 0.85),
            $chosen->usedAi() ? 'ai+wizard' : 'wizard_guard',
            array_merge($chosen->entities, ['wizard_step' => $step, 'domain' => MobileAppAiIntentDomainCatalog::BOOKING])
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function applyCartBookingGuard(
        User $user,
        string $text,
        MobileAppAiIntentClassification $chosen,
        array $context,
    ): MobileAppAiIntentClassification {
        if (MobileAppAiCartScheduleReply::looksLikeCartScheduleQuery($text)) {
            return new MobileAppAiIntentClassification(
                MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY,
                max($chosen->confidence, 0.92),
                'schedule_guard',
                array_merge($chosen->entities, ['domain' => 'cart'])
            );
        }

        if ($chosen->intent !== MobileAppAiIntentCatalog::BOOKING_START) {
            return $chosen;
        }

        $ruleCart = $this->understandWithRules($user, $text, $context);
        $cartCount = (int) ($context['cart_count'] ?? 0);
        $threshold = (float) config('mobile_app_ai_intent_classification.cart_blocks_booking_threshold', 0.55);

        if ($cartCount > 0 && MobileAppAiIntentCatalog::isCartFamily($ruleCart->intent)
            && $ruleCart->confidence >= $threshold) {
            return $ruleCart;
        }

        if (MobileAppAiCartScheduleReply::looksLikeCartScheduleQuery($text)
            || MobileAppAiCartRequestParser::looksLikeViewCart($text)) {
            return new MobileAppAiIntentClassification(
                MobileAppAiCartScheduleReply::looksLikeCartScheduleQuery($text)
                    ? MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY
                    : MobileAppAiIntentCatalog::CART_SUMMARY,
                0.88,
                'cart_guard',
                ['domain' => 'cart']
            );
        }

        return $chosen;
    }

    /**
     * @param  array<string, mixed>  $draft
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function buildContext(User $user, array $draft, array $state, ?MobileAppAiConversation $conversation = null): array
    {
        $cart = $this->cartService->cartSummaryForUser($user);
        $names = [];
        $lines = [];
        foreach ($cart['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $name = (string) ($item['service_name'] ?? '');
            if ($name !== '') {
                $names[] = $name;
            }
            $id = (string) ($item['cart_line_id'] ?? '');
            if ($id === '') {
                continue;
            }
            $visit = (string) ($item['schedule_label'] ?? '');
            if ($visit === '' && ($item['service_schedule'] ?? '') !== '') {
                $visit = (string) $item['service_schedule'];
            }
            if ($visit === '') {
                $visit = 'no visit set';
            }
            $lines[] = ['id' => $id, 'name' => $name, 'visit' => $visit];
        }

        $catalog = $lines === []
            ? 'Cart: empty'
            : "Cart lines (id | service | visit):\n".implode("\n", array_map(
                static fn (array $l): string => '- '.$l['id'].' | '.$l['name'].' | '.$l['visit'],
                array_slice($lines, 0, 12)
            ));

        $choices = is_array($draft['choices'] ?? null) ? $draft['choices'] : [];

        return [
            'cart_count' => (int) ($cart['item_count'] ?? 0),
            'cart_total' => (float) ($cart['cart_total'] ?? 0),
            'cart_item_names' => $names,
            'cart_lines' => $lines,
            'cart_summary' => $catalog,
            'server_now' => now()->format('Y-m-d H:i:s'),
            'wizard_step' => (string) ($draft['step'] ?? 'idle'),
            'wizard_choices' => array_filter([
                'service_query' => (string) ($choices['service_query'] ?? ''),
                'service_name' => (string) ($choices['service_name'] ?? ''),
                'issue_description' => (string) ($choices['issue_description'] ?? ''),
            ], static fn (string $v): bool => trim($v) !== ''),
            'conversation_state' => $state['conversation_state'] ?? [],
            'recent_messages' => $this->recentChatTurns($conversation),
        ];
    }

    /**
     * @return list<array{role: string, body: string}>
     */
    private function recentChatTurns(?MobileAppAiConversation $conversation): array
    {
        if ($conversation === null) {
            return [];
        }

        $limit = max(2, min(8, (int) config('mobile_app_ai_production.understanding.recent_messages', 6)));
        $rows = $conversation->messages()
            ->where('source', \Modules\BusinessSettingsModule\Entities\MobileAppAiMessage::SOURCE_MOBILE_APP)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['role', 'body']);

        return $rows->reverse()->map(static fn ($m): array => [
            'role' => (string) $m->role,
            'body' => mb_substr(trim((string) $m->body), 0, 240),
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function cartRemoveRuleConfidence(string $target, array $context, string $keepTarget = ''): float
    {
        if ($target !== '' && $keepTarget !== '') {
            return 0.9;
        }

        if ($this->cartRemoveEntitiesMatchCart(
            array_filter(['remove_target' => $target, 'keep_target' => $keepTarget], static fn (string $v): bool => $v !== ''),
            $context
        )) {
            return 0.9;
        }

        return ($target !== '' || $keepTarget !== '') ? 0.72 : 0.75;
    }

    /**
     * @param  array<string, mixed>  $entities
     * @param  array<string, mixed>  $context
     */
    private function cartRemoveEntitiesMatchCart(array $entities, array $context): bool
    {
        $remove = trim((string) ($entities['remove_target'] ?? ''));
        $keep = trim((string) ($entities['keep_target'] ?? ''));

        if ($remove !== '' && $this->removeTargetMatchesCart($remove, $context)) {
            return true;
        }

        if ($keep !== '' && $this->removeTargetMatchesCart($keep, $context)) {
            return true;
        }

        return $remove === '' && $keep === '';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function removeTargetMatchesCart(string $target, array $context): bool
    {
        $needle = mb_strtolower(trim($target));
        if ($needle === '') {
            return true;
        }

        foreach ((array) ($context['cart_item_names'] ?? []) as $name) {
            $hay = mb_strtolower((string) $name);
            if (str_contains($hay, $needle) || str_contains($needle, $hay)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  mixed  $raw
     * @return list<string>
     */
    private function normalizeIdList(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $id) {
            if (is_string($id) && trim($id) !== '') {
                $ids[] = trim($id);
            }
        }

        return array_values(array_unique($ids));
    }

    private function normalizeCartFilter(string $filter): string
    {
        $filter = trim($filter);
        if ($filter === 'none' || $filter === '') {
            return '';
        }

        return in_array($filter, ['visit_before_now', 'visit_after_now', 'no_schedule'], true) ? $filter : '';
    }

    private function looksLikeCartEscape(string $text): bool
    {
        return MobileAppAiCartRequestParser::looksLikeViewCart($text)
            || MobileAppAiCartScheduleReply::looksLikeCartScheduleQuery($text)
            || MobileAppAiPricingReply::looksLikePricingQuery($text)
            || MobileAppAiCartRequestParser::parse($text) !== null;
    }

    /**
     * @return array<string, string>
     */
    private function extractProviderHint(string $text): array
    {
        if (preg_match('/\b(?:from|by)\s+(.+)$/iu', $text, $m)) {
            return ['provider_hint' => trim((string) ($m[1] ?? ''))];
        }

        return [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractJson(string $raw): ?array
    {
        $raw = trim($raw);
        if (preg_match('/\{[\s\S]*\}/', $raw, $m)) {
            $raw = $m[0];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
