<?php

namespace Modules\BookingModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingChangeLog;
use Modules\BookingModule\Entities\BookingDetail;
use Modules\BookingModule\Entities\BookingExtraService;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\Serviceman;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use Modules\ZoneManagement\Entities\Zone;

final class BookingAuditLogger
{
    /** @var array<string, mixed> */
    private static array $cache = [];

    private const SKIP_BOOKING_KEYS = [
        'updated_at',
    ];

    /** @var array<string, list<string>> */
    private const BOOKING_UPDATE_GROUPS = [
        'payment' => [
            'is_paid',
            'payment_method',
            'transaction_id',
            'total_booking_amount',
            'total_tax_amount',
            'total_discount_amount',
            'total_campaign_discount_amount',
            'total_coupon_discount_amount',
            'coupon_code',
            'additional_charge',
            'additional_tax_amount',
            'additional_discount_amount',
            'additional_campaign_discount_amount',
            'extra_fee',
            'total_referral_discount_amount',
            'provider_payment_confirmed_at',
            'allow_complete_without_full_payment',
            'additional_charges_breakdown',
            'settlement_outcome',
            'settlement_config',
            'settlement_snapshot',
            'settlement_remarks',
        ],
        'schedule' => [
            'service_schedule',
        ],
        'assignment' => [
            'provider_id',
            'serviceman_id',
            'assignee_id',
        ],
        'service' => [
            'category_id',
            'sub_category_id',
            'service_description',
        ],
        'status' => [
            'booking_status',
        ],
    ];

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    public static function actorUserId(): ?string
    {
        if (!auth()->check()) {
            return null;
        }

        return (string) auth()->id();
    }

    public static function actorDisplayName(): string
    {
        $user = auth()->user();
        if ($user) {
            $name = trim((string) ($user->first_name ?? '') . ' ' . (string) ($user->last_name ?? ''));
            if ($name !== '') {
                return $name;
            }

            return (string) ($user->email ?? $user->phone ?? $user->id);
        }

        return translate('System');
    }

    public static function log(
        string $bookingId,
        string $propertyKey,
        string $propertyLabel,
        ?string $oldDisplay,
        ?string $newDisplay,
        ?string $context = null
    ): void {
        BookingChangeLog::query()->create([
            'booking_id' => $bookingId,
            'changed_by' => self::actorUserId(),
            'actor_name' => self::actorDisplayName(),
            'property_key' => $propertyKey,
            'property_label' => $propertyLabel,
            'old_value' => $oldDisplay,
            'new_value' => $newDisplay,
            'context' => $context,
        ]);
    }

    public static function logBookingCreated(Booking $booking): void
    {
        self::clearCache();
        $ref = $booking->readable_id !== null && $booking->readable_id !== ''
            ? (string) $booking->readable_id
            : (string) $booking->id;
        self::log(
            (string) $booking->id,
            'booking.created',
            translate('Booking created'),
            '—',
            '#' . $ref,
            null
        );
    }

    /**
     * @param  array<string, mixed>  $original  Attribute snapshot from before save (see observer updating hook).
     * @param  array<string, mixed>  $changes  From $model->getChanges() in updated hook.
     */
    public static function logBookingUpdatedFromDiff(Booking $booking, array $original, array $changes): void
    {
        self::clearCache();
        unset($changes['updated_at']);
        $keysChanged = [];
        foreach ($changes as $key => $newRaw) {
            if (in_array($key, self::SKIP_BOOKING_KEYS, true)) {
                continue;
            }
            $oldRaw = array_key_exists($key, $original) ? $original[$key] : null;
            if (self::valuesEquivalent($oldRaw, $newRaw, $booking, $key)) {
                continue;
            }
            $keysChanged[] = $key;
        }
        if ($keysChanged === []) {
            return;
        }

        $handled = [];
        foreach (self::BOOKING_UPDATE_GROUPS as $groupId => $groupKeys) {
            $inGroup = array_values(array_intersect($keysChanged, $groupKeys));
            if ($inGroup === []) {
                continue;
            }
            foreach ($inGroup as $k) {
                $handled[$k] = true;
            }
            [$oldText, $newText] = self::summarizeBookingFieldGroup($booking, $inGroup, $original, $changes);
            self::log(
                (string) $booking->id,
                'booking.updated.' . $groupId,
                self::bookingGroupLabel($groupId),
                $oldText,
                $newText,
                null
            );
        }

        $remaining = array_values(array_diff($keysChanged, array_keys($handled)));
        if ($remaining !== []) {
            [$oldText, $newText] = self::summarizeBookingFieldGroup($booking, $remaining, $original, $changes);
            self::log(
                (string) $booking->id,
                'booking.updated.other',
                translate('Booking updated'),
                $oldText,
                $newText,
                null
            );
        }
    }

