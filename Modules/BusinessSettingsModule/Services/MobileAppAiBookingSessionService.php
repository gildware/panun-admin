<?php

namespace Modules\BusinessSettingsModule\Services;

use Carbon\Carbon;
use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Services\WhatsAppAiPromptBuilder;

/**
 * Server-side booking wizard (same steps as the app). Customer only sees names and numbers — never UUIDs.
 */
class MobileAppAiBookingSessionService
{
    public function __construct(
        protected MobileAppAiCatalogSearchService $catalog,
        protected MobileAppAiBookingFlowService $flow,
        protected MobileAppAiCartService $cart,
        protected MobileAppAiServiceTriageService $serviceTriage,
        protected MobileAppAiCartManageService $cartManage,
        protected MobileAppAiCustomerAgentService $customerAgent,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function handle(User $user, array $args): array
    {
        $conversation = MobileAppAiConversation::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['last_message_at' => now()]
        );
        $draft = $this->loadDraft($conversation);
        $step = (string) ($draft['step'] ?? '');
        $args = $this->coerceBookingArgsForStep($step, $args);
        $action = strtolower(trim((string) ($args['action'] ?? '')));

        return match ($action) {
            'start', 'search' => $this->actionStart($user, $conversation, $args),
            'apply', 'submit', 'complete' => $this->actionApply($user, $conversation, $args),
            'confirm_service' => $this->actionConfirmService($user, $conversation),
            'show_service_options' => $this->actionShowServiceOptions($user, $conversation),
            'pick', 'choose' => $this->actionPick($user, $conversation, $args),
            'time', 'schedule' => $this->actionTime($conversation, $args),
            'confirm', 'finalize' => $this->actionConfirm($user, $conversation),
            'cancel' => $this->actionCancel($conversation),
            'status' => $this->actionStatus($conversation),
            'proceed_booking', 'book_now' => $this->actionProceedFromTriage($user, $conversation),
            'clarify_step' => $this->actionClarifyStep($conversation),
            'triage_issue' => $this->actionTriageIssue($user, $conversation, $args),
            'more_triage_tips' => $this->serviceTriage->moreTips($conversation),
            'confirm_cart_action' => $this->wrapCartManageResult($this->cartManage->confirmPending($user, $conversation)),
            'cancel_cart_action' => $this->wrapCartManageResult($this->cartManage->cancelPending($conversation)),
            'pick_cart_remove' => $this->wrapCartManageResult($this->cartManage->beginRemoveLine(
                $user,
                $conversation,
                (string) ($args['choice'] ?? ''),
                (string) ($args['message'] ?? '')
            )),
            'confirm_coupon_action' => $this->wrapAgentResult($this->customerAgent->confirmCoupon($user, $conversation)),
            'cancel_coupon_action' => $this->wrapAgentResult($this->customerAgent->cancelPending($conversation)),
            'confirm_bid_action' => $this->wrapAgentResult($this->customerAgent->confirmBid($user, $conversation)),
            'cancel_bid_action' => $this->wrapAgentResult($this->customerAgent->cancelPending($conversation)),
            'confirm_booking_cancel_action' => $this->wrapAgentResult($this->customerAgent->confirmBookingCancel($user, $conversation)),
            'cancel_booking_cancel_action' => $this->wrapAgentResult($this->customerAgent->cancelPending($conversation)),
            'confirm_cart_qty_action' => $this->wrapAgentResult($this->customerAgent->confirmQty($user, $conversation)),
            'cancel_cart_qty_action' => $this->wrapAgentResult($this->customerAgent->cancelPending($conversation)),
            default => $this->wrapError('Unknown action. Use: start, apply, pick, time, confirm, cancel, status.'),
        };
    }

    /**
     * Advance the booking wizard as far as possible from one message (all details at once).
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function actionApply(User $user, MobileAppAiConversation $conversation, array $args): array
    {
        $parsed = $this->resolveApplyFields($args);
        $this->persistParsedContextInDraft($conversation, $parsed);
        $serviceQuery = $parsed['service'];
        if ($serviceQuery === '' || $this->isGenericServicePhrase($serviceQuery)) {
            return $this->actionApplyPartial($user, $conversation, $parsed, $args);
        }

        $searchText = trim((string) ($parsed['message'] ?? '')) !== '' ? trim((string) $parsed['message']) : $serviceQuery;
        $searchResult = $this->searchServicesWithFallback($user, $searchText, 12);
        if (! empty($searchResult['unsupported'])) {
            return $this->wrap(
                MobileAppAiServiceIntentResolver::unsupportedMessage($searchText, (string) $searchResult['unsupported']),
                'service_query',
                'Customer asked for a service we do not offer.'
            );
        }
        if (! ($searchResult['ok'] ?? false)) {
            return $this->wrapError('I could not search services right now. Please try again in a moment.');
        }

        $options = [];
        foreach ($searchResult['selectable_options'] ?? [] as $row) {
            $options[] = [
                'pick' => (int) ($row['option'] ?? 0),
                'service_id' => (string) ($row['service_id'] ?? ''),
                'category_id' => (string) ($row['category_id'] ?? ''),
                'sub_category_id' => (string) ($row['sub_category_id'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
            ];
        }

        if ($options === []) {
            $draft = $this->loadDraft($conversation);
            $draft['step'] = 'service_query';
            $draft['choices']['last_search'] = $searchText;
            $this->saveDraft($conversation, $draft);
            $intent = MobileAppAiServiceIntentResolver::resolve($searchText);

            return $this->wrap(
                MobileAppAiServiceIntentResolver::noCatalogMatchMessage(
                    $searchText,
                    $intent['catalog_query'],
                    $intent['trade_label']
                ),
                'service_query',
                'Call manage_app_booking action=search with a clearer query.'
            );
        }

        $pickedService = MobileAppAiCatalogServiceMatcher::pickBest($options, $searchText);
        if ($pickedService !== null) {
            $draft = $this->emptyDraft();
            $draft['step'] = 'service';
            $draft['choices']['search_query'] = $searchText;
            $draft['choices']['last_customer_message'] = $searchText;
            $draft['options']['service'] = $options;
            $this->saveDraft($conversation, $draft);

            return $this->proposeServiceConfirm($user, $conversation, $draft, $pickedService, $searchText);
        }

        $intent = MobileAppAiServiceIntentResolver::resolve($searchText);
        $displayOptions = count($options) > 3
            ? MobileAppAiCatalogServiceMatcher::rankTop($options, $searchText, $intent, 3)
            : $options;
        $draft = $this->emptyDraft();
        $draft['step'] = 'service';
        $draft['choices']['search_query'] = $searchText;
        $draft['choices']['last_customer_message'] = $searchText;
        $draft['options']['service'] = $displayOptions;
        $this->saveDraft($conversation, $draft);

        return $this->wrap(
            'Pick the service that fits best (tap a card or type the number).',
            'service',
            'Customer must pick a number; then call action=pick.'
        );
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{service: string, when: string, address: string, variation: string, provider: string, asap: bool}
     */
    private function resolveApplyFields(array $args): array
    {
        $service = trim((string) ($args['service'] ?? $args['query'] ?? ''));
        $when = trim((string) ($args['when'] ?? $args['datetime'] ?? $args['schedule'] ?? ''));
        $address = trim((string) ($args['address'] ?? ''));
        $variation = trim((string) ($args['variation'] ?? $args['type'] ?? ''));
        $provider = trim((string) ($args['provider'] ?? ''));
        $asap = filter_var($args['asap'] ?? false, FILTER_VALIDATE_BOOL);
        $message = trim((string) ($args['message'] ?? $args['user_message'] ?? ''));

        if ($message !== '') {
            $extracted = $this->extractFreeformBookingFields($message);
            if ($service === '') {
                $service = $extracted['service'];
            }
            if ($when === '') {
                $when = $extracted['when'];
            }
            if ($address === '') {
                $address = $extracted['address'];
            }
            $asap = $asap || $extracted['asap'];
        }

        if ($when !== '' && ! $asap) {
            $lower = mb_strtolower($when);
            $asap = str_contains($lower, 'asap') || str_contains($lower, 'as soon') || str_contains($lower, 'earliest');
        }

        return [
            'service' => $service,
            'when' => $asap ? '' : $when,
            'address' => $address,
            'variation' => $variation,
            'provider' => $provider,
            'asap' => $asap,
            'message' => $message,
        ];
    }

