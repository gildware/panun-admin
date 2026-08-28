<?php

namespace Modules\BookingModule\Services;

use Carbon\Carbon;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetail;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingExtraService;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\BookingRepeatDetails;
use Modules\BookingModule\Entities\BookingScheduleHistory;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\UserManagement\Entities\UserAddress;

class AdminRepeatBookingWriter
{
    /**
     * Mark the parent as a repeat series without pre-creating visit rows.
     * Billing stays at the per-visit quote until visits are logged.
     */
    public function createSeriesFromCart(
        Booking $booking,
        string $type,
        bool $untilStopped,
        int $visitsPerPeriod,
        Carbon|string $startDate,
        ?string $endDate = null
    ): void {
        $booking->is_repeated = 1;
        $booking->service_schedule = $this->formatDate($startDate);
        $this->applyCadence($booking, $type, $untilStopped, [], $startDate, [], $visitsPerPeriod, $endDate);
        $booking->save();
    }

    /**
     * @param  list<Carbon|string>  $dates
     * @param  array<string, mixed>  $cartPricing
     * @param  list<int>  $weekdays
     * @param  list<int>  $monthDays
     */
    public function createVisitsFromCart(
        Booking $booking,
        array $dates,
        string $type,
        array $cartPricing,
        int|string|null $changedBy,
        string $serviceLocation,
        bool $untilStopped = false,
        array $weekdays = [],
        array $monthDays = []
    ): void {
        $visitCount = count($dates);
        if ($visitCount < 2) {
            return;
        }

        $perVisitAmount = round((float) ($cartPricing['sum_line_totals'] ?? 0), 2);
        $perVisitTax = round((float) ($cartPricing['sum_tax'] ?? 0), 2);
        $perVisitDiscount = round((float) ($cartPricing['sum_basic_discount'] ?? 0), 2);
        $perVisitCampaign = round((float) ($cartPricing['sum_campaign_discount'] ?? 0), 2);
        $perVisitExtra = round((float) ($cartPricing['extra_fee'] ?? 0), 2);

        $booking->is_repeated = 1;
        $booking->service_schedule = $this->formatDate($dates[0]);
        $booking->total_booking_amount = round($perVisitAmount * $visitCount, 2);
        $booking->total_tax_amount = round($perVisitTax * $visitCount, 2);
        $booking->total_discount_amount = round($perVisitDiscount * $visitCount, 2);
        $booking->total_campaign_discount_amount = round($perVisitCampaign * $visitCount, 2);
        $booking->extra_fee = round($perVisitExtra * $visitCount, 2);
        $booking->additional_charges_breakdown = $this->multiplyBreakdown(
            $cartPricing['additional_charge_lines'] ?? [],
            $visitCount
        );
        $this->applyCadence(
            $booking,
            $type,
            $untilStopped,
            $weekdays,
            $dates[0],
            $monthDays,
            $untilStopped ? 0 : $visitCount
        );
        $booking->save();

        $addressJson = $booking->service_address_location
            ?: (json_encode(UserAddress::find($booking->service_address_id)) ?: null);

        foreach ($dates as $index => $date) {
            $repeat = $this->makeRepeatRow(
                $booking,
                $type,
                $this->formatDate($date),
                $perVisitAmount,
                $perVisitTax,
                $perVisitDiscount,
                $perVisitCampaign,
                $perVisitExtra,
                $index,
                (string) ($booking->booking_status ?? 'accepted'),
                $addressJson,
                $serviceLocation
            );

            $this->logSchedule($booking, $repeat, $this->formatDate($date), $changedBy);
            if ($index === 0) {
                $this->logStatus($booking, $repeat, (string) ($booking->booking_status ?? 'accepted'), $changedBy);
            }

            foreach ($cartPricing['lines'] ?? [] as $calc) {
                $svc = $calc['service'] ?? null;
                $quantity = (int) ($calc['quantity'] ?? 1);
                $unitPrice = (float) ($calc['service_cost_unit'] ?? 0);
                $basicDiscount = (float) ($calc['basic_discount'] ?? 0);
                $campaignDiscount = (float) ($calc['campaign_discount'] ?? 0);
                $tax = (float) ($calc['tax_amount'] ?? 0);
                $lineTotal = (float) ($calc['line_total_before_ac'] ?? 0);

                $repeatDetail = new BookingRepeatDetails();
                $repeatDetail->booking_repeat_id = $repeat->id;
                $repeatDetail->booking_id = $booking->id;
                $repeatDetail->service_id = $svc->id ?? ($calc['service_id'] ?? null);
                $repeatDetail->service_name = $svc->name ?? ($calc['service_name'] ?? 'service-not-found');
                $repeatDetail->variant_key = (string) ($calc['variant_key'] ?? '');
                $repeatDetail->quantity = $quantity;
                $repeatDetail->service_cost = $unitPrice;
                $repeatDetail->discount_amount = $basicDiscount;
                $repeatDetail->campaign_discount_amount = $campaignDiscount;
                $repeatDetail->overall_coupon_discount_amount = 0;
                $repeatDetail->tax_amount = $tax;
                $repeatDetail->total_cost = $lineTotal;
                $repeatDetail->save();

                $this->saveAmountFromSplits(
                    $booking->id,
                    $repeat->id,
                    $repeatDetail->id,
                    $unitPrice,
                    $quantity,
                    $tax,
                    $basicDiscount,
                    $campaignDiscount,
                    $calc['line_discount_cost_bearer'] ?? null
                );
            }
        }
    }