    public static function logBookingDeleted(Booking $booking): void
    {
        self::clearCache();
        self::log(
            (string) $booking->id,
            '_deleted',
            translate('Booking'),
            translate('Record existed'),
            translate('Booking deleted'),
            null
        );
    }

    public static function logBookingDetailChange(string $action, BookingDetail $detail, ?array $changes = null): void
    {
        if (!$detail->booking_id) {
            return;
        }
        self::clearCache();
        $ctx = 'booking_detail:' . $detail->id;
        $label = translate('Service_line') . ' #' . $detail->id;

        if ($action === 'created') {
            self::log(
                (string) $detail->booking_id,
                'booking_detail.created',
                $label,
                '—',
                self::summarizeBookingDetail($detail),
                $ctx
            );

            return;
        }

        if ($action === 'deleted') {
            self::log(
                (string) $detail->booking_id,
                'booking_detail.deleted',
                $label,
                self::summarizeBookingDetail($detail),
                '—',
                $ctx
            );

            return;
        }

        if ($action === 'updated' && is_array($changes)) {
            $oldParts = [];
            $newParts = [];
            foreach ($changes as $key => $pair) {
                if ($key === 'updated_at') {
                    continue;
                }
                if (!is_array($pair) || !array_key_exists('old', $pair) || !array_key_exists('new', $pair)) {
                    continue;
                }
                $oldRaw = $pair['old'];
                $newRaw = $pair['new'];
                if (self::rawEquivalent($oldRaw, $newRaw)) {
                    continue;
                }
                $oldParts[] = self::humanizeKey($key) . ': ' . self::formatDetailAttribute($key, $oldRaw, $detail);
                $newParts[] = self::humanizeKey($key) . ': ' . self::formatDetailAttribute($key, $newRaw, $detail);
            }
            if ($oldParts !== [] && $newParts !== []) {
                self::log(
                    (string) $detail->booking_id,
                    'booking_detail.updated',
                    $label . ' — ' . translate('Updated'),
                    implode('; ', $oldParts),
                    implode('; ', $newParts),
                    $ctx
                );
            }
        }
    }

    public static function logBookingExtraServiceChange(string $action, BookingExtraService $row, ?array $changes = null): void
    {
        if (!$row->booking_id) {
            return;
        }
        self::clearCache();
        $ctx = 'booking_extra_service:' . $row->id;
        $label = translate('Extra_service') . ' #' . $row->id;

        if ($action === 'created') {
            self::log(
                (string) $row->booking_id,
                'booking_extra_service.created',
                $label,
                '—',
                self::summarizeExtraService($row),
                $ctx
            );

            return;
        }

        if ($action === 'deleted') {
            self::log(
                (string) $row->booking_id,
                'booking_extra_service.deleted',
                $label,
                self::summarizeExtraService($row),
                '—',
                $ctx
            );

            return;
        }

        if ($action === 'updated' && is_array($changes)) {
            $oldParts = [];
            $newParts = [];
            foreach ($changes as $key => $pair) {
                if ($key === 'updated_at') {
                    continue;
                }
                if (!is_array($pair) || !array_key_exists('old', $pair) || !array_key_exists('new', $pair)) {
                    continue;
                }
                $oldRaw = $pair['old'];
                $newRaw = $pair['new'];
                if (self::rawEquivalent($oldRaw, $newRaw)) {
                    continue;
                }
                $oldParts[] = self::humanizeKey($key) . ': ' . self::formatScalar($oldRaw);
                $newParts[] = self::humanizeKey($key) . ': ' . self::formatScalar($newRaw);
            }
            if ($oldParts !== [] && $newParts !== []) {
                self::log(
                    (string) $row->booking_id,
                    'booking_extra_service.updated',
                    $label . ' — ' . translate('Updated'),
                    implode('; ', $oldParts),
                    implode('; ', $newParts),
                    $ctx
                );
            }
        }
    }

