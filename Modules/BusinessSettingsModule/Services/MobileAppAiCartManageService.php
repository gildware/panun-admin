<?php

namespace Modules\BusinessSettingsModule\Services;

use Carbon\Carbon;
use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\CartModule\Entities\Cart;
use Modules\CartModule\Entities\CartServiceInfo;
use Modules\UserManagement\Entities\User;

/**
 * Cart changes from AI chat: clear, remove selected lines, reschedule — always confirm first.
 */
class MobileAppAiCartManageService
{
    public function __construct(
        protected MobileAppAiCartService $cartService,
        protected MobileAppAiPricingReply $pricingReply,
        protected MobileAppAiCartActionResolver $cartActionResolver,
    ) {}

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool}|null
     */
    public function tryHandle(
        User $user,
        MobileAppAiConversation $conversation,
        string $text,
        ?MobileAppAiIntentClassification $classification = null,
    ): ?array {
        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
        $step = (string) ($draft['step'] ?? '');

        if ($step === 'cart_confirm') {
            return $this->handleConfirmationTurn($user, $conversation, $text, $draft);
        }

        $parsed = null;
        if ($classification !== null) {
            $parsed = $this->cartActionResolver->resolve($user, $text, $classification);
        }
        if ($parsed === null) {
            $parsed = $this->cartActionResolver->resolveMessage($user, $text);
        }
        if ($parsed === null) {
            return null;
        }

        return $this->executeParsed($user, $conversation, $parsed, $text);
    }

    /**
     * @param  array{op: string, target: string, schedule_text: string, cart_line_ids?: list<string>, cart_filter?: string}  $parsed
     * @return array{reply: string, ui: mixed, cart_updated: bool}|null
     */
    public function executeParsed(User $user, MobileAppAiConversation $conversation, array $parsed, string $userText = ''): ?array
    {
        if (($parsed['op'] ?? '') === 'view') {
            $priced = $this->pricingReply->build($user, $userText);

            return [
                'reply' => (string) ($priced['customer_message'] ?? ''),
                'ui' => $priced['ui'] ?? null,
                'cart_updated' => false,
            ];
        }

        return $this->beginConfirmation($user, $conversation, $parsed, $userText);
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool}
     */
    public function beginRemoveLine(User $user, MobileAppAiConversation $conversation, string $cartLineId, string $userText = ''): array
    {
        $cartLineId = trim($cartLineId);
        if ($cartLineId === '') {
            return [
                'reply' => MobileAppAiReplyStyle::localize(
                    'Which cart item should I remove?',
                    'Kaunsi item hataani hai? Cart mein tap karke bataiye.',
                    $userText
                ),
                'ui' => $this->cartActionsUi($userText),
                'cart_updated' => false,
            ];
        }

        return $this->beginConfirmation($user, $conversation, [
            'op' => 'remove',
            'target' => '',
            'schedule_text' => '',
            'cart_line_ids' => [$cartLineId],
            'cart_filter' => '',
        ], $userText);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, customer_message: string, cart_updated?: bool, ui?: mixed}
     */
    public function confirmPending(User $user, MobileAppAiConversation $conversation): array
    {
        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
        $pending = is_array($draft['choices']['cart_pending'] ?? null) ? $draft['choices']['cart_pending'] : [];

        if ($pending === []) {
            return ['ok' => false, 'customer_message' => 'Nothing to confirm — tell me if you want to change your cart.'];
        }

        $result = $this->executePending($user, $pending);
        $this->resetDraft($conversation);

        return $result;
    }

    /**
     * @param  array{op: string, target: string, schedule_text: string}  $parsed
     * @return array{ids: list<string>, labels: list<string>, error?: string}
     */
    public function matchTargets(User $user, array $parsed): array
    {
        return $this->resolveTargetLines($this->loadCartLines($user), $parsed);
    }

    public function cancelPending(MobileAppAiConversation $conversation): array
    {
        $this->resetDraft($conversation);

        return [
            'ok' => true,
            'customer_message' => 'No problem — your cart is unchanged. Say **show my cart** or **book a service** anytime.',
            'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array{reply: string, ui: mixed, cart_updated: bool}
     */
    private function handleConfirmationTurn(
        User $user,
        MobileAppAiConversation $conversation,
        string $text,
        array $draft,
    ): array {
        if (MobileAppAiBookingMessageDetector::isNegative($text)) {
            $result = $this->cancelPending($conversation);

            return [
                'reply' => (string) $result['customer_message'],
                'ui' => $result['ui'] ?? null,
                'cart_updated' => false,
            ];
        }

        if (MobileAppAiBookingMessageDetector::isAffirmative($text)
            || MobileAppAiBookingMessageDetector::wantsProceedServiceConfirm($text)) {
            $result = $this->confirmPending($user, $conversation);

            return [
                'reply' => (string) ($result['customer_message'] ?? ''),
                'ui' => $result['ui'] ?? null,
                'cart_updated' => ($result['cart_updated'] ?? false) === true,
            ];
        }

        $summary = trim((string) ($draft['choices']['cart_confirm_summary'] ?? 'this cart change'));

        return [
            'reply' => $this->buildReconfirmPrompt($summary, $text),
            'ui' => $this->confirmUi($text),
            'cart_updated' => false,
        ];
    }

    /**
     * @param  array{op: string, target: string, schedule_text: string}  $parsed
     * @return array{reply: string, ui: mixed, cart_updated: bool}
     */
    private function beginConfirmation(User $user, MobileAppAiConversation $conversation, array $parsed, string $userText = ''): array
    {
        $lines = $this->loadCartLines($user);
        if ($lines === []) {
            return [
                'reply' => 'Your cart is already empty. Tell me what service you need and I can add it.',
                'ui' => MobileAppAiConversationalResponder::homeActionsUi(),
                'cart_updated' => false,
            ];
        }

        $resolution = $this->assessCartActionResolution($lines, $parsed);
        if ($resolution->isAmbiguous()) {
            return [
                'reply' => MobileAppAiReplyStyle::localize(
                    $resolution->clarificationQuestion,
                    'Kaunsi line hataani hai? Neeche tap karein.',
                    $userText
                ),
                'ui' => $this->cartLinePickUi($resolution->matchedEntities),
                'cart_updated' => false,
            ];
        }
        if ($resolution->status === MobileAppAiActionResolutionResult::NOT_FOUND) {
            return [
                'reply' => MobileAppAiReplyStyle::localize(
                    $resolution->clarificationQuestion,
                    'Cart mein woh item nahi mila. **Show my cart** likhein ya neeche **Open cart** tap karein.',
                    $userText
                ),
                'ui' => $this->cartActionsUi($userText),
                'cart_updated' => false,
            ];
        }
        $resolvedIds = $resolution->matchedLineIds();
        if ($resolvedIds !== []) {
            $parsed['cart_line_ids'] = $resolvedIds;
        }

        $match = $this->resolveTargetLines($lines, $parsed);
        if ($match['ids'] === []) {
            $error = (string) ($match['error'] ?? '');
            if ($error === '__which_remove__') {
                $error = MobileAppAiReplyStyle::localize(
                    'Which item should I remove? For example: **remove AC repair** or **clear my cart** for everything.',
                    'Kaunsi service hataani hai? Jaise **AC repair hata do** ya poora cart **clear karo**.',
                    $userText
                );
            } elseif ($error !== '') {
                $error = MobileAppAiReplyStyle::localize(
                    $error,
                    'Cart mein match nahi mila. **Show my cart** likhein ya **Open cart** tap karein.',
                    $userText
                );
            } else {
                $error = MobileAppAiReplyStyle::localize(
                    'I could not find that item in your cart. Say **show my cart** to see what is there.',
                    'Cart mein woh item nahi mila. **Show my cart** likhein.',
                    $userText
                );
            }

            return [
                'reply' => MobileAppAiReplyStyle::clampReply($error),
                'ui' => $this->cartActionsUi(),
                'cart_updated' => false,
            ];
        }

        $pending = [
            'op' => $parsed['op'],
            'cart_line_ids' => $match['ids'],
            'schedule_text' => $parsed['schedule_text'],
            'labels' => $match['labels'],
        ];

        if ($parsed['op'] === 'reschedule') {
            $scheduleText = trim($parsed['schedule_text']);
            if ($scheduleText === '') {
                return [
                    'reply' => 'When should the visit be? For example: **tomorrow 5pm**, **kal subah**, or **ASAP**.',
                    'ui' => null,
                    'cart_updated' => false,
                ];
            }
            $schedule = MobileAppAiSchedulePhraseParser::parse($scheduleText);
            if (! ($schedule['ok'] ?? false)) {
                return [
                    'reply' => 'I could not understand that date/time. Try **tomorrow 5pm**, **aaj sham**, or **ASAP**.',
                    'ui' => null,
                    'cart_updated' => false,
                ];
            }
            $pending['normalized_schedule'] = (string) ($schedule['schedule'] ?? '');
            $pending['schedule_label'] = (string) ($schedule['label'] ?? $scheduleText);
        }

        $summary = $this->describePending($pending);
        $conversation->booking_draft = [
            'step' => 'cart_confirm',
            'choices' => [
                'cart_pending' => $pending,
                'cart_confirm_summary' => $summary,
            ],
        ];
        $conversation->save();

        $listed = $this->formatLineList($match['labels']);

        return [
            'reply' => $this->buildConfirmPrompt($summary, $listed, $userText),
            'ui' => $this->confirmUi($userText),
            'cart_updated' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $pending
     * @return array{ok: bool, customer_message: string, cart_updated: bool, ui?: mixed}
     */
    private function executePending(User $user, array $pending): array
    {
        $op = (string) ($pending['op'] ?? '');
        $ids = array_values(array_filter((array) ($pending['cart_line_ids'] ?? [])));

        return match ($op) {
            'clear_all', 'remove', 'keep_only', 'keep_one' => $this->removeLines($user, $ids, $op === 'clear_all'),
            'reschedule' => $this->rescheduleLines(
                $user,
                $ids,
                (string) ($pending['normalized_schedule'] ?? ''),
                (string) ($pending['schedule_label'] ?? '')
            ),
            default => [
                'ok' => false,
                'customer_message' => 'That cart action is not supported.',
                'cart_updated' => false,
            ],
        };
    }

    /**
     * @param  list<string>  $ids
     * @return array{ok: bool, customer_message: string, cart_updated: bool, ui?: mixed}
     */
    private function removeLines(User $user, array $ids, bool $clearedAll): array
    {
        $customerId = (string) $user->id;
        $count = Cart::query()
            ->where('customer_id', $customerId)
            ->where('is_guest', false)
            ->whereIn('id', $ids)
            ->delete();

        if ($count === 0) {
            return [
                'ok' => false,
                'customer_message' => 'Those cart items were not found — they may already be removed.',
                'cart_updated' => false,
            ];
        }

        $remaining = Cart::query()->where('customer_id', $customerId)->where('is_guest', false)->count();
        if ($remaining === 0) {
            CartServiceInfo::query()->where('customer_id', $customerId)->delete();
        }

        $msg = $clearedAll
            ? 'Done — your cart is now empty. Tell me anytime if you want to book a service.'
            : 'Done — I removed '.$count.' item(s) from your cart.'
                .($remaining > 0 ? ' You still have '.$remaining.' item(s) — say **show my cart** to review.' : ' Your cart is now empty.');

        return [
            'ok' => true,
            'customer_message' => MobileAppAiReplyStyle::clampReply($msg),
            'cart_updated' => true,
            'ui' => $remaining > 0 ? $this->cartActionsUi() : MobileAppAiConversationalResponder::homeActionsUi(),
        ];
    }

    /**
     * @param  list<string>  $ids
     * @return array{ok: bool, customer_message: string, cart_updated: bool, ui?: mixed}
     */
    private function rescheduleLines(User $user, array $ids, string $schedule, string $label): array
    {
        if ($schedule === '') {
            return [
                'ok' => false,
                'customer_message' => 'I need a valid date/time — try **tomorrow 5pm** or **ASAP**.',
                'cart_updated' => false,
            ];
        }

        $customerId = (string) $user->id;
        $updated = Cart::query()
            ->where('customer_id', $customerId)
            ->where('is_guest', false)
            ->whereIn('id', $ids)
            ->update(['service_schedule' => $schedule]);

        if ($updated === 0) {
            return [
                'ok' => false,
                'customer_message' => 'I could not update the visit time — open **Cart** on Home to change it there.',
                'cart_updated' => false,
            ];
        }

        $info = CartServiceInfo::query()->where('customer_id', $customerId)->first();
        if ($info !== null && count($ids) === Cart::query()->where('customer_id', $customerId)->count()) {
            $info->service_schedule = $schedule;
            $info->save();
        }

        return [
            'ok' => true,
            'customer_message' => MobileAppAiReplyStyle::clampReply(
                'Updated visit time to **'.$label.'** for '.$updated.' item(s). Open **Cart** on Home to review before checkout.'
            ),
            'cart_updated' => true,
            'ui' => $this->cartActionsUi(),
        ];
    }

    /**
     * @param  list<array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}>  $lines
     * @param  array{op: string, target: string, schedule_text: string, cart_line_ids?: list<string>, cart_filter?: string}  $parsed
     * @return array{ids: list<string>, labels: list<string>, error?: string}
     */
    private function resolveTargetLines(array $lines, array $parsed): array
    {
        $lineIds = array_values(array_filter((array) ($parsed['cart_line_ids'] ?? [])));
        if ($lineIds !== []) {
            $byId = [];
            foreach ($lines as $line) {
                $byId[(string) ($line['cart_line_id'] ?? '')] = $line;
            }
            $matched = [];
            foreach ($lineIds as $id) {
                if (isset($byId[$id])) {
                    $matched[] = $byId[$id];
                }
            }
            if ($matched !== []) {
                return [
                    'ids' => array_column($matched, 'cart_line_id'),
                    'labels' => array_map(fn (array $l): string => $this->lineLabel($l), $matched),
                ];
            }
        }

        $filter = trim((string) ($parsed['cart_filter'] ?? ''));
        if ($filter !== '') {
            $filtered = $this->linesMatchingCartFilter($lines, $filter);
            if ($filtered === []) {
                return [
                    'ids' => [],
                    'labels' => [],
                    'error' => $this->cartFilterEmptyMessage($filter),
                ];
            }

            return [
                'ids' => array_column($filtered, 'cart_line_id'),
                'labels' => array_map(fn (array $l): string => $this->lineLabel($l), $filtered),
            ];
        }

        if ($parsed['op'] === 'clear_all') {
            return [
                'ids' => array_column($lines, 'cart_line_id'),
                'labels' => array_map(fn (array $l): string => $this->lineLabel($l), $lines),
            ];
        }

        if ($parsed['op'] === 'keep_one') {
            return $this->resolveKeepOneRemoval($lines, $parsed);
        }

        if ($parsed['op'] === 'keep_only') {
            $keep = mb_strtolower(trim($parsed['target']));
            if ($keep === '') {
                return [
                    'ids' => [],
                    'labels' => [],
                    'error' => 'Which item should stay in your cart? For example: **keep only inverter**.',
                ];
            }

            $toRemove = [];
            foreach ($lines as $line) {
                $name = mb_strtolower((string) ($line['service_name'] ?? ''));
                if (! str_contains($name, $keep) && ! self::nameMatchesLoose($name, $keep)) {
                    $toRemove[] = $line;
                }
            }

            if ($toRemove === []) {
                return [
                    'ids' => [],
                    'labels' => [],
                    'error' => 'Everything in your cart already matches **'.$parsed['target'].'**, or I could not find other items to remove.',
                ];
            }

            return [
                'ids' => array_column($toRemove, 'cart_line_id'),
                'labels' => array_map(fn (array $l): string => $this->lineLabel($l), $toRemove),
            ];
        }

        $target = mb_strtolower(trim($parsed['target']));
        if ($target === '' && $parsed['op'] === 'remove') {
            return [
                'ids' => [],
                'labels' => [],
                'error' => '__which_remove__',
            ];
        }

        if ($parsed['op'] === 'reschedule' && $target === '') {
            return [
                'ids' => array_column($lines, 'cart_line_id'),
                'labels' => array_map(fn (array $l): string => $this->lineLabel($l), $lines),
            ];
        }

        $matched = [];
        foreach ($lines as $line) {
            $name = mb_strtolower((string) ($line['service_name'] ?? ''));
            $variant = mb_strtolower((string) ($line['variant_key'] ?? ''));
            if (self::nameMatchesLoose($name, $target)
                || str_contains($variant, str_replace(' ', '-', $target))) {
                $matched[] = $line;
            }
        }

        if ($matched === []) {
            return [
                'ids' => [],
                'labels' => [],
                'error' => 'I could not find **'.$parsed['target'].'** in your cart. Say **show my cart** to see item names.',
            ];
        }

        return [
            'ids' => array_column($matched, 'cart_line_id'),
            'labels' => array_map(fn (array $l): string => $this->lineLabel($l), $matched),
        ];
    }

    /**
     * @param  list<array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}>  $lines
     * @param  array{op: string, target: string, schedule_text: string, cart_line_ids?: list<string>, cart_filter?: string}  $parsed
     */
    private function assessCartActionResolution(array $lines, array $parsed): MobileAppAiActionResolutionResult
    {
        $match = $this->resolveTargetLines($lines, $parsed);
        if (($match['ids'] ?? []) === []) {
            return new MobileAppAiActionResolutionResult(
                MobileAppAiActionResolutionResult::NOT_FOUND,
                [],
                (string) ($match['error'] ?? 'I could not find that in your cart. Say **show my cart** to see item names.'),
                MobileAppAiIntentDomainCatalog::CART
            );
        }

        if (($parsed['op'] ?? '') === 'remove' && count($match['ids']) > 1
            && trim((string) ($parsed['cart_filter'] ?? '')) === ''
            && ($parsed['cart_line_ids'] ?? []) === []) {
            $labels = implode(', ', $match['labels'] ?? []);

            return new MobileAppAiActionResolutionResult(
                MobileAppAiActionResolutionResult::AMBIGUOUS,
                $this->entitiesFromIds($lines, $match['ids']),
                'I found **'.count($match['ids']).'** items matching that ('.$labels.'). Which one should I remove? Be more specific.',
                MobileAppAiIntentDomainCatalog::CART
            );
        }

        return new MobileAppAiActionResolutionResult(
            MobileAppAiActionResolutionResult::RESOLVED,
            $this->entitiesFromIds($lines, $match['ids']),
            '',
            MobileAppAiIntentDomainCatalog::CART
        );
    }

    /**
     * @param  list<array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}>  $lines
     * @param  list<string>  $ids
     * @return list<array{label: string, cart_line_id: string}>
     */
    private function entitiesFromIds(array $lines, array $ids): array
    {
        $entities = [];
        foreach ($lines as $line) {
            $id = (string) ($line['cart_line_id'] ?? '');
            if (in_array($id, $ids, true)) {
                $entities[] = [
                    'cart_line_id' => $id,
                    'label' => $this->lineLabel($line),
                ];
            }
        }

        return $entities;
    }

    /**
     * @param  list<array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}>  $lines
     * @return list<array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}>
     */
    private function linesMatchingCartFilter(array $lines, string $filter): array
    {
        $now = Carbon::now();
        $matched = [];
        foreach ($lines as $line) {
            $raw = $line['service_schedule'] ?? null;
            $hasSchedule = is_string($raw) && trim($raw) !== '';
            $isPast = false;
            $isFuture = false;
            if ($hasSchedule) {
                try {
                    $at = Carbon::parse($raw);
                    $isPast = $at->lt($now);
                    $isFuture = $at->gte($now);
                } catch (\Throwable) {
                    $hasSchedule = false;
                }
            }

            $include = match ($filter) {
                'visit_before_now' => $hasSchedule && $isPast,
                'visit_after_now' => $hasSchedule && $isFuture,
                'no_schedule' => ! $hasSchedule,
                default => false,
            };
            if ($include) {
                $matched[] = $line;
            }
        }

        return $matched;
    }

    private function cartFilterEmptyMessage(string $filter): string
    {
        return match ($filter) {
            'visit_before_now' => 'None of your cart visits are in the past right now. Say **show my cart** to review times.',
            'visit_after_now' => 'I did not find any upcoming visits in your cart.',
            'no_schedule' => 'Every cart item already has a visit time set.',
            default => 'I could not match any cart items for that request. Say **show my cart** to see what is there.',
        };
    }

    /**
     * @return list<array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}>
     */
    private function loadCartLines(User $user): array
    {
        $summary = $this->cartService->cartSummaryForUser($user);
        $out = [];
        foreach ($summary['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $out[] = [
                'cart_line_id' => (string) ($item['cart_line_id'] ?? ''),
                'service_name' => (string) ($item['service_name'] ?? 'Service'),
                'variant_key' => (string) ($item['variant_key'] ?? ''),
                'line_total' => (float) ($item['line_total'] ?? 0),
                'service_schedule' => isset($item['service_schedule']) ? (string) $item['service_schedule'] : null,
            ];
        }

        return array_values(array_filter($out, static fn (array $l): bool => $l['cart_line_id'] !== ''));
    }

    /**
     * @param  array<string, mixed>  $pending
     */
    private function describePending(array $pending): string
    {
        $count = count((array) ($pending['labels'] ?? []));
        $op = (string) ($pending['op'] ?? '');

        return match ($op) {
            'clear_all' => 'clear your entire cart ('.$count.' item'.($count === 1 ? '' : 's').')',
            'keep_one' => 'keep 1 item and remove '.$count.' duplicate'.($count === 1 ? '' : 's'),
            'remove', 'keep_only' => 'remove '.$count.' item'.($count === 1 ? '' : 's').' from your cart',
            'reschedule' => 'change the visit time to **'.((string) ($pending['schedule_label'] ?? 'the new time')).'** for '.$count.' item'.($count === 1 ? '' : 's'),
            default => 'update your cart',
        };
    }

    /**
     * @param  array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}  $line
     */
    private function lineLabel(array $line): string
    {
        $name = (string) ($line['service_name'] ?? 'Service');
        $when = $line['service_schedule'] ?? null;
        $suffix = $when !== null && $when !== '' ? ' — '.date('j M, g:i A', strtotime($when)) : '';

        return '• '.$name.' (₹'.number_format((float) ($line['line_total'] ?? 0), 2).')'.$suffix;
    }

    /**
     * @param  list<string>  $labels
     */
    private function formatLineList(array $labels): string
    {
        return implode("\n", array_slice($labels, 0, 6));
    }

    private function resetDraft(MobileAppAiConversation $conversation): void
    {
        $conversation->booking_draft = ['step' => 'idle', 'choices' => []];
        $conversation->save();
    }

    /**
     * @param  list<array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}>  $lines
     * @param  array{op: string, target: string, schedule_text: string, cart_line_ids?: list<string>, cart_filter?: string}  $parsed
     * @return array{ids: list<string>, labels: list<string>, error?: string}
     */
    private function resolveKeepOneRemoval(array $lines, array $parsed): array
    {
        $scope = mb_strtolower(trim($parsed['target'] ?? ''));
        $group = $this->linesInScope($lines, $scope);

        if (count($group) <= 1) {
            $fallback = $scope === '' ? $this->largestDuplicateGroup($lines) : null;
            if (is_array($fallback) && count($fallback) > 1) {
                $group = $fallback;
            }
        }

        if (count($group) <= 1) {
            $hint = $scope !== ''
                ? 'I only found one **'.$parsed['target'].'** item — nothing extra to remove.'
                : 'I need at least 2 similar items to keep one and delete the rest. Say **show my cart** to review.';

            return ['ids' => [], 'labels' => [], 'error' => $hint];
        }

        $keep = $this->pickLineToKeep($group);
        $keepId = (string) ($keep['cart_line_id'] ?? '');
        $toRemove = array_values(array_filter(
            $group,
            static fn (array $line): bool => (string) ($line['cart_line_id'] ?? '') !== $keepId
        ));

        return [
            'ids' => array_column($toRemove, 'cart_line_id'),
            'labels' => array_map(fn (array $l): string => $this->lineLabel($l), $toRemove),
        ];
    }

    /**
     * @param  list<array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}>  $lines
     * @return list<array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}>
     */
    private function linesInScope(array $lines, string $scope): array
    {
        if ($scope === '') {
            return $lines;
        }

        return array_values(array_filter(
            $lines,
            fn (array $line): bool => self::nameMatchesLoose(
                mb_strtolower((string) ($line['service_name'] ?? '')),
                $scope
            )
        ));
    }

    /**
     * @param  list<array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}>  $lines
     * @return list<array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}>|null
     */
    private function largestDuplicateGroup(array $lines): ?array
    {
        $byName = [];
        foreach ($lines as $line) {
            $key = mb_strtolower(trim((string) ($line['service_name'] ?? '')));
            if ($key === '') {
                continue;
            }
            $byName[$key][] = $line;
        }

        $best = null;
        $bestCount = 1;
        foreach ($byName as $group) {
            if (count($group) > $bestCount) {
                $bestCount = count($group);
                $best = $group;
            }
        }

        return $best;
    }

    /**
     * @param  list<array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}>  $lines
     * @return array{cart_line_id: string, service_name: string, variant_key: string, line_total: float, service_schedule: ?string}
     */
    private function pickLineToKeep(array $lines): array
    {
        $now = Carbon::now();
        usort($lines, static function (array $a, array $b) use ($now): int {
            $ta = self::scheduleSortKey($a['service_schedule'] ?? null, $now);
            $tb = self::scheduleSortKey($b['service_schedule'] ?? null, $now);

            return $ta <=> $tb;
        });

        return $lines[0];
    }

    private static function scheduleSortKey(mixed $raw, Carbon $now): int
    {
        if (! is_string($raw) || trim($raw) === '') {
            return PHP_INT_MAX;
        }

        try {
            $at = Carbon::parse($raw);

            return $at->gte($now) ? $at->timestamp : ($at->timestamp + 2_000_000_000);
        } catch (\Throwable) {
            return PHP_INT_MAX - 1;
        }
    }

    private static function nameMatchesLoose(string $serviceName, string $target): bool
    {
        if ($target === '' || $serviceName === '') {
            return false;
        }

        $target = self::normalizeCartMatchTarget($target);
        $serviceName = mb_strtolower(trim($serviceName));
        if ($target === '') {
            return false;
        }

        if (str_contains($serviceName, $target)) {
            return true;
        }

        $words = array_values(array_filter(preg_split('/\s+/u', $target) ?: [], static fn (string $w): bool => mb_strlen($w) >= 3));
        if ($words === []) {
            return false;
        }

        $hits = 0;
        foreach ($words as $word) {
            if (str_contains($serviceName, mb_strtolower($word))) {
                $hits++;
            }
        }

        return $hits >= max(1, (int) ceil(count($words) * 0.6));
    }

    private static function normalizeCartMatchTarget(string $target): string
    {
        $t = mb_strtolower(trim($target));
        $t = (string) preg_replace(
            '/\b(wali|wala|wale|walee|ko|se|the|one|ones|item|items|service|services|line|lines)\b/iu',
            ' ',
            $t
        );

        return trim((string) preg_replace('/\s+/', ' ', $t));
    }

    private function buildConfirmPrompt(string $summary, string $listed, string $userText): string
    {
        if (MobileAppAiReplyStyle::prefersHinglish($userText)) {
            return MobileAppAiReplyStyle::clampReply(
                'Confirm karein — '.$summary.':'."\n\n".$listed."\n\n**Yes, go ahead** tap karein ya **yes** likhein. **Cancel** agar mann badal gaya."
            );
        }

        return MobileAppAiReplyStyle::clampReply(
            'Just to confirm — '.$summary.':'."\n\n".$listed."\n\nTap **Yes, go ahead** or reply **yes**. Tap **Cancel** if you changed your mind."
        );
    }

    private function buildReconfirmPrompt(string $summary, string $userText): string
    {
        if (MobileAppAiReplyStyle::prefersHinglish($userText)) {
            return MobileAppAiReplyStyle::clampReply(
                'Please confirm — **Yes, go ahead** tap karein '.$summary.' ke liye, ya **Cancel** cart waise hi rakhne ke liye.'
            );
        }

        return MobileAppAiReplyStyle::clampReply(
            'Please confirm — tap **Yes, go ahead** to '.$summary.', or **Cancel** to keep your cart as it is.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function confirmUi(string $userText = ''): array
    {
        $hinglish = MobileAppAiReplyStyle::prefersHinglish($userText);

        return [
            'type' => 'cart_confirm',
            'step' => 'cart_confirm',
            'compact' => true,
            'layout' => 'actions',
            'actions' => [
                [
                    'action' => 'confirm_cart_action',
                    'choice' => 'yes',
                    'label' => $hinglish ? 'Haan, kar do' : 'Yes, go ahead',
                    'style' => 'primary',
                    'icon' => 'check',
                ],
                [
                    'action' => 'cancel_cart_action',
                    'choice' => 'no',
                    'label' => $hinglish ? 'Cancel' : 'Cancel',
                    'style' => 'outline',
                    'icon' => 'close',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cartActionsUi(string $userText = ''): array
    {
        $hinglish = MobileAppAiReplyStyle::prefersHinglish($userText);

        return [
            'type' => 'assistant_actions',
            'layout' => 'actions',
            'actions' => [
                [
                    'action' => 'open_cart',
                    'label' => $hinglish ? 'Cart kholen' : 'Open cart',
                    'style' => 'primary',
                    'icon' => 'shopping_cart',
                ],
                [
                    'action' => 'start_booking',
                    'label' => $hinglish ? 'Aur service book karein' : 'Book another service',
                    'style' => 'outline',
                    'icon' => 'home_repair_service',
                ],
                [
                    'action' => 'troubleshoot',
                    'label' => $hinglish ? 'App help' : 'App help',
                    'style' => 'outline',
                    'icon' => 'help_outline',
                ],
            ],
        ];
    }

    /**
     * @param  list<array{label: string, cart_line_id: string}>  $entities
     * @return array<string, mixed>
     */
    private function cartLinePickUi(array $entities): array
    {
        $cards = [];
        foreach (array_slice($entities, 0, 6) as $entity) {
            $cards[] = [
                'choice' => (string) ($entity['cart_line_id'] ?? ''),
                'title' => (string) ($entity['label'] ?? 'Cart item'),
                'subtitle' => 'Remove this line',
                'icon' => 'remove_circle_outline',
            ];
        }

        return [
            'type' => 'cart_line_pick',
            'layout' => 'cards',
            'compact' => true,
            'cards' => $cards,
            'footer_actions' => [
                ['action' => 'open_cart', 'label' => 'Open cart', 'style' => 'outline', 'icon' => 'shopping_cart'],
            ],
        ];
    }
}
