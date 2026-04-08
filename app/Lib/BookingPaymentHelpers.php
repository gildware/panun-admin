<?php

use App\Lib\DiscountCostBearer;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetail;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingExtraService;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\BookingRepeatDetails;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\ServiceManagement\Entities\Service;
use Modules\TransactionModule\Entities\LedgerTransaction;

if (!function_exists('get_booking_total_amount')) {
    /**
     * Payable grand total for the booking: stored line total (total_booking_amount) + sum(extra_services.total) + extra_fee.
     * Admin/cart flows store total_booking_amount excluding extra_fee; extra_fee is added here. Use this everywhere
     * payment due, invoices, and UI totals must agree (do not rebuild from gross subtotal + tax in Blade).
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

if (!function_exists('get_booking_invoice_due_amount')) {
    /**
     * Remaining amount due on an invoice (payable total minus partial payments), with legacy additional_charge for non–cash-after-service.
     */
    function get_booking_invoice_due_amount($booking): float
    {
        if ($booking instanceof Booking
            && BookingFinancialSettlementService::outcomeUsesDecidedVisitCharges((string) ($booking->settlement_outcome ?? ''))) {
            $config = is_array($booking->settlement_config) ? $booking->settlement_config : [];
            $retained = app(BookingFinancialSettlementService::class)->resolveRetainedVisitAmount($booking, $config);
            $invTotal = round($retained, 2);
            $partials = $booking->booking_partial_payments ?? collect();
            $paid = (float) $partials->sum('paid_amount');
            $due = ((bool) ($booking->is_paid ?? false) || round($paid, 2) >= $invTotal)
                ? 0.0
                : round(max(0, $invTotal - $paid), 2);
            if ($due > 0 && in_array((string) ($booking->booking_status ?? ''), ['pending', 'accepted', 'ongoing'], true)
                && ($booking->payment_method ?? '') !== 'cash_after_service'
                && (float) ($booking->additional_charge ?? 0) > 0) {
                $due = round($due + (float) $booking->additional_charge, 2);
            }

            return $due;
        }

        // Standard outcome: do not show invoice "due" vs full grand total once the booking is
        // canceled/refunded (refund is capped at paid; shortfall is not collectible the same way).
        // Retained / visit-charge outcomes are handled in the branch above.
        if (in_array((string) ($booking->booking_status ?? ''), ['canceled', 'cancelled', 'refunded'], true)) {
            return 0.0;
        }

        $invTotal = round(get_booking_total_amount($booking), 2);
        $partials = $booking->booking_partial_payments ?? collect();
        $paid = (float) $partials->sum('paid_amount');
        $due = ((bool) ($booking->is_paid ?? false) || round($paid, 2) >= $invTotal)
            ? 0.0
            : round(max(0, $invTotal - $paid), 2);
        if ($due > 0 && in_array((string) ($booking->booking_status ?? ''), ['pending', 'accepted', 'ongoing'], true)
            && ($booking->payment_method ?? '') !== 'cash_after_service'
            && (float) ($booking->additional_charge ?? 0) > 0) {
            $due = round($due + (float) $booking->additional_charge, 2);
        }

        return $due;
    }
}

if (!function_exists('get_booking_payable_total_for_partial_dues')) {
    /**
     * Total customer payment obligation used for installment rows: "due after this payment" and {@see BookingPartialPayment::due_amount}.
     * Aligns with invoice grand total (or retained visit amount when that settlement mode applies), without legacy additional_charge bumps.
     */
    function get_booking_payable_total_for_partial_dues($booking): float
    {
        if ($booking instanceof Booking
            && BookingFinancialSettlementService::outcomeUsesDecidedVisitCharges((string) ($booking->settlement_outcome ?? ''))) {
            $config = is_array($booking->settlement_config) ? $booking->settlement_config : [];

            return round((float) app(BookingFinancialSettlementService::class)->resolveRetainedVisitAmount($booking, $config), 2);
        }

        return round(get_booking_total_amount($booking), 2);
    }
}