    public static function logBookingRepeatCreated(BookingRepeat $repeat): void
    {
        if (!$repeat->booking_id) {
            return;
        }
        self::clearCache();
        self::log(
            (string) $repeat->booking_id,
            'repeat.created',
            translate('Repeat_visit'),
            '—',
            self::summarizeRepeat($repeat),
            'booking_repeat:' . $repeat->id
        );
    }

    /**
     * @param  array<string, mixed>  $original
     * @param  array<string, mixed>  $changes
     */
    public static function logBookingRepeatUpdatedFromDiff(BookingRepeat $repeat, array $original, array $changes): void
    {
        if (!$repeat->booking_id) {
            return;
        }
        self::clearCache();
        unset($changes['updated_at']);
        foreach ($changes as $key => $newRaw) {
            $oldRaw = array_key_exists($key, $original) ? $original[$key] : null;
            if (self::rawEquivalent($oldRaw, $newRaw)) {
                continue;
            }
            self::log(
                (string) $repeat->booking_id,
                'repeat.' . $key,
                translate('Repeat_visit') . ' #' . $repeat->readable_id . ' — ' . self::humanizeKey($key),
                self::formatRepeatAttribute($key, $oldRaw),
                self::formatRepeatAttribute($key, $newRaw),
                'booking_repeat:' . $repeat->id
            );
        }
    }

    public static function logBookingRepeatDeleted(BookingRepeat $repeat): void
    {
        if (!$repeat->booking_id) {
            return;
        }
        self::clearCache();
        self::log(
            (string) $repeat->booking_id,
            'repeat.deleted',
            translate('Repeat_visit'),
            self::summarizeRepeat($repeat),
            '—',
            'booking_repeat:' . $repeat->id
        );
    }

