<?php

use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingExtraService;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\TransactionModule\Entities\LedgerTransaction;

if (!function_exists('get_booking_total_amount')) {
    /**
     * Total amount for the booking (main amount + extra services total + visiting/extra fee).
     * Must match the details page Grand Total so payment checks are correct.
     * Works for both Booking and BookingRepeat (repeat uses parent booking's extra_services).
     */
    function get_booking_total_amount($booking): float
    {
        $base = (float) ($booking->total_booking_amount ?? 0);
        $extraTotal = 0;
        if ($booking instanceof Booking && $booking->relationLoaded('extra_services')) {
            $extraTotal = $booking->extra_services->sum('total');
        } elseif ($booking instanceof Booking) {
            $extraTotal = (float) BookingExtraService::where('booking_id', $booking->id)->sum('total');
        } elseif ($booking instanceof BookingRepeat && $booking->relationLoaded('booking')) {
            $extraTotal = (float) BookingExtraService::where('booking_id', $booking->booking_id)->sum('total');
        } elseif ($booking instanceof BookingRepeat) {
            $extraTotal = (float) BookingExtraService::where('booking_id', $booking->booking_id)->sum('total');
        }
        $extraFee = (float) ($booking->extra_fee ?? 0);
        return round($base + $extraTotal + $extraFee, 2);
    }
}

if (!function_exists('get_booking_spare_parts_amount')) {
    /**
     * Sum of spare-parts extra-service lines for this booking (commissioned separately from service rules).
     */
    function get_booking_spare_parts_amount($booking): float
    {
        $bookingId = $booking instanceof BookingRepeat ? $booking->booking_id : $booking->id;
        return (float) BookingExtraService::where('booking_id', $bookingId)
            ->where('type', BookingExtraService::TYPE_SPARE_PART)
            ->sum('total');
    }
}

if (!function_exists('get_booking_advance_paid_amount')) {
    /**
     * Sum of advance/offline partial payments (paid to company at booking create) for this booking.
     * Used to reduce provider's account_payable at completion: net commission = commission - advance.
     * Returns 0 for BookingRepeat (advance is on one-time booking only).
     */
    function get_booking_advance_paid_amount($booking): float
    {
        if ($booking instanceof BookingRepeat) {
            return 0;
        }
        $bookingId = $booking->id ?? null;
        if (!$bookingId) {
            return 0;
        }
        return (float) BookingPartialPayment::where('booking_id', $bookingId)
            ->where('paid_with', 'offline')
            ->sum('paid_amount');
    }
}

if (!function_exists('get_booking_service_amount')) {
    /**
     * service_amount = total_booking_amount - spare_parts_amount (for legacy/other use).
     * Does not include extra_services or extra_fee; use get_booking_commissionable_amount for commission.
     */
    function get_booking_service_amount($booking): float
    {
        $total = (float) ($booking->total_booking_amount ?? 0);
        $spareParts = get_booking_spare_parts_amount($booking);
        return round(max(0, $total - $spareParts), 2);
    }
}

if (!function_exists('get_booking_commissionable_amount')) {
    /**
     * Service-side total for commission: grand total (incl. non–spare extras & fees) minus spare-parts extras.
     * Admin commission on this portion uses Business Model “Service charges” rules.
     */
    function get_booking_commissionable_amount($booking): float
    {
        $grandTotal = get_booking_total_amount($booking);
        $spareParts = get_booking_spare_parts_amount($booking);
        return round(max(0, $grandTotal - $spareParts), 2);
    }
}

