<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\ProviderManagement\Entities\Provider;

/**
 * Queues WhatsApp automation for admin confirmation: preview rows + token, then {@see execute()} runs the real sends.
 */
class BookingWhatsAppAdminPromptService
{
    public const CACHE_PREFIX = 'wa:admin_booking_prompt:';

    public function __construct(
        protected BookingWhatsAppNotificationService $notifications,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array<string, mixed>>  $ops
     * @return array{token: string, title: string, rows: array<int, array<string, mixed>>}|null
     */
    public function storePrompt(string $title, array $rows, array $ops): ?array
    {
        if ($rows === []) {
            return null;
        }

        $token = (string) Str::uuid();
        Cache::put(self::CACHE_PREFIX.$token, [
            'admin_user_id' => auth()->id(),
            'title' => $title,
            'rows' => $rows,
            'ops' => $ops,
        ], now()->addMinutes(30));

        return [
            'token' => $token,
            'title' => $title,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array{rows: array<int, array<string, mixed>>, ops: array<int, array<string, mixed>>}|null  $compiled
     */
    public function finalizeCompile(?array $compiled): ?array
    {
        if ($compiled === null || ($compiled['rows'] ?? []) === []) {
            return null;
        }

        return $this->storePrompt(
            translate('WhatsApp_admin_send_message_modal_title'),
            $compiled['rows'],
            $compiled['ops'] ?? []
        );
    }

    /**
     * @param  array<int, array{rows: array<int, array<string, mixed>>, ops: array<int, array<string, mixed>>}|null>  $parts
     */
    public function mergePromptCompilations(array $parts): ?array
    {
        $rows = [];
        $ops = [];
        foreach ($parts as $p) {
            if (! is_array($p) || ($p['rows'] ?? []) === []) {
                continue;
            }
            foreach ($p['rows'] as $r) {
                $rows[] = $r;
            }
            foreach ($p['ops'] ?? [] as $o) {
                $ops[] = $o;
            }
        }
        if ($rows === []) {
            return null;
        }

        return $this->storePrompt(
            translate('WhatsApp_admin_send_message_modal_title'),
            $rows,
            $ops
        );
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function execute(string $token): array
    {
        $bag = Cache::pull(self::CACHE_PREFIX.$token);
        if (! is_array($bag)) {
            return ['ok' => false, 'message' => translate('Request expired or invalid. Please try the action again.')];
        }
        if ((int) ($bag['admin_user_id'] ?? 0) !== (int) auth()->id()) {
            return ['ok' => false, 'message' => translate('Access_denied')];
        }

        $ops = $bag['ops'] ?? [];
        if (! is_array($ops)) {
            return ['ok' => false, 'message' => translate('Request expired or invalid. Please try the action again.')];
        }

        foreach ($ops as $op) {
            if (is_array($op)) {
                $this->dispatchOp($op);
            }
        }

        return ['ok' => true, 'message' => translate('WhatsApp_admin_messages_sent')];
    }

    /**
     * Send or skip one queued row; updates cache until all rows are cleared.
     *
     * @return array{ok: bool, message: string, remaining?: int}
     */
    public function removePromptRow(string $token, int $index, bool $runOp): array
    {
        $cacheKey = self::CACHE_PREFIX.$token;
        $bag = Cache::get($cacheKey);
        if (! is_array($bag)) {
            return ['ok' => false, 'message' => translate('Request expired or invalid. Please try the action again.')];
        }
        if ((int) ($bag['admin_user_id'] ?? 0) !== (int) auth()->id()) {
            return ['ok' => false, 'message' => translate('Access_denied')];
        }
        $rows = $bag['rows'] ?? [];
        $ops = $bag['ops'] ?? [];
        if (! is_array($rows) || ! is_array($ops) || $index < 0 || $index >= count($ops)) {
            return ['ok' => false, 'message' => translate('Request expired or invalid. Please try the action again.')];
        }

        if ($runOp) {
            $this->dispatchOp($ops[$index]);
        }

        array_splice($rows, $index, 1);
        array_splice($ops, $index, 1);

        if ($rows === []) {
            Cache::forget($cacheKey);
        } else {
            Cache::put($cacheKey, array_merge($bag, ['rows' => $rows, 'ops' => $ops]), now()->addMinutes(30));
        }

        $msg = $runOp
            ? translate('WhatsApp_admin_message_row_sent')
            : translate('WhatsApp_admin_message_row_skipped');

        return ['ok' => true, 'message' => $msg, 'remaining' => count($rows)];
    }

    /**
     * @param  array<string, mixed>  $op
     */
    protected function dispatchOp(array $op): void
    {
        $kind = (string) ($op['kind'] ?? '');
        $wa = $this->notifications;

        match ($kind) {
            'wa_meta' => $this->dispatchWaMeta($op),
            'wa_booking_status_party' => $this->dispatchWaBookingStatusParty($op),
            'wa_repeat_status_party' => $this->dispatchWaRepeatStatusParty($op),
            'wa_booking_schedule_meta' => $this->dispatchWaBookingScheduleMeta($op),
            'wa_repeat_schedule_meta' => $this->dispatchWaRepeatScheduleMeta($op),
            'wa_provider_change_meta' => $this->dispatchWaProviderChangeMeta($op),
            'wa_payment_added_meta' => $this->dispatchWaPaymentAddedMeta($op),
            'refund' => $this->runRefund($wa, $op),
            'wa_reopen_resolved_party' => $this->dispatchWaReopenResolvedParty($op),
            'wa_disputed_refund_party' => $this->dispatchWaDisputedRefundParty($op),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $op
     */
    protected function dispatchWaMeta(array $op): void
    {
        $id = (string) ($op['booking_id'] ?? '');
        $mk = (string) ($op['message_key'] ?? '');
        if ($id === '' || $mk === '') {
            return;
        }
        $booking = Booking::query()
            ->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments'])
            ->find($id);
        if (! $booking) {
            return;
        }
        $vars = $this->notifications->buildReplacements($booking, null);
        $prevProvId = $op['previous_provider_id'] ?? null;
        $phone = $this->resolvePhoneForMetaMessageKey($booking, $mk, $prevProvId);

        $this->notifications->adminPromptTrySendMetaKey($booking, $mk, $vars, $phone);
    }

    /**
     * @param  array<string, mixed>  $op
     */
    protected function dispatchWaBookingStatusParty(array $op): void
    {
        $id = (string) ($op['booking_id'] ?? '');
        $prev = (string) ($op['previous_booking_status'] ?? '');
        $party = (string) ($op['party'] ?? '');
        if ($id === '' || ($party !== 'customer' && $party !== 'provider')) {
            return;
        }
        $booking = Booking::query()
            ->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments'])
            ->find($id);
        if (! $booking) {
            return;
        }
        $this->notifications->adminPromptDeliverBookingStatusParty($booking, $prev, $party, []);
    }

    /**
     * @param  array<string, mixed>  $op
     */
    protected function dispatchWaRepeatStatusParty(array $op): void
    {
        $id = (string) ($op['booking_repeat_id'] ?? '');
        $prev = (string) ($op['previous_booking_status'] ?? '');
        $party = (string) ($op['party'] ?? '');
        if ($id === '' || ($party !== 'customer' && $party !== 'provider')) {
            return;
        }
        $repeat = BookingRepeat::query()
            ->with([
                'booking.customer',
                'booking.service_address',
                'booking.detail',
                'booking.booking_partial_payments',
                'booking',
                'detail',
                'provider.owner',
                'serviceman.user',
            ])
            ->find($id);
        if (! $repeat) {
            return;
        }
        $this->notifications->adminPromptDeliverRepeatStatusParty($repeat, $prev, $party);
    }

    /**
     * @param  array<string, mixed>  $op
     */
    protected function dispatchWaBookingScheduleMeta(array $op): void
    {
        $id = (string) ($op['booking_id'] ?? '');
        $mk = (string) ($op['message_key'] ?? '');
        if ($id === '' || $mk === '') {
            return;
        }
        $booking = Booking::query()
            ->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments', 'serviceman.user'])
            ->find($id);
        if (! $booking) {
            return;
        }
        $prevRaw = array_key_exists('previous_service_schedule_raw', $op)
            ? ($op['previous_service_schedule_raw'] !== null ? (string) $op['previous_service_schedule_raw'] : null)
            : null;
        $vars = array_merge($this->notifications->buildReplacements($booking, null), [
            '{previous_service_schedule}' => $this->notifications->formatScheduleToken($prevRaw),
        ]);
        $phone = $this->resolvePhoneForMetaMessageKey($booking, $mk, null);
        $this->notifications->adminPromptTrySendMetaKey($booking, $mk, $vars, $phone);
    }

    /**
     * @param  array<string, mixed>  $op
     */
    protected function dispatchWaRepeatScheduleMeta(array $op): void
    {
        $id = (string) ($op['booking_repeat_id'] ?? '');
        $mk = (string) ($op['message_key'] ?? '');
        if ($id === '' || $mk === '') {
            return;
        }
        $repeat = BookingRepeat::query()
            ->with([
                'booking.customer',
                'booking.service_address',
                'booking.detail',
                'booking.booking_partial_payments',
                'booking',
                'detail',
                'provider.owner',
                'serviceman.user',
            ])
            ->find($id);
        $parent = $repeat?->booking;
        if (! $repeat || ! $parent) {
            return;
        }
        $prevRaw = array_key_exists('previous_service_schedule_raw', $op)
            ? ($op['previous_service_schedule_raw'] !== null ? (string) $op['previous_service_schedule_raw'] : null)
            : null;
        $vars = array_merge($this->notifications->buildRepeatRowReplacements($repeat, $parent), [
            '{previous_service_schedule}' => $this->notifications->formatScheduleToken($prevRaw),
        ]);
        $phone = $this->resolvePhoneForMetaMessageKey($parent, $mk, null);
        $this->notifications->adminPromptTrySendMetaKey($parent, $mk, $vars, $phone);
    }

    /**
     * @param  array<string, mixed>  $op
     */
    protected function dispatchWaProviderChangeMeta(array $op): void
    {
        $id = (string) ($op['booking_id'] ?? '');
        $mk = (string) ($op['message_key'] ?? '');
        if ($id === '' || $mk === '') {
            return;
        }
        $booking = Booking::query()
            ->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments'])
            ->find($id);
        if (! $booking) {
            return;
        }
        $prevId = $op['previous_provider_id'] ?? null;
        $previous = $prevId ? Provider::query()->with('owner')->find($prevId) : null;
        $vars = array_merge(
            $this->notifications->buildReplacements($booking, null),
            $this->notifications->previousProviderReplacementMap($previous)
        );
        $phone = $this->resolvePhoneForMetaMessageKey($booking, $mk, $prevId);
        $this->notifications->adminPromptTrySendMetaKey($booking, $mk, $vars, $phone);
    }

    /**
     * @param  array<string, mixed>  $op
     */
    protected function dispatchWaPaymentAddedMeta(array $op): void
    {
        $bid = (string) ($op['booking_id'] ?? '');
        $pid = (string) ($op['partial_payment_id'] ?? '');
        $mk = (string) ($op['message_key'] ?? '');
        if ($bid === '' || $pid === '' || $mk === '') {
            return;
        }
        $booking = Booking::query()
            ->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments'])
            ->find($bid);
        $partial = BookingPartialPayment::query()->where('booking_id', $bid)->whereKey($pid)->first();
        if (! $booking || ! $partial) {
            return;
        }
        $meta = is_array($op['meta'] ?? null) ? $op['meta'] : [];
        $amount = (float) ($partial->paid_amount ?? 0);
        $amountLabel = function_exists('with_currency_symbol') ? with_currency_symbol($amount) : (string) $amount;
        $receivedBy = strtolower(trim((string) ($partial->received_by ?? '')));
        $receivedByLabel = match ($receivedBy) {
            'company' => translate('Company'),
            'provider' => translate('Provider'),
            default => $receivedBy !== '' ? ucfirst($receivedBy) : '—',
        };
        $date = trim((string) ($meta['date'] ?? ''));
        if ($date === '') {
            $date = $partial->created_at ? $partial->created_at->toDateString() : now()->toDateString();
        }
        $method = trim((string) ($meta['payment_method'] ?? ''));
        if ($method === '') {
            $method = '—';
        }
        $reference = trim((string) ($meta['reference_id'] ?? ''));
        if ($reference === '') {
            $reference = (string) ($partial->transaction_id ?? '');
        }
        if ($reference === '') {
            $reference = '—';
        }
        $vars = array_merge($this->notifications->buildReplacements($booking, null), [
            '{amount_added}' => $amountLabel,
            '{payment_received_by}' => $receivedByLabel,
            '{payment_date}' => $date,
            '{payment_method}' => $method,
            '{payment_reference}' => $reference,
        ]);
        $phone = $this->resolvePhoneForMetaMessageKey($booking, $mk, null);
        $this->notifications->adminPromptTrySendMetaKey($booking, $mk, $vars, $phone);
    }

    /**
     * @param  array<string, mixed>  $op
     */
    protected function dispatchWaReopenResolvedParty(array $op): void
    {
        $id = (string) ($op['booking_id'] ?? '');
        $party = (string) ($op['party'] ?? '');
        if ($id === '' || ($party !== 'customer' && $party !== 'provider')) {
            return;
        }
        $booking = Booking::query()
            ->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments'])
            ->find($id);
        if (! $booking) {
            return;
        }
        $this->notifications->adminPromptDeliverReopenResolvedParty($booking, $party, []);
    }

    /**
     * @param  array<string, mixed>  $op
     */
    protected function dispatchWaDisputedRefundParty(array $op): void
    {
        $id = (string) ($op['booking_id'] ?? '');
        $prev = (string) ($op['previous_booking_status'] ?? '');
        $party = (string) ($op['party'] ?? '');
        if ($id === '' || ($party !== 'customer' && $party !== 'provider')) {
            return;
        }
        $booking = Booking::query()
            ->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments'])
            ->find($id);
        if (! $booking) {
            return;
        }
        $this->notifications->adminPromptDeliverDisputedRefundParty($booking, $prev, $party);
    }

    /**
     * @param  mixed  $previousProviderId
     */
    protected function resolvePhoneForMetaMessageKey(Booking $booking, string $messageKey, $previousProviderId): ?string
    {
        $config = $this->notifications->getConfig();
        if ($messageKey === 'provider_change_previous_provider') {
            if ($previousProviderId === null || $previousProviderId === '') {
                return null;
            }
            $prov = Provider::query()->with('owner')->find($previousProviderId);

            return $this->notifications->resolveProviderPhone($prov, $config);
        }
        if (str_ends_with($messageKey, '_customer') || str_contains($messageKey, '_customer_')) {
            return $this->notifications->normalizePhone($booking->customer?->phone, $config);
        }

        return $this->notifications->resolveProviderPhone($booking->provider, $config);
    }

    /**
     * @param  array<string, mixed>  $op
     */
    protected function runRefund(BookingWhatsAppNotificationService $wa, array $op): void
    {
        $id = (string) ($op['booking_id'] ?? '');
        if ($id === '') {
            return;
        }
        $booking = Booking::query()
            ->with(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments'])
            ->find($id);
        if ($booking) {
            $amount = round((float) ($op['amount'] ?? 0), 2);
            $meta = is_array($op['meta'] ?? null) ? $op['meta'] : [];
            $wa->sendBookingRefundToCustomer($booking, $amount, $meta);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function normalizePreviewRows(array $slots): array
    {
        $out = [];
        foreach ($slots as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'message_key' => (string) ($row['message_key'] ?? ''),
                'recipient_label' => (string) ($row['recipient_label'] ?? ''),
                'to_phone' => isset($row['to_phone']) ? (string) $row['to_phone'] : '',
                'template_name' => (string) ($row['template_name'] ?? ''),
                'preview_text' => (string) ($row['preview_text'] ?? ''),
                'missing_phone' => ! empty($row['missing_phone']),
            ];
        }

        return $out;
    }

    public function compileBookingConfirmationPrompt(Booking $booking): ?array
    {
        $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
        $config = $this->notifications->getConfig();
        if (empty($config['enabled'])) {
            return null;
        }
        $vars = $this->notifications->buildReplacements($booking, null);
        $slots = [];
        $c = $this->notifications->previewBookingMetaTemplateSlot(
            $config,
            'booking_confirmation_customer',
            $vars,
            $booking->customer?->phone,
            translate('Customer')
        );
        if ($c) {
            $slots[] = $c;
        }
        $p = $this->notifications->previewBookingMetaTemplateSlot(
            $config,
            'booking_confirmation_provider',
            $vars,
            $this->notifications->resolveProviderPhone($booking->provider, $config),
            translate('Provider')
        );
        if ($p) {
            $slots[] = $p;
        }
        $rows = $this->normalizePreviewRows($slots);
        if ($rows === []) {
            return null;
        }
        $ops = [];
        foreach ($slots as $slot) {
            if (is_array($slot) && ($slot['message_key'] ?? '') !== '') {
                $ops[] = [
                    'kind' => 'wa_meta',
                    'booking_id' => (string) $booking->id,
                    'message_key' => (string) $slot['message_key'],
                ];
            }
        }

        return [
            'rows' => $rows,
            'ops' => $ops,
        ];
    }

    public function buildBookingConfirmationPrompt(Booking $booking): ?array
    {
        return $this->finalizeCompile($this->compileBookingConfirmationPrompt($booking));
    }

    public function compileStatusChangePrompt(Booking $booking, string $previousBookingStatus): ?array
    {
        $newStatus = (string) $booking->booking_status;
        if ($previousBookingStatus === $newStatus) {
            return null;
        }
        $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
        $config = $this->notifications->getConfig();
        if (empty($config['enabled'])) {
            return null;
        }
        $segment = $this->notifications->resolveStatusTemplateSegment($previousBookingStatus, $newStatus, $booking);
        $vars = $this->notifications->buildReplacements($booking, $previousBookingStatus);

        $slots = [];
        $ops = [];
        $c = $this->notifications->previewDeliverStatusTemplateMessage(
            $config,
            $segment,
            'customer',
            $vars,
            $booking->customer?->phone,
            $booking,
            null,
            translate('Customer')
        );
        if ($c) {
            $slots[] = $c;
            $ops[] = [
                'kind' => 'wa_booking_status_party',
                'booking_id' => (string) $booking->id,
                'previous_booking_status' => $previousBookingStatus,
                'party' => 'customer',
            ];
        }
        $p = $this->notifications->previewDeliverStatusTemplateMessage(
            $config,
            $segment,
            'provider',
            $vars,
            $this->notifications->resolveProviderPhone($booking->provider, $config),
            $booking,
            null,
            translate('Provider')
        );
        if ($p) {
            $slots[] = $p;
            $ops[] = [
                'kind' => 'wa_booking_status_party',
                'booking_id' => (string) $booking->id,
                'previous_booking_status' => $previousBookingStatus,
                'party' => 'provider',
            ];
        }
        $rows = $this->normalizePreviewRows($slots);
        if ($rows === []) {
            return null;
        }

        return [
            'rows' => $rows,
            'ops' => $ops,
        ];
    }

    public function buildStatusChangePrompt(Booking $booking, string $previousBookingStatus): ?array
    {
        return $this->finalizeCompile($this->compileStatusChangePrompt($booking, $previousBookingStatus));
    }

    public function compileRepeatStatusChangePrompt(BookingRepeat $repeat, string $previousBookingStatus): ?array
    {
        $newStatus = (string) $repeat->booking_status;
        if ($previousBookingStatus === $newStatus) {
            return null;
        }
        $repeat->loadMissing([
            'booking.customer',
            'booking.service_address',
            'booking.detail',
            'booking.booking_partial_payments',
            'booking',
            'detail',
            'provider.owner',
            'serviceman.user',
        ]);
        $parent = $repeat->booking;
        if (! $parent) {
            return null;
        }
        $config = $this->notifications->getConfig();
        if (empty($config['enabled'])) {
            return null;
        }
        $segment = $this->notifications->resolveStatusTemplateSegment($previousBookingStatus, $newStatus, $parent);
        $vars = $this->notifications->buildRepeatStatusReplacements($repeat, $parent, $previousBookingStatus);

        $slots = [];
        $ops = [];
        $c = $this->notifications->previewDeliverStatusTemplateMessage(
            $config,
            $segment,
            'customer',
            $vars,
            $parent->customer?->phone,
            $parent,
            $repeat,
            translate('Customer')
        );
        if ($c) {
            $slots[] = $c;
            $ops[] = [
                'kind' => 'wa_repeat_status_party',
                'booking_repeat_id' => (string) $repeat->id,
                'previous_booking_status' => $previousBookingStatus,
                'party' => 'customer',
            ];
        }
        $provider = $repeat->provider ?? $parent->provider;
        $p = $this->notifications->previewDeliverStatusTemplateMessage(
            $config,
            $segment,
            'provider',
            $vars,
            $this->notifications->resolveProviderPhone($provider, $config),
            $parent,
            $repeat,
            translate('Provider')
        );
        if ($p) {
            $slots[] = $p;
            $ops[] = [
                'kind' => 'wa_repeat_status_party',
                'booking_repeat_id' => (string) $repeat->id,
                'previous_booking_status' => $previousBookingStatus,
                'party' => 'provider',
            ];
        }
        $rows = $this->normalizePreviewRows($slots);
        if ($rows === []) {
            return null;
        }

        return [
            'rows' => $rows,
            'ops' => $ops,
        ];
    }

    public function buildRepeatStatusChangePrompt(BookingRepeat $repeat, string $previousBookingStatus): ?array
    {
        return $this->finalizeCompile($this->compileRepeatStatusChangePrompt($repeat, $previousBookingStatus));
    }

    public function compileScheduleChangePrompt(Booking $booking, ?string $previousServiceScheduleRaw): ?array
    {
        $config = $this->notifications->getConfig();
        if (empty($config['enabled'])) {
            return null;
        }
        $prevFormatted = $this->notifications->formatScheduleToken($previousServiceScheduleRaw);
        $newFormatted = $this->notifications->formatScheduleToken($booking->service_schedule);
        if ($prevFormatted === $newFormatted) {
            return null;
        }
        $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
        $vars = array_merge($this->notifications->buildReplacements($booking, null), [
            '{previous_service_schedule}' => $prevFormatted,
        ]);
        $slots = [];
        $ops = [];
        $c = $this->notifications->previewBookingMetaTemplateSlot(
            $config,
            'booking_schedule_customer',
            $vars,
            $booking->customer?->phone,
            translate('Customer')
        );
        if ($c) {
            $slots[] = $c;
            $ops[] = [
                'kind' => 'wa_booking_schedule_meta',
                'booking_id' => (string) $booking->id,
                'previous_service_schedule_raw' => $previousServiceScheduleRaw,
                'message_key' => 'booking_schedule_customer',
            ];
        }
        $p = $this->notifications->previewBookingMetaTemplateSlot(
            $config,
            'booking_schedule_provider',
            $vars,
            $this->notifications->resolveProviderPhone($booking->provider, $config),
            translate('Provider')
        );
        if ($p) {
            $slots[] = $p;
            $ops[] = [
                'kind' => 'wa_booking_schedule_meta',
                'booking_id' => (string) $booking->id,
                'previous_service_schedule_raw' => $previousServiceScheduleRaw,
                'message_key' => 'booking_schedule_provider',
            ];
        }
        $rows = $this->normalizePreviewRows($slots);
        if ($rows === []) {
            return null;
        }

        return [
            'rows' => $rows,
            'ops' => $ops,
        ];
    }

    public function buildScheduleChangePrompt(Booking $booking, ?string $previousServiceScheduleRaw): ?array
    {
        return $this->finalizeCompile($this->compileScheduleChangePrompt($booking, $previousServiceScheduleRaw));
    }

    public function compileRepeatScheduleChangePrompt(BookingRepeat $repeat, ?string $previousServiceScheduleRaw): ?array
    {
        $repeat->loadMissing(['booking.customer', 'booking.service_address', 'booking.detail', 'booking.booking_partial_payments', 'booking', 'detail', 'provider.owner', 'serviceman.user']);
        $parent = $repeat->booking;
        if (! $parent) {
            return null;
        }
        $config = $this->notifications->getConfig();
        if (empty($config['enabled'])) {
            return null;
        }
        $prevFormatted = $this->notifications->formatScheduleToken($previousServiceScheduleRaw);
        $newFormatted = $this->notifications->formatScheduleToken($repeat->service_schedule);
        if ($prevFormatted === $newFormatted) {
            return null;
        }
        $vars = array_merge($this->notifications->buildRepeatRowReplacements($repeat, $parent), [
            '{previous_service_schedule}' => $prevFormatted,
        ]);
        $provider = $repeat->provider ?? $parent->provider;
        $slots = [];
        $ops = [];
        $c = $this->notifications->previewBookingMetaTemplateSlot(
            $config,
            'booking_schedule_customer',
            $vars,
            $parent->customer?->phone,
            translate('Customer')
        );
        if ($c) {
            $slots[] = $c;
            $ops[] = [
                'kind' => 'wa_repeat_schedule_meta',
                'booking_repeat_id' => (string) $repeat->id,
                'previous_service_schedule_raw' => $previousServiceScheduleRaw,
                'message_key' => 'booking_schedule_customer',
            ];
        }
        $p = $this->notifications->previewBookingMetaTemplateSlot(
            $config,
            'booking_schedule_provider',
            $vars,
            $this->notifications->resolveProviderPhone($provider, $config),
            translate('Provider')
        );
        if ($p) {
            $slots[] = $p;
            $ops[] = [
                'kind' => 'wa_repeat_schedule_meta',
                'booking_repeat_id' => (string) $repeat->id,
                'previous_service_schedule_raw' => $previousServiceScheduleRaw,
                'message_key' => 'booking_schedule_provider',
            ];
        }
        $rows = $this->normalizePreviewRows($slots);
        if ($rows === []) {
            return null;
        }

        return [
            'rows' => $rows,
            'ops' => $ops,
        ];
    }

    public function buildRepeatScheduleChangePrompt(BookingRepeat $repeat, ?string $previousServiceScheduleRaw): ?array
    {
        return $this->finalizeCompile($this->compileRepeatScheduleChangePrompt($repeat, $previousServiceScheduleRaw));
    }

    public function compileProviderChangePrompt(Booking $booking, ?Provider $previousProvider): ?array
    {
        if (! $booking->provider_id) {
            return null;
        }
        if ($previousProvider && (string) $previousProvider->id === (string) $booking->provider_id) {
            return null;
        }
        $config = $this->notifications->getConfig();
        if (empty($config['enabled'])) {
            return null;
        }
        $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
        $vars = array_merge(
            $this->notifications->buildReplacements($booking, null),
            $this->notifications->previousProviderReplacementMap($previousProvider)
        );
        $slots = [];
        $ops = [];
        $prevId = $previousProvider?->id;
        $c = $this->notifications->previewBookingMetaTemplateSlot(
            $config,
            'provider_change_customer',
            $vars,
            $booking->customer?->phone,
            translate('Customer')
        );
        if ($c) {
            $slots[] = $c;
            $ops[] = [
                'kind' => 'wa_provider_change_meta',
                'booking_id' => (string) $booking->id,
                'message_key' => 'provider_change_customer',
                'previous_provider_id' => $prevId,
            ];
        }
        if ($previousProvider) {
            $prevP = $this->notifications->previewBookingMetaTemplateSlot(
                $config,
                'provider_change_previous_provider',
                $vars,
                $this->notifications->resolveProviderPhone($previousProvider, $config),
                translate('Previous provider')
            );
            if ($prevP) {
                $slots[] = $prevP;
                $ops[] = [
                    'kind' => 'wa_provider_change_meta',
                    'booking_id' => (string) $booking->id,
                    'message_key' => 'provider_change_previous_provider',
                    'previous_provider_id' => $prevId,
                ];
            }
        }
        $n = $this->notifications->previewBookingMetaTemplateSlot(
            $config,
            'provider_change_new_provider',
            $vars,
            $this->notifications->resolveProviderPhone($booking->provider, $config),
            translate('New provider')
        );
        if ($n) {
            $slots[] = $n;
            $ops[] = [
                'kind' => 'wa_provider_change_meta',
                'booking_id' => (string) $booking->id,
                'message_key' => 'provider_change_new_provider',
                'previous_provider_id' => $prevId,
            ];
        }
        $rows = $this->normalizePreviewRows($slots);
        if ($rows === []) {
            return null;
        }

        return [
            'rows' => $rows,
            'ops' => $ops,
        ];
    }

    public function buildProviderChangePrompt(Booking $booking, ?Provider $previousProvider): ?array
    {
        return $this->finalizeCompile($this->compileProviderChangePrompt($booking, $previousProvider));
    }

    /**
     * @param  array{date?: string, payment_method?: string, reference_id?: string}  $meta
     */
    public function compilePaymentAddedPrompt(Booking $booking, BookingPartialPayment $partial, array $meta): ?array
    {
        $config = $this->notifications->getConfig();
        if (empty($config['enabled'])) {
            return null;
        }
        $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
        $amount = (float) ($partial->paid_amount ?? 0);
        $amountLabel = function_exists('with_currency_symbol') ? with_currency_symbol($amount) : (string) $amount;
        $receivedBy = strtolower(trim((string) ($partial->received_by ?? '')));
        $receivedByLabel = match ($receivedBy) {
            'company' => translate('Company'),
            'provider' => translate('Provider'),
            default => $receivedBy !== '' ? ucfirst($receivedBy) : '—',
        };
        $date = trim((string) ($meta['date'] ?? ''));
        if ($date === '') {
            $date = $partial->created_at ? $partial->created_at->toDateString() : now()->toDateString();
        }
        $method = trim((string) ($meta['payment_method'] ?? ''));
        if ($method === '') {
            $method = '—';
        }
        $reference = trim((string) ($meta['reference_id'] ?? ''));
        if ($reference === '') {
            $reference = (string) ($partial->transaction_id ?? '');
        }
        if ($reference === '') {
            $reference = '—';
        }
        $vars = array_merge($this->notifications->buildReplacements($booking, null), [
            '{amount_added}' => $amountLabel,
            '{payment_received_by}' => $receivedByLabel,
            '{payment_date}' => $date,
            '{payment_method}' => $method,
            '{payment_reference}' => $reference,
        ]);
        $slots = [];
        $ops = [];
        $c = $this->notifications->previewBookingMetaTemplateSlot(
            $config,
            'booking_payment_added_customer',
            $vars,
            $booking->customer?->phone,
            translate('Customer')
        );
        if ($c) {
            $slots[] = $c;
            $ops[] = [
                'kind' => 'wa_payment_added_meta',
                'booking_id' => (string) $booking->id,
                'partial_payment_id' => (string) $partial->id,
                'meta' => $meta,
                'message_key' => 'booking_payment_added_customer',
            ];
        }
        $p = $this->notifications->previewBookingMetaTemplateSlot(
            $config,
            'booking_payment_added_provider',
            $vars,
            $this->notifications->resolveProviderPhone($booking->provider, $config),
            translate('Provider')
        );
        if ($p) {
            $slots[] = $p;
            $ops[] = [
                'kind' => 'wa_payment_added_meta',
                'booking_id' => (string) $booking->id,
                'partial_payment_id' => (string) $partial->id,
                'meta' => $meta,
                'message_key' => 'booking_payment_added_provider',
            ];
        }
        $rows = $this->normalizePreviewRows($slots);
        if ($rows === []) {
            return null;
        }

        return [
            'rows' => $rows,
            'ops' => $ops,
        ];
    }

    public function buildPaymentAddedPrompt(Booking $booking, BookingPartialPayment $partial, array $meta): ?array
    {
        return $this->finalizeCompile($this->compilePaymentAddedPrompt($booking, $partial, $meta));
    }

    /**
     * @param  array{date?: string, transaction_id?: string, reference_note?: string}  $meta
     */
    public function compileRefundPrompt(Booking $booking, float $amount, array $meta): ?array
    {
        $config = $this->notifications->getConfig();
        if (empty($config['enabled'])) {
            return null;
        }
        $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
        $totalsAfter = get_booking_refund_display_totals($booking);
        $remaining = round((float) ($totalsAfter['refundable_remaining'] ?? 0), 2);
        $refundedAfter = round((float) ($totalsAfter['refunded_total'] ?? 0), 2);
        $refundedBeforeThis = round(max(0.0, $refundedAfter - $amount), 2);
        $date = trim((string) ($meta['date'] ?? ''));
        if ($date === '') {
            $date = now()->toDateString();
        }
        $tx = trim((string) ($meta['transaction_id'] ?? ''));
        if ($tx === '') {
            $tx = '—';
        }
        $note = trim((string) ($meta['reference_note'] ?? ''));
        if ($note === '') {
            $note = '—';
        }
        $vars = array_merge($this->notifications->buildReplacements($booking, null), [
            '{refund_amount}' => $this->notifications->formatMoneyAmountForMessages($amount),
            '{refund_date}' => $date,
            '{refund_transaction_id}' => $tx,
            '{refund_reference_note}' => $note,
            '{refund_remaining}' => $this->notifications->formatMoneyAmountForMessages($remaining),
            '{customer_refund_before_this}' => $this->notifications->formatMoneyAmountForMessages($refundedBeforeThis),
            '{customer_refund_total}' => $this->notifications->formatMoneyAmountForMessages($refundedAfter),
            '{customer_refund_cap}' => $this->notifications->formatMoneyAmountForMessages(round((float) ($totalsAfter['max_eligible'] ?? 0), 2)),
        ]);
        $row = $this->notifications->previewBookingMetaTemplateSlot(
            $config,
            'booking_refund_to_customer',
            $vars,
            $booking->customer?->phone,
            translate('Customer')
        );
        if (! $row) {
            return null;
        }
        $rows = $this->normalizePreviewRows([$row]);

        return [
            'rows' => $rows,
            'ops' => [['kind' => 'refund', 'booking_id' => (string) $booking->id, 'amount' => $amount, 'meta' => $meta]],
        ];
    }

    public function buildRefundPrompt(Booking $booking, float $amount, array $meta): ?array
    {
        return $this->finalizeCompile($this->compileRefundPrompt($booking, $amount, $meta));
    }

    public function compileReopenResolvedPrompt(Booking $booking): ?array
    {
        $config = $this->notifications->getConfig();
        if (empty($config['enabled'])) {
            return null;
        }
        $segment = 'reopen_resolved';
        $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
        $vars = array_merge($this->notifications->buildReplacements($booking, null), [
            '{reopen_resolve_remarks}' => trim((string) ($booking->reopen_resolve_remarks ?? '')) !== ''
                ? (string) $booking->reopen_resolve_remarks
                : '—',
        ]);
        $slots = [];
        $ops = [];
        $c = $this->notifications->previewDeliverStatusTemplateMessage(
            $config,
            $segment,
            'customer',
            $vars,
            $booking->customer?->phone,
            $booking,
            null,
            translate('Customer')
        );
        if ($c) {
            $slots[] = $c;
            $ops[] = [
                'kind' => 'wa_reopen_resolved_party',
                'booking_id' => (string) $booking->id,
                'party' => 'customer',
            ];
        }
        $p = $this->notifications->previewDeliverStatusTemplateMessage(
            $config,
            $segment,
            'provider',
            $vars,
            $this->notifications->resolveProviderPhone($booking->provider, $config),
            $booking,
            null,
            translate('Provider')
        );
        if ($p) {
            $slots[] = $p;
            $ops[] = [
                'kind' => 'wa_reopen_resolved_party',
                'booking_id' => (string) $booking->id,
                'party' => 'provider',
            ];
        }
        $rows = $this->normalizePreviewRows($slots);
        if ($rows === []) {
            return null;
        }

        return [
            'rows' => $rows,
            'ops' => $ops,
        ];
    }

    public function buildReopenResolvedPrompt(Booking $booking): ?array
    {
        return $this->finalizeCompile($this->compileReopenResolvedPrompt($booking));
    }

    public function compileDisputedRefundPrompt(Booking $booking, string $previousBookingStatus): ?array
    {
        $snap = $booking->reopen_disputed_snapshot ?? null;
        if (! is_array($snap) || (($snap['type'] ?? null) !== 'reopen_disputed_refund')) {
            return null;
        }
        $config = $this->notifications->getConfig();
        if (empty($config['enabled'])) {
            return null;
        }
        $segment = 'disputed_close';
        $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
        $vars = array_merge($this->notifications->buildReplacements($booking, $previousBookingStatus), [
            '{reopen_resolve_remarks}' => trim((string) ($booking->reopen_resolve_remarks ?? '')) !== ''
                ? (string) $booking->reopen_resolve_remarks
                : '—',
        ], $this->notifications->buildDisputedReopenRefundPlaceholderExtras($snap));

        $slots = [];
        $ops = [];
        $c = $this->notifications->previewDeliverStatusTemplateMessage(
            $config,
            $segment,
            'customer',
            $vars,
            $booking->customer?->phone,
            $booking,
            null,
            translate('Customer')
        );
        if ($c) {
            $slots[] = $c;
            $ops[] = [
                'kind' => 'wa_disputed_refund_party',
                'booking_id' => (string) $booking->id,
                'previous_booking_status' => $previousBookingStatus,
                'party' => 'customer',
            ];
        }
        $p = $this->notifications->previewDeliverStatusTemplateMessage(
            $config,
            $segment,
            'provider',
            $vars,
            $this->notifications->resolveProviderPhone($booking->provider, $config),
            $booking,
            null,
            translate('Provider')
        );
        if ($p) {
            $slots[] = $p;
            $ops[] = [
                'kind' => 'wa_disputed_refund_party',
                'booking_id' => (string) $booking->id,
                'previous_booking_status' => $previousBookingStatus,
                'party' => 'provider',
            ];
        }
        $rows = $this->normalizePreviewRows($slots);
        if ($rows === []) {
            return null;
        }

        return [
            'rows' => $rows,
            'ops' => $ops,
        ];
    }

    public function buildDisputedRefundPrompt(Booking $booking, string $previousBookingStatus): ?array
    {
        return $this->finalizeCompile($this->compileDisputedRefundPrompt($booking, $previousBookingStatus));
    }
}