    /**
     * Remember schedule/address from the customer's message so we do not re-ask.
     *
     * @param  array<string, mixed>  $parsed
     */
    private function persistParsedContextInDraft(MobileAppAiConversation $conversation, array $parsed): void
    {
        $draft = $this->loadDraft($conversation);
        if (($draft['step'] ?? 'idle') === 'idle') {
            $draft = $this->emptyDraft();
        }

        $message = trim((string) ($parsed['message'] ?? ''));
        if ($message !== '') {
            $draft['choices']['last_customer_message'] = $message;
        }

        if (empty($draft['choices']['schedule'])) {
            $when = trim((string) ($parsed['when'] ?? ''));
            $asap = ($parsed['asap'] ?? false) === true;
            if ($asap || $when !== '') {
                $draft = $this->applyScheduleToDraft($draft, $when, $asap);
            }
        }

        $address = trim((string) ($parsed['address'] ?? ''));
        if ($address !== '' && empty($draft['choices']['service_address_id'])) {
            $draft['choices']['address_hint'] = $address;
        }

        $this->saveDraft($conversation, $draft);
    }

    private function enrichDraftFromLastCustomerMessage(MobileAppAiConversation $conversation): void
    {
        $draft = $this->loadDraft($conversation);
        $message = trim((string) ($draft['choices']['last_customer_message'] ?? ''));
        if ($message === '') {
            return;
        }

        $this->persistParsedContextInDraft($conversation, $this->resolveApplyFields(['message' => $message]));
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function applyScheduleToDraft(array $draft, string $when, bool $asap): array
    {
        $timeArgs = $asap ? ['schedule_type' => 'asap'] : ['service_schedule' => $when, 'when' => $when];
        $resolved = $this->flow->resolveSchedule($timeArgs);
        if (! ($resolved['ok'] ?? false)) {
            if ($when !== '') {
                $draft['choices']['schedule_hint'] = $when;
            }

            return $draft;
        }

        $draft['choices']['schedule'] = $resolved['schedule'];
        $draft['choices']['schedule_type'] = $resolved['schedule_type'] ?? 'custom';
        $draft['choices']['schedule_label'] = ($resolved['schedule_type'] ?? '') === 'asap'
            ? 'ASAP (earliest available)'
            : Carbon::parse($resolved['schedule'])->format('j M Y, g:i A');

        return $draft;
    }

    /**
     * Customer named a time/place but not a specific service (e.g. "book a service tomorrow at 5pm at rajbagh").
     *
     * @param  array<string, mixed>  $parsed
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function actionApplyPartial(User $user, MobileAppAiConversation $conversation, array $parsed, array $args): array
    {
        $draft = $this->emptyDraft();
        $draft['step'] = 'service_query';
        $contextLines = [];

        $when = trim((string) ($parsed['when'] ?? ''));
        if (($parsed['asap'] ?? false) === true) {
            $timeArgs = ['schedule_type' => 'asap'];
        } elseif ($when !== '') {
            $timeArgs = ['service_schedule' => $when];
        } else {
            $timeArgs = [];
        }

        if ($timeArgs !== []) {
            $resolved = $this->flow->resolveSchedule($timeArgs);
            if ($resolved['ok'] ?? false) {
                $draft['choices']['schedule'] = $resolved['schedule'];
                $draft['choices']['schedule_type'] = $resolved['schedule_type'] ?? 'custom';
                $draft['choices']['schedule_label'] = ($resolved['schedule_type'] ?? '') === 'asap'
                    ? 'ASAP (earliest available)'
                    : Carbon::parse($resolved['schedule'])->format('j M Y, g:i A');
                $contextLines[] = '• When: '.$draft['choices']['schedule_label'];
            } else {
                $draft['choices']['schedule_hint'] = $when !== '' ? $when : 'ASAP';
                $contextLines[] = '• When: '.$draft['choices']['schedule_hint'].' (confirm time after you pick the service)';
            }
        }

        $addressHint = trim((string) ($parsed['address'] ?? ''));
        if ($addressHint !== '') {
            $addrResult = $this->catalog->listCustomerAddresses($user);
            $addrOptions = [];
            foreach ($addrResult['selectable_options'] ?? [] as $row) {
                $addrOptions[] = [
                    'pick' => (int) ($row['option'] ?? 0),
                    'service_address_id' => (int) ($row['service_address_id'] ?? 0),
                    'zone_id' => (string) ($row['zone_id'] ?? ''),
                    'address_label' => (string) ($row['address_label'] ?? ''),
                    'address' => (string) ($row['address'] ?? ''),
                ];
            }
            $picked = $this->resolvePick($addrOptions, $addressHint, 'address');
            if ($picked !== null) {
                $draft['choices']['service_address_id'] = $picked['service_address_id'];
                $draft['choices']['zone_id'] = $picked['zone_id'];
                $draft['choices']['address_label'] = $picked['address_label'] ?? '';
                $draft['choices']['address_line'] = $picked['address'] ?? '';
                $line = $draft['choices']['address_label'] !== ''
                    ? $draft['choices']['address_label'].' — '.$draft['choices']['address_line']
                    : $draft['choices']['address_line'];
                $contextLines[] = '• Where: '.$line;
            } else {
                $draft['choices']['address_hint'] = $addressHint;
                $contextLines[] = '• Where: '.$addressHint.' (pick your saved address below after you choose the service)';
            }
        }

        $draft['choices']['partial_from_message'] = trim((string) ($args['message'] ?? ''));
        $this->saveDraft($conversation, $draft);

        $intro = $contextLines !== []
            ? 'Noted: '.implode(' ', $contextLines).' What service do you need?'
            : MobileAppAiStepCopy::bookingStart();

        return $this->wrap(
            $intro,
            'service_query',
            'Call manage_app_booking action=search with the service type they name next. Schedule/address are already stored in the wizard when present.'
        );
    }

    private function isGenericServicePhrase(string $service): bool
    {
        return MobileAppAiServiceQueryNormalizer::isGenericBookingPhrase($service);
    }

    /**
     * @return array{service: string, when: string, address: string, asap: bool}
     */
    private function extractFreeformBookingFields(string $message): array
    {
        $text = trim($message);
        $asap = (bool) preg_match('/\b(asap|as soon as possible|earliest|jaldi)\b/i', $text);

        $when = $this->extractWhenFromMessage($text);
        $address = $this->extractLocationFromMessage($text);

        $service = $text;
        foreach ($this->extractSchedulePhraseCandidates($text) as $phrase) {
            $service = str_ireplace($phrase, '', $service);
        }
        if ($when !== '') {
            $service = str_ireplace($when, '', $service);
        }
        if ($address !== '') {
            $service = preg_replace('/\bat\s+'.preg_quote($address, '/').'\s*$/iu', '', $service) ?? $service;
        }
        $service = trim((string) preg_replace(
            '/\b(i want to|i need to|please|help me|book a|book an|book my|book|booking|a service|the service|service|for|to)\b/i',
            ' ',
            $service
        ));
        $service = trim((string) preg_replace('/\s+/', ' ', $service));
        if ($this->isGenericServicePhrase($service)) {
            $service = '';
        }
        if ($service !== '') {
            $service = MobileAppAiServiceQueryNormalizer::normalize($service);
        } elseif (MobileAppAiServiceQueryNormalizer::looksLikeServiceRequest($message)) {
            $service = MobileAppAiServiceQueryNormalizer::normalize($message);
        }

        return [
            'service' => $service,
            'when' => $when,
            'address' => $address,
            'asap' => $asap,
        ];
    }

    private function extractWhenFromMessage(string $text): string
    {
        foreach ($this->extractSchedulePhraseCandidates($text) as $phrase) {
            $parsed = MobileAppAiSchedulePhraseParser::parse($phrase);
            if ($parsed['ok'] ?? false) {
                return $phrase;
            }
        }

        $patterns = [
            '/\bfor\s+((?:today|tomorrow|day after tomorrow)\s+at\s+\d{1,2}(?::\d{2})?\s*(?:am|pm)?)/i',
            '/\b((?:today|tomorrow|day after tomorrow)\s+at\s+\d{1,2}(?::\d{2})?\s*(?:am|pm)?)/i',
            '/\b(?:on\s+)?((?:today|tomorrow|day after tomorrow)\s+at\s+\d{1,2}(?::\d{2})?\s*(?:am|pm)?)/i',
            '/\bat\s+(\d{1,2}(?::\d{2})?\s*(?:am|pm))\b/i',
            '/\bfor\s+((?:today|tomorrow|day after tomorrow))/i',
            '/\b(today|tomorrow|day after tomorrow)\s+(morning|afternoon|evening|night)\b/i',
            '/\b(today|tomorrow|day after tomorrow)\b/i',
            '/\b(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]?\d{2,4})(?:\s+at\s+)?(\d{1,2}(?::\d{2})?\s*(?:am|pm)?)?/i',
            '/\b(monday|tuesday|wednesday|thursday|friday|saturday|sunday)(?:\s+at\s+\d{1,2}(?::\d{2})?\s*(?:am|pm)?)?/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $chunk = trim((string) ($m[1] ?? $m[0] ?? ''));
                if (isset($m[2]) && trim((string) $m[2]) !== '') {
                    $chunk .= ' at '.trim((string) $m[2]);
                }
                if (MobileAppAiSchedulePhraseParser::looksLikeSchedulePhrase($chunk)) {
                    return $chunk;
                }
            }
        }

        return '';
    }