if (!function_exists('calculate_commission_for_booking')) {
    /**
     * Admin commission for commission-based bookings.
     * Resolution: provider custom → service → subcategory → category (from first line item) → company;
     * if there is no line item, booking sub_category_id / category_id are used before company.
     */
    function calculate_commission_for_booking($booking, int|string|null $providerId = null): array
    {
        $grandTotal = get_booking_total_amount($booking);
        $serviceLineAmount = get_booking_commissionable_amount($booking);
        $spareLineAmount = get_booking_spare_parts_amount($booking);

        $setup = resolve_commission_tier_setup_for_booking($booking, $providerId);
        $serviceGroup = [
            'mode' => $setup['service']['mode'] ?? 'tiered',
            'fixed_amount' => (float) ($setup['service']['fixed_amount'] ?? 0),
            'tiers' => is_array($setup['service']['tiers'] ?? null) ? $setup['service']['tiers'] : [],
        ];
        $spareGroup = [
            'mode' => $setup['spare_parts']['mode'] ?? 'tiered',
            'fixed_amount' => (float) ($setup['spare_parts']['fixed_amount'] ?? 0),
            'tiers' => is_array($setup['spare_parts']['tiers'] ?? null) ? $setup['spare_parts']['tiers'] : [],
        ];

        $adminOnService = commission_calc_line_preview($serviceLineAmount, $serviceGroup)['admin_commission'];
        $adminOnSpare = $spareLineAmount > 0
            ? commission_calc_line_preview($spareLineAmount, $spareGroup)['admin_commission']
            : 0.0;

        $commission = round((float) $adminOnService + (float) $adminOnSpare, 2);
        $providerEarning = round($grandTotal - $commission, 2);

        return [
            'commissionable_amount' => $serviceLineAmount,
            'service_amount' => $serviceLineAmount,
            'spare_parts_amount' => $spareLineAmount,
            'commission' => $commission,
            'provider_earning' => $providerEarning,
        ];
    }
}

if (!function_exists('get_commission_breakdown_for_booking')) {
    /**
     * Full breakdown for transaction/ledger from calculate_commission_for_booking, then admin promotional deductions.
     */
    function get_commission_breakdown_for_booking($booking): array
    {
        $bookingId = $booking instanceof \Modules\BookingModule\Entities\BookingRepeat ? $booking->booking_id : $booking->id;
        $subscriptionType = \Modules\BookingModule\Entities\SubscriptionBookingType::where('booking_id', $bookingId)->where('type', 'subscription')->first();
        if ($subscriptionType) {
            $totalBookingAmount = (float) ($booking->total_booking_amount ?? 0);
            $extraFee = (float) ($booking->extra_fee ?? 0);
            return [
                'commission' => 0,
                'commission_without_cost' => 0,
                'booking_amount_without_commission' => round($totalBookingAmount - $extraFee, 2),
                'promotional_cost_by_admin' => 0,
                'promotional_cost_by_provider' => 0,
            ];
        }

        $providerId = $booking->provider_id ?? null;
        $result = calculate_commission_for_booking($booking, $providerId);
        $commission = $result['commission'];

        $bookingDetailsAmounts = $booking instanceof \Modules\BookingModule\Entities\BookingRepeat
            ? \Modules\BookingModule\Entities\BookingDetailsAmount::where('booking_repeat_id', $booking->id)->get()
            : \Modules\BookingModule\Entities\BookingDetailsAmount::where('booking_id', $booking->id)->get();

        $promotionalCostByAdmin = 0;
        $promotionalCostByProvider = 0;
        foreach ($bookingDetailsAmounts as $amount) {
            $promotionalCostByAdmin += ($amount->discount_by_admin ?? 0) + ($amount->coupon_discount_by_admin ?? 0) + ($amount->campaign_discount_by_admin ?? 0);
            $promotionalCostByProvider += ($amount->discount_by_provider ?? 0) + ($amount->coupon_discount_by_provider ?? 0) + ($amount->campaign_discount_by_provider ?? 0);
        }
        $commissionWithoutCost = max(0, $commission - $promotionalCostByAdmin);
        $grandTotal = get_booking_total_amount($booking);
        $bookingAmountWithoutCommission = round($grandTotal - $commissionWithoutCost, 2);

        return [
            'commission' => $commission,
            'commission_without_cost' => $commissionWithoutCost,
            'booking_amount_without_commission' => $bookingAmountWithoutCommission,
            'promotional_cost_by_admin' => $promotionalCostByAdmin,
            'promotional_cost_by_provider' => $promotionalCostByProvider,
        ];
    }
}

