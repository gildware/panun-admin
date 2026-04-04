<?php

use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BusinessSettingsModule\Entities\AdditionalChargeType;
use Modules\ServiceManagement\Entities\Service;

if (! function_exists('resolve_additional_charge_group_for_service')) {
    /**
     * Resolve which commission-style group applies for one additional-charge type and service.
     *
     * Precedence (first match wins): service.additional_charge_overrides[type] →
     * subCategory (service.sub_category_id) additional_charge_overrides[type] →
     * parent category (service.category_id) additional_charge_overrides[type] →
     * company default on AdditionalChargeType.charge_setup.
     *
     * @return array{mode: string, fixed_amount: float, tiers: list<array<string, mixed>>}
     */
    function resolve_additional_charge_group_for_service(string $chargeTypeId, ?Service $service): array
    {
        $type = AdditionalChargeType::query()->where('id', $chargeTypeId)->where('is_active', true)->first();
        if (! $type) {
            return ['mode' => 'fixed', 'fixed_amount' => 0.0, 'tiers' => []];
        }

        $company = normalize_commission_tier_group_for_ui(
            is_array($type->charge_setup) ? $type->charge_setup : null,
            0.0
        );

        if (! $service) {
            return $company;
        }

        $svcOverrides = $service->additional_charge_overrides ?? null;
        if (is_array($svcOverrides) && isset($svcOverrides[$chargeTypeId]) && is_array($svcOverrides[$chargeTypeId])) {
            return normalize_commission_tier_group_for_ui($svcOverrides[$chargeTypeId], 0.0);
        }

        $sub = $service->relationLoaded('subCategory') ? $service->subCategory : $service->subCategory()->withoutGlobalScopes()->first();
        if ($sub) {
            $subO = $sub->additional_charge_overrides ?? null;
            if (is_array($subO) && isset($subO[$chargeTypeId]) && is_array($subO[$chargeTypeId])) {
                return normalize_commission_tier_group_for_ui($subO[$chargeTypeId], 0.0);
            }
        }

        $cat = $service->relationLoaded('category') ? $service->category : $service->category()->withoutGlobalScopes()->first();
        if ($cat) {
            $catO = $cat->additional_charge_overrides ?? null;
            if (is_array($catO) && isset($catO[$chargeTypeId]) && is_array($catO[$chargeTypeId])) {
                return normalize_commission_tier_group_for_ui($catO[$chargeTypeId], 0.0);
            }
        }

        return $company;
    }
}

if (! function_exists('compute_additional_charges_for_cart_items')) {
    /**
     * @param  iterable<int, object>  $cartItems  Cart rows with service_id, total_cost, tax_amount
     * @return array{total: float, lines: list<array{id: string, name: string, amount: float}>}
     */
    function compute_additional_charges_for_cart_items(iterable $cartItems): array
    {
        $types = AdditionalChargeType::query()->active()->ordered()->get();
        if ($types->isEmpty()) {
            return ['total' => 0.0, 'lines' => []];
        }

        $aggregated = [];

        foreach ($cartItems as $item) {
            $service = null;
            if (is_object($item) && isset($item->service_id)) {
                // Admin create-booking cart uses stdClass rows with ->service attached; booking details use Eloquent models.
                if (isset($item->service) && $item->service instanceof Service) {
                    $service = $item->service;
                    $service->loadMissing(['category', 'subCategory']);
                } elseif (method_exists($item, 'relationLoaded') && $item->relationLoaded('service') && $item->service) {
                    $service = $item->service;
                    $service->loadMissing(['category', 'subCategory']);
                } else {
                    $service = Service::query()->with(['category', 'subCategory'])->find($item->service_id);
                }
            }

            $basis = 0.0;
            if (is_object($item)) {
                $basis = max(0.0, (float) ($item->total_cost ?? 0) - (float) ($item->tax_amount ?? 0));
            }

            foreach ($types as $type) {
                $group = resolve_additional_charge_group_for_service((string) $type->id, $service);
                $amount = (float) (commission_calc_line_preview($basis, $group, true)['admin_commission'] ?? 0);
                if ($amount <= 0) {
                    continue;
                }
                $tid = (string) $type->id;
                if (! isset($aggregated[$tid])) {
                    $aggregated[$tid] = [
                        'id' => $tid,
                        'name' => $type->name,
                        'amount' => 0.0,
                        'commissionable' => (bool) ($type->is_commissionable ?? true),
                        'customizable' => (bool) ($type->customizable_at_booking ?? false),
                    ];
                }
                $aggregated[$tid]['amount'] += $amount;
            }
        }

        $lines = [];
        foreach ($aggregated as $row) {
            $row['amount'] = round($row['amount'], 2);
            if ($row['amount'] > 0) {
                $lines[] = $row;
            }
        }

        $total = round(array_sum(array_column($lines, 'amount')), 2);

        return ['total' => $total, 'lines' => $lines];
    }
}