    /**
     * @param  list<Carbon|string>  $dates
     * @param  list<int>  $weekdays
     * @param  list<int>  $monthDays
     */
    public function convertExisting(
        Booking $booking,
        array $dates,
        string $type,
        int|string|null $changedBy,
        bool $untilStopped = false,
        array $weekdays = [],
        array $monthDays = [],
        int $plannedVisits = 0,
        ?string $endDate = null
    ): void {
        $visitCount = count($dates);
        if ($visitCount < 1) {
            return;
        }

        $booking->loadMissing(['detail', 'details_amounts']);

        $perVisitAmount = round((float) ($booking->total_booking_amount ?? 0), 2);
        $perVisitTax = round((float) ($booking->total_tax_amount ?? 0), 2);
        $perVisitDiscount = round((float) ($booking->total_discount_amount ?? 0), 2);
        $perVisitCampaign = round((float) ($booking->total_campaign_discount_amount ?? 0), 2);
        $perVisitExtra = round((float) ($booking->extra_fee ?? 0), 2);
        $perVisitCoupon = round((float) ($booking->total_coupon_discount_amount ?? 0), 2);

        $firstStatus = (string) ($booking->booking_status ?? 'accepted');
        $laterStatus = in_array($firstStatus, ['ongoing', 'on_hold'], true) ? 'accepted' : $firstStatus;

        $booking->is_repeated = 1;
        $booking->service_schedule = $this->formatDate($dates[0]);
        $this->applyCadence(
            $booking,
            $type,
            $untilStopped,
            $weekdays,
            $dates[0],
            $monthDays,
            max(1, $plannedVisits),
            $endDate
        );
        $booking->save();

        $addressJson = $booking->service_address_location
            ?: (json_encode(UserAddress::find($booking->service_address_id)) ?: null);
        $serviceLocation = (string) ($booking->service_location ?? 'customer');

        $parentAmounts = $booking->details_amounts ?? collect();

        foreach ($dates as $index => $date) {
            $visitStatus = $index === 0 ? $firstStatus : $laterStatus;
            $repeat = $this->makeRepeatRow(
                $booking,
                $type,
                $this->formatDate($date),
                $perVisitAmount,
                $perVisitTax,
                $perVisitDiscount,
                $perVisitCampaign,
                $perVisitExtra,
                $index,
                $visitStatus,
                $addressJson,
                $serviceLocation
            );
            $repeat->total_coupon_discount_amount = $index === 0 ? $perVisitCoupon : 0;
            $repeat->coupon_code = $index === 0 ? $booking->coupon_code : null;
            $repeat->save();

            $this->logSchedule($booking, $repeat, $this->formatDate($date), $changedBy);
            $this->logStatus($booking, $repeat, $visitStatus, $changedBy);

            foreach ($booking->detail as $detail) {
                $repeatDetail = new BookingRepeatDetails();
                $repeatDetail->booking_repeat_id = $repeat->id;
                $repeatDetail->booking_id = $booking->id;
                $repeatDetail->service_id = $detail->service_id;
                $repeatDetail->service_name = $detail->service_name;
                $repeatDetail->variant_key = $detail->variant_key;
                $repeatDetail->quantity = $detail->quantity;
                $repeatDetail->service_cost = $detail->service_cost;
                $repeatDetail->discount_amount = $detail->discount_amount;
                $repeatDetail->campaign_discount_amount = $detail->campaign_discount_amount;
                $repeatDetail->overall_coupon_discount_amount = $index === 0
                    ? $detail->overall_coupon_discount_amount
                    : 0;
                $repeatDetail->tax_amount = $detail->tax_amount;
                $repeatDetail->total_cost = $detail->total_cost;
                $repeatDetail->save();

                $matched = $parentAmounts->firstWhere('booking_details_id', $detail->id);
                if ($matched) {
                    $amount = $matched->replicate();
                    $amount->booking_details_id = 0;
                    $amount->booking_repeat_id = $repeat->id;
                    $amount->booking_repeat_details_id = $repeatDetail->id;
                    $amount->admin_commission = 0;
                    $amount->provider_earning = 0;
                    if ($index > 0) {
                        $amount->coupon_discount_by_admin = 0;
                        $amount->coupon_discount_by_provider = 0;
                    }
                    $amount->save();
                } else {
                    $this->saveAmountFromSplits(
                        $booking->id,
                        $repeat->id,
                        $repeatDetail->id,
                        (float) $detail->service_cost,
                        (int) $detail->quantity,
                        (float) $detail->tax_amount,
                        (float) $detail->discount_amount,
                        (float) $detail->campaign_discount_amount,
                        null
                    );
                }
            }
        }

        BookingDetailsAmount::query()
            ->where('booking_id', $booking->id)
            ->whereNull('booking_repeat_id')
            ->delete();
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>|null
     */
    private function multiplyBreakdown(array $lines, int $visitCount): ?array
    {
        if ($lines === [] || $visitCount < 1) {
            return $lines === [] ? null : $lines;
        }
        $out = [];
        foreach ($lines as $row) {
            if (! is_array($row)) {
                continue;
            }
            $copy = $row;
            $copy['amount'] = round(((float) ($row['amount'] ?? 0)) * $visitCount, 2);
            $out[] = $copy;
        }

        return $out === [] ? null : $out;
    }

    private function makeRepeatRow(
        Booking $booking,
        string $type,
        string $schedule,
        float $amount,
        float $tax,
        float $discount,
        float $campaign,
        float $extraFee,
        int $index,
        string $status,
        mixed $addressJson,
        string $serviceLocation,
        ?string $visitRemarks = null
    ): BookingRepeat {
        $repeat = new BookingRepeat();
        $repeat->booking_id = $booking->id;
        $repeat->provider_id = $booking->provider_id;
        $repeat->booking_type = $type;
        $repeat->transaction_id = $booking->transaction_id;
        $repeat->booking_status = $status;
        $repeat->payment_method = $booking->payment_method;
        $repeat->is_paid = 0;
        $repeat->service_schedule = $schedule;
        $repeat->visit_remarks = $visitRemarks;
        $repeat->total_booking_amount = $amount;
        $repeat->total_tax_amount = $tax;
        $repeat->total_discount_amount = $discount;
        $repeat->total_campaign_discount_amount = $campaign;
        $repeat->total_coupon_discount_amount = 0;
        $repeat->extra_fee = $extraFee;
        $repeat->total_referral_discount_amount = $index === 0
            ? (float) ($booking->total_referral_discount_amount ?? 0)
            : 0;
        $repeat->booking_otp = (string) random_int(100000, 999999);
        $repeat->readable_id = $booking->readable_id . '-' . $this->suffix($index);
        $repeat->service_address_location = $addressJson;
        $repeat->service_location = $serviceLocation;
        $repeat->save();

        return $repeat;
    }

    /**
     * @param  list<Carbon>  $dates
     */
    public function appendVisitsFromTemplate(Booking $booking, array $dates, int|string|null $changedBy, ?string $status = null, ?string $visitRemarks = null): int
    {
        if ($dates === []) {
            return 0;
        }
        $booking->loadMissing(['repeat.detail', 'repeat.details_amounts']);
        $template = $booking->repeat->sortBy('service_schedule')->first();
        if (! $template) {
            return 0;
        }

        $existingCount = $booking->repeat->count();
        $addressJson = $booking->service_address_location
            ?: (json_encode(UserAddress::find($booking->service_address_id)) ?: null);
        $serviceLocation = (string) ($booking->service_location ?? 'customer');
        $type = (string) ($template->booking_type ?? 'weekly');
        $laterStatus = $status;
        if ($laterStatus === null) {
            $laterStatus = in_array((string) ($booking->booking_status ?? ''), ['ongoing', 'on_hold'], true)
                ? 'accepted'
                : (string) ($booking->booking_status ?? 'accepted');
            if (in_array($laterStatus, ['completed', 'canceled', 'cancelled'], true)) {
                $laterStatus = 'accepted';
            }
        }

        $added = 0;
        foreach ($dates as $date) {
            $index = $existingCount + $added;
            $repeat = $this->makeRepeatRow(
                $booking,
                $type,
                $this->formatDate($date),
                (float) $template->total_booking_amount,
                (float) $template->total_tax_amount,
                (float) $template->total_discount_amount,
                (float) $template->total_campaign_discount_amount,
                (float) $template->extra_fee,
                $index,
                $laterStatus,
                $addressJson,
                $serviceLocation,
                $visitRemarks
            );
            $this->logSchedule($booking, $repeat, $this->formatDate($date), $changedBy);
            $this->logStatus($booking, $repeat, $laterStatus, $changedBy, $visitRemarks);

            foreach ($template->detail as $detail) {
                $repeatDetail = $detail->replicate();
                $repeatDetail->booking_repeat_id = $repeat->id;
                $repeatDetail->booking_id = $booking->id;
                $repeatDetail->overall_coupon_discount_amount = 0;
                $repeatDetail->save();

                $matched = $template->details_amounts->firstWhere('booking_repeat_details_id', $detail->id)
                    ?? $template->details_amounts->first();
                if ($matched) {
                    $amount = $matched->replicate();
                    $amount->booking_details_id = 0;
                    $amount->booking_id = $booking->id;
                    $amount->booking_repeat_id = $repeat->id;
                    $amount->booking_repeat_details_id = $repeatDetail->id;
                    $amount->admin_commission = 0;
                    $amount->provider_earning = 0;
                    $amount->coupon_discount_by_admin = 0;
                    $amount->coupon_discount_by_provider = 0;
                    $amount->save();
                }
            }
            if ($existingCount === 0 && $added === 0) {
                $this->attachOrphanParentExtrasToVisit($booking, $repeat);
            }
            $added++;
        }

        if ($added > 0) {
            $booking->total_booking_amount = round((float) $booking->total_booking_amount + ((float) $template->total_booking_amount * $added), 2);
            $booking->total_tax_amount = round((float) $booking->total_tax_amount + ((float) $template->total_tax_amount * $added), 2);
            $booking->total_discount_amount = round((float) $booking->total_discount_amount + ((float) $template->total_discount_amount * $added), 2);
            $booking->total_campaign_discount_amount = round((float) $booking->total_campaign_discount_amount + ((float) $template->total_campaign_discount_amount * $added), 2);
            $booking->extra_fee = round((float) $booking->extra_fee + ((float) $template->extra_fee * $added), 2);
            if ((string) ($booking->booking_status ?? '') === 'completed') {
                $booking->booking_status = 'accepted';
            }
            $booking->save();
        }

        return $added;
    }

    /**
     * Add one visit. Scheduled visits stay accepted so reminders can fire;
     * attended visits start as ongoing.
     */
    public function addVisitAt(Booking $booking, Carbon|string $at, int|string|null $changedBy, ?string $visitRemarks = null, string $kind = 'attended'): int
    {
        $booking->loadMissing(['repeat.detail', 'repeat.details_amounts', 'detail', 'details_amounts']);
        $schedule = $this->formatDate($at);
        $remarks = $this->normalizeVisitRemarks($visitRemarks);
        $status = $kind === 'scheduled' ? 'accepted' : 'ongoing';

        if ($booking->repeat->isNotEmpty()) {
            $added = $this->appendVisitsFromTemplate($booking, [$at], $changedBy, $status, $remarks);
            if ($added > 0) {
                $booking->load('repeat');
                $this->syncParentAfterVisit($booking, $schedule, $kind);
            }

            return $added;
        }

        $type = $booking->repeatCadenceType() ?: 'weekly';
        $amount = round((float) ($booking->total_booking_amount ?? 0), 2);
        $tax = round((float) ($booking->total_tax_amount ?? 0), 2);
        $discount = round((float) ($booking->total_discount_amount ?? 0), 2);
        $campaign = round((float) ($booking->total_campaign_discount_amount ?? 0), 2);
        $extra = round((float) ($booking->extra_fee ?? 0), 2);
        $addressJson = $booking->service_address_location
            ?: (json_encode(UserAddress::find($booking->service_address_id)) ?: null);
        $serviceLocation = (string) ($booking->service_location ?? 'customer');

        $repeat = $this->makeRepeatRow(
            $booking,
            $type,
            $schedule,
            $amount,
            $tax,
            $discount,
            $campaign,
            $extra,
            0,
            $status,
            $addressJson,
            $serviceLocation,
            $remarks
        );
        $repeat->total_coupon_discount_amount = round((float) ($booking->total_coupon_discount_amount ?? 0), 2);
        $repeat->coupon_code = $booking->coupon_code;
        $repeat->save();

        $this->logSchedule($booking, $repeat, $schedule, $changedBy);
        $this->logStatus($booking, $repeat, $status, $changedBy, $remarks);

        $parentAmounts = $booking->details_amounts ?? collect();
        foreach ($booking->detail as $detail) {
            $repeatDetail = new BookingRepeatDetails();
            $repeatDetail->booking_repeat_id = $repeat->id;
            $repeatDetail->booking_id = $booking->id;
            $repeatDetail->service_id = $detail->service_id;
            $repeatDetail->service_name = $detail->service_name;
            $repeatDetail->variant_key = $detail->variant_key;
            $repeatDetail->quantity = $detail->quantity;
            $repeatDetail->service_cost = $detail->service_cost;
            $repeatDetail->discount_amount = $detail->discount_amount;
            $repeatDetail->campaign_discount_amount = $detail->campaign_discount_amount;
            $repeatDetail->overall_coupon_discount_amount = $detail->overall_coupon_discount_amount;
            $repeatDetail->tax_amount = $detail->tax_amount;
            $repeatDetail->total_cost = $detail->total_cost;
            $repeatDetail->save();

            $matched = $parentAmounts->firstWhere('booking_details_id', $detail->id);
            if ($matched) {
                $amountRow = $matched->replicate();
                $amountRow->booking_details_id = 0;
                $amountRow->booking_repeat_id = $repeat->id;
                $amountRow->booking_repeat_details_id = $repeatDetail->id;
                $amountRow->admin_commission = 0;
                $amountRow->provider_earning = 0;
                $amountRow->save();
            } else {
                $this->saveAmountFromSplits(
                    $booking->id,
                    $repeat->id,
                    $repeatDetail->id,
                    (float) $detail->service_cost,
                    (int) $detail->quantity,
                    (float) $detail->tax_amount,
                    (float) $detail->discount_amount,
                    (float) $detail->campaign_discount_amount,
                    null
                );
            }
        }

        BookingDetailsAmount::query()
            ->where('booking_id', $booking->id)
            ->whereNull('booking_repeat_id')
            ->delete();

        $this->attachOrphanParentExtrasToVisit($booking, $repeat);

        $booking->load('repeat');
        $this->syncParentAfterVisit($booking, $schedule, $kind);

        return 1;
    }

    /**
     * Replace a visit's copied series lines with a create-booking cart (services + prices).
     *
     * @param  array<string, mixed>  $cartPricing
     */
    public function replaceVisitServicesFromCart(Booking $booking, BookingRepeat $visit, array $cartPricing): void
    {
        $lines = $cartPricing['lines'] ?? [];
        if (! is_array($lines) || $lines === []) {
            return;
        }

        $oldAmount = round((float) ($visit->total_booking_amount ?? 0), 2);
        $oldTax = round((float) ($visit->total_tax_amount ?? 0), 2);
        $oldDiscount = round((float) ($visit->total_discount_amount ?? 0), 2);
        $oldCampaign = round((float) ($visit->total_campaign_discount_amount ?? 0), 2);
        $oldExtra = round((float) ($visit->extra_fee ?? 0), 2);

        BookingDetailsAmount::query()->where('booking_repeat_id', $visit->id)->delete();
        BookingRepeatDetails::query()->where('booking_repeat_id', $visit->id)->delete();

        $newAmount = round((float) ($cartPricing['sum_line_totals'] ?? $cartPricing['sum_line_totals'] ?? 0), 2);
        $newTax = round((float) ($cartPricing['sum_tax'] ?? $cartPricing['sum_tax'] ?? 0), 2);
        $newDiscount = round((float) ($cartPricing['sum_basic_discount'] ?? $cartPricing['sum_basic_discount'] ?? 0), 2);
        $newCampaign = round((float) ($cartPricing['sum_campaign_discount'] ?? $cartPricing['sum_campaign_discount'] ?? 0), 2);
        $newExtra = round((float) ($cartPricing['extra_fee'] ?? $cartPricing['extra_fee'] ?? 0), 2);

        foreach ($lines as $calc) {
            if (! is_array($calc)) {
                continue;
            }
            $svc = $calc['service'] ?? null;
            $quantity = (int) ($calc['quantity'] ?? 1);
            $unitPrice = (float) ($calc['service_cost_unit'] ?? $calc['service_cost_unit'] ?? 0);
            $basicDiscount = (float) ($calc['basic_discount'] ?? 0);
            $campaignDiscount = (float) ($calc['campaign_discount'] ?? 0);
            $tax = (float) ($calc['tax_amount'] ?? 0);
            $lineTotal = (float) ($calc['line_total_before_ac'] ?? $calc['line_total_before_ac'] ?? 0);

            $repeatDetail = new BookingRepeatDetails();
            $repeatDetail->booking_repeat_id = $visit->id;
            $repeatDetail->booking_id = $booking->id;
            $repeatDetail->service_id = $svc->id ?? ($calc['service_id'] ?? null);
            $repeatDetail->service_name = $svc->name ?? ($calc['service_name'] ?? 'service-not-found');
            $repeatDetail->variant_key = (string) ($calc['variant_key'] ?? $calc['variant_key'] ?? '');
            $repeatDetail->quantity = max(1, $quantity);
            $repeatDetail->service_cost = $unitPrice;
            $repeatDetail->discount_amount = $basicDiscount;
            $repeatDetail->campaign_discount_amount = $campaignDiscount;
            $repeatDetail->overall_coupon_discount_amount = 0;
            $repeatDetail->tax_amount = $tax;
            $repeatDetail->total_cost = $lineTotal;
            $repeatDetail->save();

            $this->saveAmountFromSplits(
                $booking->id,
                $visit->id,
                $repeatDetail->id,
                $unitPrice,
                max(1, $quantity),
                $tax,
                $basicDiscount,
                $campaignDiscount,
                $calc['line_discount_cost_bearer'] ?? $calc['line_discount_cost_bearer'] ?? null
            );
        }

        $visit->total_booking_amount = $newAmount;
        $visit->total_tax_amount = $newTax;
        $visit->total_discount_amount = $newDiscount;
        $visit->total_campaign_discount_amount = $newCampaign;
        $visit->extra_fee = $newExtra;
        $visit->save();

        $booking->total_booking_amount = round((float) $booking->total_booking_amount + ($newAmount - $oldAmount), 2);
        $booking->total_tax_amount = round((float) $booking->total_tax_amount + ($newTax - $oldTax), 2);
        $booking->total_discount_amount = round((float) $booking->total_discount_amount + ($newDiscount - $oldDiscount), 2);
        $booking->total_campaign_discount_amount = round((float) $booking->total_campaign_discount_amount + ($newCampaign - $oldCampaign), 2);
        $booking->extra_fee = round((float) $booking->extra_fee + ($newExtra - $oldExtra), 2);
        $booking->save();
    }

    public function rescheduleVisit(BookingRepeat $repeat, Carbon|string $at, int|string|null $changedBy): bool
    {
        $schedule = $this->formatDate($at);
        $previous = $repeat->service_schedule
            ? Carbon::parse($repeat->service_schedule)->format('Y-m-d H:i:s')
            : '';
        if ($previous === $schedule) {
            return false;
        }

        $repeat->service_schedule = $schedule;
        $repeat->save();

        $booking = $repeat->booking;
        if ($booking) {
            $this->logSchedule($booking, $repeat, $schedule, $changedBy);
            $this->syncParentScheduleFromVisits($booking);
        }

        return true;
    }

    public function updateSeriesDates(Booking $booking, Carbon $start, ?Carbon $end): void
    {
        $meta = is_array($booking->repeat_cadence_meta) ? $booking->repeat_cadence_meta : [];
        $untilStopped = $end === null;
        $meta['start_date'] = $start->toDateString();
        $meta['end_date'] = $untilStopped ? null : $end->toDateString();
        $meta['until_stopped'] = $untilStopped;

        $booking->loadMissing('repeat');
        if ($booking->repeat->isEmpty()) {
            $time = trim((string) ($meta['time'] ?? ''));
            if ($time === '' && ! empty($booking->service_schedule)) {
                $time = Carbon::parse($booking->service_schedule)->format('H:i:s');
            }
            if ($time === '') {
                $time = '09:00:00';
            }
            $meta['time'] = $time;
            $booking->service_schedule = $start->copy()->setTimeFromTimeString($time)->format('Y-m-d H:i:s');
        }

        $booking->repeat_cadence_meta = $meta;
        $booking->repeat_until_stopped = $untilStopped ? 1 : 0;
        $booking->save();
    }

    private function syncParentAfterVisit(Booking $booking, string $schedule, string $kind): void
    {
        $parentStatus = (string) ($booking->booking_status ?? '');
        if ($kind === 'attended') {
            if (in_array($parentStatus, ['accepted', 'pending', 'completed'], true)) {
                $booking->booking_status = 'ongoing';
            }
            $booking->service_schedule = $schedule;
            $booking->save();

            return;
        }

        if ($parentStatus === 'completed') {
            $booking->booking_status = 'accepted';
        }
        $this->syncParentScheduleFromVisits($booking);
    }

    private function syncParentScheduleFromVisits(Booking $booking): void
    {
        $booking->loadMissing('repeat');
        $active = $booking->repeat
            ->filter(function ($repeat) {
                return in_array((string) $repeat->booking_status, ['ongoing', 'on_hold', 'accepted', 'pending'], true)
                    && ! empty($repeat->service_schedule);
            })
            ->sortBy('service_schedule')
            ->first();
        if ($active) {
            $booking->service_schedule = $this->formatDate($active->service_schedule);
            $booking->save();
        }
    }

    private function normalizeVisitRemarks(?string $remarks): ?string
    {
        $trimmed = trim((string) $remarks);
        if ($trimmed === '') {
            return null;
        }

        return mb_substr($trimmed, 0, 2000);
    }

    /**
     * @param  list<int>  $weekdays
     * @param  list<int>  $monthDays
     */
    private function applyCadence(
        Booking $booking,
        string $type,
        bool $untilStopped,
        array $weekdays,
        Carbon|string $firstDate,
        array $monthDays = [],
        int $plannedVisits = 0,
        ?string $endDate = null
    ): void {
        $first = $firstDate instanceof Carbon ? $firstDate : Carbon::parse(str_replace('T', ' ', (string) $firstDate));
        $end = $endDate ? trim($endDate) : '';
        $untilStopped = $untilStopped || $end === '';
        $visitsPerPeriod = max(1, $plannedVisits);
        $booking->repeat_until_stopped = $untilStopped ? 1 : 0;
        $booking->repeat_stopped_at = null;
        $booking->repeat_cadence_meta = [
            'until_stopped' => $untilStopped,
            'type' => $type,
            'weekdays' => array_values(array_map('intval', $weekdays)),
            'month_days' => array_values(array_map('intval', $monthDays)),
            'visits_per_period' => $visitsPerPeriod,
            'planned_visits' => $visitsPerPeriod,
            'start_date' => $first->toDateString(),
            'end_date' => $untilStopped ? null : $end,
            'time' => $first->format('H:i:s'),
        ];
    }

    private function saveAmountFromSplits(
        string $bookingId,
        string $repeatId,
        string $repeatDetailId,
        float $unitPrice,
        int $quantity,
        float $tax,
        float $basicDiscount,
        float $campaignDiscount,
        mixed $bearer
    ): void {
        $lineBearer = \App\Lib\DiscountCostBearer::normalize($bearer);
        $lineSplits = \App\Lib\DiscountCostBearer::splitBasicAndCampaign(
            $basicDiscount,
            $campaignDiscount,
            $lineBearer
        );
        $amount = new BookingDetailsAmount();
        $amount->booking_details_id = 0;
        $amount->booking_id = $bookingId;
        $amount->booking_repeat_id = $repeatId;
        $amount->booking_repeat_details_id = $repeatDetailId;
        $amount->service_unit_cost = $unitPrice;
        $amount->service_quantity = $quantity;
        $amount->service_tax = $tax;
        $amount->discount_by_admin = $lineSplits['discount_by_admin'];
        $amount->discount_by_provider = $lineSplits['discount_by_provider'];
        $amount->campaign_discount_by_admin = $lineSplits['campaign_discount_by_admin'];
        $amount->campaign_discount_by_provider = $lineSplits['campaign_discount_by_provider'];
        $amount->coupon_discount_by_admin = 0;
        $amount->coupon_discount_by_provider = 0;
        $amount->discount_cost_bearer = $lineBearer;
        $amount->admin_commission = 0;
        $amount->save();
    }

    private function attachOrphanParentExtrasToVisit(Booking $booking, BookingRepeat $visit): void
    {
        $orphans = BookingExtraService::query()
            ->where('booking_id', $booking->id)
            ->whereNull('booking_repeat_id')
            ->get();

        foreach ($orphans as $extra) {
            $extra->booking_repeat_id = $visit->id;
            $extra->save();
        }
    }

    private function logSchedule(Booking $booking, BookingRepeat $repeat, string $schedule, int|string|null $changedBy): void
    {
        $row = new BookingScheduleHistory();
        $row->booking_id = $booking->id;
        $row->booking_repeat_id = $repeat->id;
        $row->changed_by = $changedBy;
        $row->schedule = $schedule;
        $row->save();
    }

    private function logStatus(Booking $booking, BookingRepeat $repeat, string $status, int|string|null $changedBy, ?string $remarks = null): void
    {
        $row = new BookingStatusHistory();
        $row->changed_by = $changedBy;
        $row->booking_id = $booking->id;
        $row->booking_repeat_id = $repeat->id;
        $row->booking_status = $status;
        $row->status_change_remarks = $remarks;
        $row->save();
    }

    private function formatDate(Carbon|string $date): string
    {
        if ($date instanceof Carbon) {
            return $date->format('Y-m-d H:i:s');
        }

        return Carbon::parse(str_replace('T', ' ', $date))->format('Y-m-d H:i:s');
    }

    private function suffix(int $index): string
    {
        $letters = range('A', 'Z');
        $base = count($letters);
        $result = '';
        do {
            $result = $letters[$index % $base] . $result;
            $index = intdiv($index, $base) - 1;
        } while ($index >= 0);

        return $result;
    }
}