if (!function_exists('get_booking_received_and_settlement')) {
    /**
     * For admin booking details: amounts received by company vs provider, and settlement (pay provider / provider owes).
     * Company keeps commission; rest goes to provider. So: pay_to_provider = company received - commission (when company has provider's share).
     * provider_owes_company = provider received - provider_share (when provider has company's commission).
     *
     * @return array{company_share, provider_share, amount_received_by_company, amount_received_by_provider, total_paid, pay_to_provider, provider_owes_company}
     */
    function get_booking_received_and_settlement($booking): array
    {
        $breakdown = get_commission_breakdown_for_booking($booking);
        $companyShare = (float) $breakdown['commission_without_cost'];
        $providerShare = (float) $breakdown['booking_amount_without_commission'];

        $bookingForPartials = $booking instanceof \Modules\BookingModule\Entities\BookingRepeat ? $booking->booking : $booking;
        $partials = $bookingForPartials && $bookingForPartials->relationLoaded('booking_partial_payments')
            ? $bookingForPartials->booking_partial_payments
            : ($bookingForPartials ? $bookingForPartials->booking_partial_payments()->get() : collect());
        if (!$partials) {
            $partials = collect();
        }
        $totalPaid = $partials->isNotEmpty()
            ? (float) $partials->sum('paid_amount')
            : ($booking->is_paid ? get_booking_total_amount($booking) : 0);

        if ($partials->isNotEmpty()) {
            // received_by null/empty = company (advance at add-booking is always received by company)
            $amountReceivedByCompany = (float) $partials->filter(function ($p) {
                $by = $p->received_by ?? '';
                return $by === 'company' || $by === '';
            })->sum('paid_amount');
            $amountReceivedByProvider = (float) $partials->where('received_by', 'provider')->sum('paid_amount');
            if ($amountReceivedByCompany == 0 && $amountReceivedByProvider == 0 && $totalPaid > 0) {
                $amountReceivedByCompany = $booking->payment_method !== 'cash_after_service' ? $totalPaid : 0;
                $amountReceivedByProvider = $booking->payment_method === 'cash_after_service' ? $totalPaid : 0;
            }
        } else {
            $amountReceivedByCompany = ($booking->is_paid && $booking->payment_method !== 'cash_after_service') ? $totalPaid : 0;
            $amountReceivedByProvider = ($booking->is_paid && $booking->payment_method === 'cash_after_service') ? $totalPaid : 0;
        }

        // Company keeps company_share; rest goes to provider.
        // Pay to provider = company received more than its share (excess to pass to provider).
        $payToProvider = round(max(0, $amountReceivedByCompany - $companyShare), 2);
        // Provider owes you = only when provider has actually received money they should remit (commission). If provider received 0, they owe 0.
        $commissionShortfall = max(0, $companyShare - $amountReceivedByCompany);
        $providerOwesCompany = $amountReceivedByProvider > 0
            ? round(min($amountReceivedByProvider, $commissionShortfall), 2)
            : 0;

        return [
            'company_share' => round($companyShare, 2),
            'provider_share' => round($providerShare, 2),
            'amount_received_by_company' => round($amountReceivedByCompany, 2),
            'amount_received_by_provider' => round($amountReceivedByProvider, 2),
            'total_paid' => round($totalPaid, 2),
            'pay_to_provider' => $payToProvider,
            'provider_owes_company' => $providerOwesCompany,
        ];
    }
}

