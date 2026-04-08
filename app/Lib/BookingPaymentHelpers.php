<?php

use App\Lib\DiscountCostBearer;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetail;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingExtraService;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\BookingRepeatDetails;
use Illuminate\Support\Facades\DB;
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

if (!function_exists('customer_pending_bad_debt_loss_making_bookings_total')) {
    /**
     * Remaining amount the customer still owes on loss-making (scaled-to-payments) bookings:
     * full booking payable total minus recorded payments. Parent bookings only (settlement lives on the main row).
     * Canceled / refunded bookings are excluded.
     */
    function customer_pending_bad_debt_loss_making_bookings_total(string $customerId): float
    {
        $bookings = Booking::query()
            ->where('customer_id', $customerId)
            ->where('settlement_outcome', BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS)
            ->whereNotIn('booking_status', ['canceled', 'cancelled', 'refunded'])
            ->with(['booking_partial_payments', 'extra_services'])
            ->get();

        $sum = 0.0;
        foreach ($bookings as $booking) {
            $grand = get_booking_total_amount($booking);
            $paid = get_booking_total_paid($booking);
            $sum += round(max(0.0, $grand - $paid), 2);
        }

        return round($sum, 2);
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
        $svc = app(BookingFinancialSettlementService::class);
        $main = $svc->mainBookingFor($booking);
        $outcomeMain = trim((string) ($main->settlement_outcome ?? ''));
        if ($booking instanceof BookingRepeat
            && $outcomeMain === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            $w = $svc->scaledLossRepeatLineWeight($booking, $main);
            $parentCd = $svc->calculateAdminCommissionDetails($main, $booking->provider_id ?? $main->provider_id);
            $parentGrand = round(max(0.0, get_booking_total_amount($main)), 2);
            $parentWo = round(max(0.0, (float) ($parentCd['adminCommissionWithoutCost'] ?? 0)), 2);
            $providerGrossParent = round(max(0.0, $parentGrand - $parentWo), 2);
            $bookingAmountWithoutCommission = round(max(0.0, $providerGrossParent * $w), 2);
        } else {
            $paid = $svc->totalPaidForMainBooking($main);
            $providerBasis = $svc->providerEarningBasisAmount($main, $grandTotal, $paid);
            $bookingAmountWithoutCommission = round(max(0, $providerBasis - $commissionWithoutCost), 2);
        }

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

        // Loss-making (scaled): commission and provider share are on full booking total; net shares subtract the configured loss split.
        if ($booking instanceof Booking) {
            $outcomeMain = trim((string) ($booking->settlement_outcome ?? ''));
            if ($outcomeMain === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                $svc = app(BookingFinancialSettlementService::class);
                $cfg = is_array($booking->settlement_config) ? $booking->settlement_config : [];
                $gt = get_booking_total_amount($booking);
                $paidMain = $svc->totalPaidForMainBooking($booking);
                [, , $lossCompany, $lossProvider] = $svc->resolveScaledLossBreakdown($booking, $cfg, $gt, $paidMain);
                $companyShare = round($companyShare - $lossCompany, 2);
                $providerShare = round($providerShare - $lossProvider, 2);
            }
        }

        // Company keeps up to max(0, company_share); if net company share is negative, company must cover that amount toward the provider (loss).
        $companyKeep = max(0.0, $companyShare);
        $companySupport = max(0.0, -$companyShare);
        $payToProvider = round(max(0.0, $amountReceivedByCompany - $companyKeep) + $companySupport, 2);
        $commissionShortfall = max(0.0, $companyKeep - $amountReceivedByCompany);
        $providerOwesCompany = $amountReceivedByProvider > 0
            ? round(min($amountReceivedByProvider, $commissionShortfall), 2)
            : 0.0;

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

if (!function_exists('booking_main_financial_settlement_outcome')) {
    /**
     * Parent booking settlement_outcome for a Booking or BookingRepeat (settlement is stored on parent only).
     */
    function booking_main_financial_settlement_outcome($booking): string
    {
        if ($booking instanceof BookingRepeat) {
            $main = $booking->relationLoaded('booking') ? $booking->booking : $booking->booking()->first();
        } else {
            $main = $booking;
        }

        return $main ? trim((string) ($main->settlement_outcome ?? '')) : '';
    }
}

if (!function_exists('booking_has_special_financial_settlement')) {
    /**
     * True when the parent booking uses a non-standard financial settlement (special scenario).
     */
    function booking_has_special_financial_settlement($booking): bool
    {
        return booking_main_financial_settlement_outcome($booking) !== '';
    }
}

if (!function_exists('booking_special_financial_settlement_provider_owes_company')) {
    /**
     * Amount the provider must remit to the company when they hold customer cash but company share
     * (commission) was not paid to the company — same basis as get_booking_received_and_settlement().
     * Returns null when not a special settlement (caller uses legacy advance / proportional rules).
     */
    function booking_special_financial_settlement_provider_owes_company($booking): ?float
    {
        if (!booking_has_special_financial_settlement($booking)) {
            return null;
        }
        $settled = get_booking_received_and_settlement($booking);

        return max(0.0, round((float) ($settled['provider_owes_company'] ?? 0), 2));
    }
}

if (!function_exists('booking_special_settlement_customer_paid_into_admin_pending')) {
    /**
     * Total paid on the main booking when special settlement applies (for capping admin balance_pending release).
     */
    function booking_special_settlement_customer_paid_into_admin_pending($booking): ?float
    {
        if (!booking_has_special_financial_settlement($booking)) {
            return null;
        }
        $main = $booking instanceof BookingRepeat
            ? ($booking->relationLoaded('booking') ? $booking->booking : $booking->booking()->first())
            : $booking;
        if (!$main) {
            return null;
        }
        $paid = app(BookingFinancialSettlementService::class)->totalPaidForMainBooking($main);
        $p = round(max(0.0, (float) $paid), 2);

        return $p > 0 ? $p : null;
    }
}

if (!function_exists('booking_repeat_special_settlement_admin_commission_cap_for_cas')) {
    /**
     * Caps repeat CAS commission ledger by cumulative provider-owes helper (avoids double-posting across repeats).
     */
    function booking_repeat_special_settlement_admin_commission_cap_for_cas(BookingRepeat $booking): ?float
    {
        $owes = booking_special_financial_settlement_provider_owes_company($booking);
        if ($owes === null || $owes <= 0) {
            return null;
        }
        $breakdown = get_commission_breakdown_for_booking($booking);
        $admin = max(0.0, round((float) ($breakdown['commission_without_cost'] ?? 0), 2));

        return min($admin, $owes);
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
            && (
                (bool) ($booking->after_visit_cancel ?? false)
                || (string) ($booking->settlement_outcome ?? '') === BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL
            )) {
            $svc = app(\Modules\BookingModule\Services\BookingFinancialSettlementService::class);
            $config = is_array($booking->settlement_config) ? $booking->settlement_config : [];

            return round((float) $svc->resolveRetainedVisitAmount($booking, $config), 2);
        }

        return get_booking_total_amount($booking);
    }
}

if (!function_exists('provider_payment_tab_sum_repeat_line_totals_by_parent_booking_id')) {
    /**
     * Sum of {@see get_booking_total_amount} per parent booking_id for allocating scaled / visit-retained revenue across repeat rows.
     *
     * @param  iterable<int, BookingRepeat>  $repeats
     * @return array<string, float>
     */
    function provider_payment_tab_sum_repeat_line_totals_by_parent_booking_id(iterable $repeats): array
    {
        $sums = [];
        foreach ($repeats as $repeat) {
            if (! $repeat instanceof BookingRepeat) {
                continue;
            }
            $key = (string) $repeat->booking_id;
            if (! isset($sums[$key])) {
                $sums[$key] = 0.0;
            }
            $sums[$key] += get_booking_total_amount($repeat);
        }

        return $sums;
    }
}

if (!function_exists('get_provider_payment_tab_revenue_amount_for_booking')) {
    /**
     * Revenue for provider overview / payment tab / admin dashboard: same basis as a normal completed job
     * ({@see get_booking_revenue_reporting_amount}). Loss-making (scaled_to_payments) uses that path too—
     * scaled economic loss is shown separately, not by inflating or altering this total.
     */
    function get_provider_payment_tab_revenue_amount_for_booking(Booking $booking): float
    {
        $outcome = trim((string) ($booking->settlement_outcome ?? ''));
        if ($outcome === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return get_booking_revenue_reporting_amount($booking);
        }

        $svc = app(BookingFinancialSettlementService::class);
        if (! $svc->usesNonStandardSettlement($booking)) {
            return get_booking_revenue_reporting_amount($booking);
        }

        $preview = $svc->buildPreview($booking);

        if (BookingFinancialSettlementService::outcomeUsesDecidedVisitCharges($outcome)) {
            return round((float) ($preview['booking_total'] ?? 0), 2);
        }

        return round((float) ($preview['booking_total'] ?? get_booking_total_amount($booking)), 2);
    }
}

if (!function_exists('get_admin_dashboard_reporting_total_and_spare_for_booking')) {
    /**
     * Admin dashboard totals / earning chart: revenue matches provider payment tab (booking totals per job).
     * Spare parts scale proportionally when reported revenue is below {@see get_booking_revenue_reporting_amount}.
     *
     * @return array{reported_total: float, spare_parts: float}
     */
    function get_admin_dashboard_reporting_total_and_spare_for_booking(Booking $booking): array
    {
        $reportedTotal = round((float) get_provider_payment_tab_revenue_amount_for_booking($booking), 2);
        $baselineTotal = round((float) get_booking_revenue_reporting_amount($booking), 2);
        $rawSpare = round((float) get_booking_revenue_reporting_spare_parts_amount($booking), 2);

        if ($baselineTotal <= 0) {
            return [
                'reported_total' => $reportedTotal,
                'spare_parts' => 0.0,
            ];
        }

        $spare = round($rawSpare * ($reportedTotal / $baselineTotal), 2);

        return [
            'reported_total' => $reportedTotal,
            'spare_parts' => min($spare, $reportedTotal),
        ];
    }
}

if (!function_exists('get_admin_dashboard_reporting_total_and_spare_for_repeat')) {
    /**
     * @return array{reported_total: float, spare_parts: float}
     */
    function get_admin_dashboard_reporting_total_and_spare_for_repeat(BookingRepeat $repeat, float $sumCompletedRepeatLineTotalsSameParent): array
    {
        $reportedTotal = round((float) get_provider_payment_tab_revenue_amount_for_repeat($repeat, $sumCompletedRepeatLineTotalsSameParent), 2);
        $baselineTotal = round((float) get_booking_total_amount($repeat), 2);
        $rawSpare = round((float) get_booking_revenue_reporting_spare_parts_amount($repeat), 2);

        if ($baselineTotal <= 0) {
            return [
                'reported_total' => $reportedTotal,
                'spare_parts' => 0.0,
            ];
        }

        $spare = round($rawSpare * ($reportedTotal / $baselineTotal), 2);

        return [
            'reported_total' => $reportedTotal,
            'spare_parts' => min($spare, $reportedTotal),
        ];
    }
}

if (!function_exists('get_provider_payment_tab_revenue_amount_for_repeat')) {
    /**
     * Repeat-line revenue: like a normal completed repeat line ({@see get_booking_total_amount}) when the parent is
     * loss-making (scaled)—no grand-total allocation. Other non-standard parents still scale preview totals by weight.
     */
    function get_provider_payment_tab_revenue_amount_for_repeat(BookingRepeat $repeat, float $sumCompletedRepeatLineTotalsSameParent): float
    {
        $main = $repeat->relationLoaded('booking') ? $repeat->booking : $repeat->booking()->first();
        $outcome = $main instanceof Booking ? trim((string) ($main->settlement_outcome ?? '')) : '';
        if ($outcome === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return round((float) get_booking_total_amount($repeat), 2);
        }

        $svc = app(BookingFinancialSettlementService::class);
        if (! $svc->usesNonStandardSettlement($repeat)) {
            return get_booking_total_amount($repeat);
        }

        if (! $main instanceof Booking) {
            return get_booking_total_amount($repeat);
        }

        $preview = $svc->buildPreview($main);
        $line = get_booking_total_amount($repeat);
        $den = round(max(0.01, $sumCompletedRepeatLineTotalsSameParent), 2);
        $weight = $line / $den;

        if (BookingFinancialSettlementService::outcomeUsesDecidedVisitCharges($outcome)) {
            $retained = (float) ($preview['booking_total'] ?? 0);

            return round($retained * $weight, 2);
        }

        return round($line, 2);
    }
}

if (!function_exists('aggregate_provider_payment_summary_for_completed_jobs')) {
    /**
     * Totals for provider overview / payment: total_revenue sums per-booking amounts
     * ({@see get_provider_payment_tab_revenue_amount_for_booking} / repeat);
     * total_company_commission / provider_net_earning: sum of detail rows for normal jobs; for loss-making (scaled)
     * jobs, admin commission and provider earning are taken from full-booking gross ({@see provider_payment_tab_loss_making_earning_display_for_scaled} before_loss), not row sums.
     * Scaled loss splits remain in scaled_loss_company_share_total / scaled_loss_provider_share_total.
     *
     * @param  iterable<int, Booking>  $oneTimeBookings
     * @param  iterable<int, BookingRepeat>  $repeats
     * @return array{total_revenue: float, total_company_commission: float, provider_net_earning: float, total_provider_earning_from_rows: float, scaled_loss_company_share_total: float, scaled_loss_provider_share_total: float}
     */
    function aggregate_provider_payment_summary_for_completed_jobs(iterable $oneTimeBookings, iterable $repeats): array
    {
        $svc = app(BookingFinancialSettlementService::class);
        $oneTimeCol = collect($oneTimeBookings);
        $repeatsCol = collect($repeats);

        $sumRepeatByParent = provider_payment_tab_sum_repeat_line_totals_by_parent_booking_id($repeatsCol);

        $totalRevenue = 0.0;
        foreach ($oneTimeCol as $b) {
            if ($b instanceof Booking) {
                $totalRevenue += get_provider_payment_tab_revenue_amount_for_booking($b);
            }
        }
        foreach ($repeatsCol as $r) {
            if ($r instanceof BookingRepeat) {
                $parentKey = (string) $r->booking_id;
                $den = (float) ($sumRepeatByParent[$parentKey] ?? get_booking_total_amount($r));
                $totalRevenue += get_provider_payment_tab_revenue_amount_for_repeat($r, $den);
            }
        }

        $oneTimeIds = $oneTimeCol->pluck('id')->filter()->values();
        $repeatIds = $repeatsCol->pluck('id')->filter()->values();

        $totalCompanyCommission = (float) BookingDetailsAmount::query()
            ->whereIn('booking_id', $oneTimeIds)
            ->sum('admin_commission');
        $totalCompanyCommission += (float) BookingDetailsAmount::query()
            ->whereIn('booking_repeat_id', $repeatIds)
            ->sum('admin_commission');

        $totalProviderEarning = (float) BookingDetailsAmount::query()
            ->whereIn('booking_id', $oneTimeIds)
            ->sum('provider_earning');
        $totalProviderEarning += (float) BookingDetailsAmount::query()
            ->whereIn('booking_repeat_id', $repeatIds)
            ->sum('provider_earning');

        $scaledGrossAdjustedParents = [];

        foreach ($oneTimeCol as $b) {
            if (! $b instanceof Booking) {
                continue;
            }
            if (trim((string) ($b->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                continue;
            }
            $idStr = (string) $b->id;
            if (isset($scaledGrossAdjustedParents[$idStr])) {
                continue;
            }
            $scaledGrossAdjustedParents[$idStr] = true;
            $subC = (float) BookingDetailsAmount::query()->where('booking_id', $b->id)->sum('admin_commission');
            $subP = (float) BookingDetailsAmount::query()->where('booking_id', $b->id)->sum('provider_earning');
            $totalCompanyCommission -= $subC;
            $totalProviderEarning -= $subP;
            $grossLine = provider_payment_tab_loss_making_earning_display_for_scaled($b, 1.0);
            if ($grossLine !== null) {
                $totalCompanyCommission += (float) ($grossLine['admin_commission_before_loss'] ?? 0);
                $totalProviderEarning += (float) ($grossLine['provider_earning_before_loss'] ?? 0);
            }
        }

        foreach ($repeatsCol->groupBy('booking_id') as $parentId => $group) {
            $first = $group->first();
            if (! $first instanceof BookingRepeat) {
                continue;
            }
            $main = $first->relationLoaded('booking') ? $first->booking : $first->booking()->first();
            if (! $main instanceof Booking) {
                continue;
            }
            if (trim((string) ($main->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                continue;
            }
            $idStr = (string) $main->id;
            if (isset($scaledGrossAdjustedParents[$idStr])) {
                continue;
            }
            $scaledGrossAdjustedParents[$idStr] = true;
            $repeatIdList = $group->pluck('id')->filter()->values()->all();
            if ($repeatIdList === []) {
                continue;
            }
            $subC = (float) BookingDetailsAmount::query()->whereIn('booking_repeat_id', $repeatIdList)->sum('admin_commission');
            $subP = (float) BookingDetailsAmount::query()->whereIn('booking_repeat_id', $repeatIdList)->sum('provider_earning');
            $totalCompanyCommission -= $subC;
            $totalProviderEarning -= $subP;
            $den = (float) ($sumRepeatByParent[(string) $parentId] ?? 0.01);
            $den = round(max(0.01, $den), 2);
            foreach ($group as $r) {
                if (! $r instanceof BookingRepeat) {
                    continue;
                }
                $lineW = get_booking_total_amount($r) / $den;
                $grossLine = provider_payment_tab_loss_making_earning_display_for_scaled($main, $lineW);
                if ($grossLine !== null) {
                    $totalCompanyCommission += (float) ($grossLine['admin_commission_before_loss'] ?? 0);
                    $totalProviderEarning += (float) ($grossLine['provider_earning_before_loss'] ?? 0);
                }
            }
        }

        $scaledLossParents = [];
        $companyLossShare = 0.0;
        $providerLossShare = 0.0;

        foreach ($oneTimeCol as $b) {
            if (! $b instanceof Booking) {
                continue;
            }
            if (! $svc->usesNonStandardSettlement($b)) {
                continue;
            }
            if (trim((string) ($b->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                continue;
            }
            $idStr = (string) $b->id;
            if (isset($scaledLossParents[$idStr])) {
                continue;
            }
            $scaledLossParents[$idStr] = true;
            $p = $svc->buildPreview($b);
            $companyLossShare += (float) ($p['scaled_loss_company_share'] ?? 0);
            $providerLossShare += (float) ($p['scaled_loss_provider_share'] ?? 0);
        }

        foreach ($repeatsCol as $r) {
            if (! $r instanceof BookingRepeat) {
                continue;
            }
            $main = $r->relationLoaded('booking') ? $r->booking : $r->booking()->first();
            if (! $main instanceof Booking) {
                continue;
            }
            if (! $svc->usesNonStandardSettlement($r)) {
                continue;
            }
            if (trim((string) ($main->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                continue;
            }
            $idStr = (string) $main->id;
            if (isset($scaledLossParents[$idStr])) {
                continue;
            }
            $scaledLossParents[$idStr] = true;
            $p = $svc->buildPreview($main);
            $companyLossShare += (float) ($p['scaled_loss_company_share'] ?? 0);
            $providerLossShare += (float) ($p['scaled_loss_provider_share'] ?? 0);
        }

        $providerNetEarning = round($totalProviderEarning, 2);

        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_company_commission' => round($totalCompanyCommission, 2),
            'provider_net_earning' => $providerNetEarning,
            'total_provider_earning_from_rows' => round($totalProviderEarning, 2),
            'scaled_loss_company_share_total' => round($companyLossShare, 2),
            'scaled_loss_provider_share_total' => round($providerLossShare, 2),
        ];
    }
}

if (!function_exists('booking_details_admin_commission_sum_for_admin_dashboard_cohort')) {
    /**
     * Sum of admin_commission on detail rows counted in admin dashboard / transaction report cohort for this main booking
     * (parent lines when booking is revenue-reporting, plus completed repeat lines).
     */
    function booking_details_admin_commission_sum_for_admin_dashboard_cohort(Booking $main): float
    {
        $repeatIds = BookingRepeat::query()
            ->where('booking_id', $main->id)
            ->ofBookingStatus('completed')
            ->pluck('id');

        $q = BookingDetailsAmount::query()
            ->where(function ($outer) use ($main, $repeatIds) {
                $outer->where(function ($q2) use ($main) {
                    $q2->where('booking_id', $main->id)
                        ->whereHas('booking', fn ($bq) => $bq->forRevenueReporting());
                });
                if ($repeatIds->isNotEmpty()) {
                    $outer->orWhereIn('booking_repeat_id', $repeatIds);
                }
            });

        return round((float) $q->sum('admin_commission'), 2);
    }
}

if (!function_exists('booking_scaled_gross_admin_commission_total_for_main')) {
    /**
     * Admin commission on the full booking total for a loss-making (scaled) job — matches provider payment-tab gross logic.
     */
    function booking_scaled_gross_admin_commission_total_for_main(Booking $main): float
    {
        if (trim((string) ($main->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return 0.0;
        }

        $repeats = BookingRepeat::query()
            ->where('booking_id', $main->id)
            ->ofBookingStatus('completed')
            ->get();

        if ($repeats->isEmpty()) {
            $line = provider_payment_tab_loss_making_earning_display_for_scaled($main, 1.0);

            return $line !== null ? round((float) ($line['admin_commission_before_loss'] ?? 0), 2) : 0.0;
        }

        $sumByParent = provider_payment_tab_sum_repeat_line_totals_by_parent_booking_id($repeats);
        $den = round(max(0.01, (float) ($sumByParent[(string) $main->id] ?? 0.01)), 2);
        $acc = 0.0;
        foreach ($repeats as $r) {
            $w = get_booking_total_amount($r) / $den;
            $g = provider_payment_tab_loss_making_earning_display_for_scaled($main, $w);
            if ($g !== null) {
                $acc += (float) ($g['admin_commission_before_loss'] ?? 0);
            }
        }

        return round($acc, 2);
    }
}

if (!function_exists('booking_scaled_admin_commission_delta_for_main')) {
    /**
     * Difference between full-booking gross admin commission and stored detail rows for a main booking (scaled jobs only; otherwise 0).
     */
    function booking_scaled_admin_commission_delta_for_main(Booking $main): float
    {
        if (trim((string) ($main->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return 0.0;
        }

        $stored = booking_details_admin_commission_sum_for_admin_dashboard_cohort($main);
        $gross = booking_scaled_gross_admin_commission_total_for_main($main);

        return round($gross - $stored, 2);
    }
}

if (!function_exists('admin_dashboard_scaled_admin_commission_adjustments')) {
    /**
     * Delta to add to raw sums of booking_details_amounts.admin_commission so company totals match full-booking gross
     * commission on loss-making (scaled) jobs (same basis as {@see aggregate_provider_payment_summary_for_completed_jobs}).
     * Month buckets use the main booking’s created_at (for the dashboard earning chart).
     *
     * @return array{total: float, by_month: array<int, float>}
     */
    function admin_dashboard_scaled_admin_commission_adjustments(?int $onlyYear = null): array
    {
        $query = Booking::query()
            ->forRevenueReporting()
            ->where('settlement_outcome', BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS);

        if ($onlyYear !== null) {
            $query->whereYear('created_at', $onlyYear);
        }

        $byMonth = array_fill(1, 12, 0.0);
        $total = 0.0;

        foreach ($query->cursor() as $main) {
            if (! $main instanceof Booking) {
                continue;
            }
            $delta = booking_scaled_admin_commission_delta_for_main($main);
            if (abs($delta) < 0.00001) {
                continue;
            }
            $total += $delta;
            $m = (int) $main->created_at->format('n');
            if ($m >= 1 && $m <= 12) {
                $byMonth[$m] += $delta;
            }
        }

        foreach ($byMonth as $k => $v) {
            $byMonth[$k] = round((float) $v, 2);
        }

        return [
            'total' => round($total, 2),
            'by_month' => $byMonth,
        ];
    }
}

if (!function_exists('provider_payment_tab_receipts_for_main_booking')) {
    /**
     * Cash split (company vs provider) from partials on the parent booking — same rules as {@see get_booking_received_and_settlement}.
     *
     * @return array{company: float, provider: float, total_paid: float}
     */
    function provider_payment_tab_receipts_for_main_booking(Booking $main): array
    {
        $main->loadMissing('booking_partial_payments');
        $partials = $main->booking_partial_payments ?? collect();
        $totalPaid = $partials->isNotEmpty()
            ? (float) $partials->sum('paid_amount')
            : ((bool) ($main->is_paid ?? false) ? get_booking_total_amount($main) : 0.0);

        $amountReceivedByCompany = 0.0;
        $amountReceivedByProvider = 0.0;
        if ($partials->isNotEmpty()) {
            $amountReceivedByCompany = (float) $partials->filter(function ($p) {
                $by = $p->received_by ?? '';

                return $by === 'company' || $by === '';
            })->sum('paid_amount');
            $amountReceivedByProvider = (float) $partials->where('received_by', 'provider')->sum('paid_amount');
            if ($amountReceivedByCompany == 0 && $amountReceivedByProvider == 0 && $totalPaid > 0) {
                $amountReceivedByCompany = ($main->payment_method ?? '') !== 'cash_after_service' ? $totalPaid : 0.0;
                $amountReceivedByProvider = ($main->payment_method ?? '') === 'cash_after_service' ? $totalPaid : 0.0;
            }
        } else {
            $amountReceivedByCompany = ((bool) ($main->is_paid ?? false) && ($main->payment_method ?? '') !== 'cash_after_service') ? $totalPaid : 0.0;
            $amountReceivedByProvider = ((bool) ($main->is_paid ?? false) && ($main->payment_method ?? '') === 'cash_after_service') ? $totalPaid : 0.0;
        }

        return [
            'company' => round($amountReceivedByCompany, 2),
            'provider' => round($amountReceivedByProvider, 2),
            'total_paid' => round((float) $totalPaid, 2),
        ];
    }
}

if (!function_exists('provider_payment_tab_settlement_legs_from_receipts')) {
    /**
     * @return array{pay_to_provider: float, provider_owes_company: float}
     */
    function provider_payment_tab_settlement_legs_from_receipts(float $companyReceived, float $providerReceived, float $companyCommission): array
    {
        $cc = round($companyCommission, 2);
        $companyKeep = max(0.0, $cc);
        $companySupport = max(0.0, -$cc);
        $payToProvider = round(max(0.0, $companyReceived - $companyKeep) + $companySupport, 2);
        $commissionShortfall = max(0.0, $companyKeep - $companyReceived);
        $providerOwesCompany = $providerReceived > 0
            ? round(min($providerReceived, $commissionShortfall), 2)
            : 0.0;

        return [
            'pay_to_provider' => $payToProvider,
            'provider_owes_company' => $providerOwesCompany,
        ];
    }
}

if (!function_exists('provider_payment_tab_earning_report_settlement_columns_for_booking')) {
    /**
     * Cash / settlement columns for provider earning reports (one-time booking row).
     *
     * @return array{amount_received_by_company: float, amount_received_by_provider: float, provider_owes_company: float, company_owes_provider: float}
     */
    function provider_payment_tab_earning_report_settlement_columns_for_booking(Booking $booking): array
    {
        $booking->loadMissing('booking_partial_payments');
        $s = get_booking_received_and_settlement($booking);

        return [
            'amount_received_by_company' => round((float) ($s['amount_received_by_company'] ?? 0), 2),
            'amount_received_by_provider' => round((float) ($s['amount_received_by_provider'] ?? 0), 2),
            'provider_owes_company' => round((float) ($s['provider_owes_company'] ?? 0), 2),
            'company_owes_provider' => round((float) ($s['pay_to_provider'] ?? 0), 2),
        ];
    }
}

if (!function_exists('provider_payment_tab_loss_making_earning_display_for_scaled')) {
    /**
     * Loss-making (scaled_to_payments): gross shares on the full booking total ({@see scaled_gross_*}) and net after loss split.
     * Reports and payment aggregates use before_loss for Provider Earning / Admin Commission; settlement columns may use net commission.
     * Repeat rows weight gross/net by line total ÷ sum of completed repeat lines for the parent.
     *
     * @return array{provider_earning: float, admin_commission: float, provider_earning_before_loss: float, admin_commission_before_loss: float}|null
     */
    function provider_payment_tab_loss_making_earning_display_for_scaled(Booking $main, float $lineWeight): ?array
    {
        if (trim((string) ($main->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return null;
        }
        $preview = app(BookingFinancialSettlementService::class)->buildPreview($main);
        $grossP = (float) ($preview['scaled_gross_provider_share'] ?? 0);
        $grossC = (float) ($preview['scaled_gross_company_commission_without_cost'] ?? 0);
        $lossP = (float) ($preview['scaled_loss_provider_share'] ?? 0);
        $lossC = (float) ($preview['scaled_loss_company_share'] ?? 0);
        $netP = (float) ($preview['scaled_net_provider_share'] ?? round($grossP - $lossP, 2));
        $netC = (float) ($preview['scaled_net_company_share'] ?? round($grossC - $lossC, 2));
        $w = min(1.0, max(0.0, $lineWeight));

        return [
            'provider_earning' => round($netP * $w, 2),
            'admin_commission' => round($netC * $w, 2),
            'provider_earning_before_loss' => round($grossP * $w, 2),
            'admin_commission_before_loss' => round($grossC * $w, 2),
        ];
    }
}

if (!function_exists('provider_payment_tab_earning_report_settlement_columns_for_repeat')) {
    /**
     * Repeat row: parent receipts split by this repeat’s line total vs sum of completed repeat lines (same cohort as revenue).
     *
     * @return array{amount_received_by_company: float, amount_received_by_provider: float, provider_owes_company: float, company_owes_provider: float}
     */
    function provider_payment_tab_earning_report_settlement_columns_for_repeat(BookingRepeat $repeat, float $sumCompletedRepeatLineTotalsSameParent): array
    {
        $main = $repeat->relationLoaded('booking') ? $repeat->booking : $repeat->booking()->first();
        if (! $main instanceof Booking) {
            return [
                'amount_received_by_company' => 0.0,
                'amount_received_by_provider' => 0.0,
                'provider_owes_company' => 0.0,
                'company_owes_provider' => 0.0,
            ];
        }
        if (booking_should_zero_net_revenue_settlement_display($main)) {
            return [
                'amount_received_by_company' => 0.0,
                'amount_received_by_provider' => 0.0,
                'provider_owes_company' => 0.0,
                'company_owes_provider' => 0.0,
            ];
        }
        $receipts = provider_payment_tab_receipts_for_main_booking($main);
        $den = round(max(0.01, $sumCompletedRepeatLineTotalsSameParent), 2);
        $line = get_booking_total_amount($repeat);
        $weight = $line / $den;
        $allocCompany = round($receipts['company'] * $weight, 2);
        $allocProvider = round($receipts['provider'] * $weight, 2);
        $outcomeMain = trim((string) ($main->settlement_outcome ?? ''));
        if ($outcomeMain === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            $scaled = provider_payment_tab_loss_making_earning_display_for_scaled($main, $weight);
            $commission = round((float) ($scaled['admin_commission'] ?? 0), 2);
        } else {
            $br = get_commission_breakdown_for_booking($repeat);
            $commission = round((float) ($br['commission_without_cost'] ?? 0), 2);
        }
        $legs = provider_payment_tab_settlement_legs_from_receipts($allocCompany, $allocProvider, $commission);

        return [
            'amount_received_by_company' => $allocCompany,
            'amount_received_by_provider' => $allocProvider,
            'provider_owes_company' => $legs['provider_owes_company'],
            'company_owes_provider' => $legs['pay_to_provider'],
        ];
    }
}

if (!function_exists('provider_payment_tab_customer_refund_hint_for_main_booking')) {
    /**
     * Suggested refund still due to customer (visit/retained settlement preview), when applicable.
     */
    function provider_payment_tab_customer_refund_hint_for_main_booking(Booking $main): float
    {
        if (booking_should_zero_net_revenue_settlement_display($main)) {
            return 0.0;
        }
        $outcome = trim((string) ($main->settlement_outcome ?? ''));
        if (! BookingFinancialSettlementService::outcomeUsesDecidedVisitCharges($outcome)) {
            return 0.0;
        }
        $preview = app(BookingFinancialSettlementService::class)->buildPreview($main);

        return round(max(0.0, (float) ($preview['refund_to_customer'] ?? 0)), 2);
    }
}

if (!function_exists('aggregate_provider_booking_settlement_net_for_completed_jobs')) {
    /**
     * Company↔provider settlement net from completed jobs (positive = company owes provider), using the same
     * pay-to-provider / provider-owes logic as booking details (including loss-making net shares on the main row).
     * Repeat rows are grouped by parent so parent receipts are not multiplied.
     *
     * @param  iterable<int, Booking>  $oneTimeBookings
     * @param  iterable<int, BookingRepeat>  $repeats
     * @return array{settlement_net: float, customer_refund_due_total: float}
     */
    function aggregate_provider_booking_settlement_net_for_completed_jobs(iterable $oneTimeBookings, iterable $repeats): array
    {
        $svc = app(BookingFinancialSettlementService::class);
        $oneTimeCol = collect($oneTimeBookings);
        $repeatsCol = collect($repeats);

        $net = 0.0;
        $customerRefundDue = 0.0;

        foreach ($oneTimeCol as $b) {
            if (! $b instanceof Booking) {
                continue;
            }
            $settled = get_booking_received_and_settlement($b);
            if (empty($settled['net_revenue_zeroed_after_refund'])) {
                $net += (float) ($settled['pay_to_provider'] ?? 0) - (float) ($settled['provider_owes_company'] ?? 0);
            }
            $customerRefundDue += provider_payment_tab_customer_refund_hint_for_main_booking($b);
        }

        foreach ($repeatsCol->groupBy('booking_id') as $_parentId => $group) {
            $first = $group->first();
            if (! $first instanceof BookingRepeat) {
                continue;
            }
            $main = $first->relationLoaded('booking') ? $first->booking : $first->booking()->first();
            if (! $main instanceof Booking) {
                continue;
            }
            if (booking_should_zero_net_revenue_settlement_display($main)) {
                $legs = ['pay_to_provider' => 0.0, 'provider_owes_company' => 0.0];
            } else {
                $receipts = provider_payment_tab_receipts_for_main_booking($main);
                $totalCommission = 0.0;
                foreach ($group as $r) {
                    if (! $r instanceof BookingRepeat) {
                        continue;
                    }
                    $br = get_commission_breakdown_for_booking($r);
                    $totalCommission += (float) ($br['commission_without_cost'] ?? 0);
                }
                $totalCommission = round($totalCommission, 2);
                $legs = provider_payment_tab_settlement_legs_from_receipts(
                    $receipts['company'],
                    $receipts['provider'],
                    $totalCommission
                );
            }
            $net += (float) $legs['pay_to_provider'] - (float) $legs['provider_owes_company'];

            $customerRefundDue += provider_payment_tab_customer_refund_hint_for_main_booking($main);
        }

        return [
            'settlement_net' => round($net, 2),
            'customer_refund_due_total' => round($customerRefundDue, 2),
        ];
    }
}

if (!function_exists('aggregate_provider_booking_settlement_net_for_provider_id')) {
    /**
     * Same completed-job cohort and settlement net as the admin provider payment tab (one-time bookings that are
     * not repeat parents, plus completed repeats for that provider).
     *
     * @return array{settlement_net: float, customer_refund_due_total: float}
     */
    function aggregate_provider_booking_settlement_net_for_provider_id(string $providerId): array
    {
        $providerBookingIds = DB::table('bookings')->where('provider_id', $providerId)->pluck('id')->toArray();
        $bookingIdsWithRepeats = DB::table('booking_repeats')->whereNotNull('booking_id')->distinct()->pluck('booking_id')->toArray();

        $oneTimeQuery = DB::table('bookings')->where('provider_id', $providerId)->where(function ($q) {
            $q->where('booking_status', 'completed')
                ->orWhere(function ($q2) {
                    $q2->where('booking_status', 'canceled')
                        ->where('after_visit_cancel', 1);
                });
        });
        if ($bookingIdsWithRepeats !== []) {
            $oneTimeQuery->whereNotIn('id', $bookingIdsWithRepeats);
        }
        $completedOneTimeBookingIds = $oneTimeQuery->pluck('id');

        $oneTimeBookingsForRevenue = $completedOneTimeBookingIds->isEmpty()
            ? collect()
            : Booking::whereIn('id', $completedOneTimeBookingIds)->with('extra_services')->get();

        $completedRepeatIds = collect();
        if ($providerBookingIds !== []) {
            $completedRepeatIds = DB::table('booking_repeats')
                ->where('booking_status', 'completed')
                ->whereIn('booking_id', $providerBookingIds)
                ->pluck('id');
        }
        $repeatsForRevenue = $completedRepeatIds->isNotEmpty()
            ? BookingRepeat::whereIn('id', $completedRepeatIds)->with('booking.extra_services')->get()
            : collect();

        return aggregate_provider_booking_settlement_net_for_completed_jobs($oneTimeBookingsForRevenue, $repeatsForRevenue);
    }
}

if (!function_exists('provider_ledger_manual_flow_totals_for_provider')) {
    /**
     * Sums on ledger rows scoped to this provider (same subset as the provider payment tab ledger table).
     * OUT with reason provider_payout: add payment to provider + approved withdrawals. IN: collect-from-provider (and any other IN rows with provider_id).
     */
    function provider_ledger_manual_flow_totals_for_provider(string $providerId): array
    {
        $payoutOut = (float) LedgerTransaction::query()
            ->where('provider_id', $providerId)
            ->where('type', LedgerTransaction::TYPE_OUT)
            ->where('reason', LedgerTransaction::REASON_PROVIDER_PAYOUT)
            ->sum('amount');

        $collectIn = (float) LedgerTransaction::query()
            ->where('provider_id', $providerId)
            ->where('type', LedgerTransaction::TYPE_IN)
            ->sum('amount');

        return [
            'payout_out_total' => round($payoutOut, 2),
            'collect_in_total' => round($collectIn, 2),
        ];
    }
}

if (!function_exists('provider_ledger_manual_flow_totals_all_providers')) {
    /**
     * Same rules as {@see provider_ledger_manual_flow_totals_for_provider()} but summed across all providers
     * (ledger rows with non-null provider_id). Used to align admin dashboard totals with provider payment tabs.
     */
    function provider_ledger_manual_flow_totals_all_providers(): array
    {
        $payoutOut = (float) LedgerTransaction::query()
            ->whereNotNull('provider_id')
            ->where('type', LedgerTransaction::TYPE_OUT)
            ->where('reason', LedgerTransaction::REASON_PROVIDER_PAYOUT)
            ->sum('amount');

        $collectIn = (float) LedgerTransaction::query()
            ->whereNotNull('provider_id')
            ->where('type', LedgerTransaction::TYPE_IN)
            ->sum('amount');

        return [
            'payout_out_total' => round($payoutOut, 2),
            'collect_in_total' => round($collectIn, 2),
        ];
    }
}

if (!function_exists('booking_settlement_net_with_provider_ledger_for_provider_id')) {
    /**
     * Booking-derived settlement net adjusted by this provider’s ledger: remaining company↔provider obligation
     * after recorded OUT (paid provider / withdrawals) and IN (collected from provider).
     *
     * @return array{
     *     settlement_net: float,
     *     settlement_net_before_ledger: float,
     *     customer_refund_due_total: float,
     *     provider_ledger_payout_out_total: float,
     *     provider_ledger_collect_in_total: float
     * }
     */
    function booking_settlement_net_with_provider_ledger_for_provider_id(string $providerId): array
    {
        $agg = aggregate_provider_booking_settlement_net_for_provider_id($providerId);
        $raw = (float) ($agg['settlement_net'] ?? 0);
        $ledger = provider_ledger_manual_flow_totals_for_provider($providerId);
        $adjusted = round($raw - $ledger['payout_out_total'] + $ledger['collect_in_total'], 2);

        return [
            'settlement_net' => $adjusted,
            'settlement_net_before_ledger' => round($raw, 2),
            'customer_refund_due_total' => (float) ($agg['customer_refund_due_total'] ?? 0),
            'provider_ledger_payout_out_total' => $ledger['payout_out_total'],
            'provider_ledger_collect_in_total' => $ledger['collect_in_total'],
        ];
    }
}

if (!function_exists('admin_dashboard_financial_summary_metrics')) {
    /**
     * Admin dashboard financial top cards: same booking cohort as provider payment / settlement aggregates.
     * Settlement net and payable/balance-with-providers subtract all provider-ledger OUT (provider_payout) and add
     * provider-ledger IN (e.g. collected from provider), matching the headline net on each provider’s payment tab.
     *
     * @return array{
     *     payable_to_providers: float,
     *     payable_to_customers: float,
     *     balance_with_providers: float,
     *     settlement_net: float,
     *     total_amount_received_by_company: float,
     *     total_loss_in_all_bookings: float,
     *     total_bad_debt_with_customers: float
     * }
     * total_loss_in_all_bookings: sum of scaled loss amounts (customer shortfall on loss-making bookings).
     * total_bad_debt_with_customers: sum of the company’s configured loss share on those bookings (company loss absorbed).
     */
    function admin_dashboard_financial_summary_metrics(): array
    {
        $bookingIdsWithRepeats = BookingRepeat::query()
            ->whereNotNull('booking_id')
            ->distinct()
            ->pluck('booking_id')
            ->filter()
            ->values()
            ->all();

        $oneTimeQuery = Booking::query()->where(function ($q) {
            $q->where('booking_status', 'completed')
                ->orWhere(function ($q2) {
                    $q2->where('booking_status', 'canceled')
                        ->where('after_visit_cancel', true);
                });
        });
        if ($bookingIdsWithRepeats !== []) {
            $oneTimeQuery->whereNotIn('id', $bookingIdsWithRepeats);
        }

        $oneTimeIds = $oneTimeQuery->pluck('id');
        $oneTimeBookings = $oneTimeIds->isEmpty()
            ? collect()
            : Booking::query()
                ->whereIn('id', $oneTimeIds)
                ->with(['booking_partial_payments', 'extra_services'])
                ->get();

        $repeats = BookingRepeat::query()
            ->where('booking_status', 'completed')
            ->with(['booking.booking_partial_payments', 'booking.extra_services'])
            ->get();

        $agg = aggregate_provider_booking_settlement_net_for_completed_jobs($oneTimeBookings, $repeats);
        $net = (float) ($agg['settlement_net'] ?? 0);
        $ledgerAllProviders = provider_ledger_manual_flow_totals_all_providers();
        $net = round(
            $net - $ledgerAllProviders['payout_out_total'] + $ledgerAllProviders['collect_in_total'],
            2
        );
        $refundDue = (float) ($agg['customer_refund_due_total'] ?? 0);

        $totalCompanyReceived = 0.0;
        foreach ($oneTimeBookings as $b) {
            if ($b instanceof Booking) {
                $rec = provider_payment_tab_receipts_for_main_booking($b);
                $totalCompanyReceived += (float) ($rec['company'] ?? 0);
            }
        }

        $repeatsCol = collect($repeats);
        foreach ($repeatsCol->groupBy('booking_id') as $_parentId => $group) {
            $first = $group->first();
            if (! $first instanceof BookingRepeat) {
                continue;
            }
            $main = $first->relationLoaded('booking') ? $first->booking : $first->booking()->first();
            if (! $main instanceof Booking) {
                continue;
            }
            $rec = provider_payment_tab_receipts_for_main_booking($main);
            $totalCompanyReceived += (float) ($rec['company'] ?? 0);
        }

        $svc = app(BookingFinancialSettlementService::class);
        $scaledParentsDone = [];
        $totalScaledLossAmount = 0.0;
        $totalCompanyLossShare = 0.0;

        $accumulateScaledLossForMain = function (Booking $main) use ($svc, &$scaledParentsDone, &$totalScaledLossAmount, &$totalCompanyLossShare): void {
            $idStr = (string) $main->id;
            if (isset($scaledParentsDone[$idStr])) {
                return;
            }
            if (! $svc->usesNonStandardSettlement($main)) {
                return;
            }
            if (trim((string) ($main->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                return;
            }
            $scaledParentsDone[$idStr] = true;
            $p = $svc->buildPreview($main);
            $totalScaledLossAmount += (float) ($p['scaled_loss_amount'] ?? 0);
            $totalCompanyLossShare += (float) ($p['scaled_loss_company_share'] ?? 0);
        };

        foreach ($oneTimeBookings as $b) {
            if ($b instanceof Booking) {
                $accumulateScaledLossForMain($b);
            }
        }
        foreach ($repeatsCol->groupBy('booking_id') as $_parentId => $group) {
            $first = $group->first();
            if (! $first instanceof BookingRepeat) {
                continue;
            }
            $main = $first->relationLoaded('booking') ? $first->booking : $first->booking()->first();
            if ($main instanceof Booking) {
                $accumulateScaledLossForMain($main);
            }
        }

        return [
            'settlement_net' => round($net, 2),
            'payable_to_providers' => round(max(0.0, $net), 2),
            'payable_to_customers' => round($refundDue, 2),
            'balance_with_providers' => round(max(0.0, -$net), 2),
            'total_amount_received_by_company' => round($totalCompanyReceived, 2),
            'total_loss_in_all_bookings' => round($totalScaledLossAmount, 2),
            'total_bad_debt_with_customers' => round($totalCompanyLossShare, 2),
        ];
    }
}

if (!function_exists('admin_dashboard_payable_and_balance_from_booking_settlement')) {
    /**
     * @deprecated Use {@see admin_dashboard_financial_summary_metrics()}; kept for backward compatibility.
     *
     * @return array{payable_amount: float, balance_with_providers: float, settlement_net: float, customer_refund_due_total: float}
     */
    function admin_dashboard_payable_and_balance_from_booking_settlement(): array
    {
        $m = admin_dashboard_financial_summary_metrics();

        return [
            'settlement_net' => $m['settlement_net'],
            'customer_refund_due_total' => $m['payable_to_customers'],
            'payable_amount' => round((float) $m['payable_to_providers'] + (float) $m['payable_to_customers'], 2),
            'balance_with_providers' => $m['balance_with_providers'],
        ];
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
            && (
                (bool) ($booking->after_visit_cancel ?? false)
                || (string) ($booking->settlement_outcome ?? '') === BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL
            )) {
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