    private static function bookingGroupLabel(string $groupId): string
    {
        return match ($groupId) {
            'payment' => translate('Payment updated'),
            'schedule' => translate('Schedule updated'),
            'assignment' => translate('Reassignment'),
            'service' => translate('Service updated'),
            'status' => translate('Status updated'),
            default => translate('Booking updated'),
        };
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, mixed>  $original
     * @param  array<string, mixed>  $changes
     * @return array{0: string, 1: string}
     */
    private static function summarizeBookingFieldGroup(
        Booking $booking,
        array $keys,
        array $original,
        array $changes
    ): array {
        $oldParts = [];
        $newParts = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $changes)) {
                continue;
            }
            $oldRaw = array_key_exists($key, $original) ? $original[$key] : null;
            $newRaw = $changes[$key];
            if (self::valuesEquivalent($oldRaw, $newRaw, $booking, $key)) {
                continue;
            }
            if ($key === 'is_paid') {
                $oldParts[] = self::formatIsPaidChangeForAuditHistory($booking, (int) $oldRaw);
                $newParts[] = self::formatIsPaidChangeForAuditHistory($booking, (int) $newRaw);

                continue;
            }
            $oldParts[] = self::humanizeKey($key) . ': ' . self::formatBookingAttribute($key, $oldRaw);
            $newParts[] = self::humanizeKey($key) . ': ' . self::formatBookingAttribute($key, $newRaw);
        }

        return [
            $oldParts === [] ? '—' : implode('; ', $oldParts),
            $newParts === [] ? '—' : implode('; ', $newParts),
        ];
    }

    /**
     * Same figures as the admin booking details payment card, for a hypothetical {@see Booking::$is_paid} value
     * (used to show Total / amount paid / due balance before vs after a payment-status toggle).
     *
     * @return array{total: float, amount_paid_display: float, due_balance: float, status_label: string, amount_row_label: string}
     */
    private static function adminPaymentSnapshot(Booking $booking, int $isPaid): array
    {
        $booking->loadMissing(['booking_partial_payments', 'extra_services']);

        $totalPaidFromPartials = round((float) $booking->booking_partial_payments->sum('paid_amount'), 2);
        $bookingTotalForPayment = round((float) get_booking_payable_total_for_partial_dues($booking), 2);

        $paymentFullyCovered = $booking->booking_partial_payments->isEmpty()
            ? ($isPaid === 1)
            : (round($totalPaidFromPartials, 2) >= round($bookingTotalForPayment, 2));

        $displayPaidAmount = $booking->booking_partial_payments->isNotEmpty()
            ? $totalPaidFromPartials
            : (($paymentFullyCovered && $isPaid === 1) ? $bookingTotalForPayment : 0.0);

        $visitRetainedCanceled = (string) ($booking->booking_status ?? '') === 'canceled'
            && (
                ! empty($booking->after_visit_cancel)
                || (string) ($booking->settlement_outcome ?? '') === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL
            );
        $decidedChargesPaidDisplayCap = $visitRetainedCanceled
            || (
                (string) ($booking->booking_status ?? '') === 'completed'
                && (string) ($booking->settlement_outcome ?? '') === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT
            );
        if ($decidedChargesPaidDisplayCap && round($bookingTotalForPayment, 2) > 0
            && round($totalPaidFromPartials, 2) >= round($bookingTotalForPayment, 2)) {
            $displayPaidAmount = round($bookingTotalForPayment, 2);
        }

        $dueBalanceDisplay = round(max(0.0, $bookingTotalForPayment - $displayPaidAmount), 2);
        if ($dueBalanceDisplay > 0 && in_array((string) ($booking->booking_status ?? ''), ['pending', 'accepted', 'ongoing'], true)
            && ($booking->payment_method ?? '') !== 'cash_after_service'
            && (float) ($booking->additional_charge ?? 0) > 0) {
            $dueBalanceDisplay = round($dueBalanceDisplay + (float) $booking->additional_charge, 2);
        }

        if ($visitRetainedCanceled) {
            $payableCap = round((float) get_booking_payable_total_for_partial_dues($booking), 2);
            $paidPartials = round((float) $booking->booking_partial_payments->sum('paid_amount'), 2);
            if ($payableCap <= 0) {
                $statusLabel = translate('Unpaid');
            } elseif ($paidPartials + 0.005 >= $payableCap || $paymentFullyCovered) {
                $statusLabel = translate('Paid');
            } elseif ($paidPartials > 0) {
                $statusLabel = translate('Partially paid');
            } else {
                $statusLabel = translate('Unpaid');
            }
        } elseif (in_array((string) ($booking->booking_status ?? ''), ['canceled', 'refunded'], true)) {
            $statusLabel = translate('Refunded');
        } elseif ($paymentFullyCovered) {
            $statusLabel = translate('Paid');
        } elseif ($booking->booking_partial_payments->isNotEmpty()) {
            $statusLabel = translate('Partially paid');
        } else {
            $statusLabel = translate('Unpaid');
        }

        $showAsAmountPaidLabel = (string) ($booking->booking_status ?? '') === 'completed' || $paymentFullyCovered;
        $amountRowLabel = $showAsAmountPaidLabel ? translate('Amount_Paid') : translate('Advance_Paid');

        return [
            'total' => $bookingTotalForPayment,
            'amount_paid_display' => round($displayPaidAmount, 2),
            'due_balance' => $dueBalanceDisplay,
            'status_label' => $statusLabel,
            'amount_row_label' => $amountRowLabel,
        ];
    }

    private static function formatIsPaidChangeForAuditHistory(Booking $booking, int $isPaid): string
    {
        $s = self::adminPaymentSnapshot($booking, $isPaid);

        return translate('Total_Amount') . ': ' . with_currency_symbol($s['total'])
            . '; ' . $s['amount_row_label'] . ': ' . with_currency_symbol($s['amount_paid_display'])
            . '; ' . translate('Due_Balance') . ': ' . with_currency_symbol($s['due_balance'])
            . '; ' . translate('Payment_Status') . ': ' . $s['status_label'];
    }

    private static function humanizeKey(string $key): string
    {
        return Str::headline(str_replace('_', ' ', $key));
    }

    private static function valuesEquivalent(mixed $oldRaw, mixed $newRaw, Booking $booking, string $key): bool
    {
        if ($key === 'total_booking_amount' || str_ends_with($key, '_amount') || str_ends_with($key, '_charge') || str_ends_with($key, '_fee')) {
            return abs((float) $oldRaw - (float) $newRaw) < 0.00001;
        }

        return self::rawEquivalent($oldRaw, $newRaw);
    }

    private static function rawEquivalent(mixed $a, mixed $b): bool
    {
        if ($a === $b) {
            return true;
        }
        if ($a === null && $b === '') {
            return true;
        }
        if ($a === '' && $b === null) {
            return true;
        }
        if (is_array($a) || is_array($b) || is_object($a) || is_object($b)) {
            return json_encode($a) === json_encode($b);
        }

        return (string) $a === (string) $b;
    }

    private static function formatBookingAttribute(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($key) {
            'customer_id' => self::customerLabel($value),
            'provider_id' => self::providerLabel($value),
            'serviceman_id' => self::servicemanLabel($value),
            'assignee_id' => self::userLabel($value),
            'zone_id' => self::zoneLabel($value),
            'category_id', 'sub_category_id' => self::categoryLabel($value),
            'service_address_id' => self::addressLabel($value),
            'service_schedule' => self::formatDateTime($value),
            'booking_status', 'payment_method', 'booking_source', 'service_location' => is_scalar($value) ? (string) $value : json_encode($value),
            'is_paid', 'is_verified', 'is_checked' => self::formatBoolInt($value),
            'evidence_photos', 'additional_charges_breakdown', 'service_address_location', 'service_location' => self::formatJsonBrief($value),
            default => is_scalar($value) ? (string) $value : json_encode($value),
        };
    }

    private static function formatDetailAttribute(string $key, mixed $value, BookingDetail $detail): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($key) {
            'service_id' => self::serviceLabel($value),
            'booking_id' => (string) $value,
            default => is_scalar($value) ? (string) $value : json_encode($value),
        };
    }

    private static function formatRepeatAttribute(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($key) {
            'provider_id' => self::providerLabel($value),
            'serviceman_id' => self::servicemanLabel($value),
            'category_id', 'sub_category_id' => self::categoryLabel($value),
            'service_address_id' => self::addressLabel($value),
            'service_schedule' => self::formatDateTime($value),
            'is_paid', 'is_verified', 'is_checked' => self::formatBoolInt($value),
            'evidence_photos', 'service_address_location', 'service_location' => self::formatJsonBrief($value),
            default => is_scalar($value) ? (string) $value : json_encode($value),
        };
    }

    private static function summarizeBookingDetail(BookingDetail $detail): string
    {
        $parts = [];
        if ($detail->service_id) {
            $parts[] = self::serviceLabel($detail->service_id);
        }
        if ($detail->variant_key) {
            $parts[] = (string) $detail->variant_key;
        }
        $parts[] = '×' . (string) ($detail->quantity ?? 1);

        return implode(' ', array_filter($parts)) ?: ('#' . $detail->id);
    }

    private static function summarizeExtraService(BookingExtraService $row): string
    {
        return trim(($row->title ?? '') . ' ×' . (string) ($row->quantity ?? 1));
    }

    private static function summarizeRepeat(BookingRepeat $repeat): string
    {
        return '#' . ($repeat->readable_id ?? $repeat->id)
            . ' — ' . self::formatDateTime($repeat->service_schedule ?? null)
            . ' — ' . (string) ($repeat->booking_status ?? '');
    }

    private static function formatScalar(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return is_scalar($value) ? (string) $value : json_encode($value);
    }

    private static function formatBoolInt(mixed $v): string
    {
        return ((int) $v) === 1 ? '1' : '0';
    }

    private static function formatDateTime(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '—';
        }
        try {
            return Carbon::parse($v)->format('Y-m-d H:i');
        } catch (\Throwable) {
            return (string) $v;
        }
    }

    private static function formatJsonBrief(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '—';
        }
            if (is_string($v)) {
            $decoded = json_decode($v, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return 'JSON (' . count($decoded) . ')';
            }

            return Str::limit($v, 120);
        }
        if (is_array($v)) {
            return 'JSON (' . count($v) . ')';
        }

        return (string) $v;
    }

    private static function customerLabel(mixed $id): string
    {
        $id = (string) $id;
        if ($id === '') {
            return '—';
        }
        $cacheKey = 'user:' . $id;
        if (!array_key_exists($cacheKey, self::$cache)) {
            $u = User::query()->find($id);
            self::$cache[$cacheKey] = $u ? trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: (string) ($u->phone ?? $id) : $id;
        }

        return (string) self::$cache[$cacheKey];
    }

    private static function providerLabel(mixed $id): string
    {
        $id = (string) $id;
        if ($id === '') {
            return '—';
        }
        $cacheKey = 'provider:' . $id;
        if (!array_key_exists($cacheKey, self::$cache)) {
            $p = Provider::query()->find($id);
            self::$cache[$cacheKey] = $p ? (string) ($p->company_name ?? $id) : $id;
        }

        return (string) self::$cache[$cacheKey];
    }

    private static function servicemanLabel(mixed $id): string
    {
        $id = (string) $id;
        if ($id === '') {
            return '—';
        }
        $cacheKey = 'serviceman:' . $id;
        if (!array_key_exists($cacheKey, self::$cache)) {
            $s = Serviceman::query()->with('user')->find($id);
            if ($s && $s->user) {
                self::$cache[$cacheKey] = trim(($s->user->first_name ?? '') . ' ' . ($s->user->last_name ?? '')) ?: $id;
            } else {
                self::$cache[$cacheKey] = $id;
            }
        }

        return (string) self::$cache[$cacheKey];
    }

    private static function userLabel(mixed $id): string
    {
        $id = (string) $id;
        if ($id === '') {
            return '—';
        }
        $cacheKey = 'assignee:' . $id;
        if (!array_key_exists($cacheKey, self::$cache)) {
            $u = User::query()->find($id);
            self::$cache[$cacheKey] = $u ? trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: (string) ($u->email ?? $id) : $id;
        }

        return (string) self::$cache[$cacheKey];
    }

    private static function zoneLabel(mixed $id): string
    {
        $id = (string) $id;
        if ($id === '') {
            return '—';
        }
        $cacheKey = 'zone:' . $id;
        if (!array_key_exists($cacheKey, self::$cache)) {
            $z = Zone::query()->withoutGlobalScope('translate')->find($id);
            self::$cache[$cacheKey] = $z ? (string) ($z->name ?? $id) : $id;
        }

        return (string) self::$cache[$cacheKey];
    }

    private static function categoryLabel(mixed $id): string
    {
        $id = (string) $id;
        if ($id === '') {
            return '—';
        }
        $cacheKey = 'category:' . $id;
        if (!array_key_exists($cacheKey, self::$cache)) {
            $c = Category::query()->find($id);
            self::$cache[$cacheKey] = $c ? (string) ($c->name ?? $id) : $id;
        }

        return (string) self::$cache[$cacheKey];
    }

    private static function addressLabel(mixed $id): string
    {
        $id = (string) $id;
        if ($id === '') {
            return '—';
        }
        $cacheKey = 'address:' . $id;
        if (!array_key_exists($cacheKey, self::$cache)) {
            $a = UserAddress::query()->find($id);
            self::$cache[$cacheKey] = $a ? (string) ($a->address ?? $a->contact_person_name ?? $id) : $id;
        }

        return (string) self::$cache[$cacheKey];
    }

    private static function serviceLabel(mixed $id): string
    {
        $id = (string) $id;
        if ($id === '') {
            return '—';
        }
        $cacheKey = 'service:' . $id;
        if (!array_key_exists($cacheKey, self::$cache)) {
            $s = Service::query()->withTrashed()->find($id);
            self::$cache[$cacheKey] = $s ? (string) ($s->name ?? $id) : $id;
        }

        return (string) self::$cache[$cacheKey];
    }
}