if (!function_exists('get_booking_total_paid')) {
    /**
     * Total amount paid for the booking (from partial payments or full payment flag).
     * For BookingRepeat, partial payments are on the main booking; repeat uses is_paid + total_booking_amount.
     */
    function get_booking_total_paid($booking): float
    {
        if ($booking instanceof BookingRepeat) {
            return $booking->is_paid ? round((float) $booking->total_booking_amount, 2) : 0;
        }
        $model = Booking::find($booking->id);
        if (!$model) {
            return 0;
        }
        $partials = $model->booking_partial_payments;
        if ($partials->isNotEmpty()) {
            return round((float) $partials->sum('paid_amount'), 2);
        }
        return $model->is_paid ? round((float) $model->total_booking_amount, 2) : 0;
    }
}

if (!function_exists('booking_can_be_completed')) {
    /**
     * Booking can only be completed if total_paid >= booking_total.
     */
    function booking_can_be_completed($booking): bool
    {
        $totalPaid = get_booking_total_paid($booking);
        $bookingTotal = get_booking_total_amount($booking);
        return $totalPaid >= $bookingTotal;
    }
}

if (!function_exists('booking_display_customer_name')) {
    /**
     * Resolved customer name for booking UIs: linked user profile first, then saved address row,
     * then service_address JSON snapshot (when used). Accepts Booking or BookingRepeat.
     */
    function booking_display_customer_name($booking, $customerAddressModel = null): string
    {
        $main = ($booking instanceof BookingRepeat) ? ($booking->booking ?? null) : $booking;
        if (!$main instanceof Booking) {
            return '';
        }
        $fromUser = $main->customer
            ? trim((string) ($main->customer->first_name ?? '') . ' ' . (string) ($main->customer->last_name ?? ''))
            : '';
        if ($fromUser !== '') {
            return trim($fromUser);
        }
        $fromAddress = $customerAddressModel?->contact_person_name ?? null;
        if (is_string($fromAddress) && trim($fromAddress) !== '') {
            return trim($fromAddress);
        }
        $sa = $booking->service_address ?? null;
        if (is_object($sa) && isset($sa->contact_person_name)) {
            $n = (string) $sa->contact_person_name;
            if (trim($n) !== '') {
                return trim($n);
            }
        }

        return '';
    }
}

if (!function_exists('booking_display_customer_phone')) {
    /**
     * Resolved customer phone for booking UIs (same precedence as booking_display_customer_name).
     */
    function booking_display_customer_phone($booking, $customerAddressModel = null): string
    {
        $main = ($booking instanceof BookingRepeat) ? ($booking->booking ?? null) : $booking;
        if (!$main instanceof Booking) {
            return '';
        }
        $fromUser = $main->customer ? trim((string) ($main->customer->phone ?? '')) : '';
        if ($fromUser !== '') {
            return $fromUser;
        }
        $fromAddress = $customerAddressModel?->contact_person_number ?? null;
        if (is_string($fromAddress) && trim($fromAddress) !== '') {
            return trim($fromAddress);
        }
        $sa = $booking->service_address ?? null;
        if (is_object($sa) && isset($sa->contact_person_number)) {
            $p = (string) $sa->contact_person_number;
            if (trim($p) !== '') {
                return trim($p);
            }
        }

        return '';
    }
}

if (!function_exists('ledger_record_in')) {
    /**
     * Record an IN transaction in the ledger.
     */
    function ledger_record_in(array $data): LedgerTransaction
    {
        $data['type'] = LedgerTransaction::TYPE_IN;
        $data['date'] = $data['date'] ?? now()->toDateString();
        if ((!array_key_exists('created_by', $data) || $data['created_by'] === null) && auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        return LedgerTransaction::create($data);
    }
}

if (!function_exists('ledger_record_out')) {
    /**
     * Record an OUT transaction in the ledger.
     */
    function ledger_record_out(array $data): LedgerTransaction
    {
        $data['type'] = LedgerTransaction::TYPE_OUT;
        $data['date'] = $data['date'] ?? now()->toDateString();
        if ((!array_key_exists('created_by', $data) || $data['created_by'] === null) && auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        return LedgerTransaction::create($data);
    }
}