    /**
     * @return list<string>
     */
    private function extractSchedulePhraseCandidates(string $text): array
    {
        $candidates = [];
        $patterns = [
            '/\b((?:aaj|kal|parson|today|tomorrow|tonight)(?:\s+(?:subah|sham|dopahar|raat|morning|evening|afternoon|night))?(?:\s+ko)?\s*\d{1,2}(?::\d{2})?\s*(?:baje|bje|bajey|bajhe)?(?:\s*(?:am|pm))?)/iu',
            '/\b((?:subah|sham|dopahar|raat|morning|evening|afternoon|night)(?:\s+ko)?\s*\d{1,2}\s*(?:baje|bje|bajey|bajhe)?)/iu',
            '/\b(\d{1,2}\s*(?:baje|bje|bajey|bajhe)(?:\s*(?:am|pm))?)/iu',
            '/\b(asap|as soon|earliest|jaldi|abhi|turant)\b/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $candidates[] = trim((string) ($m[1] ?? ''));
            }
        }

        if (MobileAppAiSchedulePhraseParser::looksLikeSchedulePhrase($text)) {
            $candidates[] = $text;
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function extractLocationFromMessage(string $text): string
    {
        if (preg_match('/\bat\s+([a-zA-Z][a-zA-Z0-9\s\-]{1,80})\s*$/iu', $text, $m)) {
            $loc = trim((string) ($m[1] ?? ''));
            if ($loc !== '' && ! $this->looksLikeTimePhrase($loc)) {
                return $loc;
            }
        }

        if (! preg_match_all('/\bat\s+([a-zA-Z][a-zA-Z0-9\-]{1,40})\b/iu', $text, $matches)) {
            return '';
        }

        $tokens = $matches[1] ?? [];
        for ($i = count($tokens) - 1; $i >= 0; $i--) {
            $loc = trim((string) $tokens[$i]);
            if ($loc !== '' && ! $this->looksLikeTimePhrase($loc)) {
                return $loc;
            }
        }

        return '';
    }

    private function looksLikeTimePhrase(string $phrase): bool
    {
        return (bool) preg_match(
            '/^(\d{1,2}(?::\d{2})?\s*(?:am|pm)?|today|tomorrow|day after tomorrow)$/i',
            trim($phrase)
        );
    }

    private function providerMeansAutoAssign(string $hint): bool
    {
        return MobileAppAiWizardChoiceInterpreter::meansCompanyChoosesProvider($hint);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function actionStart(User $user, MobileAppAiConversation $conversation, array $args): array
    {
        $query = trim((string) ($args['query'] ?? $args['search'] ?? ''));
        $message = trim((string) ($args['message'] ?? ''));
        if ($message !== '' && $query !== ''
            && (MobileAppAiBookingMessageDetector::looksLikeServiceBookingRequest($message)
                || MobileAppAiServiceQueryNormalizer::isGenericBookingPhrase($message))) {
            $raw = $query;
        } else {
            $raw = $message !== '' ? $message : $query;
        }
        if ($raw !== '' && MobileAppAiServiceQueryNormalizer::isGenericBookingPhrase($raw)) {
            $draft = $this->emptyDraft();
            $draft['step'] = 'service_query';
            $this->saveDraft($conversation, $draft);

            return $this->wrap(
                MobileAppAiStepCopy::bookingStart(),
                'service_query',
                'Wait for their description, then call manage_app_booking with action=search and query=their words.'
            );
        }
        if ($raw !== '' && MobileAppAiBookingMessageDetector::hasTimeHint($raw)) {
            return $this->actionApply($user, $conversation, array_merge($args, ['message' => $raw]));
        }

        if ($query === '') {
            $draft = $this->emptyDraft();
            $draft['step'] = 'service_query';
            $this->saveDraft($conversation, $draft);

            return $this->wrap(
                MobileAppAiStepCopy::bookingStart(),
                'service_query',
                'Wait for their description, then call manage_app_booking with action=search and query=their words.'
            );
        }

        return $this->showServiceMatches($user, $conversation, $raw);
    }

    /**
     * @return array<string, mixed>
     */
    private function searchServicesWithFallback(User $user, string $rawText, int $limit): array
    {
        $intent = MobileAppAiServiceIntentResolver::resolve($rawText);
        if ($intent['unsupported'] !== null) {
            return [
                'ok' => true,
                'unsupported' => $intent['unsupported'],
                'selectable_options' => [],
                'intent' => $intent,
            ];
        }

        if (MobileAppAiServiceQueryNormalizer::isGenericBookingPhrase($rawText)) {
            return [
                'ok' => true,
                'generic' => true,
                'selectable_options' => [],
                'intent' => $intent,
            ];
        }

        $queries = [$intent['catalog_query']];
        if (! MobileAppAiServiceQueryNormalizer::isGenericBookingPhrase($intent['catalog_query'])) {
            $queries = array_values(array_unique(array_filter(array_merge(
                $queries,
                $intent['fallback_queries'],
            ))));
        }

        $last = ['ok' => false, 'selectable_options' => []];
        foreach ($queries as $q) {
            $q = trim($q);
            if ($q === '' || MobileAppAiServiceQueryNormalizer::isGenericBookingPhrase($q)) {
                continue;
            }
            $normalized = MobileAppAiServiceQueryNormalizer::normalize($q);
            $result = $this->catalog->searchServices($normalized, $limit, null, null, $user);
            $last = $result;
            if (($result['ok'] ?? false) && ($result['selectable_options'] ?? []) !== []) {
                $result['_resolved_query'] = $normalized;
                $result['_intent'] = $intent;

                return $result;
            }
        }

        $last['_intent'] = $intent;

        return $last;
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function actionTriageIssue(User $user, MobileAppAiConversation $conversation, array $args): array
    {
        $text = trim((string) ($args['message'] ?? $args['choice'] ?? ''));
        if (MobileAppAiServiceTriageService::wantsToProceedToBooking($text)) {
            return $this->actionProceedFromTriage($user, $conversation);
        }

        return $this->serviceTriage->continueTriage($conversation, $text);
    }

    private function actionProceedFromTriage(User $user, MobileAppAiConversation $conversation): array
    {
        $draft = $this->loadDraft($conversation);
        $query = trim((string) ($draft['choices']['service_query'] ?? ''));
        $issue = trim((string) ($draft['choices']['issue_description'] ?? ''));
        $message = trim($issue !== '' ? $query.' — '.$issue : $query);

        if ($message === '') {
            return $this->actionStart($user, $conversation, []);
        }

        $parsed = $this->resolveApplyFields(['message' => $message, 'query' => $query]);
        $this->persistParsedContextInDraft($conversation, $parsed);

        if (MobileAppAiBookingMessageDetector::looksLikeBulkBookingDetails($message)
            || MobileAppAiBookingMessageDetector::hasTimeHint($message)) {
            return $this->actionApply($user, $conversation, ['message' => $message]);
        }

        $searchText = $issue !== '' ? $issue : ($query !== '' ? $query : $message);

        return $this->showServiceMatches($user, $conversation, $searchText);
    }

    private function showServiceMatches(User $user, MobileAppAiConversation $conversation, string $rawText): array
    {
        $rawText = trim($rawText);
        if ($rawText === '') {
            $draft = $this->loadDraft($conversation);
            $draft['step'] = 'service_query';
            $this->saveDraft($conversation, $draft);

            return $this->wrap(
                MobileAppAiStepCopy::bookingStart(),
                'service_query'
            );
        }

        $intent = MobileAppAiServiceIntentResolver::resolve($rawText);
        if ($intent['unsupported'] !== null) {
            $draft = $this->loadDraft($conversation);
            $draft['step'] = 'service_query';
            $draft['choices']['last_search'] = $rawText;
            $this->saveDraft($conversation, $draft);

            return $this->wrap(
                MobileAppAiServiceIntentResolver::unsupportedMessage($rawText, $intent['unsupported']),
                'service_query',
                'Service not offered on the platform.'
            );
        }

        $result = $this->searchServicesWithFallback($user, $rawText, 12);
        if (! empty($result['generic'] ?? false)) {
            $draft = $this->loadDraft($conversation);
            $draft['step'] = 'service_query';
            $this->saveDraft($conversation, $draft);

            return $this->wrap(MobileAppAiStepCopy::bookingStart(), 'service_query');
        }
        if (! ($result['ok'] ?? false)) {
            return $this->wrapError('I could not search services right now. Please try again in a moment.');
        }

        $options = [];
        foreach ($result['selectable_options'] ?? [] as $row) {
            $options[] = [
                'pick' => (int) ($row['option'] ?? 0),
                'service_id' => (string) ($row['service_id'] ?? ''),
                'category_id' => (string) ($row['category_id'] ?? ''),
                'sub_category_id' => (string) ($row['sub_category_id'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
            ];
        }

        if ($options === []) {
            $draft = $this->loadDraft($conversation);
            $draft['step'] = 'service_query';
            $draft['choices']['last_search'] = $rawText;
            $this->saveDraft($conversation, $draft);

            return $this->wrap(
                MobileAppAiServiceIntentResolver::noCatalogMatchMessage(
                    $rawText,
                    $intent['catalog_query'],
                    $intent['trade_label']
                ),
                'service_query',
                'Call manage_app_booking action=search with a new query when they clarify.'
            );
        }

        $displayQuery = (string) ($result['_resolved_query'] ?? $intent['catalog_query']);
        $draft = $this->loadDraft($conversation);
        $preservedChoices = is_array($draft['choices'] ?? null) ? $draft['choices'] : [];
        if (($draft['step'] ?? 'idle') === 'idle') {
            $draft = $this->emptyDraft();
        } elseif (($draft['step'] ?? '') === 'service_query' && $preservedChoices !== []) {
            $draft = $this->emptyDraft();
            $draft['choices'] = $preservedChoices;
        }
        $draft['step'] = 'service';
        $draft['choices']['search_query'] = $displayQuery;
        $draft['choices']['last_customer_message'] = $rawText;
        $displayOptions = count($options) > 3
            ? MobileAppAiCatalogServiceMatcher::rankTop($options, $rawText, $intent, 3)
            : $options;
        $draft['options']['service'] = $displayOptions;
        $this->saveDraft($conversation, $draft);

        $best = count($displayOptions) === 1
            ? $displayOptions[0]
            : MobileAppAiCatalogServiceMatcher::pickBest($displayOptions, $rawText, $intent);
        if ($best !== null && MobileAppAiCatalogServiceMatcher::scoreOption($best, $rawText, $intent) >= 6) {
            return $this->proposeServiceConfirm($user, $conversation, $draft, $best, $rawText);
        }

        $ack = MobileAppAiServiceIntentResolver::matchedTradeAck($rawText, $intent['trade_label'], $displayQuery);
        $count = count($displayOptions);

        return $this->wrap(
            $ack.'Here are **'.$count.'** options for **'.$displayQuery.'** — tap one or tell me the number.',
            'service',
            'After they pick, call manage_app_booking with action=pick and choice=their selection.'
        );
    }

    /**
     * @param  array<string, mixed>  $draft
     * @param  array<string, mixed>  $picked
     * @return array<string, mixed>
     */
    private function proposeServiceConfirm(
        User $user,
        MobileAppAiConversation $conversation,
        array $draft,
        array $picked,
        string $rawText,
    ): array {
        $draft['step'] = 'service_confirm';
        $draft['choices']['pending_service_id'] = $picked['service_id'];
        $draft['choices']['pending_service_name'] = $picked['name'];
        $draft['choices']['pending_category_id'] = $picked['category_id'];
        $draft['choices']['pending_sub_category_id'] = $picked['sub_category_id'];
        $draft['choices']['confirm_pick'] = (string) ($picked['name'] ?? '1');
        $draft['choices']['last_customer_message'] = $rawText;
        $this->saveDraft($conversation, $draft);

        MobileAppAiChatLogger::pick($user->id, (string) $picked['name'], ['confirm' => true, 'query' => $rawText]);

        return $this->wrap(
            'Book **'.($picked['name'] ?? 'this service').'** for your request?',
            'service_confirm',
            'Wait for yes, then action=confirm_service; or show_service_options if they decline.'
        );
    }

    private function actionConfirmService(User $user, MobileAppAiConversation $conversation): array
    {
        $draft = $this->loadDraft($conversation);
        $choice = (string) ($draft['choices']['confirm_pick'] ?? $draft['choices']['pending_service_name'] ?? '1');

        return $this->pickService($user, $conversation, $draft, $choice);
    }

    private function actionShowServiceOptions(User $user, MobileAppAiConversation $conversation): array
    {
        $draft = $this->loadDraft($conversation);
        $draft['step'] = 'service';
        $this->saveDraft($conversation, $draft);
        $options = $draft['options']['service'] ?? [];
        $count = count($options);

        return $this->wrap(
            $count > 0
                ? 'Pick the right service below (tap a card or type the number).'
                : 'Tell me the service you need in a few words.',
            $count > 0 ? 'service' : 'service_query',
        );
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function actionPick(User $user, MobileAppAiConversation $conversation, array $args): array
    {
        $draft = $this->loadDraft($conversation);
        $choice = trim((string) ($args['choice'] ?? $args['option'] ?? $args['number'] ?? $args['message'] ?? ''));
        if ($choice === '') {
            return $this->wrapError('Ask the customer which **number** they want from the list you just showed.');
        }

        $step = (string) ($draft['step'] ?? 'service');

        if ($step === 'service_query') {
            $query = trim($choice) !== '' ? trim($choice) : trim((string) ($args['query'] ?? ''));

            return $this->showServiceMatches($user, $conversation, $query);
        }

        if ($step === 'ready' && ($this->isAffirmative($choice) || strtolower($choice) === 'yes')) {
            return $this->actionConfirm($user, $conversation);
        }
        if ($step === 'ready' && $this->isNegative($choice)) {
            return $this->actionCancel($conversation);
        }

        if ($step === 'schedule') {
            $asap = MobileAppAiWizardChoiceInterpreter::meansAsapSchedule($choice);

            return $this->actionTime($conversation, [
                'asap' => $asap,
                'when' => $asap ? '' : $choice,
                'message' => $choice,
            ]);
        }

        return match ($step) {
            'service' => $this->pickService($user, $conversation, $draft, $choice),
            'variation' => $this->pickVariation($conversation, $draft, $choice),
            'address' => $this->pickAddress($user, $conversation, $draft, $choice),
            'provider' => $this->pickProvider($conversation, $draft, $choice),
            default => $this->showServiceMatches($user, $conversation, $choice),
        };
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function actionTime(MobileAppAiConversation $conversation, array $args): array
    {
        $draft = $this->loadDraft($conversation);
        if (($draft['step'] ?? '') !== 'schedule') {
            return $this->wrapError('Complete service and variation selection first (action=pick).');
        }

        $payload = $args;
        $rawWhen = trim((string) ($args['when'] ?? $args['datetime'] ?? $args['message'] ?? ''));
        if (filter_var($args['asap'] ?? false, FILTER_VALIDATE_BOOL)) {
            $payload['schedule_type'] = 'asap';
        } elseif ($rawWhen !== '') {
            $parsed = MobileAppAiSchedulePhraseParser::parse($rawWhen);
            if ($parsed['ok'] ?? false) {
                $payload['schedule_type'] = $parsed['schedule_type'] ?? 'custom';
                $payload['service_schedule'] = $parsed['schedule'];
            } else {
                $payload['service_schedule'] = $rawWhen;
            }
        }

        $resolved = $this->flow->resolveSchedule($payload);
        if (! ($resolved['ok'] ?? false)) {
            $instant = (int) (business_config('instant_booking', 'booking_setup')?->live_values ?? 0);

            return $this->wrap(
                $instant === 1
                    ? "Try **ASAP**, **kal**, **aaj**, or e.g. *tomorrow 5pm*."
                    : 'Send **kal** / **tomorrow**, or a time 2+ hours from now.',
                'schedule'
            );
        }

        $draft['choices']['schedule'] = $resolved['schedule'];
        $draft['choices']['schedule_type'] = $resolved['schedule_type'] ?? 'custom';
        $draft['choices']['schedule_label'] = ($resolved['schedule_type'] ?? '') === 'asap'
            ? 'ASAP (earliest available)'
            : Carbon::parse($resolved['schedule'])->format('j M Y, g:i A');
        $draft['step'] = 'address';
        $this->saveDraft($conversation, $draft);

        $owner = User::query()->find($conversation->user_id);

        return $this->showAddresses($owner, $conversation, $draft, true);
    }

    private function actionConfirm(User $user, MobileAppAiConversation $conversation): array
    {
        $draft = $this->loadDraft($conversation);
        if (($draft['step'] ?? '') !== 'ready') {
            return $this->wrapError('Your booking is not on the final step yet. Finish the highlighted step above, then tap **Add to cart** or say **yes**.');
        }

        $c = $draft['choices'];
        $scheduleType = (string) ($c['schedule_type'] ?? '');
        if ($scheduleType === '' && str_contains(mb_strtolower((string) ($c['schedule_label'] ?? '')), 'asap')) {
            $scheduleType = 'asap';
        }

        $cartArgs = [
            'service_id' => (string) ($c['service_id'] ?? ''),
            'variant_key' => (string) ($c['variant_key'] ?? ''),
            'category_id' => (string) ($c['category_id'] ?? ''),
            'sub_category_id' => (string) ($c['sub_category_id'] ?? ''),
            'zone_id' => (string) ($c['zone_id'] ?? ''),
            'service_address_id' => (int) ($c['service_address_id'] ?? 0),
            'service_schedule' => (string) ($c['schedule'] ?? ''),
            'schedule_type' => $scheduleType,
            'schedule_label' => (string) ($c['schedule_label'] ?? ''),
            'let_company_choose_provider' => ($c['let_company_choose'] ?? false) === true,
            'provider_id' => $c['provider_id'] !== null ? (string) $c['provider_id'] : '',
        ];

        $result = $this->cart->addServiceForUser($user, $cartArgs);
        if (! ($result['ok'] ?? false)) {
            if (($result['error'] ?? '') === 'booking_incomplete') {
                return $this->wrapError($this->formatIncompleteBookingError($result));
            }

            return $this->wrapError('Could not add to cart right now. Please tap **Add to cart** again, or open **Cart** on Home to finish checkout.');
        }

        $this->saveDraft($conversation, $this->emptyDraft());

        $summary = $this->formatSummary($draft);
        $brand = WhatsAppAiPromptBuilder::resolveBrandName();

        return $this->wrap(
            "Done — I've added this to your cart:\n\n".$summary
            ."\n\n**Next:** tap **Cart** at the top of Home, review everything, then **checkout and pay**. "
            .($c['let_company_choose'] ?? false
                ? $brand.' will assign the best available provider after you pay.'
                : 'Your chosen provider will be notified after payment.'),
            'done',
            'cart_updated=true. Do not mention cart line ids.',
            true
        );
    }

    private function actionCancel(MobileAppAiConversation $conversation): array
    {
        $this->saveDraft($conversation, $this->emptyDraft());

        return $this->wrap('No problem — I\'ve cancelled this booking. Tell me if you want to start again or need something else.', 'idle');
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function coerceBookingArgsForStep(string $step, array $args): array
    {
        $action = strtolower(trim((string) ($args['action'] ?? '')));
        $message = trim((string) ($args['message'] ?? $args['query'] ?? ''));

        if ($message !== '' && MobileAppAiBookingMessageDetector::looksLikeConfusionQuestion($message)
            && in_array($step, ['service_confirm', 'service_triage', 'service', 'ready'], true)) {
            return array_merge($args, ['action' => 'clarify_step']);
        }

        if ($step === 'service_confirm'
            && (MobileAppAiBookingMessageDetector::looksLikeServiceBookingRequest($message)
                || MobileAppAiBookingMessageDetector::isAffirmative($message)
                || MobileAppAiServiceTriageService::wantsToProceedToBooking($message))) {
            return array_merge($args, ['action' => 'confirm_service']);
        }

        if ($step === 'service_triage'
            && (MobileAppAiBookingMessageDetector::looksLikeServiceBookingRequest($message)
                || MobileAppAiServiceTriageService::wantsToProceedToBooking($message))) {
            return array_merge($args, ['action' => 'proceed_booking']);
        }

        if (in_array($step, ['service_confirm', 'service_triage', 'service', 'variation', 'schedule', 'address', 'provider', 'ready'], true)
            && in_array($action, ['start', 'search', 'triage_issue'], true)
            && $message !== ''
            && ! $this->looksLikeNewServiceSearch($message, $step)) {
            if ($step === 'service_confirm') {
                return array_merge($args, ['action' => 'clarify_step']);
            }
            if ($step === 'service_triage' && MobileAppAiBookingMessageDetector::looksLikeServiceBookingRequest($message)) {
                return array_merge($args, ['action' => 'proceed_booking']);
            }
            if ($step === 'service_triage' && MobileAppAiBookingMessageDetector::looksLikeConfusionQuestion($message)) {
                return array_merge($args, ['action' => 'clarify_step']);
            }
        }

        return $args;
    }

    private function looksLikeNewServiceSearch(string $message, string $step): bool
    {
        if (MobileAppAiBookingMessageDetector::looksLikeServiceBookingRequest($message)
            || MobileAppAiBookingMessageDetector::looksLikeConfusionQuestion($message)) {
            return false;
        }

        if (MobileAppAiBookingMessageDetector::hasBookingIntent($message)
            && ! MobileAppAiBookingMessageDetector::hasServiceTradeHint($message)) {
            return false;
        }

        return MobileAppAiServiceQueryNormalizer::looksLikeServiceRequest($message)
            && mb_strlen(trim($message)) >= 2;
    }

    private function actionClarifyStep(MobileAppAiConversation $conversation): array
    {
        $draft = $this->loadDraft($conversation);
        $step = (string) ($draft['step'] ?? 'idle');
        $service = trim((string) ($draft['choices']['pending_service_name'] ?? $draft['choices']['service_name'] ?? $draft['choices']['service_query'] ?? ''));

        $message = match ($step) {
            'service_confirm' => $service !== ''
                ? 'Main **'.$service.'** book karne ki baat kar raha hoon. Haan boliye ya **Yes, book this** dabayein — phir time aur address set karte hain.'
                : 'Yeh service book karni hai? Haan boliye ya **Yes, book this** dabayein.',
            'service_triage' => $service !== ''
                ? '**'.$service.'** ke liye main do cheezein kar sakta hoon: seedha **Book this service** (booking start), ya pehle problem batayein (quick tips). Aap booking chahte hain to *service karwani hai* ya button dabayein.'
                : 'Booking ke liye **Book this service** dabayein, ya problem batayein troubleshooting tips ke liye.',
            'service' => 'Neeche se service choose karein ya number likhein.',
            'ready' => 'Booking almost ready hai — confirm karein ya kuch change batayein.',
            default => 'Booking chal rahi hai — main aapki madad kar raha hoon. Kya aap aage badhna chahte hain?',
        };

        return $this->wrap($message, $step !== '' ? $step : 'idle');
    }

    private function actionStatus(MobileAppAiConversation $conversation): array
    {
        $draft = $this->loadDraft($conversation);
        $step = (string) ($draft['step'] ?? 'idle');

        if ($step === 'idle' || $step === '') {
            return $this->wrap('No booking in progress. Use action=start when the customer wants to book.', 'idle');
        }

        if ($step === 'ready') {
            return $this->wrap(
                "Booking summary:\n\n".$this->formatSummary($draft)
                ."\n\nAsk the customer to confirm, then use action=confirm.",
                'ready'
            );
        }

        $next = match ($step) {
            'service_query' => 'Ask what service they need, then action=search with their description.',
            'service' => 'Wait for them to pick a matching service (action=pick).',
            'variation' => 'Wait for them to pick a service type (action=pick).',
            'schedule' => 'Ask when to visit — ASAP or date/time (action=time).',
            'address' => 'Wait for address pick or add-new in app.',
            'provider' => 'Wait for provider pick (0 = we choose).',
            default => 'action=start',
        };

        return $this->wrap('Current booking step: **'.$step.'**. '.$next, $step);
    }

    private function pickService(User $user, MobileAppAiConversation $conversation, array $draft, string $choice): array
    {
        $this->enrichDraftFromLastCustomerMessage($conversation);
        $draft = $this->loadDraft($conversation);
        $options = $draft['options']['service'] ?? [];

        if (count($options) === 1) {
            $choice = (string) ($options[0]['name'] ?? '1');
        } elseif ($this->shouldAutoPickService($choice, $draft, $options)) {
            $picked = MobileAppAiWizardChoiceInterpreter::resolveByNameOrNumber(
                $options,
                (string) ($draft['choices']['search_query'] ?? $choice)
            );
            if ($picked !== null) {
                $choice = (string) ($picked['name'] ?? '1');
            }
        }

        $customerText = trim((string) ($draft['choices']['last_customer_message'] ?? $choice));
        $picked = MobileAppAiCatalogServiceMatcher::pickBest($options, $customerText)
            ?? MobileAppAiWizardChoiceInterpreter::resolveByNameOrNumber($options, $choice)
            ?? $this->resolvePick($options, $choice);
        if ($picked === null) {
            if (! preg_match('/^\d+$/', $choice)) {
                return $this->showServiceMatches($user, $conversation, $choice);
            }

            return $this->wrapError('Tell me the **number** from the list, or describe the service in a few words (e.g. *AC repair*).');
        }

        $draft['choices']['service_id'] = $picked['service_id'];
        $draft['choices']['category_id'] = $picked['category_id'];
        $draft['choices']['sub_category_id'] = $picked['sub_category_id'];
        $draft['choices']['service_name'] = $picked['name'];

        $varResult = $this->flow->listVariationsForBooking($picked['service_id'], null);
        if (! ($varResult['ok'] ?? false)) {
            return $this->wrapError('Could not load options for that service.');
        }

        $options = [];
        foreach ($varResult['selectable_options'] ?? [] as $row) {
            $label = (string) ($row['label'] ?? '');
            $pick = (int) ($row['option'] ?? 0);
            $options[] = [
                'pick' => $pick,
                'variant_key' => (string) ($row['variant_key'] ?? ''),
                'label' => $label,
            ];
        }

        if (count($options) === 1) {
            $draft['choices']['variant_key'] = $options[0]['variant_key'];
            $draft['choices']['variation_label'] = $options[0]['label'];
            $draft['step'] = 'schedule';
            $this->saveDraft($conversation, $draft);

            return $this->advanceAfterVariation(
                $user,
                $conversation,
                $draft,
                MobileAppAiStepCopy::serviceAutoSelected((string) $picked['name'])
            );
        }

        $draft['step'] = 'variation';
        $draft['options']['variation'] = $options;
        $this->saveDraft($conversation, $draft);

        return $this->wrap(
            '**'.$picked['name'].'** — pick the type of service below, or type it.',
            'variation'
        );
    }

    /**
     * @param  list<array<string, mixed>>  $options
     */
    private function shouldAutoPickService(string $choice, array $draft, array $options): bool
    {
        if ($options === []) {
            return false;
        }

        if (MobileAppAiWizardChoiceInterpreter::isReaffirmation($choice)) {
            return true;
        }

        $search = mb_strtolower(trim((string) ($draft['choices']['search_query'] ?? '')));
        $norm = mb_strtolower(MobileAppAiServiceQueryNormalizer::normalize($choice));
        if ($search !== '' && ($search === $norm || str_contains($norm, $search) || str_contains($search, $norm))) {
            return true;
        }

        return false;
    }

    private function pickVariation(MobileAppAiConversation $conversation, array $draft, string $choice): array
    {
        $this->enrichDraftFromLastCustomerMessage($conversation);
        $draft = $this->loadDraft($conversation);
        $options = $draft['options']['variation'] ?? [];
        if (count($options) === 1) {
            $choice = (string) ($options[0]['label'] ?? '1');
        } elseif (MobileAppAiWizardChoiceInterpreter::isReaffirmation($choice) && $options !== []) {
            $choice = (string) ($options[0]['label'] ?? '1');
        }

        $picked = MobileAppAiWizardChoiceInterpreter::resolveByNameOrNumber($options, $choice, 'label')
            ?? $this->resolvePick($options, $choice, 'label');
        if ($picked === null) {
            return $this->wrapError('Please tap one of the types shown above.');
        }

        $draft['choices']['variant_key'] = $picked['variant_key'];
        $draft['choices']['variation_label'] = $picked['label'];
        $draft['step'] = 'schedule';
        $this->saveDraft($conversation, $draft);

        $owner = User::query()->find($conversation->user_id);

        return $this->advanceAfterVariation($owner, $conversation, $draft);
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function advanceAfterVariation(
        ?User $user,
        MobileAppAiConversation $conversation,
        array $draft,
        ?string $prefix = null,
    ): array {
        if (! empty($draft['choices']['schedule'])) {
            if (! empty($draft['choices']['service_address_id'])) {
                return $this->showProviders($conversation, $draft);
            }

            if (! $user instanceof User) {
                $user = User::query()->find($conversation->user_id);
            }

            return $this->showAddresses($user, $conversation, $draft, true);
        }

        return $this->askSchedule($conversation, $draft, $prefix);
    }

    private function askSchedule(MobileAppAiConversation $conversation, array $draft, ?string $prefix = null): array
    {
        if (! empty($draft['choices']['schedule'])) {
            $owner = User::query()->find($conversation->user_id);

            return $this->showAddresses($owner, $conversation, $draft, true);
        }

        $this->saveDraft($conversation, $draft);
        return $this->wrap(
            MobileAppAiStepCopy::schedulePrompt($prefix),
            'schedule',
            'Call manage_app_booking action=time with asap=true or when=their datetime.'
        );
    }

    private function pickAddress(?User $user, MobileAppAiConversation $conversation, array $draft, string $choice): array
    {
        $options = $draft['options']['address'] ?? [];
        if (count($options) === 1 && ! str_contains(mb_strtolower($choice), 'add')) {
            $choice = (string) ($options[0]['address'] ?? '1');
        } elseif (MobileAppAiWizardChoiceInterpreter::isReaffirmation($choice) && $options !== []) {
            $choice = (string) ($options[0]['address'] ?? '1');
        }

        if (strtolower($choice) === 'new' || str_contains(strtolower($choice), 'add')) {
            return $this->wrap(
                "No problem — add a new address from **Home → location bar → Add new address**.\n\nWhen you're done, tap **I've added my address** below and I'll continue.",
                'address'
            );
        }

        if (strtolower($choice) === 'done') {
            return $this->showAddresses(null, $conversation, $draft, false);
        }

        $picked = MobileAppAiWizardChoiceInterpreter::resolveByNameOrNumber($draft['options']['address'] ?? [], $choice, 'address')
            ?? $this->resolvePick($draft['options']['address'] ?? [], $choice, 'address');
        if ($picked === null) {
            return $this->wrapError('Please tap one of your saved addresses, or add a new address first.');
        }

        $draft['choices']['service_address_id'] = $picked['service_address_id'];
        $draft['choices']['zone_id'] = $picked['zone_id'];
        $draft['choices']['address_label'] = $picked['address_label'] ?? '';
        $draft['choices']['address_line'] = $picked['address'] ?? '';
        $this->saveDraft($conversation, $draft);

        return $this->showProviders($conversation, $draft);
    }

    private function showAddresses(?User $user, MobileAppAiConversation $conversation, array $draft, bool $afterSchedule): array
    {
        if (! $user instanceof User) {
            $user = User::query()->find($conversation->user_id);
        }
        if (! $user) {
            return $this->wrapError('Please sign in again to continue booking.');
        }

        $addrResult = $this->catalog->listCustomerAddresses($user);
        $options = [];
        foreach ($addrResult['selectable_options'] ?? [] as $row) {
            $options[] = [
                'pick' => (int) ($row['option'] ?? 0),
                'service_address_id' => (int) ($row['service_address_id'] ?? 0),
                'zone_id' => (string) ($row['zone_id'] ?? ''),
                'address' => (string) ($row['address'] ?? ''),
                'address_label' => (string) ($row['address_label'] ?? ''),
            ];
        }

        if ($options === []) {
            $draft['step'] = 'address';
            $this->saveDraft($conversation, $draft);

            return $this->wrap(
                "I don't see a saved address on your account yet.\n\nAdd one from **Home → location bar → Add new address**, then tap **I've added my address**.",
                'address'
            );
        }

        $draft['step'] = 'address';
        $draft['options']['address'] = $options;
        $this->saveDraft($conversation, $draft);

        if (count($options) === 1) {
            return $this->pickAddress($user, $conversation, $draft, (string) ($options[0]['address'] ?? '1'));
        }

        return $this->wrap(
            MobileAppAiStepCopy::addressPrompt((string) ($draft['choices']['schedule_label'] ?? '')),
            'address'
        );
    }

    private function showProviders(MobileAppAiConversation $conversation, array $draft): array
    {
        $provResult = $this->flow->listBookingProviders([
            'sub_category_id' => $draft['choices']['sub_category_id'],
            'zone_id' => $draft['choices']['zone_id'],
            'service_schedule' => $draft['choices']['schedule'],
        ]);

        $options = [];
        foreach ($provResult['selectable_options'] ?? [] as $row) {
            $options[] = [
                'pick' => (int) ($row['option'] ?? 0),
                'provider_id' => $row['provider_id'],
                'name' => (string) ($row['name'] ?? ''),
            ];
        }

        $draft['step'] = 'provider';
        $draft['options']['provider'] = $options;
        $this->saveDraft($conversation, $draft);

        $brand = WhatsAppAiPromptBuilder::resolveBrandName();

        if (count($options) === 1) {
            return $this->pickProvider($conversation, $draft, (string) ($options[0]['name'] ?? '0'));
        }

        return $this->wrap(
            MobileAppAiStepCopy::providerPrompt(),
            'provider'
        );
    }

    private function pickProvider(MobileAppAiConversation $conversation, array $draft, string $choice): array
    {
        $options = $draft['options']['provider'] ?? [];
        $picked = MobileAppAiWizardChoiceInterpreter::resolveProviderOption($options, $choice);
        if ($picked === null) {
            $brand = WhatsAppAiPromptBuilder::resolveBrandName();

            return $this->wrapError(
                'Tell me a **provider name** from the list, or say **let '.$brand.' choose** / **kisi ko bhej do** and I will assign the best available provider.'
            );
        }

        $draft['choices']['let_company_choose'] = ($picked['pick'] ?? -1) === 0 || ($picked['provider_id'] ?? null) === null;
        $draft['choices']['provider_id'] = $draft['choices']['let_company_choose'] ? null : $picked['provider_id'];
        $draft['choices']['provider_name'] = $picked['name'] ?? '';
        $draft['step'] = 'ready';
        $this->saveDraft($conversation, $draft);

        $providerLine = $draft['choices']['let_company_choose']
            ? 'Got it — **'.WhatsAppAiPromptBuilder::resolveBrandName().'** will assign the best available provider.'
            : 'Got it — **'.($draft['choices']['provider_name'] ?? 'your provider').'** selected.';

        return $this->wrap(
            MobileAppAiStepCopy::confirmPrompt(),
            'ready',
            'On confirm → action=confirm.'
        );
    }

    private function formatSummary(array $draft): string
    {
        $c = $draft['choices'] ?? [];
        $lines = [
            '• Service: '.($c['service_name'] ?? ''),
            '• Type: '.($c['variation_label'] ?? ''),
            '• When: '.($c['schedule_label'] ?? ''),
            '• Where: '.($c['address_label'] !== '' ? $c['address_label'].' — ' : '').($c['address_line'] ?? ''),
        ];
        if ($c['let_company_choose'] ?? false) {
            $brand = WhatsAppAiPromptBuilder::resolveBrandName();
            $lines[] = '• Provider: '.$brand.' will choose for you';
        } else {
            $lines[] = '• Provider: '.($c['provider_name'] ?? '');
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @return array<string, mixed>|null
     */
    private function resolvePick(array $options, string $choice, string $labelKey = 'name'): ?array
    {
        if (preg_match('/^\d+$/', $choice)) {
            $n = (int) $choice;
            foreach ($options as $o) {
                if ((int) ($o['pick'] ?? -1) === $n) {
                    return $o;
                }
            }
        }

        $lower = strtolower($choice);
        foreach ($options as $o) {
            $name = strtolower((string) ($o[$labelKey] ?? $o['name'] ?? ''));
            if ($name !== '' && (str_contains($name, $lower) || str_contains($lower, $name))) {
                return $o;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyDraft(): array
    {
        return [
            'step' => 'idle',
            'options' => [],
            'choices' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDraft(MobileAppAiConversation $conversation): array
    {
        $raw = $conversation->booking_draft;
        if (is_array($raw) && $raw !== []) {
            return $raw;
        }

        return $this->emptyDraft();
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    private function saveDraft(MobileAppAiConversation $conversation, array $draft): void
    {
        $conversation->booking_draft = $draft;
        $conversation->save();
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function wrapCartManageResult(array $result): array
    {
        if (! isset($result['customer_message']) && isset($result['reply'])) {
            $result['customer_message'] = $result['reply'];
        }
        if (! isset($result['ok'])) {
            $result['ok'] = true;
        }

        return $this->wrapAgentResult($result);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function wrapAgentResult(array $result): array
    {
        return [
            'ok' => $result['ok'] ?? true,
            'customer_message' => (string) ($result['customer_message'] ?? ''),
            'wizard_step' => 'idle',
            'cart_updated' => ($result['cart_updated'] ?? false) === true,
            'ui' => $result['ui'] ?? null,
        ];
    }

    private function wrap(string $customerMessage, string $step, string $modelHint = '', bool $cartUpdated = false): array
    {
        $out = [
            'ok' => true,
            'customer_message' => $customerMessage,
            'wizard_step' => $step,
            '_instruction' => 'Use ONLY customer_message — keep it crisp (1–3 sentences). Never show IDs or internal field names. '.$modelHint,
        ];
        if ($cartUpdated) {
            $out['cart_updated'] = true;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function wrapError(string $customerMessage): array
    {
        return [
            'ok' => false,
            'customer_message' => $customerMessage,
            '_instruction' => 'Tell the customer this in friendly words only — no technical codes.',
        ];
    }

    private function isAffirmative(string $choice): bool
    {
        return MobileAppAiBookingMessageDetector::isAffirmative($choice)
            || MobileAppAiConversationalResponder::isReaffirmation($choice);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function formatIncompleteBookingError(array $result): string
    {
        $error = (string) ($result['error'] ?? '');
        if ($error === 'schedule_too_soon') {
            return 'Your visit time needs updating. Say **ASAP** or a new time like **kal 5pm** (tomorrow 5pm), then confirm again.';
        }

        return "I couldn't add this to your cart. Please tap **Add to cart** again — your summary above still looks saved.";
    }

    private function isNegative(string $choice): bool
    {
        return (bool) preg_match('/^(no|n|cancel|stop)$/i', $choice);
    }
}