if (! function_exists('compute_additional_charges_for_service_basis')) {
    /**
     * Single-line basis (e.g. admin booking or bidding) before tax / fees.
     *
     * @return array{total: float, lines: list<array{id: string, name: string, amount: float}>}
     */
    function compute_additional_charges_for_service_basis(float $basisExTax, ?Service $service): array
    {
        $types = AdditionalChargeType::query()->active()->ordered()->get();
        if ($types->isEmpty()) {
            return ['total' => 0.0, 'lines' => []];
        }

        $basisExTax = max(0.0, $basisExTax);
        $lines = [];

        foreach ($types as $type) {
            $group = resolve_additional_charge_group_for_service((string) $type->id, $service);
            $amount = round((float) (commission_calc_line_preview($basisExTax, $group, true)['admin_commission'] ?? 0), 2);
            if ($amount > 0) {
                $lines[] = [
                    'id' => (string) $type->id,
                    'name' => $type->name,
                    'amount' => $amount,
                    'commissionable' => (bool) ($type->is_commissionable ?? true),
                    'customizable' => (bool) ($type->customizable_at_booking ?? false),
                ];
            }
        }

        $total = round(array_sum(array_column($lines, 'amount')), 2);

        return ['total' => $total, 'lines' => $lines];
    }
}

if (! function_exists('get_additional_charges_cart_total')) {
    function get_additional_charges_cart_total(int|string $customerUserId): float
    {
        $cart = \Modules\CartModule\Entities\Cart::query()
            ->with(['service.category', 'service.subCategory'])
            ->where('customer_id', $customerUserId)
            ->get();

        return compute_additional_charges_for_cart_items($cart)['total'];
    }
}

if (! function_exists('merge_additional_charge_line_amount_overrides')) {
    /**
     * @param  list<array<string, mixed>>  $lines
     * @param  array<string, float|int|string|null>  $overrides  type id => amount
     * @return list<array<string, mixed>>
     */
    function merge_additional_charge_line_amount_overrides(array $lines, array $overrides): array
    {
        if ($overrides === []) {
            return $lines;
        }

        $out = [];
        foreach ($lines as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id !== '' && ! empty($row['customizable']) && array_key_exists($id, $overrides)) {
                $row['amount'] = max(0.0, round((float) $overrides[$id], 2));
            }
            $out[] = $row;
        }

        return $out;
    }
}

if (! function_exists('finalize_additional_charge_lines')) {
    /**
     * Drop zero amounts and recompute total (same filtering as compute_* helpers).
     *
     * @param  list<array<string, mixed>>  $lines
     * @return array{total: float, lines: list<array<string, mixed>>}
     */
    function finalize_additional_charge_lines(array $lines): array
    {
        $filtered = [];
        foreach ($lines as $row) {
            $amt = round((float) ($row['amount'] ?? 0), 2);
            if ($amt <= 0) {
                continue;
            }
            $row['amount'] = $amt;
            $filtered[] = $row;
        }

        $total = round(array_sum(array_column($filtered, 'amount')), 2);

        return ['total' => $total, 'lines' => $filtered];
    }
}

if (! function_exists('compute_additional_charges_for_booking_details')) {
    /**
     * Uses the same basis rules as cart checkout (per detail line ex tax).
     *
     * @return array{total: float, lines: list<array<string, mixed>>}
     */
    function compute_additional_charges_for_booking_details(Booking $booking): array
    {
        $items = $booking->relationLoaded('detail') ? $booking->detail : $booking->detail()->get();
        if ($items->isEmpty()) {
            return ['total' => 0.0, 'lines' => []];
        }

        return compute_additional_charges_for_cart_items($items);
    }
}

if (! function_exists('booking_single_detail_embeds_extra_fee')) {
    /**
     * Admin-style single line: detail.total_cost includes extra_fee (total_booking_amount + extra_fee ≈ detail).
     * Cart-style single line: detail.total_cost === total_booking_amount; extra_fee is separate (do not bump detail).
     */
    function booking_single_detail_embeds_extra_fee(Booking $booking, float $oldExtraFee): bool
    {
        $details = $booking->relationLoaded('detail') ? $booking->detail : $booking->detail()->get();
        if ($details->count() !== 1) {
            return false;
        }
        $d = $details->first();
        $tb = round((float) ($booking->total_booking_amount ?? 0), 2);
        $dc = round((float) ($d->total_cost ?? 0), 2);
        $te = round($oldExtraFee, 2);

        return abs($dc - ($tb + $te)) <= 0.02;
    }
}

if (! function_exists('apply_booking_additional_charges_snapshot')) {
    /**
     * Persists additional charge lines and total. Adjusts the single detail line only when it embeds extra_fee (admin flow).
     */
    function apply_booking_additional_charges_snapshot(Booking $booking, array $lines): void
    {
        $final = finalize_additional_charge_lines($lines);
        $newTotal = $final['total'];
        $keptLines = $final['lines'];
        $oldTotal = round((float) ($booking->extra_fee ?? 0), 2);
        $delta = round($newTotal - $oldTotal, 2);

        $booking->extra_fee = $newTotal;
        $booking->additional_charges_breakdown = count($keptLines) ? $keptLines : null;

        if (abs($delta) > 0.0001 && booking_single_detail_embeds_extra_fee($booking, $oldTotal)) {
            $details = $booking->relationLoaded('detail') ? $booking->detail : $booking->detail()->get();
            $d = $details->first();
            $d->total_cost = round((float) $d->total_cost + $delta, 2);
            $d->save();
        }

        $booking->save();
    }
}