if (!function_exists('format_booking_payment_method_for_admin_display')) {
    /**
     * Single line for admin/provider UIs: e.g. "Offline payment — QR Code UPI" when customer chose an offline method.
     */
    function format_booking_payment_method_for_admin_display($booking): string
    {
        if (($booking->payment_method ?? '') === 'prepaid') {
            return translate('Prepaid_payment');
        }
        $pm = (string) ($booking->payment_method ?? '');
        if ($pm === 'offline_payment') {
            if (! $booking->relationLoaded('booking_offline_payments')) {
                $booking->loadMissing('booking_offline_payments');
            }
            $mn = trim((string) ($booking->booking_offline_payments?->first()?->method_name ?? ''));
            if ($mn !== '') {
                $generic = translate('ledger_pm_offline_payment');
                if ($generic === 'ledger_pm_offline_payment') {
                    $generic = translate('offline_payment');
                }

                return $generic . ' — ' . $mn;
            }
        }

        return str_replace(['_', '-'], ' ', $pm);
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

if (!function_exists('get_booking_extra_service_line_discount_total')) {
    /**
     * Sum of manual discounts on admin "Extra Service" lines (booking_extra_services.discount, type service only).
     * Booking.total_discount_amount only aggregates catalog booking detail lines; spare-part discounts stay inside the Spare Parts total.
     */
    function get_booking_extra_service_line_discount_total($booking): float
    {
        $bookingId = null;
        if ($booking instanceof BookingRepeat) {
            $bookingId = $booking->booking_id ?? null;
        } elseif ($booking instanceof Booking) {
            $bookingId = $booking->id ?? null;
        }
        if (!$bookingId) {
            return 0.0;
        }

        return round((float) BookingExtraService::query()
            ->where('booking_id', $bookingId)
            ->where('type', BookingExtraService::TYPE_SERVICE)
            ->sum('discount'), 2);
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

if (!function_exists('get_booking_non_commissionable_additional_charges_total')) {
    /**
     * Sum of additional charge lines marked not commissionable (excluded from admin commission basis).
     */
    function get_booking_non_commissionable_additional_charges_total($booking): float
    {
        if ($booking instanceof \Modules\BookingModule\Entities\BookingRepeat) {
            $parent = $booking->relationLoaded('booking') ? $booking->booking : $booking->booking()->first();
            $breakdown = $parent ? ($parent->additional_charges_breakdown ?? null) : null;
        } else {
            $breakdown = $booking->additional_charges_breakdown ?? null;
        }

        if (! is_array($breakdown) || $breakdown === []) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($breakdown as $row) {
            $commissionable = $row['commissionable'] ?? true;
            if ($commissionable === false || $commissionable === 0 || $commissionable === '0') {
                $sum += (float) ($row['amount'] ?? 0);
            }
        }

        return round($sum, 2);
    }
}

if (!function_exists('booking_line_row_commission_uplift_when_no_bearer')) {
    /**
     * @deprecated Commission tiers use payable line totals; uplift is no longer applied for discount_cost_bearer "none".
     *
     * @param  BookingDetail|BookingRepeatDetails  $lineRow
     */
    function booking_line_row_commission_uplift_when_no_bearer(object $lineRow): float
    {
        return 0.0;
    }
}

if (!function_exists('get_booking_commission_basis_uplift_for_none_bearer')) {
    /**
     * @deprecated Retained for call compatibility; always zero. See get_booking_commissionable_amount().
     */
    function get_booking_commission_basis_uplift_for_none_bearer($booking): float
    {
        return 0.0;
    }
}

if (!function_exists('get_booking_commissionable_amount')) {
    /**
     * Service-side total for commission: grand total (incl. non–spare extras & fees) minus spare-parts extras.
     * Additional charge lines marked not commissionable are excluded from this basis.
     * Admin commission on this portion uses Business Model “Service charges” rules.
     * Uses payable amounts after line discounts (including discount_cost_bearer "none"); no pre-discount uplift.
     */
    function get_booking_commissionable_amount($booking): float
    {
        $grandTotal = get_booking_total_amount($booking);
        $spareParts = get_booking_spare_parts_amount($booking);
        $nonCommissionableAc = get_booking_non_commissionable_additional_charges_total($booking);

        return round(max(0, $grandTotal - $spareParts - $nonCommissionableAc), 2);
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

if (!function_exists('calculate_commission_for_admin_booking_create_preview')) {
    /**
     * Admin add-booking preview: mirrors calculate_commission_for_booking using cart-derived amounts
     * (no persisted booking, extra_services, or details rows).
     *
     * @param  array{service: array, spare_parts: array}  $tierSetup
     * @return array{company_commission: float, provider_commission: float}
     */
    function calculate_commission_for_admin_booking_create_preview(
        float $grandTotal,
        float $sparePartsAmount,
        float $nonCommissionableAdditionalChargesAmount,
        array $tierSetup
    ): array {
        $commissionableServiceAmount = round(max(0.0, $grandTotal - $sparePartsAmount - $nonCommissionableAdditionalChargesAmount), 2);

        $serviceGroup = [
            'mode' => $tierSetup['service']['mode'] ?? 'tiered',
            'fixed_amount' => (float) ($tierSetup['service']['fixed_amount'] ?? 0),
            'tiers' => is_array($tierSetup['service']['tiers'] ?? null) ? $tierSetup['service']['tiers'] : [],
        ];
        $spareGroup = [
            'mode' => $tierSetup['spare_parts']['mode'] ?? 'tiered',
            'fixed_amount' => (float) ($tierSetup['spare_parts']['fixed_amount'] ?? 0),
            'tiers' => is_array($tierSetup['spare_parts']['tiers'] ?? null) ? $tierSetup['spare_parts']['tiers'] : [],
        ];

        $adminOnService = (float) (commission_calc_line_preview($commissionableServiceAmount, $serviceGroup)['admin_commission'] ?? 0);
        $adminOnSpare = $sparePartsAmount > 0.0001
            ? (float) (commission_calc_line_preview($sparePartsAmount, $spareGroup)['admin_commission'] ?? 0)
            : 0.0;

        $companyCommission = round($adminOnService + $adminOnSpare, 2);
        $providerCommission = round(max(0.0, $grandTotal - $companyCommission), 2);

        return [
            'company_commission' => $companyCommission,
            'provider_commission' => $providerCommission,
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
        $cd = $booking->calculateCommissionDetails($booking, $providerId);
        $commission = (float) $cd['adminCommission'];
        $commissionWithoutCost = (float) $cd['adminCommissionWithoutCost'];

        $bookingDetailsAmounts = $booking instanceof \Modules\BookingModule\Entities\BookingRepeat
            ? \Modules\BookingModule\Entities\BookingDetailsAmount::where('booking_repeat_id', $booking->id)->get()
            : \Modules\BookingModule\Entities\BookingDetailsAmount::where('booking_id', $booking->id)->get();

        $promotionalCostByAdmin = 0;
        $promotionalCostByProvider = 0;
        foreach ($bookingDetailsAmounts as $amount) {
            $promotionalCostByAdmin += ($amount->discount_by_admin ?? 0) + ($amount->coupon_discount_by_admin ?? 0) + ($amount->campaign_discount_by_admin ?? 0);
            $promotionalCostByProvider += ($amount->discount_by_provider ?? 0) + ($amount->coupon_discount_by_provider ?? 0) + ($amount->campaign_discount_by_provider ?? 0);
        }
        $grandTotal = get_booking_total_amount($booking);
        $svc = app(\Modules\BookingModule\Services\BookingFinancialSettlementService::class);
        $main = $svc->mainBookingFor($booking);
        $paid = $svc->totalPaidForMainBooking($main);
        $providerBasis = $svc->providerEarningBasisAmount($main, $grandTotal, $paid);
        $bookingAmountWithoutCommission = round(max(0, $providerBasis - $commissionWithoutCost), 2);

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
     * @return array{company_share: float, provider_share: float, amount_received_by_company: float, amount_received_by_provider: float, total_paid: float, pay_to_provider: float, provider_owes_company: float, net_revenue_zeroed_after_refund: bool}
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

        $netRevenueZeroedAfterRefund = $booking instanceof Booking && booking_should_zero_net_revenue_settlement_display($booking);
        if ($netRevenueZeroedAfterRefund) {
            return [
                'company_share' => 0.0,
                'provider_share' => 0.0,
                'amount_received_by_company' => 0.0,
                'amount_received_by_provider' => 0.0,
                'total_paid' => round($totalPaid, 2),
                'pay_to_provider' => 0.0,
                'provider_owes_company' => 0.0,
                'net_revenue_zeroed_after_refund' => true,
            ];
        }

        return [
            'company_share' => round($companyShare, 2),
            'provider_share' => round($providerShare, 2),
            'amount_received_by_company' => round($amountReceivedByCompany, 2),
            'amount_received_by_provider' => round($amountReceivedByProvider, 2),
            'total_paid' => round($totalPaid, 2),
            'pay_to_provider' => $payToProvider,
            'provider_owes_company' => $providerOwesCompany,
            'net_revenue_zeroed_after_refund' => false,
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

if (!function_exists('get_booking_revenue_reporting_amount')) {
    /**
     * Amount to include in admin/provider revenue totals: full booking total when completed,
     * or visit+closing retained basis when canceled as “after visit”.
     */
    function get_booking_revenue_reporting_amount($booking): float
    {
        if ($booking instanceof \Modules\BookingModule\Entities\Booking
            && (string) ($booking->booking_status ?? '') === 'canceled'
            && (bool) ($booking->after_visit_cancel ?? false)) {
            $svc = app(\Modules\BookingModule\Services\BookingFinancialSettlementService::class);
            $config = is_array($booking->settlement_config) ? $booking->settlement_config : [];

            return round((float) $svc->resolveRetainedVisitAmount($booking, $config), 2);
        }

        return get_booking_total_amount($booking);
    }
}

if (!function_exists('get_booking_revenue_reporting_spare_parts_amount')) {
    /**
     * Spare-parts subtotal for revenue reporting (after-visit cancels have no spare split on the retained basis).
     */
    function get_booking_revenue_reporting_spare_parts_amount($booking): float
    {
        if ($booking instanceof \Modules\BookingModule\Entities\Booking
            && (string) ($booking->booking_status ?? '') === 'canceled'
            && (bool) ($booking->after_visit_cancel ?? false)) {
            return 0.0;
        }

        return get_booking_spare_parts_amount($booking);
    }
}

if (!function_exists('booking_cap_refund_for_visit_retained')) {
    /**
     * Cap automatic cancel refund when visit-retained settlement applies: customer cannot get back more than (paid − retained).
     */
    function booking_cap_refund_for_visit_retained(float $computedRefund, float $totalPaidByCustomer, float $retainedAmount): float
    {
        $retainedAmount = max(0.0, round($retainedAmount, 2));
        $totalPaidByCustomer = max(0.0, round($totalPaidByCustomer, 2));
        $maxRefund = max(0.0, round($totalPaidByCustomer - $retainedAmount, 2));

        return min(max(0.0, round($computedRefund, 2)), $maxRefund);
    }
}

if (!function_exists('booking_sum_partials_for_cancel_platform_auto_refund')) {
    /**
     * Sum of partial paid_amount that qualifies for automatic wallet + ledger refund on cancel
     * (see refundTransactionForCanceledBooking). Excludes manual/offline paths so admin Refund does not double-count.
     */
    function booking_sum_partials_for_cancel_platform_auto_refund($partials): float
    {
        $exclude = ['cash_after_service', 'admin_entry', 'offline', 'offline_payment'];

        return (float) collect($partials)
            ->reject(fn ($p) => in_array((string) ($p->paid_with ?? ''), $exclude, true))
            ->sum('paid_amount');
    }
}

if (!function_exists('booking_ledger_refund_out_total')) {
    /**
     * Sum of ledger OUT rows for this booking with reason refund (money already recorded as leaving the platform).
     * Cancel auto-refund and admin "Refund customer" both write these; subtract this before recording another OUT.
     */
    function booking_ledger_refund_out_total(string $bookingId): float
    {
        $sum = LedgerTransaction::query()
            ->where('booking_id', $bookingId)
            ->where('type', LedgerTransaction::TYPE_OUT)
            ->where('reason', LedgerTransaction::REASON_REFUND)
            ->sum('amount');

        return round((float) $sum, 2);
    }
}

if (!function_exists('booking_refund_max_eligible_total')) {
    /**
     * Maximum total refund to the customer (admin manual refund cap), aligned with admin booking details / refund action.
     * Zero when the refund card is suppressed (after-visit retained) or status is not canceled/refunded.
     */
    function booking_refund_max_eligible_total($booking): float
    {
        if (! $booking instanceof Booking) {
            return 0.0;
        }
        if (! in_array((string) ($booking->booking_status ?? ''), ['canceled', 'cancelled', 'refunded'], true)) {
            return 0.0;
        }
        if ((bool) ($booking->after_visit_cancel ?? false)
            || (string) ($booking->settlement_outcome ?? '') === BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL) {
            return 0.0;
        }

        return round((float) get_booking_total_paid($booking), 2);
    }
}

if (!function_exists('get_booking_refund_display_totals')) {
    /**
     * @return array{refunded_total: float, refundable_remaining: float, max_eligible: float, show: bool}
     */
    function get_booking_refund_display_totals($booking): array
    {
        if (! $booking instanceof Booking) {
            return [
                'refunded_total' => 0.0,
                'refundable_remaining' => 0.0,
                'max_eligible' => 0.0,
                'show' => false,
            ];
        }

        $status = (string) ($booking->booking_status ?? '');
        if (! in_array($status, ['canceled', 'cancelled', 'refunded'], true)) {
            return [
                'refunded_total' => 0.0,
                'refundable_remaining' => 0.0,
                'max_eligible' => 0.0,
                'show' => false,
            ];
        }

        $bid = (string) ($booking->id ?? '');
        $refundedTotal = $bid !== '' ? booking_ledger_refund_out_total($bid) : 0.0;
        $maxEligible = booking_refund_max_eligible_total($booking);
        $refundableRemaining = max(0.0, round($maxEligible - $refundedTotal, 2));
        $show = $refundedTotal > 0 || $maxEligible > 0;

        return [
            'refunded_total' => $refundedTotal,
            'refundable_remaining' => $refundableRemaining,
            'max_eligible' => $maxEligible,
            'show' => $show,
        ];
    }
}

if (!function_exists('booking_should_zero_net_revenue_settlement_display')) {
    /**
     * When the customer was fully refunded under standard rules (no visit-fee / retained-charge settlement),
     * gross commission and "received by" partials are misleading — net retained revenue is zero.
     */
    function booking_should_zero_net_revenue_settlement_display($booking): bool
    {
        if (! $booking instanceof Booking) {
            return false;
        }
        if (BookingFinancialSettlementService::outcomeUsesDecidedVisitCharges((string) ($booking->settlement_outcome ?? ''))) {
            return false;
        }
        $st = (string) ($booking->booking_status ?? '');
        if (! in_array($st, ['canceled', 'cancelled', 'refunded'], true)) {
            return false;
        }
        if ($st === 'refunded') {
            return true;
        }
        $t = get_booking_refund_display_totals($booking);
        $maxEl = round((float) ($t['max_eligible'] ?? 0), 2);
        if ($maxEl <= 0) {
            return false;
        }
        $refunded = round((float) ($t['refunded_total'] ?? 0), 2);
        $remaining = round((float) ($t['refundable_remaining'] ?? 0), 2);

        return $remaining <= 0 && $refunded + 0.005 >= $maxEl;
    }
}

if (!function_exists('booking_can_be_completed')) {
    /**
     * Booking can only be completed if total_paid >= booking_total.
     */
    function booking_can_be_completed($booking): bool
    {
        if ($booking instanceof BookingRepeat) {
            $parent = $booking->relationLoaded('booking') ? $booking->booking : $booking->booking()->first();
            if ($parent && (
                (string) ($parent->settlement_outcome ?? '') === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS
                || (bool) ($parent->allow_complete_without_full_payment ?? false)
            )) {
                return true;
            }
        } elseif ($booking instanceof Booking) {
            if ((string) ($booking->settlement_outcome ?? '') === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                return true;
            }
            if ((bool) ($booking->allow_complete_without_full_payment ?? false)) {
                return true;
            }
        }

        $totalPaid = get_booking_total_paid($booking);
        $bookingTotal = get_booking_total_amount($booking);

        if ($booking instanceof Booking
            && (string) ($booking->settlement_outcome ?? '') === BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT) {
            $config = is_array($booking->settlement_config) ? $booking->settlement_config : [];
            $retained = app(BookingFinancialSettlementService::class)->resolveRetainedVisitAmount($booking, $config);

            return round($totalPaid, 2) >= round($retained, 2);
        }

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
