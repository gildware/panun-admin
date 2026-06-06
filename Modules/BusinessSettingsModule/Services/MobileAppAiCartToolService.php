<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\UserManagement\Entities\User;

/**
 * Gemini tool: manage_customer_cart — Gemini understands; server validates, confirms, executes.
 */
class MobileAppAiCartToolService
{
    public function __construct(
        protected MobileAppAiCartManageService $cartManage,
        protected MobileAppAiCartActionResolver $cartActionResolver,
        protected MobileAppAiPricingReply $pricingReply,
        protected MobileAppAiCustomerSnapshotService $snapshot,
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

        $action = mb_strtolower(trim((string) ($args['action'] ?? 'view')));
        $message = trim((string) ($args['message'] ?? ''));
        $confirmed = filter_var($args['confirmed'] ?? false, FILTER_VALIDATE_BOOL);

        if ($action === 'view' || $action === 'summary') {
            $priced = $this->pricingReply->build($user, $message);

            return array_merge($priced, [
                '_instruction' => 'Relay customer_message in the customer language. Summarize cart naturally.',
            ]);
        }

        if ($action === 'cancel_pending') {
            return $this->cartManage->cancelPending($conversation);
        }

        if ($action === 'confirm_pending') {
            return $this->cartManage->confirmPending($user, $conversation);
        }

        if ($message === '' && $action !== '') {
            $message = match ($action) {
                'clear_all', 'empty', 'clear' => 'clear my cart',
                'remove' => trim((string) ($args['remove_target'] ?? $args['target'] ?? 'remove from cart')),
                'reschedule' => trim((string) ($args['schedule_text'] ?? 'reschedule')).' '
                    .trim((string) ($args['target'] ?? '')),
                default => $action,
            };
        }

        if ($confirmed) {
            $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
            if (($draft['step'] ?? '') === 'cart_confirm') {
                return $this->cartManage->confirmPending($user, $conversation->fresh() ?? $conversation);
            }

            $parsed = $this->resolveParsed($user, $args, $message);
            if ($parsed === null) {
                return ['ok' => false, 'customer_message' => 'Say what to change in the cart (clear all, remove a service, or new visit time).'];
            }

            return $this->executeWithoutSecondConfirm($user, $conversation, $parsed);
        }

        $parsed = $this->resolveParsed($user, $args, $message);
        if ($parsed === null) {
            $snap = $this->snapshot->build($user);

            return [
                'ok' => false,
                'customer_message' => 'I need a clearer cart action. Pass **op** (remove, keep_one, clear_all, reschedule, view) plus **cart_line_ids** or **cart_filter** from the session cart catalog.',
                '_instruction' => 'Re-call manage_customer_cart with structured op + cart_line_ids/cart_filter/remove_target. Do not guess. Customer said: '.($message !== '' ? $message : '(no message)'),
                'cart_count' => (int) ($snap['cart_count'] ?? 0),
            ];
        }

        $result = $this->cartManage->executeParsed($user, $conversation, $parsed, $message);
        if ($result === null) {
            return ['ok' => false, 'customer_message' => 'I could not start that cart change. Try again in the chat.'];
        }

        return [
            'ok' => true,
            'customer_message' => $result['reply'],
            'cart_updated' => $result['cart_updated'],
            'ui' => $result['ui'] ?? null,
            '_instruction' => 'Relay customer_message exactly in the customer language. Never mention tools. If asking to confirm, wait for yes or button tap before confirmed=true.',
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{op: string, target: string, schedule_text: string, cart_line_ids: list<string>, cart_filter: string}|null
     */
    private function resolveParsed(User $user, array $args, string $message): ?array
    {
        $fromArgs = $this->buildParsedFromArgs($args);
        if ($fromArgs !== null) {
            return $fromArgs;
        }

        if ($message !== '') {
            return $this->cartActionResolver->resolveMessage($user, $message);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{op: string, target: string, schedule_text: string, cart_line_ids: list<string>, cart_filter: string}|null
     */
    private function buildParsedFromArgs(array $args): ?array
    {
        $explicitOp = mb_strtolower(trim((string) ($args['op'] ?? '')));
        $action = mb_strtolower(trim((string) ($args['action'] ?? '')));

        $op = $explicitOp !== '' ? $explicitOp : match ($action) {
            'view', 'summary' => 'view',
            'clear', 'clear_all', 'empty' => 'clear_all',
            'remove' => 'remove',
            'keep_only' => 'keep_only',
            'keep_one' => 'keep_one',
            'reschedule' => 'reschedule',
            default => '',
        };

        if ($op === '' || $op === 'view') {
            return $op === 'view' ? [
                'op' => 'view',
                'target' => '',
                'schedule_text' => '',
                'cart_line_ids' => [],
                'cart_filter' => '',
            ] : null;
        }

        $ids = [];
        foreach ((array) ($args['cart_line_ids'] ?? []) as $id) {
            if (is_string($id) && trim($id) !== '') {
                $ids[] = trim($id);
            }
        }

        $keepTarget = trim((string) ($args['keep_target'] ?? ''));
        $removeTarget = trim((string) ($args['remove_target'] ?? $args['target'] ?? $args['scope_target'] ?? ''));
        $filter = trim((string) ($args['cart_filter'] ?? ''));
        if ($filter === 'none') {
            $filter = '';
        }

        return [
            'op' => $op,
            'target' => $op === 'keep_only' && $keepTarget !== '' ? $keepTarget : $removeTarget,
            'schedule_text' => trim((string) ($args['schedule_text'] ?? '')),
            'cart_line_ids' => $ids,
            'cart_filter' => in_array($filter, ['visit_before_now', 'visit_after_now', 'no_schedule'], true) ? $filter : '',
        ];
    }

    /**
     * @param  array{op: string, target: string, schedule_text: string, cart_line_ids?: list<string>, cart_filter?: string}  $parsed
     * @return array<string, mixed>
     */
    private function executeWithoutSecondConfirm(User $user, MobileAppAiConversation $conversation, array $parsed): array
    {
        $match = $this->cartManage->matchTargets($user, $parsed);
        if ($match['ids'] === []) {
            return [
                'ok' => false,
                'customer_message' => $match['error'] ?? 'No matching cart items found.',
            ];
        }

        $pending = [
            'op' => $parsed['op'],
            'cart_line_ids' => $match['ids'],
            'labels' => $match['labels'],
            'schedule_text' => $parsed['schedule_text'],
        ];

        if ($parsed['op'] === 'reschedule') {
            $schedule = MobileAppAiSchedulePhraseParser::parse($parsed['schedule_text']);
            if (! ($schedule['ok'] ?? false)) {
                return ['ok' => false, 'customer_message' => 'I need a valid date/time (e.g. tomorrow 5pm, ASAP).'];
            }
            $pending['normalized_schedule'] = (string) ($schedule['schedule'] ?? '');
            $pending['schedule_label'] = (string) ($schedule['label'] ?? '');
        }

        $conversation->booking_draft = [
            'step' => 'cart_confirm',
            'choices' => ['cart_pending' => $pending],
        ];
        $conversation->save();

        return $this->cartManage->confirmPending($user, $conversation->fresh() ?? $conversation);
    }
}
