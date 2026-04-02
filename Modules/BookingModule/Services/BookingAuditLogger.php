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
        foreach ($booking->getAttributes() as $key => $value) {
            if (in_array($key, ['id', 'created_at', 'updated_at'], true)) {
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            if (in_array($key, self::SKIP_BOOKING_KEYS, true)) {
                continue;
            }
            self::log(
                (string) $booking->id,
                $key,
                self::humanizeKey($key),
                '—',
                self::formatBookingAttribute($key, $value),
                null
            );
        }
    }

    /**
     * @param  array<string, mixed>  $original  Attribute snapshot from before save (see observer updating hook).
     * @param  array<string, mixed>  $changes  From $model->getChanges() in updated hook.
     */
    public static function logBookingUpdatedFromDiff(Booking $booking, array $original, array $changes): void
    {
        self::clearCache();
        unset($changes['updated_at']);
        foreach ($changes as $key => $newRaw) {
            if (in_array($key, self::SKIP_BOOKING_KEYS, true)) {
                continue;
            }
            $oldRaw = array_key_exists($key, $original) ? $original[$key] : null;
            if (self::valuesEquivalent($oldRaw, $newRaw, $booking, $key)) {
                continue;
            }
            self::log(
                (string) $booking->id,
                $key,
                self::humanizeKey($key),
                self::formatBookingAttribute($key, $oldRaw),
                self::formatBookingAttribute($key, $newRaw),
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
                self::log(
                    (string) $detail->booking_id,
                    'booking_detail.' . $key,
                    $label . ' — ' . self::humanizeKey($key),
                    self::formatDetailAttribute($key, $oldRaw, $detail),
                    self::formatDetailAttribute($key, $newRaw, $detail),
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
                self::log(
                    (string) $row->booking_id,
                    'booking_extra_service.' . $key,
                    $label . ' — ' . self::humanizeKey($key),
                    self::formatScalar($oldRaw),
                    self::formatScalar($newRaw),
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