if (! function_exists('recalculate_and_apply_booking_additional_charges')) {
    /**
     * Recompute additional charges from current booking detail rows (non–repeat bookings only).
     */
    function recalculate_and_apply_booking_additional_charges(Booking $booking): void
    {
        if ((int) ($booking->is_repeated ?? 0)) {
            sync_repeat_series_additional_charges((string) $booking->id);

            return;
        }

        $booking->refresh();
        $booking->load(['detail.service.category', 'detail.service.subCategory']);
        $computed = compute_additional_charges_for_booking_details($booking);
        apply_booking_additional_charges_snapshot($booking, $computed['lines']);
    }
}

if (! function_exists('sync_repeat_series_additional_charges')) {
    /**
     * Recompute each repeat occurrence's extra_fee from its lines; canceled/refunded repeats contribute 0.
     * Aggregates parent booking extra_fee and breakdown for the series.
     */
    function sync_repeat_series_additional_charges(string $parentBookingId): void
    {
        $parent = Booking::query()->find($parentBookingId);
        if (! $parent || ! (int) ($parent->is_repeated ?? 0)) {
            return;
        }

        $repeats = BookingRepeat::query()->where('booking_id', $parentBookingId)->get();
        if ($repeats->isEmpty()) {
            return;
        }

        $totalExtra = 0.0;
        $mergedLines = [];

        foreach ($repeats as $r) {
            if (in_array((string) ($r->booking_status ?? ''), ['canceled', 'refunded'], true)) {
                if (abs((float) ($r->extra_fee ?? 0)) > 0.0001) {
                    $r->extra_fee = 0;
                    $r->save();
                }

                continue;
            }

            $r->load(['detail.service.category', 'detail.service.subCategory']);
            $items = $r->relationLoaded('detail') ? $r->detail : $r->detail()->get();
            $computed = compute_additional_charges_for_cart_items($items);
            $final = finalize_additional_charge_lines($computed['lines']);
            $newTotal = $final['total'];
            $oldTotal = round((float) ($r->extra_fee ?? 0), 2);
            $delta = round($newTotal - $oldTotal, 2);

            $r->extra_fee = $newTotal;

            if (abs($delta) > 0.0001 && $items->count() === 1) {
                $d = $items->first();
                $tb = round((float) ($r->total_booking_amount ?? 0), 2);
                $dc = round((float) ($d->total_cost ?? 0), 2);
                if (abs($dc - ($tb + $oldTotal)) <= 0.02) {
                    $d->total_cost = round($dc + $delta, 2);
                    $d->save();
                }
            }

            $r->save();

            $totalExtra += $newTotal;

            foreach ($final['lines'] as $row) {
                $id = (string) ($row['id'] ?? '');
                if ($id === '') {
                    continue;
                }
                if (! isset($mergedLines[$id])) {
                    $mergedLines[$id] = $row;
                    $mergedLines[$id]['amount'] = 0.0;
                }
                $mergedLines[$id]['amount'] = round($mergedLines[$id]['amount'] + (float) ($row['amount'] ?? 0), 2);
            }
        }

        $parentLines = [];
        foreach ($mergedLines as $row) {
            if (($row['amount'] ?? 0) > 0) {
                $parentLines[] = $row;
            }
        }

        $parent->extra_fee = round($totalExtra, 2);
        $parent->additional_charges_breakdown = count($parentLines) ? $parentLines : null;
        $parent->save();
    }
}

if (! function_exists('enrich_booking_additional_charges_breakdown_for_display')) {
    /**
     * Fills commissionable / customizable for legacy rows from current type settings.
     *
     * @return list<array<string, mixed>>
     */
    function enrich_booking_additional_charges_breakdown_for_display(Booking $booking): array
    {
        $rows = $booking->additional_charges_breakdown ?? null;
        if (! is_array($rows) || $rows === []) {
            return [];
        }

        $ids = array_values(array_filter(array_map(fn ($r) => $r['id'] ?? null, $rows)));
        $types = AdditionalChargeType::query()->whereIn('id', $ids)->get()->keyBy('id');

        $out = [];
        foreach ($rows as $r) {
            $id = (string) ($r['id'] ?? '');
            $t = $types->get($id);
            if (array_key_exists('commissionable', $r)) {
                $r['commissionable'] = filter_var($r['commissionable'], FILTER_VALIDATE_BOOLEAN);
            } else {
                $r['commissionable'] = (bool) ($t?->is_commissionable ?? true);
            }
            if (array_key_exists('customizable', $r)) {
                $r['customizable'] = filter_var($r['customizable'], FILTER_VALIDATE_BOOLEAN);
            } else {
                $r['customizable'] = (bool) ($t?->customizable_at_booking ?? false);
            }
            $out[] = $r;
        }

        return $out;
    }
}
