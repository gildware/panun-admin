<?php

use App\Lib\DiscountCostBearer;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetail;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingExtraService;
use Modules\BookingModule\Entities\BookingIgnore;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\BookingRepeatDetails;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\ServiceManagement\Entities\Service;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;

if (! function_exists('booking_request_cache')) {
    /**
     * Request-scoped memoization for expensive per-booking computations within one HTTP request.
     */
    function booking_request_cache(string $key, ?callable $resolver = null): mixed
    {
        static $cache = [];

        if ($resolver === null) {
            return array_key_exists($key, $cache) ? $cache[$key] : null;
        }

        if (! array_key_exists($key, $cache)) {
            $cache[$key] = $resolver();
        }

        return $cache[$key];
    }
}

if (! function_exists('booking_cached_ledger_transactions')) {
    /**
     * Single ledger query per booking per request (installments + refunds share this).
     */
    function booking_cached_ledger_transactions(string $bookingId): Collection
    {
        return booking_request_cache("ledger:{$bookingId}", function () use ($bookingId) {
            return LedgerTransaction::query()
                ->where('booking_id', $bookingId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();
        });
    }
}

if (! function_exists('booking_provider_api_payment_snapshot_cached')) {
    function booking_provider_api_payment_snapshot_cached(Booking $booking): array
    {
        $cached = $booking->getAttribute('_payment_snapshot_cache');
        if (is_array($cached)) {
            return $cached;
        }

        $snapshot = booking_provider_api_payment_snapshot($booking);
        $booking->setAttribute('_payment_snapshot_cache', $snapshot);

        return $snapshot;
    }
}

if (! function_exists('booking_financial_build_preview_cached')) {
    function booking_financial_build_preview_cached(Booking $booking): array
    {
        return booking_request_cache('bfs_preview:'.$booking->id, function () use ($booking) {
            return app(BookingFinancialSettlementService::class)->buildPreview($booking);
        });
    }
}

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

if (!function_exists('get_booking_total_amount_for_display')) {
    /**
     * Display-only "total amount" for booking pages/invoices.
     * For disputed reopen close, the effective total becomes the customer retained amount after refunds.
     */
    function get_booking_total_amount_for_display($booking): float
    {
        if ($booking instanceof Booking && !empty($booking->reopen_disputed_snapshot) && is_array($booking->reopen_disputed_snapshot)) {
            $snap = $booking->reopen_disputed_snapshot;
            return round((float) ($snap['retained_from_customer'] ?? $snap['final_net_to_customer'] ?? 0), 2);
        }

        return get_booking_total_amount($booking);
    }
}

if (!function_exists('get_booking_scaled_customer_collection_cap')) {
    /**
     * Loss-making (scaled_to_payments): effective customer-paid amount (min invoice total, max(declared, recorded partials))
     * for installments and due balance — same first value as {@see BookingFinancialSettlementService::resolveScaledLossBreakdown()}.
     */
    function get_booking_scaled_customer_collection_cap(Booking $booking): ?float
    {
        if (trim((string) ($booking->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return null;
        }
        $svc = app(BookingFinancialSettlementService::class);
        $grand = round(max(0.0, get_booking_total_amount($booking)), 2);
        $paidActual = $svc->totalPaidForMainBooking($booking);
        $config = is_array($booking->settlement_config) ? $booking->settlement_config : [];
        [$sx] = $svc->resolveScaledLossBreakdown($booking, $config, $grand, $paidActual);

        return round(max(0.0, $sx), 2);
    }
}

if (!function_exists('get_booking_invoice_due_amount')) {
    /**
     * Remaining amount due on an invoice (payable total minus partial payments).
     */
    function get_booking_invoice_due_amount($booking): float
    {
        if ($booking instanceof Booking && !empty($booking->reopen_disputed_snapshot) && is_array($booking->reopen_disputed_snapshot)) {
            return 0.0;
        }
        if ($booking instanceof Booking
            && BookingFinancialSettlementService::outcomeUsesDecidedVisitCharges((string) ($booking->settlement_outcome ?? ''))) {
            $config = is_array($booking->settlement_config) ? $booking->settlement_config : [];
            $retained = app(BookingFinancialSettlementService::class)->resolveRetainedVisitAmount($booking, $config);
            $invTotal = round($retained, 2);
            $partials = $booking->booking_partial_payments ?? collect();
            $paid = (float) $partials->sum('paid_amount');
            if (round($paid, 2) >= $invTotal - 0.009) {
                return 0.0;
            }

            return round(max(0, $invTotal - $paid), 2);
        }

        if ($booking instanceof Booking) {
            $scaledCap = get_booking_scaled_customer_collection_cap($booking);
            if ($scaledCap !== null) {
                $booking->loadMissing('booking_partial_payments');
                $paid = (float) $booking->booking_partial_payments->sum('paid_amount');
                return round(max(0.0, $scaledCap - $paid), 2);
            }
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
        if (round($paid, 2) >= $invTotal - 0.009) {
            return 0.0;
        }

        return round(max(0, $invTotal - $paid), 2);
    }
}

if (!function_exists('customer_pending_bad_debt_loss_making_bookings_total')) {
    /**
     * Remaining amount still to collect from the customer on loss-making (scaled-to-payments) bookings:
     * settlement customer-obligation cap minus recorded partials (not full invoice total).
     * Parent bookings only (settlement lives on the main row). Canceled / refunded bookings are excluded.
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
            $cap = get_booking_scaled_customer_collection_cap($booking);
            if ($cap === null) {
                continue;
            }
            $paid = get_booking_total_paid($booking);
            $sum += round(max(0.0, $cap - $paid), 2);
        }

        return round($sum, 2);
    }
}

if (!function_exists('customer_loss_making_bad_debt_not_due_total')) {
    /**
     * Sum of {@see BookingFinancialSettlementService::buildPreview()} `scaled_bad_debt_balance_not_due` per loss-making
     * booking (invoice total minus declared customer obligation). For customer profile KPI cards — not the same as
     * {@see customer_pending_bad_debt_loss_making_bookings_total()} (collectible remainder toward the declared cap).
     */
    function customer_loss_making_bad_debt_not_due_total(string $customerId): float
    {
        $bookings = Booking::query()
            ->where('customer_id', $customerId)
            ->where('settlement_outcome', BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS)
            ->whereNotIn('booking_status', ['canceled', 'cancelled', 'refunded'])
            ->with(['booking_partial_payments', 'extra_services'])
            ->get();

        $svc = app(BookingFinancialSettlementService::class);
        $sum = 0.0;
        foreach ($bookings as $booking) {
            $preview = $svc->buildPreview($booking);
            $sum += (float) ($preview['scaled_bad_debt_balance_not_due'] ?? 0);
        }

        return round($sum, 2);
    }
}

if (!function_exists('sum_customer_bookings_payable_grand_total')) {
    /**
     * Sum of {@see get_booking_total_amount} for every parent booking row for this customer
     * (service line totals + extra services + extra_fee). Matches admin customer payments tab per-booking total.
     */
    function sum_customer_bookings_payable_grand_total(string $customerId): float
    {
        $bookings = Booking::query()
            ->where('customer_id', $customerId)
            ->with('extra_services')
            ->get();

        $sum = 0.0;
        foreach ($bookings as $booking) {
            $sum += get_booking_total_amount($booking);
        }

        return round($sum, 2);
    }
}

if (!function_exists('get_booking_payable_total_for_partial_dues')) {
    /**
     * Total customer payment obligation used for installment rows: "due after this payment" and {@see BookingPartialPayment::due_amount}.
     * Aligns with invoice grand total (or retained visit amount when that settlement mode applies), or scaled settlement obligation for loss-making bookings.
     */
    function get_booking_payable_total_for_partial_dues($booking): float
    {
        if ($booking instanceof Booking
            && BookingFinancialSettlementService::outcomeUsesDecidedVisitCharges((string) ($booking->settlement_outcome ?? ''))) {
            $config = is_array($booking->settlement_config) ? $booking->settlement_config : [];

            return round((float) app(BookingFinancialSettlementService::class)->resolveRetainedVisitAmount($booking, $config), 2);
        }

        if ($booking instanceof Booking) {
            $scaledCap = get_booking_scaled_customer_collection_cap($booking);
            if ($scaledCap !== null) {
                return $scaledCap;
            }
        }

        return round(get_booking_total_amount($booking), 2);
    }
}

if (!function_exists('get_booking_admin_add_payment_remaining_amount')) {
    /**
     * Upper bound for a single admin {@see \Modules\BookingModule\Http\Controllers\Web\Admin\BookingController::addPayment}
     * line item (when not using BFS cap override fields on the same request).
     *
     * Loss-making (scaled): allow recording up to the **full invoice** remainder so post-completion recovery is possible;
     * installment `due_amount` then tracks remaining **invoice** balance. Other bookings: payable cap (invoice or retained) minus paid.
     */
    function get_booking_admin_add_payment_remaining_amount(Booking $booking): float
    {
        if (! empty($booking->reopen_disputed_snapshot) && is_array($booking->reopen_disputed_snapshot)) {
            return 0.0;
        }

        $totalPaid = round((float) get_booking_total_paid($booking), 2);
        if (trim((string) ($booking->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            $grand = round((float) get_booking_total_amount($booking), 2);
            $cfg = is_array($booking->settlement_config) ? $booking->settlement_config : [];
            $writeoff = isset($cfg['scaled_loss_writeoff_amount']) && is_numeric($cfg['scaled_loss_writeoff_amount'])
                ? round(max(0.0, (float) $cfg['scaled_loss_writeoff_amount']), 2)
                : 0.0;

            // Loss-making (scaled): "write-off" settles remaining invoice without customer payment.
            return round(max(0.0, $grand - $totalPaid - $writeoff), 2);
        }

        $cap = round((float) get_booking_payable_total_for_partial_dues($booking), 2);

        return round(max(0.0, $cap - $totalPaid), 2);
    }
}

if (! function_exists('get_booking_customer_display_due_balance')) {
    /**
     * Due balance on customer/admin payment cards and loss-making customer summaries.
     * Scaled (loss-making) bookings: full invoice total minus recorded payments — same as admin
     * {@see Modules/BookingModule/Resources/views/admin/booking/details.blade.php} Due Balance and
     * installment "due after this payment" ({@see booking_installment_payable_cap()}).
     * Other bookings: {@see get_booking_invoice_due_amount()}.
     */
    function get_booking_customer_display_due_balance(Booking $booking): float
    {
        if ((int) ($booking->is_repeated ?? 0) === 0
            && trim((string) ($booking->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS
            && ! in_array((string) ($booking->booking_status ?? ''), ['canceled', 'cancelled', 'refunded'], true)) {
            $total = round((float) get_booking_total_amount($booking), 2);
            $paid = round((float) get_booking_total_paid($booking), 2);
            $cfg = is_array($booking->settlement_config) ? $booking->settlement_config : [];
            $writeoff = isset($cfg['scaled_loss_writeoff_amount']) && is_numeric($cfg['scaled_loss_writeoff_amount'])
                ? round(max(0.0, (float) $cfg['scaled_loss_writeoff_amount']), 2)
                : 0.0;

            return round(max(0.0, $total - $paid - $writeoff), 2);
        }

        return round((float) get_booking_invoice_due_amount($booking), 2);
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

if (! function_exists('booking_detail_line_gross_total')) {
    function booking_detail_line_gross_total($detail): float
    {
        return round((float) ($detail->service_cost ?? 0) * (int) ($detail->quantity ?? 1), 2);
    }
}

if (! function_exists('booking_detail_line_discounted_total')) {
    function booking_detail_line_discounted_total($detail): float
    {
        $gross = booking_detail_line_gross_total($detail);
        $discount = round(
            (float) ($detail->discount_amount ?? 0)
            + (float) ($detail->campaign_discount_amount ?? 0)
            + (float) ($detail->overall_coupon_discount_amount ?? 0),
            2
        );

        return round(max(0, $gross - $discount), 2);
    }
}

if (! function_exists('booking_extra_service_line_gross_total')) {
    function booking_extra_service_line_gross_total($extra): float
    {
        return round((float) ($extra->price ?? 0) * (int) ($extra->quantity ?? 1), 2);
    }
}

if (! function_exists('booking_summary_dual_price_html')) {
    function booking_summary_dual_price_html(float $original, float $discounted, bool $bold = false): HtmlString
    {
        $original = round($original, 2);
        $discounted = round($discounted, 2);
        $weight = $bold ? 'fw-semibold' : '';

        if ($original <= $discounted + 0.001) {
            return new HtmlString('<span class="'.$weight.'">'.e(with_currency_symbol($discounted)).'</span>');
        }

        return new HtmlString(
            '<div class="d-flex flex-column align-items-end '.$weight.'">'
            .'<span class="text-decoration-line-through text-muted small">'.e(with_currency_symbol($original)).'</span>'
            .'<span>'.e(with_currency_symbol($discounted)).'</span>'
            .'</div>'
        );
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
        if ($booking instanceof Booking && !empty($booking->reopen_disputed_snapshot) && is_array($booking->reopen_disputed_snapshot)) {
            $snap = $booking->reopen_disputed_snapshot;

            return [
                'commission' => round((float) ($snap['final_admin_commission'] ?? 0), 2),
                'commission_without_cost' => round((float) ($snap['final_admin_commission'] ?? 0), 2),
                'booking_amount_without_commission' => round((float) ($snap['final_provider_earning'] ?? 0), 2),
                'promotional_cost_by_admin' => 0,
                'promotional_cost_by_provider' => 0,
            ];
        }

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

if (!function_exists('provider_payment_tab_one_time_revenue_bookings_inner')) {
    /**
     * Inner WHERE group: one-time bookings that count toward provider payment tab / dashboard revenue & settlement.
     * Includes completed, after-visit canceled, and closed disputed reopen (canceled/refunded with reopen_disputed_snapshot).
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $q
     */
    function provider_payment_tab_one_time_revenue_bookings_inner($q): void
    {
        $q->where('booking_status', 'completed')
            ->orWhere(function ($q2) {
                $q2->where('booking_status', 'canceled')
                    ->where('after_visit_cancel', 1);
            })
            ->orWhere(function ($q3) {
                $q3->whereIn('booking_status', ['canceled', 'refunded'])
                    ->whereNotNull('reopen_disputed_snapshot');
            });
    }
}

if (!function_exists('provider_payment_tab_earning_commission_pair')) {
    /**
     * Provider payment tab "Booking Earning Report": provider earning and admin commission from live commission breakdown
     * (same basis as booking details Revenue & Settlement via {@see get_commission_breakdown_for_booking}),
     * not sums of persisted {@see BookingDetailsAmount} rows.
     *
     * Disputed reopen refund (closed): use net basis from {@see Booking::$reopen_disputed_snapshot} so canceled/refunded
     * jobs still contribute commission and provider earning like the settlement snapshot.
     *
     * @param  Booking|BookingRepeat|object  $bookingOrRepeat
     * @return array{provider_earning: float, admin_commission: float}
     */
    function provider_payment_tab_earning_commission_pair($bookingOrRepeat): array
    {
        if ($bookingOrRepeat instanceof Booking) {
            $snap = $bookingOrRepeat->reopen_disputed_snapshot ?? null;
            if (is_array($snap)
                && ($snap['type'] ?? '') === 'reopen_disputed_refund'
                && trim((string) ($bookingOrRepeat->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                return [
                    'provider_earning' => round(max(0.0, (float) ($snap['final_provider_earning'] ?? 0)), 2),
                    'admin_commission' => round(max(0.0, (float) ($snap['final_admin_commission'] ?? 0)), 2),
                ];
            }
        }

        $breakdown = get_commission_breakdown_for_booking($bookingOrRepeat);

        return [
            'provider_earning' => (float) ($breakdown['booking_amount_without_commission'] ?? 0),
            'admin_commission' => (float) ($breakdown['commission_without_cost'] ?? 0),
        ];
    }
}

if (!function_exists('booking_reopen_disputed_refund_settlement_adjustment')) {
    /**
     * Inter-party IOU from disputed reopen refund. Provider→company total is:
     * refund leg above company pool + net admin commission on retained customer amount (provider remits commission to company).
     * Company→provider is refund leg above provider pool only.
     * Merged into {@see get_booking_received_and_settlement()} and {@see record_reopen_disputed_refund_reconciliation()}.
     *
     * @return array{provider_owes_company: float, company_owes_provider: float, provider_owes_from_refund_pool_only: float, final_admin_commission_included: float}
     */
    function booking_reopen_disputed_refund_settlement_adjustment(Booking $booking): array
    {
        $snap = $booking->reopen_disputed_snapshot ?? null;
        if (! is_array($snap) || ($snap['type'] ?? '') !== 'reopen_disputed_refund') {
            return [
                'provider_owes_company' => 0.0,
                'company_owes_provider' => 0.0,
                'provider_owes_from_refund_pool_only' => 0.0,
                'final_admin_commission_included' => 0.0,
            ];
        }

        $fromPool = round(max(0.0, (float) ($snap['provider_owes_company'] ?? 0)), 2);
        $finalComm = round(max(0.0, (float) ($snap['final_admin_commission'] ?? 0)), 2);
        $providerTotal = round($fromPool + $finalComm, 2);

        return [
            'provider_owes_company' => $providerTotal,
            'company_owes_provider' => round(max(0.0, (float) ($snap['company_owes_provider'] ?? 0)), 2),
            'provider_owes_from_refund_pool_only' => $fromPool,
            'final_admin_commission_included' => $finalComm,
        ];
    }
}

if (!function_exists('booking_partial_payment_loss_allocation_split')) {
    /**
     * Optional economic split for loss-making recovery payments: how much of paid_amount reduces provider vs company
     * loss in {@see BookingFinancialSettlementService::resolveScaledLossBreakdown()} / admin caps — not who physically
     * held the cash. Receipt columns use `received_by` on the partial only.
     *
     * @return array{provider: float, company: float}|null
     */
    function booking_partial_payment_loss_allocation_split(object $p): ?array
    {
        $lp = $p->loss_allocation_provider ?? null;
        $lc = $p->loss_allocation_company ?? null;
        if ($lp === null && $lc === null) {
            return null;
        }
        $paid = round((float) ($p->paid_amount ?? 0), 2);
        if ($paid <= 0) {
            return null;
        }
        $prov = round((float) $lp, 2);
        $co = round((float) $lc, 2);
        if (abs($prov + $co - $paid) > 0.02) {
            return null;
        }

        return ['provider' => $prov, 'company' => $co];
    }
}

if (!function_exists('booking_admin_loss_recovery_split_caps')) {
    /**
     * Remaining scaled loss per side (from live settlement preview) — caps for admin add-payment loss recovery fields.
     *
     * @return array{provider: float, company: float}|null
     */
    function booking_admin_loss_recovery_split_caps(Booking $booking): ?array
    {
        if (trim((string) ($booking->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return null;
        }
        $preview = app(BookingFinancialSettlementService::class)->buildPreview($booking);

        return [
            'provider' => max(0.0, round((float) ($preview['scaled_loss_provider_share'] ?? 0), 2)),
            'company' => max(0.0, round((float) ($preview['scaled_loss_company_share'] ?? 0), 2)),
        ];
    }
}

if (!function_exists('get_booking_received_and_settlement')) {
    /**
     * For admin booking details: amounts received by company vs provider, and settlement (pay provider / provider owes).
     * `amount_received_by_*` sums partial `paid_amount` by `received_by` only. Loss recovery splits (`loss_allocation_*`)
     * do not change those receipt totals; they feed scaled loss recovery math elsewhere.
     * Company keeps commission; rest goes to provider. So: pay_to_provider = company received - commission (when company has provider's share).
     * provider_owes_company = provider received - provider_share (when provider has company's commission).
     *
     * @return array{company_share: float, provider_share: float, amount_received_by_company: float, amount_received_by_provider: float, total_paid: float, pay_to_provider: float, provider_owes_company: float, net_revenue_zeroed_after_refund: bool}
     */
    function get_booking_received_and_settlement($booking): array
    {
        // Disputed reopen close: snapshot is the source of truth for all financial display numbers.
        // This must override the original commission breakdown / receipt splits everywhere (admin + provider UIs + reports).
        if ($booking instanceof Booking) {
            $snap = !empty($booking->reopen_disputed_snapshot) && is_array($booking->reopen_disputed_snapshot)
                ? $booking->reopen_disputed_snapshot
                : null;
            if (is_array($snap)) {
                $netRevenueZeroedAfterRefund = booking_should_zero_net_revenue_settlement_display($booking);
                if ($netRevenueZeroedAfterRefund) {
                    $disputedAdj = booking_reopen_disputed_refund_settlement_adjustment($booking);

                    return [
                        'company_share' => 0.0,
                        'provider_share' => 0.0,
                        'amount_received_by_company' => 0.0,
                        'amount_received_by_provider' => 0.0,
                        'total_paid' => round((float) ($snap['customer_paid_total'] ?? 0), 2),
                        'pay_to_provider' => $disputedAdj['company_owes_provider'],
                        'provider_owes_company' => $disputedAdj['provider_owes_company'],
                        'net_revenue_zeroed_after_refund' => true,
                    ];
                }

                return [
                    'company_share' => round((float) ($snap['final_admin_commission'] ?? 0), 2),
                    'provider_share' => round((float) ($snap['final_provider_earning'] ?? 0), 2),
                    'amount_received_by_company' => round((float) ($snap['company_cash_after_refund'] ?? 0), 2),
                    'amount_received_by_provider' => round((float) ($snap['provider_cash_after_refund'] ?? 0), 2),
                    'total_paid' => round((float) ($snap['customer_paid_total'] ?? 0), 2),
                    // Reconciliation totals (who remits / pays after refunds).
                    'pay_to_provider' => round((float) ($snap['company_pays_provider_total'] ?? ($snap['company_owes_provider'] ?? 0)), 2),
                    'provider_owes_company' => round((float) ($snap['provider_total_remittance_to_company'] ?? ($snap['provider_owes_company'] ?? 0)), 2),
                    'net_revenue_zeroed_after_refund' => false,
                ];
            }
        }

        $breakdown = get_commission_breakdown_for_booking($booking);
        $companyShare = (float) $breakdown['commission_without_cost'];
        $providerShare = (float) $breakdown['booking_amount_without_commission'];

        $bookingForPartials = $booking instanceof BookingRepeat
            ? ($booking->relationLoaded('booking') ? $booking->booking : $booking->booking()->first())
            : $booking;
        $partials = $bookingForPartials && $bookingForPartials->relationLoaded('booking_partial_payments')
            ? $bookingForPartials->booking_partial_payments
            : ($bookingForPartials ? $bookingForPartials->booking_partial_payments()->get() : collect());
        if (!$partials) {
            $partials = collect();
        }
        $scaledMain = $bookingForPartials instanceof Booking
            && trim((string) ($bookingForPartials->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;

        if ($partials->isNotEmpty()) {
            $totalPaid = (float) $partials->sum('paid_amount');
        } elseif ($scaledMain && $bookingForPartials instanceof Booking) {
            // Loss-making (scaled): recorded customer cash is only from partial rows; {@see BookingFinancialSettlementService::totalPaidForMainBooking}
            // returns 0 when there are no partials (even if is_paid=1 / complete-without-full-payment). Do not attribute full invoice to provider.
            $totalPaid = round((float) app(BookingFinancialSettlementService::class)->totalPaidForMainBooking($bookingForPartials), 2);
        } else {
            $totalPaid = ($booking->is_paid ? get_booking_total_amount($booking) : 0);
        }

        if ($partials->isNotEmpty()) {
            $amountReceivedByCompany = 0.0;
            $amountReceivedByProvider = 0.0;
            foreach ($partials as $p) {
                $paid = (float) $p->paid_amount;
                // Cash receipt columns follow `received_by` only. Loss recovery splits (`loss_allocation_*`) are for
                // scaled loss math / recovery caps — not "who held the money" (see {@see booking_partial_payment_loss_allocation_split}).
                $by = $p->received_by ?? '';
                if ($by === 'company' || $by === '') {
                    $amountReceivedByCompany += $paid;
                } elseif ($by === 'provider') {
                    $amountReceivedByProvider += $paid;
                }
            }
            if ($amountReceivedByCompany == 0 && $amountReceivedByProvider == 0 && $totalPaid > 0 && ! $scaledMain) {
                $amountReceivedByCompany = $booking->payment_method !== 'cash_after_service' ? $totalPaid : 0;
                $amountReceivedByProvider = $booking->payment_method === 'cash_after_service' ? $totalPaid : 0;
            }
        } elseif ($scaledMain && $bookingForPartials instanceof Booking) {
            $amountReceivedByCompany = 0.0;
            $amountReceivedByProvider = 0.0;
        } else {
            $amountReceivedByCompany = ($booking->is_paid && $booking->payment_method !== 'cash_after_service') ? $totalPaid : 0;
            $amountReceivedByProvider = ($booking->is_paid && $booking->payment_method === 'cash_after_service') ? $totalPaid : 0;
        }

        $netRevenueZeroedAfterRefund = $booking instanceof Booking && booking_should_zero_net_revenue_settlement_display($booking);
        if ($netRevenueZeroedAfterRefund) {
            $disputedAdj = $booking instanceof Booking
                ? booking_reopen_disputed_refund_settlement_adjustment($booking)
                : ['provider_owes_company' => 0.0, 'company_owes_provider' => 0.0];

            return [
                'company_share' => 0.0,
                'provider_share' => 0.0,
                'amount_received_by_company' => 0.0,
                'amount_received_by_provider' => 0.0,
                'total_paid' => round($totalPaid, 2),
                'pay_to_provider' => $disputedAdj['company_owes_provider'],
                'provider_owes_company' => $disputedAdj['provider_owes_company'],
                'net_revenue_zeroed_after_refund' => true,
            ];
        }

        // Loss-making (scaled): net shares are proportional to amount paid by customer (same ratio as full booking).
        if ($booking instanceof Booking) {
            $outcomeMain = trim((string) ($booking->settlement_outcome ?? ''));
            if ($outcomeMain === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                $svc = app(BookingFinancialSettlementService::class);
                $cfg = is_array($booking->settlement_config) ? $booking->settlement_config : [];
                $gt = get_booking_total_amount($booking);
                $paidMain = $svc->totalPaidForMainBooking($booking);
                [$customerPaid, , , ] = $svc->resolveScaledLossBreakdown($booking, $cfg, $gt, $paidMain);
                [$companyShare, $providerShare] = $svc->scaledNetSharesFromCustomerPaid(
                    $gt,
                    $customerPaid,
                    $companyShare,
                    $providerShare
                );
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

        $disputedAdj = $booking instanceof Booking
            ? booking_reopen_disputed_refund_settlement_adjustment($booking)
            : ['provider_owes_company' => 0.0, 'company_owes_provider' => 0.0];
        $payToProvider = round($payToProvider + $disputedAdj['company_owes_provider'], 2);
        $providerOwesCompany = round($providerOwesCompany + $disputedAdj['provider_owes_company'], 2);

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

if (!function_exists('booking_reopen_disputed_tier_split_for_amounts')) {
    /**
     * Admin vs provider split using the same tier engine as {@see calculate_commission_for_booking} / booking details
     * ({@see commission_calc_line_preview}), for the given service and spare **line subtotals**.
     *
     * @return array{admin_commission: float, provider_earning: float}
     */
    function booking_reopen_disputed_tier_split_for_amounts(Booking $booking, float $serviceAmount, float $spareAmount): array
    {
        $setup = resolve_commission_tier_setup_for_booking($booking, $booking->provider_id);
        $serviceAmount = round(max(0.0, $serviceAmount), 2);
        $spareAmount = round(max(0.0, $spareAmount), 2);
        $p1 = commission_calc_line_preview($serviceAmount, $setup['service']);
        $p2 = commission_calc_line_preview($spareAmount, $setup['spare_parts']);

        return [
            'admin_commission' => round((float) ($p1['admin_commission'] ?? 0) + (float) ($p2['admin_commission'] ?? 0), 2),
            'provider_earning' => round((float) ($p1['provider_earning'] ?? 0) + (float) ($p2['provider_earning'] ?? 0), 2),
        ];
    }
}

if (!function_exists('booking_reopen_disputed_commission_on_customer_retained')) {
    /**
     * Disputed reopen: treat "retained from customer after refunds" as the new effective booking total and compute
     * admin vs provider using the same tier engine as the booking, on **scaled service + spare subtotals** so that
     * admin + provider = retained.
     *
     * Scaling basis is the original booking economic base (service + spare), NOT customer paid, so partial payment
     * scenarios (e.g. paid 300 on total 700) still recompute commission correctly on retained cash.
     *
     * @return array{
     *   admin_commission: float,
     *   provider_earning: float,
     *   scale_factor: float,
     *   full_tier_admin: float,
     *   full_tier_provider: float,
     *   services_retained: float,
     *   spare_parts_retained: float,
     *   services_admin_commission: float,
     *   spare_parts_admin_commission: float,
     *   services_provider_earning: float,
     *   spare_parts_provider_earning: float
     * }
     */
    function booking_reopen_disputed_commission_on_customer_retained(Booking $booking, float $retainedFromCustomer, float $totalCustomerPaid): array
    {
        $paid = round(max(0.0, $totalCustomerPaid), 2);
        $retained = round(max(0.0, $retainedFromCustomer), 2);
        $svcFull = round(max(0.0, (float) get_booking_commissionable_amount($booking)), 2);
        $spFull = round(max(0.0, (float) get_booking_spare_parts_amount($booking)), 2);
        $base = round($svcFull + $spFull, 2);
        $full = booking_reopen_disputed_tier_split_for_amounts($booking, $svcFull, $spFull);
        $aFull = round((float) ($full['admin_commission'] ?? 0), 2);
        $pFull = round((float) ($full['provider_earning'] ?? 0), 2);

        $scaleFactor = $base > 0.0001 ? min(1.0, $retained / $base) : 0.0;

        if ($retained <= 0.0001) {
            return [
                'admin_commission' => 0.0,
                'provider_earning' => 0.0,
                'scale_factor' => round($scaleFactor, 6),
                'full_tier_admin' => $aFull,
                'full_tier_provider' => $pFull,
                'services_retained' => 0.0,
                'spare_parts_retained' => 0.0,
                'services_admin_commission' => 0.0,
                'spare_parts_admin_commission' => 0.0,
                'services_provider_earning' => 0.0,
                'spare_parts_provider_earning' => 0.0,
            ];
        }

        if ($base <= 0.0001) {
            // Nothing commissionable recorded; if customer retained cash, treat it all as provider earning.
            return [
                'admin_commission' => 0.0,
                'provider_earning' => $retained,
                'scale_factor' => round($scaleFactor, 6),
                'full_tier_admin' => $aFull,
                'full_tier_provider' => $pFull,
                'services_retained' => $retained,
                'spare_parts_retained' => 0.0,
                'services_admin_commission' => 0.0,
                'spare_parts_admin_commission' => 0.0,
                'services_provider_earning' => $retained,
                'spare_parts_provider_earning' => 0.0,
            ];
        }

        // Scale service + spare subtotals such that (svc + sp) = retained (avoid rounding drift).
        $svc = round($svcFull * $scaleFactor, 2);
        $sp = round(max(0.0, $retained - $svc), 2);

        $setup = resolve_commission_tier_setup_for_booking($booking, $booking->provider_id);
        $svcSplit = commission_calc_line_preview($svc, $setup['service']);
        $spSplit = commission_calc_line_preview($sp, $setup['spare_parts']);

        $svcAdmin = round((float) ($svcSplit['admin_commission'] ?? 0), 2);
        $spAdmin = round((float) ($spSplit['admin_commission'] ?? 0), 2);
        $svcProv = round((float) ($svcSplit['provider_earning'] ?? 0), 2);
        $spProv = round((float) ($spSplit['provider_earning'] ?? 0), 2);

        $admin = round($svcAdmin + $spAdmin, 2);
        $provider = round($svcProv + $spProv, 2);

        return [
            'admin_commission' => $admin,
            'provider_earning' => $provider,
            'scale_factor' => round($scaleFactor, 6),
            'full_tier_admin' => $aFull,
            'full_tier_provider' => $pFull,
            'services_retained' => $svc,
            'spare_parts_retained' => $sp,
            'services_admin_commission' => $svcAdmin,
            'spare_parts_admin_commission' => $spAdmin,
            'services_provider_earning' => $svcProv,
            'spare_parts_provider_earning' => $spProv,
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

if (!function_exists('booking_should_show_admin_revenue_settlement_breakdown')) {
    /**
     * Admin booking details "Revenue & Settlement": show commission-style splits only when there is a real
     * settlement basis (after-visit cancel, special financial settlement, or non-terminal status).
     * For cancel/refund before service, {@see get_booking_received_and_settlement()} still derives hypothetical
     * shares from the catalog total — hide that block so we do not imply earned revenue.
     */
    function booking_should_show_admin_revenue_settlement_breakdown($booking): bool
    {
        if ($booking instanceof BookingRepeat) {
            $main = $booking->relationLoaded('booking') ? $booking->booking : $booking->booking()->first();
            $booking = $main;
        }
        if (!$booking instanceof Booking) {
            return true;
        }
        $st = (string) ($booking->booking_status ?? '');
        if (! in_array($st, ['canceled', 'cancelled', 'refunded'], true)) {
            return true;
        }
        if (! empty($booking->after_visit_cancel)) {
            return true;
        }
        if (booking_has_special_financial_settlement($booking)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('booking_on_hold_is_after_visit_from_ongoing')) {
    /**
     * True when the booking is on hold and the latest transition to on_hold was from ongoing
     * (job was in progress — hold after visit / after starting work), not from accepted-only, etc.
     *
     * @param  Booking|BookingRepeat  $booking
     */
    function booking_on_hold_is_after_visit_from_ongoing($booking): bool
    {
        if (! $booking instanceof Booking && ! $booking instanceof BookingRepeat) {
            return false;
        }
        if ((string) ($booking->booking_status ?? '') !== 'on_hold') {
            return false;
        }
        $q = BookingStatusHistory::query()->orderBy('created_at')->orderBy('id');
        if ($booking instanceof Booking) {
            $q->where('booking_id', $booking->id)->whereNull('booking_repeat_id');
        } else {
            $q->where('booking_repeat_id', $booking->id);
        }
        $rows = $q->get(['booking_status']);
        if ($rows->count() < 2) {
            return false;
        }
        $last = $rows->last();
        if ((string) ($last->booking_status ?? '') !== 'on_hold') {
            return false;
        }
        $prev = $rows->get($rows->count() - 2);

        return $prev && (string) ($prev->booking_status ?? '') === 'ongoing';
    }
}

if (! function_exists('booking_admin_can_reassign_provider')) {
    /**
     * Admin may change provider only before the booking has ever been set to ongoing.
     * After service is ongoing (current or past in status history), reassign is blocked; close the booking and book again.
     *
     * @param  \Modules\BookingModule\Entities\Booking|\Modules\BookingModule\Entities\BookingRepeat  $booking
     */
    function booking_admin_can_reassign_provider($booking): bool
    {
        if ($booking instanceof BookingRepeat) {
            $main = $booking->relationLoaded('booking') ? $booking->booking : $booking->booking()->first();
            if (! $main instanceof Booking) {
                return false;
            }
            $currentSt = strtolower((string) ($booking->booking_status ?? ''));
        } elseif ($booking instanceof Booking) {
            $main = $booking;
            $currentSt = strtolower((string) ($main->booking_status ?? ''));
        } else {
            return false;
        }

        if (in_array($currentSt, ['completed', 'canceled', 'cancelled', 'refunded'], true)) {
            return false;
        }
        if ($currentSt === 'ongoing') {
            return false;
        }

        return ! BookingStatusHistory::query()
            ->where('booking_id', $main->id)
            ->where('booking_status', 'ongoing')
            ->exists();
    }
}

if (! function_exists('booking_clear_provider_ignore')) {
    /**
     * Remove a provider from a booking's ignore list when admin reassigns them.
     */
    function booking_clear_provider_ignore(string $bookingId, string $providerId): void
    {
        if ($bookingId === '' || $providerId === '') {
            return;
        }

        BookingIgnore::query()
            ->where('booking_id', $bookingId)
            ->where('provider_id', $providerId)
            ->delete();

        if (class_exists(\Modules\ProviderManagement\Services\ProviderBookingTabCountCache::class)) {
            \Modules\ProviderManagement\Services\ProviderBookingTabCountCache::forgetForProvider($providerId);
        }
    }
}

if (! function_exists('booking_invalidate_provider_tab_counts')) {
    function booking_invalidate_provider_tab_counts(?string $providerId): void
    {
        if ($providerId === null || $providerId === '') {
            return;
        }

        if (class_exists(\Modules\ProviderManagement\Services\ProviderBookingTabCountCache::class)) {
            \Modules\ProviderManagement\Services\ProviderBookingTabCountCache::forgetForProvider($providerId);
        }
    }
}

if (! function_exists('booking_reopen_combined_status_key')) {
    /**
     * Translation key for open reopen tickets, e.g. reopened_and_pending.
     * Returns null when the booking is not an active reopen case.
     */
    function booking_reopen_combined_status_key(Booking $booking): ?string
    {
        if ((int) ($booking->is_repeated ?? 0) !== 0) {
            return null;
        }
        if (! $booking->isOpenReopenTicket()) {
            return null;
        }

        $st = strtolower((string) ($booking->booking_status ?? ''));
        if ($st === 'cancelled') {
            $st = 'canceled';
        }
        if ($st === 'on_hold') {
            return 'reopened_and_on_hold';
        }
        if ($st === 'canceled') {
            return 'reopened_and_cancelled';
        }
        if ($st === 'refunded') {
            return 'reopened_and_refunded';
        }
        if ($st === 'completed') {
            return 'reopened_and_completed';
        }
        if (in_array($st, ['pending', 'accepted', 'ongoing'], true)) {
            return 'reopened_and_' . $st;
        }

        return 'reopened_and_' . ($st !== '' ? $st : 'pending');
    }
}

if (!function_exists('booking_admin_booking_status_display_label')) {
    /**
     * Admin UI label for {@see Booking::booking_status}: use "Hold after visit" when hold followed ongoing.
     * Disputed-close is a terminal case; display as "Disputed and Completed" when any customer amount was collected,
     * otherwise "Disputed and Cancelled".
     */
    function booking_admin_booking_status_display_label(Booking $booking): string
    {
        if ($reopenKey = booking_reopen_combined_status_key($booking)) {
            return translate($reopenKey);
        }

        $hasDisputedSnapshot = ! empty($booking->reopen_disputed_snapshot)
            && is_array($booking->reopen_disputed_snapshot)
            && $booking->reopen_disputed_snapshot !== [];
        if ($hasDisputedSnapshot) {
            $snap = (array) $booking->reopen_disputed_snapshot;
            $retained = 0.0;
            foreach (['retained_from_customer', 'final_net_to_customer'] as $k) {
                if (isset($snap[$k]) && is_numeric($snap[$k])) {
                    $retained = (float) $snap[$k];
                    break;
                }
            }

            return $retained > 0.009
                ? translate('Disputed_and_Completed')
                : translate('Disputed_and_Cancelled');
        }
        if (booking_on_hold_is_after_visit_from_ongoing($booking)) {
            return translate('Hold_after_visit');
        }

        $st = (string) ($booking->booking_status ?? '');
        if ($st === 'pending_cancellation') {
            return translate('Pending_cancellation');
        }
        $base = ucwords(str_replace('_', ' ', $st));
        if ($st === 'canceled' || $st === 'cancelled') {
            $refundTotals = get_booking_refund_display_totals($booking);
            if (round((float) ($refundTotals['refundable_remaining'] ?? 0), 2) > 0.009) {
                return translate('Booking_cancelled') . ' — ' . translate('Pending_refund');
            }

            return translate('Booking_cancelled');
        }

        return $base;
    }
}

if (!function_exists('booking_admin_is_cancel_before_visit')) {
    /**
     * True when booking is canceled before service went ongoing (no after-visit special settlement).
     */
    function booking_admin_is_cancel_before_visit(Booking $booking): bool
    {
        $st = strtolower((string) ($booking->booking_status ?? ''));
        if (! in_array($st, ['canceled', 'cancelled', 'refunded'], true)) {
            return false;
        }
        if ((bool) ($booking->after_visit_cancel ?? false)) {
            return false;
        }
        $out = trim((string) ($booking->settlement_outcome ?? ''));
        if ($out === BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL
            || $out === BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT) {
            return false;
        }

        return true;
    }
}

if (!function_exists('booking_admin_has_disputed_reopen_snapshot')) {
    function booking_admin_has_disputed_reopen_snapshot(Booking $booking): bool
    {
        $snap = $booking->reopen_disputed_snapshot ?? null;

        return is_array($snap) && ($snap['type'] ?? null) === 'reopen_disputed_refund';
    }
}

if (!function_exists('booking_admin_has_compensation')) {
    /**
     * True when any compensation entry exists for this booking.
     */
    function booking_admin_has_compensation(Booking $booking): bool
    {
        if (isset($booking->compensations_count)) {
            return (int) $booking->compensations_count > 0;
        }
        if ($booking->relationLoaded('compensations')) {
            return $booking->compensations->isNotEmpty();
        }

        return $booking->compensations()->exists();
    }
}

if (!function_exists('booking_admin_should_show_cancel_after_visit_tag')) {
    /**
     * Cancel-after-visit decided charges (retain visit fee) — not plain before-visit cancel.
     */
    function booking_admin_should_show_cancel_after_visit_tag(Booking $booking): bool
    {
        $st = strtolower((string) ($booking->booking_status ?? ''));
        if (! in_array($st, ['canceled', 'cancelled', 'refunded'], true)) {
            return false;
        }
        if ((bool) ($booking->after_visit_cancel ?? false)) {
            return true;
        }

        return trim((string) ($booking->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL;
    }
}

if (!function_exists('booking_admin_should_show_complete_no_service_tag')) {
    /**
     * "Complete with little / no real service" (visit fee split) after booking is closed completed.
     */
    function booking_admin_should_show_complete_no_service_tag(Booking $booking): bool
    {
        if ((string) ($booking->booking_status ?? '') !== 'completed') {
            return false;
        }

        return trim((string) ($booking->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT;
    }
}

if (!function_exists('booking_admin_refund_display_totals')) {
    /**
     * Refund amounts for admin status tags (includes completed + ledger refunds, not only canceled/refunded).
     *
     * @return array{refunded_total: float, refundable_remaining: float, max_eligible: float, show: bool, source: string}
     */
    function booking_admin_refund_display_totals(Booking $booking): array
    {
        $st = (string) ($booking->booking_status ?? '');
        $bid = (string) ($booking->id ?? '');
        $refundedLedger = $bid !== '' ? booking_ledger_refund_out_total($bid) : 0.0;

        if (in_array($st, ['canceled', 'cancelled', 'refunded'], true)) {
            $t = get_booking_refund_display_totals($booking);

            return array_merge($t, ['source' => 'cancel_or_refunded_status']);
        }

        if ($st === 'completed') {
            $totalPaid = round((float) get_booking_total_paid($booking), 2);
            $refundedTotal = round((float) $refundedLedger, 2);
            $remaining = max(0.0, round($totalPaid - $refundedTotal, 2));
            $show = ($totalPaid > 0.009 && $remaining > 0.009) || ($refundedTotal > 0.009);

            if (! $show) {
                return [
                    'refunded_total' => 0.0,
                    'refundable_remaining' => 0.0,
                    'max_eligible' => 0.0,
                    'show' => false,
                    'source' => 'none',
                ];
            }

            return [
                'refunded_total' => $refundedTotal,
                'refundable_remaining' => $remaining,
                'max_eligible' => $totalPaid,
                'show' => true,
                'source' => 'completed_with_refund',
            ];
        }

        return [
            'refunded_total' => 0.0,
            'refundable_remaining' => 0.0,
            'max_eligible' => 0.0,
            'show' => false,
            'source' => 'none',
        ];
    }
}

if (!function_exists('booking_admin_classify_refund_ui_tag')) {
    /**
     * Single refund tag for admin UI: pending | full (Refunded) | partial — mutually exclusive priority.
     *
     * @return 'pending'|'full'|'partial'|null
     */
    function booking_admin_classify_refund_ui_tag(Booking $booking): ?string
    {
        $t = booking_admin_refund_display_totals($booking);
        $refunded = round((float) ($t['refunded_total'] ?? 0), 2);
        $remaining = round((float) ($t['refundable_remaining'] ?? 0), 2);
        $maxEl = round((float) ($t['max_eligible'] ?? 0), 2);
        $st = (string) ($booking->booking_status ?? '');

        if (! ($t['show'] ?? false) && $refunded <= 0.009) {
            return null;
        }
        // Pending refund tag is only meaningful for canceled bookings where admin still must pay the customer.
        if (in_array($st, ['canceled', 'cancelled'], true) && $remaining > 0.009) {
            return 'pending';
        }
        if ($refunded <= 0.009) {
            return null;
        }
        if ($maxEl > 0.009 && $refunded + 0.005 >= $maxEl) {
            return 'full';
        }

        return 'partial';
    }
}

if (! function_exists('booking_admin_status_badge_variant')) {
    /**
     * Bootstrap-style badge variant for provider/admin list UIs and mobile API.
     */
    function booking_admin_status_badge_variant(Booking $booking): string
    {
        if (booking_reopen_combined_status_key($booking) !== null) {
            return 'warning';
        }

        $st = strtolower((string) ($booking->booking_status ?? ''));
        $hasDisputedSnapshot = ! empty($booking->reopen_disputed_snapshot)
            && is_array($booking->reopen_disputed_snapshot);
        if ($hasDisputedSnapshot) {
            $snap = (array) $booking->reopen_disputed_snapshot;
            $retained = 0.0;
            foreach (['retained_from_customer', 'final_net_to_customer'] as $k) {
                if (isset($snap[$k]) && is_numeric($snap[$k])) {
                    $retained = (float) $snap[$k];
                    break;
                }
            }

            return $retained > 0.009 ? 'warning_dark' : 'danger';
        }

        return match ($st) {
            'ongoing' => 'warning',
            'on_hold' => 'secondary',
            'completed' => 'success',
            'canceled', 'cancelled', 'refunded' => 'danger',
            default => 'info',
        };
    }
}

if (! function_exists('booking_admin_status_display_key')) {
    /**
     * Translation key for {@see booking_admin_booking_status_display_label} (provider app / API).
     */
    function booking_admin_status_display_key(Booking $booking): string
    {
        if ($reopenKey = booking_reopen_combined_status_key($booking)) {
            return $reopenKey;
        }

        $hasDisputedSnapshot = ! empty($booking->reopen_disputed_snapshot)
            && is_array($booking->reopen_disputed_snapshot)
            && $booking->reopen_disputed_snapshot !== [];
        if ($hasDisputedSnapshot) {
            $snap = (array) $booking->reopen_disputed_snapshot;
            $retained = 0.0;
            foreach (['retained_from_customer', 'final_net_to_customer'] as $k) {
                if (isset($snap[$k]) && is_numeric($snap[$k])) {
                    $retained = (float) $snap[$k];
                    break;
                }
            }

            return $retained > 0.009 ? 'disputed_and_completed' : 'disputed_and_cancelled';
        }
        if (booking_on_hold_is_after_visit_from_ongoing($booking)) {
            return 'hold_after_visit';
        }
        $st = strtolower((string) ($booking->booking_status ?? ''));
        if (in_array($st, ['canceled', 'cancelled'], true)) {
            return 'booking_cancelled';
        }

        return $st !== '' ? $st : 'pending';
    }
}

if (! function_exists('booking_admin_status_tags_for_api')) {
    /**
     * Admin booking list tags for provider mobile API.
     *
     * @return list<array{key: string, label: string, variant: string}>
     */
    function booking_admin_status_tags_for_api(Booking $booking): array
    {
        $tags = [];
        $bfs = BookingFinancialSettlementService::class;
        $refundTag = booking_admin_classify_refund_ui_tag($booking);
        if ($refundTag === 'pending') {
            $tags[] = ['key' => 'refund_pending', 'label' => 'pending_refund', 'variant' => 'warning'];
        } elseif ($refundTag === 'full') {
            $tags[] = ['key' => 'refund_full', 'label' => 'refunded', 'variant' => 'success'];
        } elseif ($refundTag === 'partial') {
            $tags[] = ['key' => 'refund_partial', 'label' => 'booking_tag_refund_partial', 'variant' => 'info'];
        }
        if (booking_admin_has_disputed_reopen_snapshot($booking)) {
            $tags[] = ['key' => 'disputed', 'label' => 'booking_tag_disputed', 'variant' => 'danger'];
        }
        if (booking_admin_has_compensation($booking)) {
            $tags[] = ['key' => 'compensated', 'label' => 'booking_tag_compensated', 'variant' => 'primary'];
        }
        if (booking_admin_should_show_cancel_after_visit_tag($booking)) {
            $tags[] = ['key' => 'cancel_after_visit', 'label' => 'booking_tag_cancel_after_visit', 'variant' => 'danger'];
        } elseif (booking_admin_should_show_complete_no_service_tag($booking)) {
            $tags[] = ['key' => 'complete_no_service', 'label' => 'booking_tag_complete_no_service', 'variant' => 'success'];
        }
        if (! empty($booking->reopen_resolved_at)) {
            $tags[] = ['key' => 'case_closed', 'label' => 'booking_tag_case_closed', 'variant' => 'success'];
        }
        if (empty($booking->is_repeated)) {
            if ($booking->isOpenReopenTicket()) {
                $tags[] = ['key' => 'reopened', 'label' => 'reopened', 'variant' => 'warning'];
            } elseif ($booking->isReopenedTagged() && ! booking_admin_has_disputed_reopen_snapshot($booking)) {
                $tags[] = ['key' => 'resolved', 'label' => 'resolved', 'variant' => 'success'];
            }
        }
        $listOutcome = trim((string) ($booking->settlement_outcome ?? ''));
        $listStatusNorm = strtolower((string) ($booking->booking_status ?? ''));
        $listIsClosed = in_array($listStatusNorm, ['completed', 'canceled', 'cancelled', 'refunded'], true);
        $cfg = is_array($booking->settlement_config ?? null) ? $booking->settlement_config : [];
        $hasWriteoff = $listOutcome === $bfs::OUTCOME_SCALED_TO_PAYMENTS
            && isset($cfg['scaled_loss_writeoff_amount'])
            && is_numeric($cfg['scaled_loss_writeoff_amount'])
            && (float) $cfg['scaled_loss_writeoff_amount'] > 0.009;
        $skipOutcomeDuplicate = in_array($listOutcome, [
            $bfs::OUTCOME_VISIT_RETAINED_CANCEL,
            $bfs::OUTCOME_VISIT_FEE_SPLIT,
        ], true);
        $showSettlementOutcomeBadge = $listOutcome !== ''
            && ! ($listOutcome === $bfs::OUTCOME_VISIT_FEE_SPLIT && ! $listIsClosed)
            && ! $skipOutcomeDuplicate;
        if ($showSettlementOutcomeBadge) {
            if ($listOutcome === $bfs::OUTCOME_CUSTOM_COMMISSION) {
                $tags[] = ['key' => 'custom_commission', 'label' => 'bfs_list_badge_custom_commission', 'variant' => 'primary'];
            } elseif ($listOutcome === $bfs::OUTCOME_SCALED_TO_PAYMENTS) {
                if ($booking->isScaledSettlementLossRecovered()) {
                    $tags[] = ['key' => 'loss_recovered', 'label' => 'bfs_list_badge_loss_recovered', 'variant' => 'success'];
                } else {
                    $tags[] = ['key' => 'loss_making', 'label' => 'bfs_list_badge_loss_making', 'variant' => 'secondary'];
                }
            } elseif ($listOutcome === $bfs::OUTCOME_STANDARD) {
                $tags[] = ['key' => 'standard_settlement', 'label' => 'bfs_label_standard', 'variant' => 'light'];
            } else {
                $tags[] = ['key' => 'settlement_' . $listOutcome, 'label' => $listOutcome, 'variant' => 'dark'];
            }
        }
        if ($hasWriteoff) {
            $tags[] = ['key' => 'writeoff_settled', 'label' => 'settled', 'variant' => 'danger'];
        }

        return $tags;
    }
}

if (! function_exists('booking_append_provider_api_ui_fields')) {
    /**
     * Attach display status + tags for provider mobile API JSON serialization.
     */
    function booking_append_provider_api_ui_fields(Booking $booking): void
    {
        $booking->setAttribute('booking_status_display_key', booking_admin_status_display_key($booking));
        $booking->setAttribute('booking_status_badge_variant', booking_admin_status_badge_variant($booking));
        $booking->setAttribute('booking_status_tags', booking_admin_status_tags_for_api($booking));

        if ($booking->relationLoaded('customer') && $booking->customer) {
            $booking->customer->setAttribute(
                'received_avg_rating',
                (float) ($booking->customer->received_avg_rating ?? 0)
            );
            $booking->customer->setAttribute(
                'received_rating_count',
                (int) ($booking->customer->received_rating_count ?? 0)
            );
        }
    }
}

if (! function_exists('booking_api_list_filter_tab_order')) {
    /**
     * Booking list tab keys in the same order as admin web.
     *
     * @return list<string>
     */
    function booking_api_list_filter_tab_order(): array
    {
        return [
            'all',
            'pending',
            'accepted',
            'pending_cancellation',
            'canceled',
            'ongoing',
            'completed',
            'reopened',
            'resolved',
            'disputed_cancelled',
            'disputed_completed',
            'on_hold',
            'hold_after_visit',
            'completed_no_or_little',
            'cancelled_after_visit',
            'loss_making_pending',
            'loss_recovered',
            'loss_settled',
        ];
    }
}

if (! function_exists('booking_api_list_filter_status_keys')) {
    /**
     * Allowed booking_status query values for customer/provider mobile list APIs.
     *
     * @return list<string>
     */
    function booking_api_list_filter_status_keys(): array
    {
        return array_values(array_unique(array_merge(
            booking_api_list_filter_tab_order(),
            array_column(BOOKING_STATUSES, 'key'),
            ['loss_making', 'refunded'],
        )));
    }
}

if (! function_exists('booking_api_list_status_tab_counts')) {
    /**
     * Counts for each booking list tab on a base query (already scoped to customer/provider).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $baseQuery
     * @param  array{max_booking_amount?: float|null, provider?: \Modules\ProviderManagement\Entities\Provider|null, provider_id?: string|null}  $options
     * @return array<string, int>
     */
    function booking_api_list_status_tab_counts($baseQuery, array $options = []): array
    {
        $clone = fn () => clone $baseQuery;
        $maxBookingAmount = $options['max_booking_amount'] ?? null;
        $provider = $options['provider'] ?? null;
        $providerId = $options['provider_id'] ?? ($provider?->id);

        $tabOptions = array_filter([
            'max_booking_amount' => $maxBookingAmount,
            'provider' => $provider,
            'provider_id' => $providerId,
        ], fn ($v) => $v !== null);

        $counts = [];
        foreach (booking_api_list_filter_tab_order() as $tab) {
            if ($tab === 'all') {
                $counts[$tab] = (int) $clone()->count();
                continue;
            }
            $q = $clone();
            $q->applyBookingListStatusTab($tab, $tabOptions);
            $counts[$tab] = (int) $q->count();
        }

        return $counts;
    }
}

if (! function_exists('booking_append_customer_api_ui_fields')) {
    /**
     * Attach display status + tags for customer mobile API JSON serialization.
     */
    function booking_append_customer_api_ui_fields(Booking $booking): void
    {
        booking_append_provider_api_ui_fields($booking);
    }
}

if (! function_exists('booking_provider_api_payment_snapshot')) {
    /**
     * Payment card figures aligned with admin booking details (Total / paid / due / status).
     *
     * @return array{
     *     total: float,
     *     amount_paid_display: float,
     *     due_balance: float,
     *     status_label: string,
     *     amount_row_label: string,
     *     show_as_amount_paid_label: bool
     * }
     */
    function booking_provider_api_payment_snapshot(Booking $booking): array
    {
        $booking->loadMissing(['booking_partial_payments', 'extra_services']);

        if (booking_admin_has_disputed_reopen_snapshot($booking)) {
            $snap = (array) $booking->reopen_disputed_snapshot;
            $customerPaid = round((float) ($snap['customer_paid_total'] ?? 0), 2);
            $refundCompany = round((float) ($snap['refund_company_amount'] ?? 0), 2);
            $refundProvider = round((float) ($snap['refund_provider_amount'] ?? 0), 2);
            $refundTotal = round((float) ($snap['refund_total'] ?? ($refundCompany + $refundProvider)), 2);
            $retained = 0.0;
            foreach (['retained_from_customer', 'final_net_to_customer'] as $k) {
                if (isset($snap[$k]) && is_numeric($snap[$k])) {
                    $retained = round((float) $snap[$k], 2);
                    break;
                }
            }

            return [
                'total' => $retained,
                'amount_paid_display' => $customerPaid,
                'due_balance' => 0.0,
                'status_label' => translate('Reopen_disputed_settlement_snapshot'),
                'amount_row_label' => translate('Amount_Paid'),
                'show_as_amount_paid_label' => true,
                'is_disputed_settlement' => true,
                'customer_paid_total' => $customerPaid,
                'refunded_amount' => $refundTotal,
                'refund_total' => $refundTotal,
                'final_booking_amount' => $retained,
                'retained_amount' => $retained,
                'pending_refund' => 0.0,
            ];
        }

        $totalPaidFromPartials = round((float) $booking->booking_partial_payments->sum('paid_amount'), 2);
        $bookingTotalForPayment = round((float) get_booking_payable_total_for_partial_dues($booking), 2);

        $paymentFullyCovered = $booking->booking_partial_payments->isNotEmpty()
            ? (round($totalPaidFromPartials, 2) >= round($bookingTotalForPayment, 2) - 0.009)
            : ((int) ($booking->is_paid ?? 0) === 1 && round($bookingTotalForPayment, 2) > 0.009);

        $displayPaidAmount = $booking->booking_partial_payments->isNotEmpty()
            ? $totalPaidFromPartials
            : ($paymentFullyCovered ? $bookingTotalForPayment : 0.0);

        $visitRetainedCanceled = (string) ($booking->booking_status ?? '') === 'canceled'
            && (
                ! empty($booking->after_visit_cancel)
                || (string) ($booking->settlement_outcome ?? '') === BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL
            );
        $decidedChargesPaidDisplayCap = $visitRetainedCanceled
            || (
                (string) ($booking->booking_status ?? '') === 'completed'
                && (string) ($booking->settlement_outcome ?? '') === BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT
            );
        if ($decidedChargesPaidDisplayCap && round($bookingTotalForPayment, 2) > 0
            && round($totalPaidFromPartials, 2) >= round($bookingTotalForPayment, 2)) {
            $displayPaidAmount = round($bookingTotalForPayment, 2);
        }

        $scaledPaymentCard = (int) ($booking->is_repeated ?? 0) === 0
            && trim((string) ($booking->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS
            && ! in_array((string) ($booking->booking_status ?? ''), ['canceled', 'cancelled', 'refunded'], true);

        $paymentDetailsTotalAmount = $scaledPaymentCard
            ? round((float) get_booking_total_amount($booking), 2)
            : $bookingTotalForPayment;
        $paymentDetailsAmountPaid = $scaledPaymentCard
            ? round((float) get_booking_total_paid($booking), 2)
            : round($displayPaidAmount, 2);

        $dueBalanceDisplay = $scaledPaymentCard
            ? round((float) get_booking_customer_display_due_balance($booking), 2)
            : round((float) get_booking_customer_display_due_balance($booking), 2);

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
        } elseif (in_array((string) ($booking->booking_status ?? ''), ['canceled', 'cancelled', 'refunded'], true)) {
            $refundTotals = get_booking_refund_display_totals($booking);
            $pendingRefund = round((float) ($refundTotals['refundable_remaining'] ?? 0), 2);
            if ($pendingRefund > 0.009 && in_array((string) ($booking->booking_status ?? ''), ['canceled', 'cancelled'], true)) {
                $statusLabel = translate('Pending_refund');
            } else {
                $statusLabel = translate('Refunded');
            }
        } elseif ($paymentFullyCovered) {
            $statusLabel = translate('Paid');
        } elseif ($booking->booking_partial_payments->isNotEmpty()) {
            $statusLabel = translate('Partially paid');
        } else {
            $statusLabel = translate('Unpaid');
        }

        if ($scaledPaymentCard) {
            $cfg = is_array($booking->settlement_config) ? $booking->settlement_config : [];
            $writeoffAmount = isset($cfg['scaled_loss_writeoff_amount']) && is_numeric($cfg['scaled_loss_writeoff_amount'])
                ? round(max(0.0, (float) $cfg['scaled_loss_writeoff_amount']), 2)
                : 0.0;
            if ($writeoffAmount > 0.009 && $dueBalanceDisplay <= 0.009) {
                $statusLabel = translate('Settled');
            } elseif ($paymentDetailsAmountPaid + 0.005 < $paymentDetailsTotalAmount && $dueBalanceDisplay > 0.009) {
                $statusLabel = translate('Partially paid');
            } elseif ($dueBalanceDisplay <= 0.009) {
                $statusLabel = translate('Paid');
            }
        }

        $showAsAmountPaidLabel = (string) ($booking->booking_status ?? '') === 'completed' || $paymentFullyCovered;
        $amountRowLabel = $showAsAmountPaidLabel ? translate('Amount_Paid') : translate('Advance_Paid');

        $payload = [
            'total' => round($paymentDetailsTotalAmount, 2),
            'amount_paid_display' => round($paymentDetailsAmountPaid, 2),
            'due_balance' => round($dueBalanceDisplay, 2),
            'status_label' => $statusLabel,
            'amount_row_label' => $amountRowLabel,
            'show_as_amount_paid_label' => $showAsAmountPaidLabel,
        ];

        $refundFields = booking_api_payment_refund_snapshot_fields($booking, $totalPaidFromPartials, $bookingTotalForPayment, $decidedChargesPaidDisplayCap);
        $payload = array_merge($payload, $refundFields);

        if (($refundFields['pending_refund'] ?? 0) > 0.009
            && in_array((string) ($booking->booking_status ?? ''), ['canceled', 'cancelled'], true)) {
            $payload['status_label'] = translate('Pending_refund');
        }

        return $payload;
    }
}

if (! function_exists('booking_api_visit_charge_overpayment_pending_refund')) {
    /**
     * Visit-charge settlements where the customer paid more than the decided retained cap.
     */
    function booking_api_visit_charge_overpayment_pending_refund(
        Booking $booking,
        float $totalPaidFromPartials,
        float $bookingTotalForPayment,
        bool $decidedChargesPaidDisplayCap
    ): float {
        if (! $decidedChargesPaidDisplayCap || round($bookingTotalForPayment, 2) <= 0) {
            return 0.0;
        }

        return round(max(0.0, $totalPaidFromPartials - $bookingTotalForPayment), 2);
    }
}

if (! function_exists('booking_api_payment_refund_snapshot_fields')) {
    /**
     * Refund breakdown for mobile payment cards (non-disputed canceled/refunded bookings).
     *
     * @return array<string, float|bool|null>
     */
    function booking_api_payment_refund_snapshot_fields(
        Booking $booking,
        float $totalPaidFromPartials = 0.0,
        float $bookingTotalForPayment = 0.0,
        bool $decidedChargesPaidDisplayCap = false
    ): array {
        if (booking_admin_has_disputed_reopen_snapshot($booking)) {
            return [];
        }

        $refundTotals = get_booking_refund_display_totals($booking);
        $refundedAmount = round((float) ($refundTotals['refunded_total'] ?? 0), 2);
        $refundableAmount = round((float) ($refundTotals['max_eligible'] ?? 0), 2);
        $refundableRemaining = round((float) ($refundTotals['refundable_remaining'] ?? 0), 2);
        $customerPaid = round((float) get_booking_total_paid($booking), 2);

        $visitOverpayPending = booking_api_visit_charge_overpayment_pending_refund(
            $booking,
            $totalPaidFromPartials,
            $bookingTotalForPayment,
            $decidedChargesPaidDisplayCap
        );

        if (! ($refundTotals['show'] ?? false) && $visitOverpayPending <= 0.009) {
            return [];
        }

        $pendingRefund = max($refundableRemaining, $visitOverpayPending);

        $fields = [
            'customer_paid_total' => $customerPaid,
            'refunded_amount' => $refundedAmount,
            'refundable_amount' => $refundableAmount,
            'refundable_remaining' => $refundableRemaining,
        ];

        if ($pendingRefund > 0.009) {
            $fields['pending_refund'] = round($pendingRefund, 2);
        }

        return $fields;
    }
}

if (! function_exists('booking_api_disputed_settlement_payload')) {
    /**
     * Disputed reopen close settlement for customer/provider mobile API booking details.
     *
     * @param  'customer'|'provider'  $audience
     * @return array<string, mixed>|null
     */
    function booking_api_disputed_settlement_payload(Booking $main, string $audience): ?array
    {
        if ((int) ($main->is_repeated ?? 0) !== 0) {
            return null;
        }

        if (! booking_admin_has_disputed_reopen_snapshot($main)) {
            return null;
        }

        $snap = (array) $main->reopen_disputed_snapshot;
        $customerPaid = round((float) ($snap['customer_paid_total'] ?? 0), 2);
        $refundCompany = round((float) ($snap['refund_company_amount'] ?? 0), 2);
        $refundProvider = round((float) ($snap['refund_provider_amount'] ?? 0), 2);
        $refundTotal = round((float) ($snap['refund_total'] ?? ($refundCompany + $refundProvider)), 2);
        $retained = 0.0;
        foreach (['retained_from_customer', 'final_net_to_customer'] as $k) {
            if (isset($snap[$k]) && is_numeric($snap[$k])) {
                $retained = round((float) $snap[$k], 2);
                break;
            }
        }

        $payload = [
            'has_disputed_settlement' => true,
            'customer_paid_total' => $customerPaid,
            'refund_total' => $refundTotal,
            'refund_company_amount' => $refundCompany,
            'refund_provider_amount' => $refundProvider,
            'final_booking_amount' => $retained,
            'retained_from_customer' => $retained,
            'is_full_refund' => $refundTotal > 0.009 && $retained <= 0.009,
            'is_partial_refund' => $refundTotal > 0.009 && $retained > 0.009,
            'pending_refund' => 0.0,
        ];

        if ($audience === 'provider') {
            $poolOwesCo = round((float) ($snap['provider_owes_company'] ?? 0), 2);
            $payload['final_admin_commission'] = round((float) ($snap['final_admin_commission'] ?? 0), 2);
            $payload['final_provider_earning'] = round((float) ($snap['final_provider_earning'] ?? 0), 2);
            $payload['provider_owes_company'] = $poolOwesCo;
            $payload['company_owes_provider'] = round((float) ($snap['company_owes_provider'] ?? 0), 2);
            $payload['provider_total_remittance_to_company'] = round(
                (float) ($snap['provider_total_remittance_to_company'] ?? ($poolOwesCo + (float) ($snap['final_admin_commission'] ?? 0))),
                2
            );
            $payload['company_pays_provider_total'] = round(
                (float) ($snap['company_pays_provider_total'] ?? ($snap['company_owes_provider'] ?? 0)),
                2
            );
            $payload['company_cash_after_refund'] = round((float) ($snap['company_cash_after_refund'] ?? 0), 2);
            $payload['provider_cash_after_refund'] = round((float) ($snap['provider_cash_after_refund'] ?? 0), 2);
        }

        return $payload;
    }
}

if (! function_exists('booking_provider_api_service_location_payload')) {
    /**
     * Service location card payload for provider mobile booking details.
     *
     * @param  Booking|BookingRepeat  $booking
     */
    function booking_provider_api_service_location_payload($booking): array
    {
        if ($booking instanceof BookingRepeat) {
            $main = $booking->relationLoaded('booking') ? $booking->booking : $booking->booking()->with(['zone.parentZone', 'provider'])->first();
            $loc = (string) ($booking->service_location ?? $main?->service_location ?? 'customer');
            $zone = $main?->zone;
            $provider = $main?->provider;
            $serviceAddress = $booking->service_address ?? $main?->service_address;
        } else {
            $loc = (string) ($booking->service_location ?? 'customer');
            $zone = $booking->zone;
            $provider = $booking->provider;
            $serviceAddress = $booking->service_address;
        }

        $zoneName = '';
        if ($zone) {
            $zoneName = (string) ($zone->name ?? '');
            $parent = $zone->parentZone ?? null;
            if ($parent && trim((string) ($parent->name ?? '')) !== '') {
                $zoneName .= ' (' . $parent->name . ')';
            }
        }

        $address = '';
        $addressPending = false;
        if ($loc === 'provider') {
            $providerId = $booking instanceof BookingRepeat ? ($booking->provider_id ?? $main?->provider_id) : $booking->provider_id;
            if ($providerId && $provider) {
                $address = (string) ($provider->company_address ?? '');
            } elseif (! $providerId) {
                $addressPending = true;
            }
        } else {
            if (is_object($serviceAddress)) {
                $address = (string) ($serviceAddress->address ?? '');
            } elseif (is_array($serviceAddress)) {
                $address = (string) ($serviceAddress['address'] ?? '');
            }
        }

        return [
            'zone_name' => $zoneName,
            'service_location' => $loc,
            'address' => $address,
            'address_pending' => $addressPending,
            'travel_note' => $loc === 'provider' ? 'customer_at_provider_site' : 'provider_at_customer_site',
        ];
    }
}

if (! function_exists('booking_api_loss_making_settlement_payload')) {
    /**
     * Loss-making (scaled) settlement snapshot for customer/provider mobile API booking details.
     *
     * @param  'customer'|'provider'  $audience
     * @return array<string, mixed>|null
     */
    function booking_api_loss_making_settlement_payload(Booking $main, string $audience): ?array
    {
        if ((int) ($main->is_repeated ?? 0) !== 0) {
            return null;
        }

        if (trim((string) ($main->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return null;
        }

        $preview = booking_financial_build_preview_cached($main);
        $snapshot = booking_provider_api_payment_snapshot_cached($main);
        $totalBooking = round((float) ($snapshot['total'] ?? get_booking_total_amount($main)), 2);
        $amountPaid = round((float) ($snapshot['amount_paid_display'] ?? get_booking_total_paid($main)), 2);
        $pendingBalance = round((float) ($snapshot['due_balance'] ?? get_booking_customer_display_due_balance($main)), 2);
        $writeoff = round((float) ($preview['scaled_loss_writeoff_amount'] ?? 0), 2);
        $isStillLossMaking = $main->isLossMakingFinancialSettlement();

        if ($writeoff <= 0.009 && ! $isStillLossMaking) {
            return null;
        }

        if ($audience === 'customer') {
            return [
                'is_loss_making' => $isStillLossMaking,
                'total_booking_amount' => $totalBooking,
                'amount_paid' => $amountPaid,
                'pending_balance' => $pendingBalance,
                'write_off_amount' => $writeoff > 0.009 ? $writeoff : null,
                'settlement_amount' => $writeoff > 0.009 ? $writeoff : null,
                'is_writeoff_settled' => $writeoff > 0.009,
            ];
        }

        return [
            'is_loss_making' => true,
            'total_booking_amount' => $totalBooking,
            'amount_paid_by_customer' => round((float) ($preview['scaled_customer_paid_amount'] ?? $amountPaid), 2),
            'loss_amount' => round((float) ($preview['scaled_loss_amount'] ?? 0), 2),
            'loss_to_company' => round((float) ($preview['scaled_loss_company_share'] ?? 0), 2),
            'loss_to_provider' => round((float) ($preview['scaled_loss_provider_share'] ?? 0), 2),
            'company_commission_full_booking' => round((float) ($preview['company_commission'] ?? 0), 2),
            'provider_share_before_loss_full_booking' => round((float) ($preview['scaled_gross_provider_share'] ?? 0), 2),
            'net_company_share_after_loss' => round((float) ($preview['scaled_net_company_share'] ?? 0), 2),
            'net_provider_share_after_loss' => round((float) ($preview['scaled_net_provider_share'] ?? 0), 2),
            'notes' => ($notes = trim((string) ($main->settlement_remarks ?? ''))) !== '' ? $notes : null,
        ];
    }
}

if (! function_exists('booking_is_financial_settlement_finalized')) {
    function booking_is_financial_settlement_finalized(Booking $booking): bool
    {
        return in_array((string) ($booking->booking_status ?? ''), ['completed', 'canceled', 'cancelled', 'refunded'], true);
    }
}

if (! function_exists('booking_api_special_financial_settlement_scenario_label_key')) {
    function booking_api_special_financial_settlement_scenario_label_key(?string $outcome, Booking $main): string
    {
        $o = trim((string) ($outcome ?? ''));

        return match ($o) {
            BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL => 'Bfs_label_cancel_keep_visit',
            BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT => 'Bfs_label_complete_visit_only',
            BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS => $main->isScaledSettlementLossRecovered()
                ? 'Bfs_label_loss_recovered_booking'
                : 'bfs_list_badge_loss_making',
            BookingFinancialSettlementService::OUTCOME_CUSTOM_COMMISSION => 'bfs_list_badge_custom_commission',
            default => 'special_financial_settlement',
        };
    }
}

if (! function_exists('booking_api_special_financial_settlement_payload')) {
    /**
     * Finalized special-scenario settlement summary for customer/provider mobile API booking details.
     *
     * @param  'customer'|'provider'  $audience
     * @return array<string, mixed>|null
     */
    function booking_api_special_financial_settlement_payload(Booking $main, string $audience): ?array
    {
        if ((int) ($main->is_repeated ?? 0) !== 0) {
            return null;
        }

        if (! booking_has_special_financial_settlement($main)) {
            return null;
        }

        if (! booking_is_financial_settlement_finalized($main)) {
            return null;
        }

        $outcome = trim((string) ($main->settlement_outcome ?? ''));
        $finalBookingAmount = round((float) get_customer_booking_list_display_total($main), 2);
        $scenarioLabelKey = booking_api_special_financial_settlement_scenario_label_key($outcome, $main);
        $notes = ($n = trim((string) ($main->settlement_remarks ?? ''))) !== '' ? $n : null;

        if ($audience === 'customer') {
            return [
                'has_special_settlement' => true,
                'settlement_outcome' => $outcome,
                'scenario_label_key' => $scenarioLabelKey,
                'final_booking_amount' => $finalBookingAmount,
                'notes' => $notes,
            ];
        }

        $reportingSlice = get_admin_dashboard_reporting_total_and_spare_for_booking($main);
        $reportedTotal = round((float) ($reportingSlice['reported_total'] ?? 0), 2);
        $spareParts = round((float) ($reportingSlice['spare_parts'] ?? 0), 2);
        $serviceCharges = round(max(0.0, $reportedTotal - $spareParts), 2);

        if ($outcome === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            $scaled = provider_payment_tab_loss_making_earning_display_for_scaled($main, 1.0);
            $finalAdmin = is_array($scaled)
                ? round((float) ($scaled['admin_commission'] ?? 0), 2)
                : 0.0;
            $finalProvider = is_array($scaled)
                ? round((float) ($scaled['provider_earning'] ?? 0), 2)
                : 0.0;
        } else {
            $pair = provider_payment_tab_earning_commission_pair($main);
            $finalAdmin = round((float) ($pair['admin_commission'] ?? 0), 2);
            $finalProvider = round((float) ($pair['provider_earning'] ?? 0), 2);
        }

        return [
            'has_special_settlement' => true,
            'settlement_outcome' => $outcome,
            'scenario_label_key' => $scenarioLabelKey,
            'final_booking_amount' => $finalBookingAmount,
            'final_service_charges' => $serviceCharges,
            'final_spare_parts_charges' => $spareParts,
            'final_admin_commission' => $finalAdmin,
            'final_provider_earning' => $finalProvider,
            'notes' => $notes,
        ];
    }
}

if (! function_exists('booking_append_provider_api_financial_fields')) {
    /**
     * Revenue & settlement, payment details summary, service location, and enriched partial payments for provider API.
     *
     * @param  Booking|BookingRepeat  $booking
     */
    function booking_append_provider_api_financial_fields($booking): void
    {
        $main = $booking instanceof BookingRepeat
            ? ($booking->relationLoaded('booking') ? $booking->booking : $booking->booking()->first())
            : $booking;

        if (! $main instanceof Booking) {
            return;
        }

        $main->loadMissing(['zone.parentZone', 'provider', 'booking_partial_payments.ledgerTransactions', 'booking_offline_payments', 'extra_services']);

        $snapshot = booking_provider_api_payment_snapshot($main);
        $revenue = get_booking_received_and_settlement($booking);
        $showBreakdown = booking_should_show_admin_revenue_settlement_breakdown($booking);
        $bookingTotalForPayment = round((float) get_booking_payable_total_for_partial_dues($main), 2);

        $settlementMessageKey = 'unpaid_or_partially_paid';
        if (! $showBreakdown) {
            $settlementMessageKey = 'no_revenue_cancelled_before_service';
        } elseif (! empty($revenue['net_revenue_zeroed_after_refund'])) {
            $settlementMessageKey = 'net_settlement_zero_after_full_refund';
        } elseif ((float) ($revenue['pay_to_provider'] ?? 0) > 0.009) {
            $settlementMessageKey = 'pay_to_provider';
        } elseif ((float) ($revenue['provider_owes_company'] ?? 0) > 0.009) {
            $settlementMessageKey = 'provider_owes_company';
        } elseif ((float) ($revenue['total_paid'] ?? 0) >= $bookingTotalForPayment - 0.009 && $bookingTotalForPayment > 0) {
            $settlementMessageKey = 'settled';
        }

        $offlineVerifyStatus = null;
        $offline = $main->booking_offline_payments?->first();
        if ($main->payment_method === 'offline_payment' && $offline) {
            $offlineVerifyStatus = (string) ($offline->payment_status ?? '');
        }

        $bfsScaledLive = null;
        if ((int) ($main->is_repeated ?? 0) === 0
            && trim((string) ($main->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            $bfsScaledLive = app(BookingFinancialSettlementService::class)->buildPreview($main);
        }

        $revenuePayload = array_merge($revenue, [
            'show_breakdown' => $showBreakdown,
            'settlement_message_key' => $settlementMessageKey,
        ]);
        if (is_array($bfsScaledLive)) {
            $revenuePayload['scaled_loss_writeoff_amount'] = round((float) ($bfsScaledLive['scaled_loss_writeoff_amount'] ?? 0), 2);
            $cfg = is_array($main->settlement_config) ? $main->settlement_config : [];
            $revenuePayload['scaled_loss_writeoff_company_amount'] = round((float) ($cfg['scaled_loss_writeoff_company_amount'] ?? 0), 2);
            $revenuePayload['scaled_loss_writeoff_provider_amount'] = round((float) ($cfg['scaled_loss_writeoff_provider_amount'] ?? 0), 2);
        }

        $paymentPayload = array_merge($snapshot, [
            'payment_method_display' => format_booking_payment_method_for_admin_display($main),
            'offline_verify_status' => $offlineVerifyStatus,
            'can_complete' => booking_can_be_completed($main),
            'can_record_payment' => booking_provider_can_record_customer_payment($main),
        ]);
        if (is_array($bfsScaledLive) && ! in_array((string) ($main->booking_status ?? ''), ['canceled', 'cancelled', 'refunded'], true)) {
            $writeoff = round((float) ($bfsScaledLive['scaled_loss_writeoff_amount'] ?? 0), 2);
            $paymentPayload['scaled_loss_writeoff_amount'] = $writeoff;
            $paymentPayload['is_writeoff_settled'] = $writeoff > 0.009;
            $paymentPayload['scaled_bad_debt_balance_not_due'] = round((float) ($bfsScaledLive['scaled_bad_debt_balance_not_due'] ?? 0), 2);
            $paymentPayload['scaled_loss_company_share'] = round((float) ($bfsScaledLive['scaled_loss_company_share'] ?? 0), 2);
            $paymentPayload['scaled_loss_provider_share'] = round((float) ($bfsScaledLive['scaled_loss_provider_share'] ?? 0), 2);
        }

        $runningPaid = 0.0;
        $payableCap = round((float) get_booking_payable_total_for_partial_dues($main), 2);
        foreach ($main->booking_partial_payments as $partial) {
            $runningPaid += (float) $partial->paid_amount;
            $partial->setAttribute('payment_method_label', $partial->paymentMethodLabelForAdmin($main));
            $partial->setAttribute('received_by_label', match ((string) ($partial->received_by ?? '')) {
                'company' => translate('Company'),
                'provider' => translate('Provider'),
                default => $partial->received_by ? ucfirst((string) $partial->received_by) : '—',
            });
            $partial->setAttribute('due_after_payment', round(max(0.0, $payableCap - $runningPaid), 2));
        }

        $extraServiceLines = ($main->extra_services ?? collect())
            ->filter(fn ($extra) => round((float) ($extra->total ?? 0), 2) > 0)
            ->map(fn ($extra) => [
                'id' => (string) ($extra->id ?? ''),
                'name' => (string) ($extra->title ?? translate('Extra_Services')),
                'amount' => round((float) ($extra->total ?? 0), 2),
                'type' => (string) ($extra->type ?? 'service'),
                'details' => $extra->details !== null ? (string) $extra->details : null,
                'price' => round((float) ($extra->price ?? 0), 2),
                'quantity' => (int) ($extra->quantity ?? 1),
                'discount' => round((float) ($extra->discount ?? 0), 2),
                'total' => round((float) ($extra->total ?? 0), 2),
            ])
            ->values()
            ->all();

        $installmentPayload = booking_installment_payments_payload($main, is_array($bfsScaledLive) ? $bfsScaledLive : null);
        $installments = collect($installmentPayload['rows'] ?? [])
            ->map(fn (array $row) => [
                'serial' => (int) ($row['serial'] ?? 0),
                'date' => $row['date'] ?? null,
                'received_by_label' => (string) ($row['received_by_label'] ?? '—'),
                'amount' => round((float) ($row['amount'] ?? 0), 2),
                'payment_method_label' => (string) ($row['payment_method_label'] ?? ''),
                'transaction_id' => $row['transaction_id'] ?? null,
                'due_after_payment' => round((float) ($row['due_after_payment'] ?? 0), 2),
            ])
            ->sortByDesc(fn (array $row) => (string) ($row['date'] ?? ''))
            ->values()
            ->all();

        $refunds = LedgerTransaction::query()
            ->where('booking_id', $main->id)
            ->where('reason', LedgerTransaction::REASON_REFUND)
            ->where('type', LedgerTransaction::TYPE_OUT)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($entry, $index) => booking_refund_ledger_row_payload($entry, $index + 1))
            ->all();

        $listDisplayTotal = get_customer_booking_list_display_total($main);
        $originalGrandTotal = round((float) get_booking_total_amount($main), 2);
        $target = $booking instanceof BookingRepeat ? $booking : $main;
        $target->setAttribute('list_display_total', $listDisplayTotal);
        $target->setAttribute('payable_grand_total', $originalGrandTotal);
        $target->setAttribute('payment_details', $paymentPayload);
        $target->setAttribute('payment_ledger', [
            'installments' => $installments,
            'refunds' => $refunds,
        ]);
        $target->setAttribute('revenue_settlement', $revenuePayload);
        $target->setAttribute('service_location_details', booking_provider_api_service_location_payload($booking));
        $target->setAttribute('extra_service_lines', $extraServiceLines);
        $target->setAttribute('booking_summary', booking_customer_api_summary_payload($main));
        $disputedSettlement = booking_api_disputed_settlement_payload($main, 'provider');
        if ($disputedSettlement !== null) {
            $target->setAttribute('disputed_settlement', $disputedSettlement);
        } else {
            $lossMakingSettlement = booking_api_loss_making_settlement_payload($main, 'provider');
            if ($lossMakingSettlement !== null) {
                $target->setAttribute('loss_making_settlement', $lossMakingSettlement);
            }
            $specialFinancialSettlement = booking_api_special_financial_settlement_payload($main, 'provider');
            if ($specialFinancialSettlement !== null) {
                $target->setAttribute('special_financial_settlement', $specialFinancialSettlement);
            }
        }
    }
}

if (! function_exists('booking_installment_payable_cap')) {
    function booking_installment_payable_cap(Booking $main, ?array $bfsScaledLive = null): float
    {
        if ($bfsScaledLive !== null) {
            return round((float) get_booking_total_amount($main), 2);
        }
        if ((int) ($main->is_repeated ?? 0) === 0
            && trim((string) ($main->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return round((float) get_booking_total_amount($main), 2);
        }

        return round((float) get_booking_payable_total_for_partial_dues($main), 2);
    }
}

if (! function_exists('booking_installment_received_by_label')) {
    function booking_installment_received_by_label(?string $receivedBy): string
    {
        return match ((string) ($receivedBy ?? '')) {
            'company' => translate('Company'),
            'provider' => translate('Provider'),
            default => $receivedBy ? ucfirst((string) $receivedBy) : '—',
        };
    }
}

if (! function_exists('booking_partial_payment_resolve_transaction_id')) {
    /**
     * Resolve display transaction id for a partial row (gateway id for digital, wallet trx uuid for wallet).
     */
    function booking_partial_payment_resolve_transaction_id(BookingPartialPayment $partial, Booking $main): ?string
    {
        $stored = trim((string) ($partial->transaction_id ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $ledger = null;
        if ($partial->relationLoaded('ledgerTransactions')) {
            $ledger = $partial->ledgerTransactions
                ->where('type', LedgerTransaction::TYPE_IN)
                ->sortByDesc('created_at')
                ->first();
        } else {
            $ledger = $partial->ledgerTransactions()
                ->where('type', LedgerTransaction::TYPE_IN)
                ->orderByDesc('created_at')
                ->first();
        }
        if ($ledger instanceof LedgerTransaction) {
            $ledgerId = trim((string) ($ledger->transaction_id ?? ''));
            if ($ledgerId !== '') {
                return $ledgerId;
            }
        }

        $paidWith = (string) ($partial->paid_with ?? '');
        $amount = round((float) ($partial->paid_amount ?? 0), 2);

        if ($paidWith === 'wallet') {
            $walletTrx = \Modules\TransactionModule\Entities\Transaction::query()
                ->where('booking_id', $main->id)
                ->where('trx_type', WALLET_TRX_TYPE['wallet_payment'])
                ->whereBetween('debit', [$amount - 0.01, $amount + 0.01])
                ->when(
                    $partial->created_at,
                    fn ($query) => $query->whereBetween('created_at', [
                        $partial->created_at->copy()->subMinutes(5),
                        $partial->created_at->copy()->addMinutes(5),
                    ])
                )
                ->orderBy('created_at')
                ->first();

            return $walletTrx?->id ? (string) $walletTrx->id : null;
        }

        if (! in_array($paidWith, ['wallet', 'cash_after_service'], true)) {
            $gatewayId = trim((string) ($main->transaction_id ?? ''));
            if ($gatewayId !== '') {
                return $gatewayId;
            }
        }

        return null;
    }
}

if (! function_exists('booking_installment_row_from_partial')) {
    /**
     * @return array<string, mixed>
     */
    function booking_installment_row_from_partial(BookingPartialPayment $partial, Booking $main, float $dueAfterPayment): array
    {
        return [
            'date' => $partial->created_at?->toIso8601String(),
            'received_by' => $partial->received_by,
            'received_by_label' => booking_installment_received_by_label($partial->received_by),
            'amount' => round((float) ($partial->paid_amount ?? 0), 2),
            'payment_method_label' => (string) $partial->paymentMethodLabelForAdmin($main),
            'transaction_id' => booking_partial_payment_resolve_transaction_id($partial, $main),
            'due_after_payment' => round(max(0.0, $dueAfterPayment), 2),
            'paid_with' => $partial->paid_with,
            'id' => $partial->id ? (string) $partial->id : null,
            'source' => 'partial',
        ];
    }
}

if (! function_exists('booking_installment_row_from_ledger')) {
    /**
     * @return array<string, mixed>
     */
    function booking_installment_row_from_ledger(LedgerTransaction $entry, float $dueAfterPayment): array
    {
        return [
            'date' => $entry->created_at?->toIso8601String(),
            'received_by' => $entry->received_by,
            'received_by_label' => booking_installment_received_by_label($entry->received_by),
            'amount' => round((float) ($entry->amount ?? 0), 2),
            'payment_method_label' => $entry->formatPaymentMethodForDisplay(),
            'transaction_id' => $entry->transaction_id ?: null,
            'due_after_payment' => round(max(0.0, $dueAfterPayment), 2),
            'paid_with' => null,
            'id' => null,
            'source' => 'ledger',
        ];
    }
}

if (! function_exists('booking_company_payment_ledger_in_total')) {
    function booking_company_payment_ledger_in_total(Booking $booking): float
    {
        return round((float) LedgerTransaction::query()
            ->where('booking_id', $booking->id)
            ->where('type', LedgerTransaction::TYPE_IN)
            ->where(function ($query) {
                $query->whereNull('reason')
                    ->orWhereNotIn('reason', [
                        LedgerTransaction::REASON_REFUND,
                        LedgerTransaction::REASON_PROVIDER_PAYOUT,
                    ]);
            })
            ->sum('amount'), 2);
    }
}

if (! function_exists('booking_digital_payment_ledger_amount')) {
    /**
     * Amount to record on ledger / admin IN for a digital (or offline-approved) company payment.
     * Uses grand total (service + visiting/extra) when no prior partials; otherwise only the remainder.
     */
    function booking_digital_payment_ledger_amount(Booking $booking): float
    {
        $booking->loadMissing('booking_partial_payments');
        $grand = round((float) get_booking_total_amount($booking), 2);
        if ($grand <= 0) {
            return 0.0;
        }

        if ($booking->booking_partial_payments->isEmpty()) {
            return $grand;
        }

        $partialSum = round((float) $booking->booking_partial_payments->sum('paid_amount'), 2);

        if ($partialSum >= $grand - 0.009) {
            return round(max(0.0, $grand - booking_company_payment_ledger_in_total($booking)), 2);
        }

        return round(max(0.0, $grand - $partialSum), 2);
    }
}

if (! function_exists('ensure_booking_company_payment_partial_for_ledger')) {
    function ensure_booking_company_payment_partial_for_ledger(
        Booking $booking,
        float $ledgerAmount,
        ?string $transactionId = null
    ): ?BookingPartialPayment {
        $ledgerAmount = round(max(0.0, $ledgerAmount), 2);
        if ($ledgerAmount <= 0) {
            return null;
        }

        $booking->loadMissing('booking_partial_payments');
        $paidWith = map_booking_payment_paid_with((string) ($booking->payment_method ?? ''));

        if ($booking->booking_partial_payments->isEmpty()) {
            return BookingPartialPayment::create([
                'booking_id' => $booking->id,
                'paid_with' => $paidWith,
                'paid_amount' => $ledgerAmount,
                'due_amount' => 0,
                'received_by' => 'company',
                'transaction_id' => $transactionId,
            ]);
        }

        $partialSum = round((float) $booking->booking_partial_payments->sum('paid_amount'), 2);
        $grand = round((float) get_booking_total_amount($booking), 2);

        if ($partialSum >= $grand - 0.009) {
            return $booking->booking_partial_payments
                ->filter(fn ($partial) => ($partial->received_by ?? 'company') === 'company')
                ->sortByDesc('created_at')
                ->first()
                ?? $booking->booking_partial_payments->sortByDesc('created_at')->first();
        }

        return BookingPartialPayment::create([
            'booking_id' => $booking->id,
            'paid_with' => $paidWith,
            'paid_amount' => $ledgerAmount,
            'due_amount' => 0,
            'received_by' => 'company',
            'transaction_id' => $transactionId,
        ]);
    }
}

if (! function_exists('booking_scaled_loss_recovery_allocation_for_payment')) {
    /**
     * Tag how much of a recovery payment reduces provider vs company loss (scaled bookings).
     * Customer app and provider-recorded payments split recovery **equally** (50/50) between sides,
     * capped by each side's remaining loss. Cash holder follows `received_by` on the partial only.
     *
     * @return array{provider: float, company: float}|null
     */
    function booking_scaled_loss_recovery_allocation_for_payment(Booking $booking, float $amount, string $receivedBy): ?array
    {
        if ((int) ($booking->is_repeated ?? 0) !== 0) {
            return null;
        }
        if (trim((string) ($booking->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            return null;
        }
        $caps = booking_admin_loss_recovery_split_caps($booking);
        if ($caps === null) {
            return null;
        }

        $amount = round(max(0.0, $amount), 2);
        $sumCaps = round($caps['provider'] + $caps['company'], 2);
        if ($sumCaps <= 0.009 || $amount <= 0.009) {
            return null;
        }

        $amount = round(min($amount, $sumCaps), 2);
        $splitCo = round($amount / 2, 2);
        $splitPr = round($amount - $splitCo, 2);

        $splitCo = round(min($splitCo, $caps['company']), 2);
        $splitPr = round(min($splitPr, $caps['provider']), 2);

        $shortfall = round($amount - $splitCo - $splitPr, 2);
        if ($shortfall > 0.009) {
            $roomCo = round(max(0.0, $caps['company'] - $splitCo), 2);
            $roomPr = round(max(0.0, $caps['provider'] - $splitPr), 2);
            if ($roomCo + 0.009 >= $shortfall) {
                $splitCo = round($splitCo + $shortfall, 2);
            } elseif ($roomPr + 0.009 >= $shortfall) {
                $splitPr = round($splitPr + $shortfall, 2);
            } else {
                $splitCo = round(min($caps['company'], $splitCo + $roomCo), 2);
                $splitPr = round(min($caps['provider'], $splitPr + $roomPr), 2);
            }
        }

        if (round($splitPr + $splitCo, 2) <= 0.009) {
            return null;
        }

        return ['provider' => $splitPr, 'company' => $splitCo];
    }
}

if (! function_exists('booking_after_partial_payment_booking_refresh')) {
    /**
     * Sync is_paid and scaled loss-recovery flags after a new partial payment row.
     */
    function booking_after_partial_payment_booking_refresh(Booking $booking): void
    {
        $booking->loadMissing('booking_partial_payments');
        $paidTotal = round((float) get_booking_total_paid($booking), 2);
        $isScaled = (int) ($booking->is_repeated ?? 0) === 0
            && trim((string) ($booking->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;

        if ($isScaled) {
            $grand = round((float) get_booking_total_amount($booking), 2);
            $booking->is_paid = ($grand > 0.009 && $paidTotal + 0.009 >= $grand) ? 1 : 0;
            $svc = app(BookingFinancialSettlementService::class);
            [, $lossTotal] = $svc->resolveScaledLossBreakdown(
                $booking,
                is_array($booking->settlement_config) ? $booking->settlement_config : [],
                $grand,
                $svc->totalPaidForMainBooking($booking)
            );
            if ($lossTotal <= 0.009) {
                $booking->allow_complete_without_full_payment = false;
            }
        } else {
            $payableCap = round((float) get_booking_payable_total_for_partial_dues($booking), 2);
            $booking->is_paid = ($payableCap > 0.009 && $paidTotal + 0.009 >= $payableCap) ? 1 : 0;
        }

        $booking->save();
    }
}

if (! function_exists('record_customer_booking_due_payment')) {
    /**
     * Record a customer-initiated due-balance payment (full or partial) from booking details.
     */
    function record_customer_booking_due_payment(
        Booking $booking,
        float $amount,
        string $transactionId,
        string $paymentMethod
    ): void {
        $amount = round(max(0.0, $amount), 2);
        if ($amount <= 0) {
            return;
        }

        $booking->loadMissing('booking_partial_payments');
        $remainingCap = round((float) get_booking_admin_add_payment_remaining_amount($booking), 2);
        if ($amount > $remainingCap + 0.009) {
            throw new \InvalidArgumentException(
                translate('Amount cannot exceed the due amount. Due amount') . ': ' . with_currency_symbol($remainingCap)
            );
        }

        $installmentCap = round((float) booking_installment_payable_cap($booking), 2);
        $totalPaid = round((float) get_booking_total_paid($booking), 2);
        $dueAfter = round(max(0.0, $installmentCap - ($totalPaid + $amount)), 2);
        $paidWith = map_booking_payment_paid_with($paymentMethod);
        $adminUserId = \Modules\UserManagement\Entities\User::where('user_type', ADMIN_USER_TYPES[0])->first()?->id;
        $lossAllocation = booking_scaled_loss_recovery_allocation_for_payment($booking, $amount, 'company');

        DB::transaction(function () use ($booking, $amount, $transactionId, $paidWith, $dueAfter, $paymentMethod, $adminUserId, $lossAllocation) {
            $attrs = [
                'paid_with' => $paidWith,
                'paid_amount' => $amount,
                'due_amount' => $dueAfter,
                'received_by' => 'company',
                'transaction_id' => $transactionId,
            ];
            if ($lossAllocation !== null) {
                $attrs['loss_allocation_provider'] = $lossAllocation['provider'];
                $attrs['loss_allocation_company'] = $lossAllocation['company'];
            }
            $partial = $booking->booking_partial_payments()->create($attrs);

            if ($adminUserId) {
                $account = \Modules\TransactionModule\Entities\Account::where('user_id', $adminUserId)->first();
                if ($account) {
                    $account->balance_pending += $amount;
                    $account->save();
                }

                Transaction::create([
                    'ref_trx_id' => null,
                    'booking_id' => $booking->id,
                    'trx_type' => TRX_TYPE['booking_amount'],
                    'company_flow' => Transaction::FLOW_IN,
                    'debit' => 0,
                    'credit' => $amount,
                    'balance' => $account?->balance_pending ?? 0,
                    'from_user_id' => $booking->customer_id,
                    'to_user_id' => $adminUserId,
                    'from_user_account' => null,
                    'to_user_account' => ACCOUNT_STATES[0]['value'],
                    'is_guest' => $booking->is_guest,
                ]);
            }

            ledger_record_in([
                'amount' => $amount,
                'transaction_id' => $transactionId,
                'booking_id' => $booking->id,
                'payment_method' => $paymentMethod,
                'date' => now()->toDateString(),
                'received_by' => \Modules\TransactionModule\Entities\LedgerTransaction::RECEIVED_BY_COMPANY,
                'booking_partial_payment_id' => $partial->id,
            ]);

            $fresh = $booking->fresh(['booking_partial_payments']);
            $fresh->payment_method = $paymentMethod;
            $fresh->transaction_id = $transactionId;
            booking_after_partial_payment_booking_refresh($fresh);
        });

        $freshBooking = $booking->fresh(['customer', 'provider.owner']);
        if ($freshBooking) {
            send_booking_payment_collected_notifications($freshBooking, $amount, 'company');
        }
    }
}

if (! function_exists('booking_provider_can_record_customer_payment')) {
    /**
     * Provider app may record cash received from customer while booking is active (or scaled recovery).
     */
    function booking_provider_can_record_customer_payment(Booking $booking): bool
    {
        if (! empty($booking->reopen_disputed_snapshot) && is_array($booking->reopen_disputed_snapshot)) {
            return false;
        }

        $snapshot = booking_provider_api_payment_snapshot($booking);
        $dueDisplay = round((float) ($snapshot['due_balance'] ?? 0), 2);
        $remainingAdmin = round((float) get_booking_admin_add_payment_remaining_amount($booking), 2);
        $remaining = max($dueDisplay, $remainingAdmin);
        if ($remaining < 0.01) {
            return false;
        }

        $status = strtolower(trim((string) ($booking->booking_status ?? '')));
        $isScaledSettlement = $booking->isLossMakingFinancialSettlement();

        return in_array($status, ['pending', 'accepted', 'ongoing'], true)
            || ($status === 'on_hold' && booking_on_hold_is_after_visit_from_ongoing($booking))
            || ($isScaledSettlement && $status === 'completed' && $remaining > 0.009);
    }
}

if (! function_exists('record_provider_booking_customer_payment')) {
    /**
     * Provider records cash (or direct payment) received from customer on-site.
     *
     * @throws \InvalidArgumentException
     */
    function record_provider_booking_customer_payment(Booking $booking, float $amount): \Modules\BookingModule\Entities\BookingPartialPayment
    {
        $booking->loadMissing('booking_partial_payments');

        if (! booking_provider_can_record_customer_payment($booking)) {
            throw new \InvalidArgumentException(translate('Payment cannot be recorded for this booking at this stage.'));
        }

        $amount = round(max(0.0, $amount), 2);
        if ($amount < 0.01) {
            throw new \InvalidArgumentException(translate('Amount must be greater than zero.'));
        }

        $dueRemaining = round((float) get_booking_admin_add_payment_remaining_amount($booking), 2);
        if ($amount > $dueRemaining + 0.009) {
            throw new \InvalidArgumentException(
                translate('Amount cannot exceed the due amount. Due amount') . ': ' . with_currency_symbol($dueRemaining)
            );
        }

        $totalPaid = round((float) get_booking_total_paid($booking), 2);
        $installmentCap = round((float) booking_installment_payable_cap($booking), 2);
        $dueAfter = round(max(0.0, $installmentCap - ($totalPaid + $amount)), 2);
        $lossAllocation = booking_scaled_loss_recovery_allocation_for_payment($booking, $amount, 'provider');

        $partial = null;
        DB::transaction(function () use ($booking, $amount, $dueAfter, $lossAllocation, &$partial) {
            $attrs = [
                'paid_with' => 'provider_entry',
                'transaction_id' => null,
                'paid_amount' => $amount,
                'due_amount' => $dueAfter,
                'received_by' => 'provider',
            ];
            if ($lossAllocation !== null) {
                $attrs['loss_allocation_provider'] = $lossAllocation['provider'];
                $attrs['loss_allocation_company'] = $lossAllocation['company'];
            }
            $partial = $booking->booking_partial_payments()->create($attrs);

            record_cross_party_booking_partial_transaction($booking, $amount, (string) $partial->id);

            $freshBooking = $booking->fresh(['booking_partial_payments']);
            booking_after_partial_payment_booking_refresh($freshBooking);
        });

        if (! $partial instanceof \Modules\BookingModule\Entities\BookingPartialPayment) {
            throw new \RuntimeException(translate('Payment recording failed.'));
        }

        $freshBooking = $booking->fresh(['customer', 'provider.owner']);
        if ($freshBooking) {
            send_booking_payment_collected_notifications($freshBooking, $amount, 'provider');
        }

        return $partial;
    }
}

if (! function_exists('booking_installment_payments_payload')) {
    /**
     * All customer payment rows for admin UI and customer payment_ledger.
     * Merges booking_partial_payments with ledger IN rows not already represented by a partial.
     *
     * @return array{payable_cap: float, rows: list<array<string, mixed>>}
     */
    function booking_installment_payments_payload(Booking $main, ?array $bfsScaledLive = null): array
    {
        $main->loadMissing(['booking_partial_payments', 'booking_offline_payments']);

        $cap = booking_installment_payable_cap($main, $bfsScaledLive);

        $partials = $main->booking_partial_payments
            ->sortBy([
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->filter(fn ($partial) => round((float) ($partial->paid_amount ?? 0), 2) != 0.0)
            ->values();

        $partialIds = $partials->pluck('id')->filter()->map(fn ($id) => (string) $id)->all();
        $partialFingerprints = $partials->map(function ($partial) {
            $minute = $partial->created_at?->format('Y-m-d H:i') ?? '';

            return round((float) ($partial->paid_amount ?? 0), 2).'|'.$minute.'|'.((string) ($partial->received_by ?? ''));
        })->all();

        $rows = [];
        $runningPaid = 0.0;

        foreach ($partials as $partial) {
            $amount = round((float) ($partial->paid_amount ?? 0), 2);
            $runningPaid += $amount;
            $rows[] = booking_installment_row_from_partial($partial, $main, $cap - $runningPaid);
        }

        $ledgerEntries = booking_cached_ledger_transactions((string) $main->id)
            ->filter(function ($entry) {
                if ($entry->type !== LedgerTransaction::TYPE_IN) {
                    return false;
                }
                $reason = $entry->reason;
                if ($reason === LedgerTransaction::REASON_REFUND || $reason === LedgerTransaction::REASON_PROVIDER_PAYOUT) {
                    return false;
                }

                return round((float) ($entry->amount ?? 0), 2) != 0.0;
            })
            ->values();

        foreach ($ledgerEntries as $entry) {
            $partialPaymentId = $entry->booking_partial_payment_id
                ? (string) $entry->booking_partial_payment_id
                : null;
            if ($partialPaymentId !== null && in_array($partialPaymentId, $partialIds, true)) {
                continue;
            }

            $minute = $entry->created_at?->format('Y-m-d H:i') ?? '';
            $fingerprint = round((float) ($entry->amount ?? 0), 2).'|'.$minute.'|'.((string) ($entry->received_by ?? ''));
            if (in_array($fingerprint, $partialFingerprints, true)) {
                continue;
            }

            $amount = round((float) ($entry->amount ?? 0), 2);
            $runningPaid += $amount;
            $rows[] = booking_installment_row_from_ledger($entry, $cap - $runningPaid);
        }

        usort($rows, function (array $a, array $b) {
            $dateCompare = strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return strcmp((string) ($a['id'] ?? ''), (string) ($b['id'] ?? ''));
        });

        // Older digital captures stored only total_booking_amount (service lines), omitting extra_fee / visiting.
        if ((int) ($main->is_paid ?? 0) === 1) {
            $rowSum = round(array_sum(array_map(
                fn (array $row) => (float) ($row['amount'] ?? 0),
                $rows
            )), 2);
            $expectedPaid = round(min($cap, (float) get_booking_total_paid($main)), 2);
            if ($expectedPaid > $rowSum + 0.009) {
                $serviceOnly = round((float) ($main->total_booking_amount ?? 0), 2);
                if (count($rows) === 1
                    && abs((float) ($rows[0]['amount'] ?? 0) - $serviceOnly) < 0.01) {
                    $rows[0]['amount'] = $expectedPaid;
                }
            }
        }

        $runningPaid = 0.0;
        foreach ($rows as $index => &$row) {
            $runningPaid += (float) ($row['amount'] ?? 0);
            $row['serial'] = $index + 1;
            $row['due_after_payment'] = round(max(0.0, $cap - $runningPaid), 2);
        }
        unset($row);

        return ['payable_cap' => $cap, 'rows' => $rows];
    }
}

if (! function_exists('booking_refund_ledger_method_key')) {
    /**
     * Customer refund delivery method for payment ledger rows.
     * Transfer refunds record a gateway/bank transaction id; wallet refunds do not.
     */
    function booking_refund_ledger_method_key(LedgerTransaction $entry): string
    {
        $transactionId = trim((string) ($entry->transaction_id ?? ''));

        return $transactionId !== '' ? 'transfer' : 'wallet';
    }
}

if (! function_exists('booking_refund_ledger_row_payload')) {
    /**
     * @return array{serial: int, date: string|null, amount: float, transaction_id: string|null, reference_note: string|null, refund_method: string, refund_method_label: string}
     */
    function booking_refund_ledger_row_payload(LedgerTransaction $entry, int $serial): array
    {
        $method = booking_refund_ledger_method_key($entry);
        $referenceNote = trim((string) ($entry->reference_note ?? ''));
        if ($referenceNote === 'wallet_refund') {
            $referenceNote = '';
        }

        return [
            'serial' => $serial,
            'date' => $entry->created_at?->toIso8601String(),
            'amount' => round((float) ($entry->amount ?? 0), 2),
            'transaction_id' => $entry->transaction_id ?: null,
            'reference_note' => $referenceNote !== '' ? $referenceNote : null,
            'refund_method' => $method,
            'refund_method_label' => $method === 'transfer'
                ? translate('Transfer_to_customer')
                : translate('Refund_to_wallet'),
        ];
    }
}

if (! function_exists('get_booking_customer_refund_delivered_breakdown')) {
    /**
     * Refunds already delivered to the customer, split by wallet credit vs bank/gateway transfer.
     * Excludes internal disputed-refund ledger legs (company/provider pool rows).
     *
     * @return array{
     *     wallet_refunded: float,
     *     transfer_refunded: float,
     *     total_refunded: float,
     *     has_any: bool
     * }
     */
    function get_booking_customer_refund_delivered_breakdown(Booking $booking): array
    {
        $bid = (string) ($booking->id ?? '');
        if ($bid === '') {
            return [
                'wallet_refunded' => 0.0,
                'transfer_refunded' => 0.0,
                'total_refunded' => 0.0,
                'has_any' => false,
            ];
        }

        $walletRefunded = 0.0;
        $transferRefunded = 0.0;

        $entries = LedgerTransaction::query()
            ->where('booking_id', $bid)
            ->where('reason', LedgerTransaction::REASON_REFUND)
            ->where('type', LedgerTransaction::TYPE_OUT)
            ->where(function ($query) {
                $query->whereNull('received_by')
                    ->orWhereNotIn('received_by', [
                        LedgerTransaction::RECEIVED_BY_COMPANY,
                        LedgerTransaction::RECEIVED_BY_PROVIDER,
                    ]);
            })
            ->get();

        foreach ($entries as $entry) {
            $amount = round((float) ($entry->amount ?? 0), 2);
            if ($amount <= 0.009) {
                continue;
            }

            if (booking_refund_ledger_method_key($entry) === 'transfer') {
                $transferRefunded += $amount;
            } else {
                $walletRefunded += $amount;
            }
        }

        $walletRefunded = round($walletRefunded, 2);
        $transferRefunded = round($transferRefunded, 2);

        return [
            'wallet_refunded' => $walletRefunded,
            'transfer_refunded' => $transferRefunded,
            'total_refunded' => round($walletRefunded + $transferRefunded, 2),
            'has_any' => $walletRefunded > 0.009 || $transferRefunded > 0.009,
        ];
    }
}

if (! function_exists('booking_append_customer_api_financial_fields')) {
    /**
     * Additional charges breakdown, payable total, payment summary, and customer payment ledger for customer API.
     *
     * @param  Booking|BookingRepeat  $booking
     */
    function booking_append_customer_api_financial_fields($booking, bool $lite = false): void
    {
        $main = $booking instanceof BookingRepeat
            ? ($booking->relationLoaded('booking') ? $booking->booking : $booking->booking()->first())
            : $booking;

        if (! $main instanceof Booking) {
            return;
        }

        $main->loadMissing(['booking_partial_payments', 'booking_offline_payments', 'extra_services']);

        $acDisplay = collect(enrich_booking_additional_charges_breakdown_for_display($main))
            ->filter(fn ($row) => round((float) ($row['amount'] ?? 0), 2) > 0)
            ->map(fn ($row) => [
                'id' => (string) ($row['id'] ?? ''),
                'name' => (string) ($row['name'] ?? translate('Additional_charges')),
                'amount' => round((float) ($row['amount'] ?? 0), 2),
            ])
            ->values()
            ->all();

        if ($acDisplay === [] && round((float) ($main->extra_fee ?? 0), 2) > 0) {
            $acDisplay = [[
                'id' => 'extra_fee',
                'name' => translate('Additional_charges'),
                'amount' => round((float) ($main->extra_fee ?? 0), 2),
            ]];
        }

        $extraServiceLines = ($main->extra_services ?? collect())
            ->filter(fn ($extra) => round((float) ($extra->total ?? 0), 2) > 0)
            ->map(fn ($extra) => [
                'id' => (string) ($extra->id ?? ''),
                'name' => (string) ($extra->title ?? translate('Extra_Services')),
                'amount' => round((float) ($extra->total ?? 0), 2),
                'type' => (string) ($extra->type ?? 'service'),
                'details' => $extra->details !== null ? (string) $extra->details : null,
                'price' => round((float) ($extra->price ?? 0), 2),
                'quantity' => (int) ($extra->quantity ?? 1),
                'discount' => round((float) ($extra->discount ?? 0), 2),
                'total' => round((float) ($extra->total ?? 0), 2),
            ])
            ->values()
            ->all();

        $snapshot = booking_provider_api_payment_snapshot_cached($main);
        $offline = $main->booking_offline_payments?->first();
        $offlineVerifyStatus = null;
        if ($main->payment_method === 'offline_payment' && $offline) {
            $offlineVerifyStatus = (string) ($offline->payment_status ?? '');
        }

        $paymentPayload = array_merge($snapshot, [
            'payment_method_display' => format_booking_payment_method_for_admin_display($main),
            'offline_verify_status' => $offlineVerifyStatus,
            'refund_channel_breakdown' => $lite ? [
                'wallet_paid' => 0.0,
                'digital_paid' => 0.0,
                'wallet_refund_amount' => 0.0,
                'digital_refund_amount' => 0.0,
                'total_refundable' => 0.0,
                'has_mixed_payments' => false,
                'requires_digital_refund_choice' => false,
            ] : get_booking_customer_refund_channel_breakdown($main),
        ]);

        if (! $lite
            && (int) ($main->is_repeated ?? 0) === 0
            && trim((string) ($main->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            $bfsPreview = booking_financial_build_preview_cached($main);
            $cfg = is_array($main->settlement_config) ? $main->settlement_config : [];
            $writeoff = round((float) ($bfsPreview['scaled_loss_writeoff_amount'] ?? 0), 2);
            if ($writeoff <= 0.009
                && isset($cfg['scaled_loss_writeoff_amount'])
                && is_numeric($cfg['scaled_loss_writeoff_amount'])) {
                $writeoff = round(max(0.0, (float) $cfg['scaled_loss_writeoff_amount']), 2);
            }
            $paymentPayload['write_off_amount'] = $writeoff > 0.009 ? $writeoff : null;
            $paymentPayload['settlement_amount'] = $writeoff > 0.009 ? $writeoff : null;
            $paymentPayload['is_writeoff_settled'] = $writeoff > 0.009;
        }

        $listDisplayTotal = get_customer_booking_list_display_total($main);
        $originalGrandTotal = round((float) get_booking_total_amount($main), 2);
        $target = $booking instanceof BookingRepeat ? $booking : $main;
        $target->setAttribute('additional_charges_display', $acDisplay);
        $target->setAttribute('extra_service_lines', $extraServiceLines);
        $target->setAttribute('list_display_total', $listDisplayTotal);
        $target->setAttribute('payable_grand_total', $originalGrandTotal);
        $target->setAttribute('payment_details', $paymentPayload);

        if ($lite) {
            return;
        }

        foreach ($main->booking_partial_payments as $partial) {
            $partial->setAttribute('payment_method_label', $partial->paymentMethodLabelForAdmin($main));
            $partial->setAttribute('received_by_label', booking_installment_received_by_label($partial->received_by));
        }

        $installmentPayload = booking_installment_payments_payload($main);
        $installments = collect($installmentPayload['rows'] ?? [])
            ->map(function (array $row) {
                $receivedBy = (string) ($row['received_by'] ?? 'company');
                $isProviderPayment = $receivedBy === 'provider';

                return [
                    'serial' => (int) ($row['serial'] ?? 0),
                    'date' => $row['date'] ?? null,
                    'received_by' => $receivedBy,
                    'received_by_label' => (string) ($row['received_by_label'] ?? '—'),
                    'amount' => round((float) ($row['amount'] ?? 0), 2),
                    'payment_method_label' => $isProviderPayment
                        ? ''
                        : (string) ($row['payment_method_label'] ?? ''),
                    'transaction_id' => $isProviderPayment
                        ? null
                        : ($row['transaction_id'] ?? null),
                    'due_after_payment' => round((float) ($row['due_after_payment'] ?? 0), 2),
                ];
            })
            ->sortByDesc(fn (array $row) => (string) ($row['date'] ?? ''))
            ->values()
            ->all();

        $refunds = booking_cached_ledger_transactions((string) $main->id)
            ->filter(fn ($entry) => $entry->reason === LedgerTransaction::REASON_REFUND
                && $entry->type === LedgerTransaction::TYPE_OUT)
            ->sortByDesc(fn ($entry) => ($entry->created_at?->timestamp ?? 0).':'.$entry->id)
            ->values()
            ->map(fn ($entry, $index) => booking_refund_ledger_row_payload($entry, $index + 1))
            ->all();

        $target->setAttribute('payment_ledger', [
            'installments' => $installments,
            'refunds' => $refunds,
        ]);
        $target->setAttribute('booking_summary', booking_customer_api_summary_payload($main));
        $disputedSettlement = booking_api_disputed_settlement_payload($main, 'customer');
        if ($disputedSettlement !== null) {
            $target->setAttribute('disputed_settlement', $disputedSettlement);
        } else {
            $lossMakingSettlement = booking_api_loss_making_settlement_payload($main, 'customer');
            if ($lossMakingSettlement !== null) {
                $target->setAttribute('loss_making_settlement', $lossMakingSettlement);
            }
            $specialFinancialSettlement = booking_api_special_financial_settlement_payload($main, 'customer');
            if ($specialFinancialSettlement !== null) {
                $target->setAttribute('special_financial_settlement', $specialFinancialSettlement);
            }
        }
    }
}

if (! function_exists('booking_customer_api_summary_payload')) {
    /**
     * Customer-facing booking summary aligned with admin booking details breakdown.
     *
     * @return array<string, mixed>
     */
    function booking_customer_api_summary_payload(Booking $booking): array
    {
        $booking->loadMissing(['detail', 'extra_services', 'booking_partial_payments']);

        $catalogGrossSubtotal = 0.0;
        foreach ($booking->detail ?? [] as $detail) {
            $catalogGrossSubtotal += (float) ($detail->service_cost ?? 0) * (int) ($detail->quantity ?? 1);
        }
        $catalogGrossSubtotal = round($catalogGrossSubtotal, 2);

        $extraServiceLines = [];
        $sparePartLines = [];
        $extraGrossService = 0.0;
        $extraGrossSpare = 0.0;

        foreach ($booking->extra_services ?? [] as $extra) {
            $lineGross = round((float) ($extra->price ?? 0) * (int) ($extra->quantity ?? 1), 2);
            $line = [
                'id' => (string) ($extra->id ?? ''),
                'name' => (string) ($extra->title ?? translate('Extra_Services')),
                'amount' => round((float) ($extra->total ?? 0), 2),
                'type' => (string) ($extra->type ?? 'service'),
            ];
            if (($extra->type ?? '') === BookingExtraService::TYPE_SPARE_PART) {
                $sparePartLines[] = $line;
                $extraGrossSpare += $lineGross;
            } else {
                $extraServiceLines[] = $line;
                $extraGrossService += $lineGross;
            }
        }

        $additionalChargeLines = collect(enrich_booking_additional_charges_breakdown_for_display($booking))
            ->filter(fn ($row) => round((float) ($row['amount'] ?? 0), 2) > 0)
            ->map(fn ($row) => [
                'id' => (string) ($row['id'] ?? ''),
                'name' => (string) ($row['name'] ?? translate('Additional_charges')),
                'amount' => round((float) ($row['amount'] ?? 0), 2),
            ])
            ->values()
            ->all();

        if ($additionalChargeLines === [] && round((float) ($booking->extra_fee ?? 0), 2) > 0) {
            $additionalChargeLines = [[
                'id' => 'extra_fee',
                'name' => translate('Additional_charges'),
                'amount' => round((float) ($booking->extra_fee ?? 0), 2),
            ]];
        }

        $additionalChargesTotal = round((float) ($booking->extra_fee ?? 0), 2);
        $grossTotal = round($catalogGrossSubtotal + $extraGrossService + $extraGrossSpare + $additionalChargesTotal, 2);
        $serviceDiscount = round(
            (float) ($booking->total_discount_amount ?? 0) + get_booking_extra_service_line_discount_total($booking),
            2
        );

        return [
            'service_amount' => $catalogGrossSubtotal,
            'extra_service_lines' => $extraServiceLines,
            'spare_part_lines' => $sparePartLines,
            'additional_charge_lines' => $additionalChargeLines,
            'gross_total' => $grossTotal,
            'service_discount' => $serviceDiscount,
            'coupon_discount' => round((float) ($booking->total_coupon_discount_amount ?? 0), 2),
            'campaign_discount' => round((float) ($booking->total_campaign_discount_amount ?? 0), 2),
            'referral_discount' => round((float) ($booking->total_referral_discount_amount ?? 0), 2),
            'tax' => round((float) ($booking->total_tax_amount ?? 0), 2),
            'has_tax' => round((float) ($booking->total_tax_amount ?? 0), 2) > 0,
            'grand_total' => round((float) get_booking_total_amount($booking), 2),
            'total_paid' => round((float) get_booking_total_paid($booking), 2),
            'due_amount' => round((float) get_booking_customer_display_due_balance($booking), 2),
        ];
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
        if (! $booking instanceof Booking) {
            return 0.0;
        }
        $model = $booking->exists
            ? Booking::query()->with('booking_partial_payments')->find($booking->id)
            : $booking;
        if (! $model) {
            return 0.0;
        }
        $partials = $model->booking_partial_payments;
        if ($partials->isNotEmpty()) {
            return round((float) $partials->sum('paid_amount'), 2);
        }
        if ((int) ($model->is_paid ?? 0) === 1) {
            if (trim((string) ($model->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                return 0.0;
            }

            return round((float) get_booking_total_amount($model), 2);
        }

        return 0.0;
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

if (!function_exists('get_customer_booking_list_display_total')) {
    /**
     * Total amount shown on admin customer booking list: full payable grand total (lines + extras + extra_fee),
     * except visit-charge settlements use the decided retained total, and canceled-after-visit uses reporting basis.
     */
    function get_customer_booking_list_display_total(Booking $booking): float
    {
        $booking->loadMissing('extra_services');

        if (booking_admin_has_disputed_reopen_snapshot($booking)) {
            $snap = (array) $booking->reopen_disputed_snapshot;
            foreach (['retained_from_customer', 'final_net_to_customer'] as $k) {
                if (isset($snap[$k]) && is_numeric($snap[$k])) {
                    return round((float) $snap[$k], 2);
                }
            }
        }

        $outcome = trim((string) ($booking->settlement_outcome ?? ''));
        if ($outcome !== '' && BookingFinancialSettlementService::outcomeUsesDecidedVisitCharges($outcome)) {
            return get_booking_payable_total_for_partial_dues($booking);
        }

        return round((float) get_booking_revenue_reporting_amount($booking), 2);
    }
}

if (! function_exists('provider_booking_report_pending_admin_amount')) {
    /**
     * Booking-report due card: amount still pending with admin (provider receivable context).
     * Cash-after-service bookings return 0. Company-collected payments return the full booking
     * total until completed, then the company_owes_provider slice still held by admin.
     */
    function provider_booking_report_pending_admin_amount(Booking $booking): float
    {
        if ((string) ($booking->payment_method ?? '') === 'cash_after_service') {
            return 0.0;
        }

        $booking->loadMissing('booking_partial_payments');
        $displayTotal = round((float) get_customer_booking_list_display_total($booking), 2);
        if ($displayTotal <= 0.009) {
            return 0.0;
        }

        $receipts = provider_payment_tab_receipts_for_main_booking($booking);
        $companyReceived = round((float) ($receipts['company'] ?? 0), 2);
        if ($companyReceived <= 0.009) {
            return 0.0;
        }

        if ((string) ($booking->booking_status ?? '') !== 'completed') {
            return $displayTotal;
        }

        $settlementCols = provider_payment_tab_earning_report_settlement_columns_for_booking($booking);

        return round((float) ($settlementCols['company_owes_provider'] ?? 0), 2);
    }
}

if (! function_exists('aggregate_provider_booking_report_amount_cards')) {
    /**
     * Provider booking report summary: total, due (pending with admin), settled (total − due).
     *
     * @param  iterable<int, Booking>  $bookings
     * @return array{total_booking_amount: float, total_unpaid_booking_amount: float, total_paid_booking_amount: float}
     */
    function aggregate_provider_booking_report_amount_cards(iterable $bookings): array
    {
        $total = 0.0;
        $pendingWithAdmin = 0.0;

        foreach ($bookings as $booking) {
            if (! $booking instanceof Booking) {
                continue;
            }
            $displayTotal = round((float) get_customer_booking_list_display_total($booking), 2);
            $total += $displayTotal;
            $pendingWithAdmin += provider_booking_report_pending_admin_amount($booking);
        }

        $total = round($total, 2);
        $pendingWithAdmin = round($pendingWithAdmin, 2);

        return [
            'total_booking_amount' => $total,
            'total_unpaid_booking_amount' => $pendingWithAdmin,
            'total_paid_booking_amount' => round(max(0.0, $total - $pendingWithAdmin), 2),
        ];
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
        $snap = $booking->reopen_disputed_snapshot ?? null;
        if (is_array($snap)
            && ($snap['type'] ?? '') === 'reopen_disputed_refund'
            && trim((string) ($booking->settlement_outcome ?? '')) !== BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
            $adm = (float) ($snap['final_admin_commission'] ?? 0);
            $prov = (float) ($snap['final_provider_earning'] ?? 0);
            $fromParts = round($adm + $prov, 2);
            if ($fromParts > 0.0001) {
                return $fromParts;
            }

            return round(max(0.0, (float) ($snap['retained_from_customer'] ?? $snap['final_net_to_customer'] ?? 0)), 2);
        }

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
     * total_company_commission / provider_net_earning: live breakdown ({@see provider_payment_tab_earning_commission_pair}) for normal jobs;
     * for loss-making (scaled) parents, from {@see provider_payment_tab_loss_making_earning_display_for_scaled} (before_loss) per line, not row sums.
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

        $totalCompanyCommission = 0.0;
        $totalProviderEarning = 0.0;

        foreach ($oneTimeCol as $b) {
            if (! $b instanceof Booking) {
                continue;
            }
            if (trim((string) ($b->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                continue;
            }
            $pair = provider_payment_tab_earning_commission_pair($b);
            $totalCompanyCommission += $pair['admin_commission'];
            $totalProviderEarning += $pair['provider_earning'];
        }

        foreach ($repeatsCol as $r) {
            if (! $r instanceof BookingRepeat) {
                continue;
            }
            $main = $r->relationLoaded('booking') ? $r->booking : $r->booking()->first();
            if ($main instanceof Booking && trim((string) ($main->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                continue;
            }
            $pair = provider_payment_tab_earning_commission_pair($r);
            $totalCompanyCommission += $pair['admin_commission'];
            $totalProviderEarning += $pair['provider_earning'];
        }

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
        $scaledMain = trim((string) ($main->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;

        if ($partials->isNotEmpty()) {
            $totalPaid = (float) $partials->sum('paid_amount');
        } elseif ($scaledMain) {
            $totalPaid = round((float) app(BookingFinancialSettlementService::class)->totalPaidForMainBooking($main), 2);
        } else {
            $totalPaid = (bool) ($main->is_paid ?? false) ? (float) get_booking_total_amount($main) : 0.0;
        }

        $amountReceivedByCompany = 0.0;
        $amountReceivedByProvider = 0.0;
        if ($partials->isNotEmpty()) {
            foreach ($partials as $p) {
                $paid = (float) $p->paid_amount;
                $by = $p->received_by ?? '';
                if ($by === 'company' || $by === '') {
                    $amountReceivedByCompany += $paid;
                } elseif ($by === 'provider') {
                    $amountReceivedByProvider += $paid;
                }
            }
            if ($amountReceivedByCompany == 0 && $amountReceivedByProvider == 0 && $totalPaid > 0 && ! $scaledMain) {
                $amountReceivedByCompany = ($main->payment_method ?? '') !== 'cash_after_service' ? $totalPaid : 0.0;
                $amountReceivedByProvider = ($main->payment_method ?? '') === 'cash_after_service' ? $totalPaid : 0.0;
            }
        } elseif ($scaledMain) {
            $amountReceivedByCompany = 0.0;
            $amountReceivedByProvider = 0.0;
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
     * Repeat rows are grouped by parent so parent receipts are not multiplied. Loss-making (scaled) parents use
     * {@see get_booking_received_and_settlement()} on the parent so customer recovery updates match booking details.
     *
     * @param  iterable<int, Booking>  $oneTimeBookings
     * @param  iterable<int, BookingRepeat>  $repeats
     * @return array{settlement_net: float, customer_refund_due_total: float}
     */
    function aggregate_provider_booking_settlement_net_for_completed_jobs(iterable $oneTimeBookings, iterable $repeats): array
    {
        $oneTimeCol = collect($oneTimeBookings);
        $repeatsCol = collect($repeats);

        $net = 0.0;
        $customerRefundDue = 0.0;

        foreach ($oneTimeCol as $b) {
            if (! $b instanceof Booking) {
                continue;
            }
            $settled = get_booking_received_and_settlement($b);
            $net += (float) ($settled['pay_to_provider'] ?? 0) - (float) ($settled['provider_owes_company'] ?? 0);
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
            } elseif (trim((string) ($main->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                // Loss-making (scaled): same legs as booking details — net company/provider shares use current paid +
                // {@see BookingFinancialSettlementService::resolveScaledLossBreakdown()}. Summing gross commission
                // across repeat lines ignores revised loss after customer recovery and left net balance stale.
                $main->loadMissing('booking_partial_payments');
                $settled = get_booking_received_and_settlement($main);
                $legs = [
                    'pay_to_provider' => (float) ($settled['pay_to_provider'] ?? 0),
                    'provider_owes_company' => (float) ($settled['provider_owes_company'] ?? 0),
                ];
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
            provider_payment_tab_one_time_revenue_bookings_inner($q);
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
     * Sums on ledger rows scoped to this provider (same subset as the provider payment tab ledger table):
     * company ↔ provider only (payout OUT, collect IN), not customer↔company booking lines.
     */
    function provider_ledger_manual_flow_totals_for_provider(string $providerId): array
    {
        $base = LedgerTransaction::query()->where('provider_id', $providerId);

        $payoutOut = (float) (clone $base)
            ->where('type', LedgerTransaction::TYPE_OUT)
            ->where('reason', LedgerTransaction::REASON_PROVIDER_PAYOUT)
            ->sum('amount');

        $collectIn = (float) (clone $base)
            ->where('type', LedgerTransaction::TYPE_IN)
            ->where(function ($c) {
                $c->where('payment_method', 'collect_from_provider')
                    ->orWhereNull('booking_id');
            })
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
        $base = LedgerTransaction::query()->whereNotNull('provider_id');

        $payoutOut = (float) (clone $base)
            ->where('type', LedgerTransaction::TYPE_OUT)
            ->where('reason', LedgerTransaction::REASON_PROVIDER_PAYOUT)
            ->sum('amount');

        $collectIn = (float) (clone $base)
            ->where('type', LedgerTransaction::TYPE_IN)
            ->where(function ($c) {
                $c->where('payment_method', 'collect_from_provider')
                    ->orWhereNull('booking_id');
            })
            ->sum('amount');

        return [
            'payout_out_total' => round($payoutOut, 2),
            'collect_in_total' => round($collectIn, 2),
        ];
    }
}

if (!function_exists('provider_payment_ledger_context')) {
    /**
     * Normalized payment / ledger figures for provider payment UI, notifications, and templates.
     *
     * @param  array{
     *     collect_in_total?: float|int|string,
     *     payout_out_total?: float|int|string,
     *     booking_settlement_net_before_ledger?: float|int|string,
     *     booking_settlement_net_after_ledger?: float|int|string,
     *     provider_account_payable?: float|int|string,
     *     provider_account_receivable?: float|int|string
     * }  $totals  Use values already computed on the provider payment tab (avoids duplicate queries).
     * @return array{
     *     amount_collected_from_provider: float,
     *     amount_paid_to_provider: float,
     *     booking_settlement_net_before_ledger: float,
     *     booking_settlement_net_after_ledger: float,
     *     balance_after_payment_collected: float,
     *     balance_remaining_to_pay_to_provider: float,
     *     provider_account_payable: float,
     *     provider_account_receivable: float
     * }
     */
    function provider_payment_ledger_context(array $totals): array
    {
        $collect = round((float) ($totals['collect_in_total'] ?? 0), 2);
        $payout = round((float) ($totals['payout_out_total'] ?? 0), 2);
        $netBefore = round((float) ($totals['booking_settlement_net_before_ledger'] ?? 0), 2);
        $netAfter = round((float) ($totals['booking_settlement_net_after_ledger'] ?? 0), 2);
        $payable = round((float) ($totals['provider_account_payable'] ?? 0), 2);
        $receivable = round((float) ($totals['provider_account_receivable'] ?? 0), 2);

        return [
            'amount_collected_from_provider' => $collect,
            'amount_paid_to_provider' => $payout,
            'booking_settlement_net_before_ledger' => $netBefore,
            'booking_settlement_net_after_ledger' => $netAfter,
            'balance_after_payment_collected' => round(max(0.0, -$netAfter), 2),
            'balance_remaining_to_pay_to_provider' => round(max(0.0, $netAfter), 2),
            'provider_account_payable' => $payable,
            'provider_account_receivable' => $receivable,
        ];
    }
}

if (!function_exists('provider_withdrawable_balance')) {
    /**
     * Amount the provider can still request to withdraw (account receivable minus payable).
     * Pending/approved withdraw requests already reduce account_receivable when created.
     */
    function provider_withdrawable_balance(float $accountReceivable, float $accountPayable): float
    {
        if ($accountPayable > $accountReceivable) {
            return 0.0;
        }

        return round(max(0.0, $accountReceivable - $accountPayable), 2);
    }
}

if (!function_exists('provider_active_withdraw_request_total')) {
    /** Sum of withdraw requests awaiting payout (pending admin action or transfer). */
    function provider_active_withdraw_request_total(string $userId): float
    {
        $totals = provider_withdraw_request_totals_by_status($userId);

        return $totals['active_total'];
    }
}

if (!function_exists('provider_withdraw_request_totals_by_status')) {
    /**
     * @return array{pending_total: float, approved_total: float, settled_total: float, active_total: float}
     */
    function provider_withdraw_request_totals_by_status(string $userId): array
    {
        $pending = (float) \Modules\ProviderManagement\Entities\WithdrawRequest::query()
            ->where('user_id', $userId)
            ->where('request_status', 'pending')
            ->sum('amount');
        $approved = (float) \Modules\ProviderManagement\Entities\WithdrawRequest::query()
            ->where('user_id', $userId)
            ->where('request_status', 'approved')
            ->sum('amount');
        $settled = (float) \Modules\ProviderManagement\Entities\WithdrawRequest::query()
            ->where('user_id', $userId)
            ->where('request_status', 'settled')
            ->sum('amount');

        return [
            'pending_total' => round($pending, 2),
            'approved_total' => round($approved, 2),
            'settled_total' => round($settled, 2),
            'active_total' => round($pending + $approved, 2),
        ];
    }
}

if (!function_exists('provider_net_balance_amount_after_active_withdraws')) {
    /**
     * Net balance shown to the provider: booking settlement obligation minus pending/approved withdraws.
     */
    function provider_net_balance_amount_after_active_withdraws(
        float $netPayableAmount,
        float $activeWithdrawTotal,
        bool $companyPaysProvider
    ): float {
        if ($companyPaysProvider) {
            return max(0.0, round($netPayableAmount - $activeWithdrawTotal, 2));
        }

        return round(abs($netPayableAmount), 2);
    }
}

if (!function_exists('provider_effective_withdrawable_balance')) {
    /**
     * Max amount a provider can request now (account balance capped by settlement net minus active withdraws).
     */
    function provider_effective_withdrawable_balance(
        string $providerId,
        string $userId,
        float $accountReceivable,
        float $accountPayable
    ): float {
        $fromAccount = provider_withdrawable_balance($accountReceivable, $accountPayable);
        $activeWithdrawTotal = provider_active_withdraw_request_total($userId);
        $settlement = booking_settlement_net_with_provider_ledger_for_provider_id($providerId);
        $bookingNet = (float) ($settlement['settlement_net'] ?? 0);

        if ($bookingNet <= 0.009) {
            return $fromAccount;
        }

        $fromBooking = max(0.0, round($bookingNet - $activeWithdrawTotal, 2));

        return round(min($fromAccount, $fromBooking), 2);
    }
}

if (!function_exists('provider_pay_to_admin_limits')) {
    /**
     * Max amount a provider may pay the company digitally (ledger commission due or settlement advance).
     *
     * @return array{max: float, is_advance: bool, settlement_debt: float, ledger_due: float}
     */
    function provider_pay_to_admin_limits(
        float $bookingSettlementNet,
        float $accountPayable,
        float $accountReceivable = 0.0
    ): array {
        $settlementDebt = max(0.0, round(-$bookingSettlementNet, 2));
        $ledgerDue = max(0.0, round($accountPayable - $accountReceivable, 2));

        if ($settlementDebt <= 0.009 && $ledgerDue <= 0.009) {
            return [
                'max' => 0.0,
                'is_advance' => false,
                'settlement_debt' => 0.0,
                'ledger_due' => 0.0,
            ];
        }

        if ($ledgerDue > 0.009) {
            $max = $settlementDebt > 0.009 ? min($ledgerDue, $settlementDebt) : $ledgerDue;

            return [
                'max' => round($max, 2),
                'is_advance' => false,
                'settlement_debt' => $settlementDebt,
                'ledger_due' => $ledgerDue,
            ];
        }

        return [
            'max' => $settlementDebt,
            'is_advance' => true,
            'settlement_debt' => $settlementDebt,
            'ledger_due' => $ledgerDue,
        ];
    }
}

if (!function_exists('provider_payment_net_balance_context')) {
    /**
     * Shared net-balance figures for admin payment tab and provider app overview API.
     *
     * @return array{
     *     booking_settlement_net: float,
     *     company_pays_provider: bool,
     *     provider_pays_company: bool,
     *     active_withdraw_total: float,
     *     pending_withdraw_total: float,
     *     approved_withdraw_total: float,
     *     settled_withdraw_total: float,
     *     display_amount: float,
     *     withdrawable_balance: float,
     *     effective_withdrawable: float
     * }
     */
    function provider_payment_net_balance_context(
        string $providerId,
        string $userId,
        float $bookingSettlementNet,
        float $accountReceivable,
        float $accountPayable
    ): array {
        $companyPaysProvider = $bookingSettlementNet > 0.009;
        $providerPaysCompany = $bookingSettlementNet < -0.009;
        $withdrawTotals = provider_withdraw_request_totals_by_status($userId);
        $activeWithdrawTotal = $withdrawTotals['active_total'];
        $displayAmount = provider_net_balance_amount_after_active_withdraws(
            $bookingSettlementNet,
            $activeWithdrawTotal,
            $companyPaysProvider
        );

        return [
            'booking_settlement_net' => round($bookingSettlementNet, 2),
            'company_pays_provider' => $companyPaysProvider,
            'provider_pays_company' => $providerPaysCompany,
            'active_withdraw_total' => $activeWithdrawTotal,
            'pending_withdraw_total' => $withdrawTotals['pending_total'],
            'approved_withdraw_total' => $withdrawTotals['approved_total'],
            'settled_withdraw_total' => $withdrawTotals['settled_total'],
            'display_amount' => $displayAmount,
            'withdrawable_balance' => provider_withdrawable_balance($accountReceivable, $accountPayable),
            'effective_withdrawable' => provider_effective_withdrawable_balance(
                $providerId,
                $userId,
                $accountReceivable,
                $accountPayable
            ),
        ];
    }
}

if (!function_exists('provider_withdraw_amount_limits')) {
    /**
     * @return array{minimum: float, maximum: float|null}
     */
    function provider_withdraw_amount_limits(): array
    {
        $minConfig = business_config('minimum_withdraw_amount', 'business_information');
        $maxConfig = business_config('maximum_withdraw_amount', 'business_information');

        $minimum = ($minConfig && $minConfig->live_values !== null && $minConfig->live_values !== '')
            ? (float) $minConfig->live_values
            : 0.0;

        $maximum = ($maxConfig && $maxConfig->live_values !== null && $maxConfig->live_values !== '')
            ? (float) $maxConfig->live_values
            : null;

        return ['minimum' => $minimum, 'maximum' => $maximum];
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

if (!function_exists('admin_dashboard_unsettled_withdraw_totals')) {
    /**
     * Sum of all provider withdraw requests awaiting payout (pending admin review + approved, not yet settled).
     *
     * @return array{unsettled_total: float, pending_total: float, approved_total: float}
     */
    function admin_dashboard_unsettled_withdraw_totals(): array
    {
        $pending = (float) \Modules\ProviderManagement\Entities\WithdrawRequest::query()
            ->where('request_status', 'pending')
            ->sum('amount');
        $approved = (float) \Modules\ProviderManagement\Entities\WithdrawRequest::query()
            ->where('request_status', 'approved')
            ->sum('amount');

        return [
            'pending_total' => round($pending, 2),
            'approved_total' => round($approved, 2),
            'unsettled_total' => round($pending + $approved, 2),
        ];
    }
}

if (!function_exists('admin_dashboard_provider_net_balance_card_totals')) {
    /**
     * Payable-to-providers and balance-with-providers dashboard cards: sum each provider’s Net Balance
     * ({@see provider_payment_net_balance_context()} display_amount), including pending/approved withdraw
     * deductions when the company owes the provider.
     *
     * @return array{payable_to_providers: float, balance_with_providers: float}
     */
    function admin_dashboard_provider_net_balance_card_totals(): array
    {
        $fromBookings = DB::table('bookings')->whereNotNull('provider_id')->distinct()->pluck('provider_id');
        $fromLedger = DB::table('ledger_transactions')->whereNotNull('provider_id')->distinct()->pluck('provider_id');
        $fromPayable = DB::table('providers')
            ->join('users', 'users.id', '=', 'providers.user_id')
            ->join('accounts', 'accounts.user_id', '=', 'users.id')
            ->where('accounts.account_payable', '>', 0.01)
            ->pluck('providers.id');
        $fromWithdraws = DB::table('withdraw_requests')
            ->join('providers', 'providers.user_id', '=', 'withdraw_requests.user_id')
            ->whereIn('withdraw_requests.request_status', ['pending', 'approved'])
            ->distinct()
            ->pluck('providers.id');

        $providerRows = DB::table('providers')
            ->whereIn('id', collect()
                ->merge($fromBookings)
                ->merge($fromLedger)
                ->merge($fromPayable)
                ->merge($fromWithdraws)
                ->filter()
                ->unique()
                ->values()
                ->all())
            ->select('id', 'user_id')
            ->get();

        $activeWithdrawByUser = DB::table('withdraw_requests')
            ->whereIn('request_status', ['pending', 'approved'])
            ->groupBy('user_id')
            ->selectRaw('user_id, SUM(amount) as total')
            ->pluck('total', 'user_id');

        $payableToProviders = 0.0;
        $balanceWithProviders = 0.0;

        foreach ($providerRows as $row) {
            $providerId = (string) $row->id;
            $userId = (string) $row->user_id;
            $settlement = booking_settlement_net_with_provider_ledger_for_provider_id($providerId);
            $bookingSettlementNet = (float) ($settlement['settlement_net'] ?? 0);
            $companyPaysProvider = $bookingSettlementNet > 0.009;
            $providerPaysCompany = $bookingSettlementNet < -0.009;
            $activeWithdrawTotal = round((float) ($activeWithdrawByUser->get($userId) ?? 0), 2);
            $displayAmount = provider_net_balance_amount_after_active_withdraws(
                $bookingSettlementNet,
                $activeWithdrawTotal,
                $companyPaysProvider
            );

            if ($companyPaysProvider) {
                $payableToProviders += $displayAmount;
            } elseif ($providerPaysCompany) {
                $balanceWithProviders += $displayAmount;
            }
        }

        return [
            'payable_to_providers' => round($payableToProviders, 2),
            'balance_with_providers' => round($balanceWithProviders, 2),
        ];
    }
}

if (!function_exists('admin_dashboard_financial_summary_metrics')) {
    /**
     * Admin dashboard financial top cards: same booking cohort as provider payment / settlement aggregates.
     * Payable/balance-with-providers cards sum per-provider Net Balance (see {@see admin_dashboard_provider_net_balance_card_totals()}).
     * Settlement net subtracts all provider-ledger OUT (provider_payout) and adds provider-ledger IN (e.g. collected from provider).
     *
     * @return array{
     *     payable_to_providers: float,
     *     payable_to_customers: float,
     *     balance_with_providers: float,
     *     unsettled_withdraws_total: float,
     *     unsettled_withdraws_pending: float,
     *     unsettled_withdraws_approved: float,
     *     settlement_net: float,
     *     total_amount_received_by_company: float,
     *     total_loss_in_all_bookings: float,
 *     total_bad_debt_with_customers: float,
 *     total_write_off_company: float,
 *     total_write_off_provider: float
     * }
     * total_amount_received_by_company: company-ledger net cash position — sum of all IN minus sum of all OUT on rows
     *     included in {@see LedgerTransaction::scopeWhereCompanyCounterpartyOnly()} (same scope as the admin ledger screen).
     * total_loss_in_all_bookings: sum of scaled loss amounts (customer shortfall on loss-making bookings).
     * total_bad_debt_with_customers: sum of the company’s configured loss share on those bookings (company loss absorbed).
     */
    function admin_dashboard_financial_summary_metrics(): array
    {
        return \Modules\AdminModule\Services\AdminDashboardCache::rememberMetrics(
            'financial_summary:v3',
            function (): array {
                return admin_dashboard_financial_summary_metrics_uncached();
            }
        );
    }
}

if (!function_exists('admin_dashboard_financial_summary_metrics_uncached')) {
    /**
     * @return array{
     *     payable_to_providers: float,
     *     payable_to_customers: float,
     *     balance_with_providers: float,
     *     unsettled_withdraws_total: float,
     *     unsettled_withdraws_pending: float,
     *     unsettled_withdraws_approved: float,
     *     settlement_net: float,
     *     total_amount_received_by_company: float,
     *     total_loss_in_all_bookings: float,
     *     total_bad_debt_with_customers: float,
     *     total_write_off_company: float,
     *     total_write_off_provider: float
     * }
     */
    function admin_dashboard_financial_summary_metrics_uncached(): array
    {
        $bookingIdsWithRepeats = BookingRepeat::query()
            ->whereNotNull('booking_id')
            ->distinct()
            ->pluck('booking_id')
            ->filter()
            ->values()
            ->all();

        $oneTimeQuery = Booking::query()->where(function ($q) {
            provider_payment_tab_one_time_revenue_bookings_inner($q);
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

        $ledgerCompanyScope = LedgerTransaction::query()->whereCompanyCounterpartyOnly();
        $ledgerTotalIn = (float) (clone $ledgerCompanyScope)->in()->sum('amount');
        $ledgerTotalOut = (float) (clone $ledgerCompanyScope)->out()->sum('amount');
        $totalCompanyReceived = round($ledgerTotalIn - $ledgerTotalOut, 2);

        $repeatsCol = collect($repeats);

        $svc = app(BookingFinancialSettlementService::class);
        $scaledParentsDone = [];
        $totalScaledLossAmount = 0.0;
        $totalCompanyLossShare = 0.0;
        $totalWriteOffCompany = 0.0;
        $totalWriteOffProvider = 0.0;

        $accumulateScaledLossForMain = function (Booking $main) use ($svc, &$scaledParentsDone, &$totalScaledLossAmount, &$totalCompanyLossShare, &$totalWriteOffCompany, &$totalWriteOffProvider): void {
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
            $cfg = is_array($main->settlement_config) ? $main->settlement_config : [];
            $totalWriteOffCompany += isset($cfg['scaled_loss_writeoff_company_amount']) && is_numeric($cfg['scaled_loss_writeoff_company_amount'])
                ? (float) $cfg['scaled_loss_writeoff_company_amount'] : 0.0;
            $totalWriteOffProvider += isset($cfg['scaled_loss_writeoff_provider_amount']) && is_numeric($cfg['scaled_loss_writeoff_provider_amount'])
                ? (float) $cfg['scaled_loss_writeoff_provider_amount'] : 0.0;
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

        $providerNetBalanceCards = admin_dashboard_provider_net_balance_card_totals();
        $unsettledWithdraws = admin_dashboard_unsettled_withdraw_totals();

        return [
            'settlement_net' => round($net, 2),
            'payable_to_providers' => $providerNetBalanceCards['payable_to_providers'],
            'payable_to_customers' => round($refundDue, 2),
            'balance_with_providers' => $providerNetBalanceCards['balance_with_providers'],
            'unsettled_withdraws_total' => $unsettledWithdraws['unsettled_total'],
            'unsettled_withdraws_pending' => $unsettledWithdraws['pending_total'],
            'unsettled_withdraws_approved' => $unsettledWithdraws['approved_total'],
            'total_amount_received_by_company' => round($totalCompanyReceived, 2),
            'total_loss_in_all_bookings' => round($totalScaledLossAmount, 2),
            'total_bad_debt_with_customers' => round($totalCompanyLossShare, 2),
            'total_write_off_company' => round($totalWriteOffCompany, 2),
            'total_write_off_provider' => round($totalWriteOffProvider, 2),
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
     * Sum of partial paid_amount that may qualify for wallet refund on cancel (excludes manual/offline paths).
     */
    function booking_sum_partials_for_cancel_platform_auto_refund($partials): float
    {
        $exclude = ['cash_after_service', 'admin_entry', 'offline', 'offline_payment'];

        return (float) collect($partials)
            ->reject(fn ($p) => in_array((string) ($p->paid_with ?? ''), $exclude, true))
            ->sum('paid_amount');
    }
}

if (! function_exists('get_booking_customer_refund_channel_breakdown')) {
    /**
     * Split customer-paid amounts by wallet vs digital for cancel/refund UI and processing.
     *
     * @return array{
     *     wallet_paid: float,
     *     digital_paid: float,
     *     wallet_refund_amount: float,
     *     digital_refund_amount: float,
     *     total_refundable: float,
     *     has_mixed_payments: bool,
     *     requires_digital_refund_choice: bool
     * }
     */
    function get_booking_customer_refund_channel_breakdown(Booking $booking): array
    {
        if (! $booking->relationLoaded('booking_partial_payments') && $booking->exists) {
            $booking->loadMissing('booking_partial_payments');
        }

        $walletPaid = 0.0;
        $digitalPaid = 0.0;

        if ($booking->relationLoaded('booking_partial_payments') && $booking->booking_partial_payments->isNotEmpty()) {
            foreach ($booking->booking_partial_payments as $partial) {
                $amount = round((float) ($partial->paid_amount ?? 0), 2);
                if ($amount <= 0.009) {
                    continue;
                }

                $paidWith = (string) ($partial->paid_with ?? '');
                if ($paidWith === 'wallet') {
                    $walletPaid += $amount;
                } elseif ($paidWith === 'digital') {
                    $digitalPaid += $amount;
                }
            }
        } elseif ((int) ($booking->is_paid ?? 0) === 1) {
            $paid = round((float) get_booking_total_paid($booking), 2);
            $method = (string) ($booking->payment_method ?? '');
            if ($method === 'wallet_payment') {
                $walletPaid = $paid;
            } elseif (! in_array($method, ['cash_after_service', 'offline_payment'], true) && $paid > 0.009) {
                $digitalPaid = $paid;
            }
        }

        $walletPaid = round($walletPaid, 2);
        $digitalPaid = round($digitalPaid, 2);

        return [
            'wallet_paid' => $walletPaid,
            'digital_paid' => $digitalPaid,
            'wallet_refund_amount' => $walletPaid,
            'digital_refund_amount' => $digitalPaid,
            'total_refundable' => round($walletPaid + $digitalPaid, 2),
            'has_mixed_payments' => $walletPaid > 0.009 && $digitalPaid > 0.009,
            'requires_digital_refund_choice' => $digitalPaid > 0.009,
        ];
    }
}

if (!function_exists('booking_ledger_refund_out_total')) {
    /**
     * Sum of ledger OUT rows for this booking with reason refund (money already recorded as leaving the platform).
     * Cancel wallet refund and admin "Transfer to customer" both write these; subtract this before recording another OUT.
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
     * For cancel-after-visit / visit-retained decided charges, cap is **overpayment** (paid minus retained obligation).
     * Zero when status is not canceled/refunded or when there is no overpayment in that scenario.
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
            // Cancel-after-visit decided charges: customer obligation is the retained visit+closing total.
            // Only **overpayment** beyond that can be refunded via the admin refund action.
            $config = is_array($booking->settlement_config) ? $booking->settlement_config : [];
            $retained = app(BookingFinancialSettlementService::class)->resolveRetainedVisitAmount($booking, $config);
            $paid = round((float) get_booking_total_paid($booking), 2);

            return round(max(0.0, $paid - $retained), 2);
        }

        return round((float) get_booking_total_paid($booking), 2);
    }
}

if (!function_exists('booking_customer_paid_split_by_receiver')) {
    /**
     * Customer amounts collected toward the booking, split by who received them (company platform vs provider directly).
     * Partials without received_by are summed as "unassigned" (shown separately; admin should correct data if needed).
     *
     * @return array{company: float, provider: float, unassigned: float, total: float}
     */
    function booking_customer_paid_split_by_receiver(Booking $booking): array
    {
        $booking->loadMissing('booking_partial_payments');
        $company = 0.0;
        $provider = 0.0;
        $unassigned = 0.0;
        foreach ($booking->booking_partial_payments as $p) {
            $amt = (float) ($p->paid_amount ?? 0);
            if ($amt <= 0) {
                continue;
            }
            $rb = (string) ($p->received_by ?? '');
            if ($rb === LedgerTransaction::RECEIVED_BY_COMPANY || $rb === 'company') {
                $company += $amt;
            } elseif ($rb === LedgerTransaction::RECEIVED_BY_PROVIDER || $rb === 'provider') {
                $provider += $amt;
            } else {
                $unassigned += $amt;
            }
        }
        $company = round($company, 2);
        $provider = round($provider, 2);
        $unassigned = round($unassigned, 2);

        return [
            'company' => $company,
            'provider' => $provider,
            'unassigned' => $unassigned,
            'total' => round($company + $provider + $unassigned, 2),
        ];
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
                (bool) ($parent->reopen_completion_allowed ?? false)
                || (!empty($parent->reopen_disputed_snapshot) && is_array($parent->reopen_disputed_snapshot))
                || (string) ($parent->settlement_outcome ?? '') === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS
                || (bool) ($parent->allow_complete_without_full_payment ?? false)
            )) {
                return true;
            }
        } elseif ($booking instanceof Booking) {
            if ((bool) ($booking->reopen_completion_allowed ?? false)) {
                return true;
            }
            if (!empty($booking->reopen_disputed_snapshot) && is_array($booking->reopen_disputed_snapshot)) {
                return true;
            }
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

if (!function_exists('format_booking_payment_event_channel_label')) {
    /**
     * Short label for payment-event rows (not raw trx_type).
     */
    function format_booking_payment_event_channel_label(?string $trxType): string
    {
        return match ($trxType) {
            TRX_TYPE['cross_party_booking_payment'] => translate('Trx_type_cross_party_booking_payment'),
            TRX_TYPE['booking_amount'] => translate('Payment_customer_to_company'),
            TRX_TYPE['booking_refund'] => translate('Refund'),
            default => $trxType ? str_replace('_', ' ', $trxType) : '—',
        };
    }
}

if (!function_exists('translate_payment_counterparty_flow_key')) {
    function translate_payment_counterparty_flow_key(string $key): string
    {
        return match ($key) {
            'customer_to_company' => translate('Payment_flow_customer_to_company'),
            'provider_to_company' => translate('Payment_flow_provider_to_company'),
            'company_to_customer' => translate('Payment_flow_company_to_customer'),
            'company_to_provider' => translate('Payment_flow_company_to_provider'),
            'customer_to_provider' => translate('Payment_flow_customer_to_provider'),
            'provider_to_customer' => translate('Payment_flow_provider_to_customer'),
            default => translate('Company_money_flow_unclassified'),
        };
    }
}

if (!function_exists('payment_counterparty_flow_party_pair')) {
    /**
     * @return array{0: string, 1: string}|null Roles: customer, provider, company
     */
    function payment_counterparty_flow_party_pair(string $key): ?array
    {
        return match ($key) {
            'customer_to_company' => ['customer', 'company'],
            'provider_to_company' => ['provider', 'company'],
            'company_to_customer' => ['company', 'customer'],
            'company_to_provider' => ['company', 'provider'],
            'customer_to_provider' => ['customer', 'provider'],
            'provider_to_customer' => ['provider', 'customer'],
            default => null,
        };
    }
}

if (!function_exists('payment_counterparty_party_pill_html')) {
    /**
     * Colored pill for one party role (customer / provider / company).
     */
    function payment_counterparty_party_pill_html(string $role): string
    {
        $label = match ($role) {
            'customer' => translate('Customer'),
            'provider' => translate('Provider'),
            'company' => translate('Company'),
            default => '—',
        };
        $class = match ($role) {
            'customer' => 'badge rounded-pill payment-flow-pill-customer',
            'provider' => 'badge rounded-pill payment-flow-pill-provider',
            'company' => 'badge rounded-pill payment-flow-pill-company',
            default => 'badge bg-secondary',
        };

        return '<span class="'.$class.'">'.e($label).'</span>';
    }
}

if (!function_exists('payment_counterparty_flow_badge_html')) {
    /**
     * Flow as "Party → Party" with distinct colors per role (admin payment UIs).
     */
    function payment_counterparty_flow_badge_html(string $key): HtmlString
    {
        $pair = payment_counterparty_flow_party_pair($key);
        if ($pair === null) {
            return new HtmlString(
                '<span class="badge bg-secondary">'.e(translate('Company_money_flow_unclassified')).'</span>'
            );
        }
        [$from, $to] = $pair;
        $title = e(translate_payment_counterparty_flow_key($key));
        $inner = payment_counterparty_party_pill_html($from)
            .'<span class="text-muted px-1" aria-hidden="true">→</span>'
            .payment_counterparty_party_pill_html($to);

        return new HtmlString(
            '<span class="d-inline-flex align-items-center flex-nowrap gap-1" title="'.$title.'">'.$inner.'</span>'
        );
    }
}

if (!function_exists('payment_counterparty_flow_arrow_text')) {
    /**
     * Plain "Customer → Company" for exports and non-HTML contexts.
     */
    function payment_counterparty_flow_arrow_text(string $key): string
    {
        $pair = payment_counterparty_flow_party_pair($key);
        if ($pair === null) {
            return translate_payment_counterparty_flow_key($key);
        }
        [$from, $to] = $pair;
        $a = match ($from) {
            'customer' => translate('Customer'),
            'provider' => translate('Provider'),
            'company' => translate('Company'),
            default => '',
        };
        $b = match ($to) {
            'customer' => translate('Customer'),
            'provider' => translate('Provider'),
            'company' => translate('Company'),
            default => '',
        };

        return $a.' → '.$b;
    }
}

if (!function_exists('admin_std_payment_event_from_ledger')) {
    function admin_std_payment_event_from_ledger(LedgerTransaction $entry): object
    {
        $companyFlow = $entry->type === LedgerTransaction::TYPE_IN
            ? Transaction::FLOW_IN
            : Transaction::FLOW_OUT;

        return (object) [
            // Ledger `date` is date-only (midnight); use created_at so Recorded payments shows actual recording time.
            'date' => $entry->created_at ?? $entry->date,
            'booking_id' => $entry->booking_id,
            'booking_readable_id' => $entry->booking?->readable_id ?? $entry->booking_id,
            'company_flow' => $companyFlow,
            'counterparty_flow' => $entry->counterpartyFlowKey(),
            'amount' => (float) $entry->amount,
            'channel' => $entry->formatPaymentMethodForDisplay(),
            'transaction_id' => (string) ($entry->transaction_id ?? ''),
            'source' => 'ledger',
        ];
    }
}

if (!function_exists('admin_std_payment_event_from_cross_party_txn')) {
    function admin_std_payment_event_from_cross_party_txn(Transaction $txn, $bookingRow): object
    {
        return (object) [
            'date' => $txn->created_at,
            'booking_id' => $txn->booking_id,
            'booking_readable_id' => $bookingRow?->readable_id ?? $txn->booking_id,
            'company_flow' => Transaction::FLOW_NONE,
            'counterparty_flow' => 'customer_to_provider',
            'amount' => round(max((float) $txn->debit, (float) $txn->credit), 2),
            'channel' => format_booking_payment_event_channel_label($txn->trx_type),
            'transaction_id' => '',
            'source' => 'transaction',
        ];
    }
}

if (!function_exists('admin_ledger_company_counterparty_for_customer_bookings')) {
    /**
     * @param  array<int, string>  $bookingIds
     */
    function admin_ledger_company_counterparty_for_customer_bookings(array $bookingIds): Collection
    {
        if ($bookingIds === []) {
            return collect();
        }

        return LedgerTransaction::query()
            ->whereIn('booking_id', $bookingIds)
            ->whereCompanyCounterpartyOnly()
            ->with(['booking' => fn ($q) => $q->select('id', 'readable_id')])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get();
    }
}

if (!function_exists('admin_ledger_company_counterparty_for_provider')) {
    /**
     * Provider ledger: company ↔ provider only (money collected from provider, payouts to provider).
     * Excludes customer↔company lines on this provider’s bookings (those belong on booking/customer payment views).
     *
     * @param  array<int, string>  $bookingIds  Unused; kept for call-site compatibility.
     */
    function admin_ledger_company_counterparty_for_provider(string $providerId, array $bookingIds): Collection
    {
        return LedgerTransaction::query()
            ->where('provider_id', $providerId)
            ->whereCompanyProviderCounterpartyOnly()
            ->with([
                'booking' => fn ($q) => $q->select('id', 'readable_id'),
                'repeat' => fn ($q) => $q->select('id', 'readable_id', 'booking_id'),
                'creator' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'email'),
                'bookingPartialPayment' => fn ($q) => $q->select('id', 'paid_with', 'booking_id', 'received_by'),
            ])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get();
    }
}

if (!function_exists('admin_merged_payment_events_for_customer_bookings')) {
    /**
     * Full transaction log: ledger (IN/OUT) + NONE (e.g. customer→provider) + orphan partials.
     *
     * @param  array<int, string>  $bookingIds
     */
    function admin_merged_payment_events_for_customer_bookings(array $bookingIds, Collection $bookingMap): Collection
    {
        if ($bookingIds === []) {
            return collect();
        }

        $rows = collect();

        foreach (admin_ledger_company_counterparty_for_customer_bookings($bookingIds) as $entry) {
            $rows->push(admin_std_payment_event_from_ledger($entry));
        }

        $partialIdsWithCrossPartyTxn = Transaction::query()
            ->whereIn('booking_id', $bookingIds)
            ->where('reference_note', 'like', 'booking_partial_payment:%')
            ->pluck('reference_note')
            ->map(fn ($n) => substr((string) $n, strlen('booking_partial_payment:')))
            ->filter()
            ->all();

        foreach (Transaction::query()
            ->whereIn('booking_id', $bookingIds)
            ->where('trx_type', TRX_TYPE['cross_party_booking_payment'])
            ->orderByDesc('created_at')
            ->get() as $txn) {
            $rows->push(admin_std_payment_event_from_cross_party_txn($txn, $bookingMap->get($txn->booking_id)));
        }

        $ledgerPartialIds = LedgerTransaction::query()
            ->whereIn('booking_id', $bookingIds)
            ->whereNotNull('booking_partial_payment_id')
            ->pluck('booking_partial_payment_id')
            ->all();

        $partials = DB::table('booking_partial_payments')
            ->whereIn('booking_id', $bookingIds)
            ->where('paid_amount', '>', 0)
            ->orderByDesc('created_at')
            ->get();

        foreach ($partials as $partial) {
            if (in_array((string) $partial->id, $partialIdsWithCrossPartyTxn, true)) {
                continue;
            }
            if (in_array((string) $partial->id, array_map('strval', $ledgerPartialIds), true)) {
                continue;
            }
            $receivedBy = $partial->received_by ?: 'company';
            $counterpartyFlow = $receivedBy === 'provider' ? 'customer_to_provider' : 'customer_to_company';
            $companyFlow = $receivedBy === 'provider' ? Transaction::FLOW_NONE : Transaction::FLOW_IN;
            $mapRow = $bookingMap->get($partial->booking_id);
            $rows->push((object) [
                'date' => $partial->created_at,
                'booking_id' => $partial->booking_id,
                'booking_readable_id' => $mapRow?->readable_id ?? $partial->booking_id,
                'company_flow' => $companyFlow,
                'counterparty_flow' => $counterpartyFlow,
                'amount' => (float) $partial->paid_amount,
                'channel' => str_replace('_', ' ', (string) ($partial->paid_with ?? '')),
                'transaction_id' => (string) ($partial->transaction_id ?? ''),
                'source' => 'partial_orphan',
            ]);
        }

        return $rows
            ->filter(fn ($r) => (float) ($r->amount ?? 0) > 0.0001)
            ->sortByDesc(function ($r) {
                $d = $r->date ?? null;
                if ($d instanceof \Carbon\Carbon) {
                    return $d->timestamp;
                }

                return strtotime((string) $d) ?: 0;
            })
            ->values();
    }
}

if (!function_exists('admin_merged_payment_events_for_provider')) {
    /**
     * @param  array<int, string>  $bookingIds
     */
    function admin_merged_payment_events_for_provider(string $providerId, array $bookingIds, Collection $bookingMap): Collection
    {
        $rows = collect();

        foreach (admin_ledger_company_counterparty_for_provider($providerId, $bookingIds) as $entry) {
            $rows->push(admin_std_payment_event_from_ledger($entry));
        }

        if ($bookingIds !== []) {
            $partialIdsWithCrossPartyTxn = Transaction::query()
                ->whereIn('booking_id', $bookingIds)
                ->where('reference_note', 'like', 'booking_partial_payment:%')
                ->pluck('reference_note')
                ->map(fn ($n) => substr((string) $n, strlen('booking_partial_payment:')))
                ->filter()
                ->all();

            foreach (Transaction::query()
                ->whereIn('booking_id', $bookingIds)
                ->where('trx_type', TRX_TYPE['cross_party_booking_payment'])
                ->orderByDesc('created_at')
                ->get() as $txn) {
                $rows->push(admin_std_payment_event_from_cross_party_txn($txn, $bookingMap->get($txn->booking_id)));
            }

            $ledgerPartialIds = LedgerTransaction::query()
                ->whereIn('booking_id', $bookingIds)
                ->whereNotNull('booking_partial_payment_id')
                ->pluck('booking_partial_payment_id')
                ->all();

            $partials = DB::table('booking_partial_payments')
                ->whereIn('booking_id', $bookingIds)
                ->where('paid_amount', '>', 0)
                ->orderByDesc('created_at')
                ->get();

            foreach ($partials as $partial) {
                if (in_array((string) $partial->id, $partialIdsWithCrossPartyTxn, true)) {
                    continue;
                }
                if (in_array((string) $partial->id, array_map('strval', $ledgerPartialIds), true)) {
                    continue;
                }
                $receivedBy = $partial->received_by ?: 'company';
                $counterpartyFlow = $receivedBy === 'provider' ? 'customer_to_provider' : 'customer_to_company';
                $companyFlow = $receivedBy === 'provider' ? Transaction::FLOW_NONE : Transaction::FLOW_IN;
                $mapRow = $bookingMap->get($partial->booking_id);
                $rows->push((object) [
                    'date' => $partial->created_at,
                    'booking_id' => $partial->booking_id,
                    'booking_readable_id' => $mapRow?->readable_id ?? $partial->booking_id,
                    'company_flow' => $companyFlow,
                    'counterparty_flow' => $counterpartyFlow,
                    'amount' => (float) $partial->paid_amount,
                    'channel' => str_replace('_', ' ', (string) ($partial->paid_with ?? '')),
                    'transaction_id' => (string) ($partial->transaction_id ?? ''),
                    'source' => 'partial_orphan',
                ]);
            }
        }

        return $rows
            ->filter(fn ($r) => (float) ($r->amount ?? 0) > 0.0001)
            ->sortByDesc(function ($r) {
                $d = $r->date ?? null;
                if ($d instanceof \Carbon\Carbon) {
                    return $d->timestamp;
                }

                return strtotime((string) $d) ?: 0;
            })
            ->values();
    }
}

if (!function_exists('record_cross_party_booking_partial_transaction')) {
    /**
     * Customer paid provider directly (booking partial). Stored on transactions with company_flow NONE — not on ledger.
     */
    function record_cross_party_booking_partial_transaction(Booking $booking, float $amount, string $partialPaymentId): \Modules\TransactionModule\Entities\Transaction
    {
        $providerUserId = \Modules\ProviderManagement\Entities\Provider::query()
            ->where('id', $booking->provider_id)
            ->value('user_id');
        if (! $providerUserId) {
            throw new \InvalidArgumentException('Provider user not found for booking.');
        }

        return \Modules\TransactionModule\Entities\Transaction::create([
            'ref_trx_id' => null,
            'booking_id' => $booking->id,
            'trx_type' => TRX_TYPE['cross_party_booking_payment'],
            'company_flow' => \Modules\TransactionModule\Entities\Transaction::FLOW_NONE,
            'debit' => $amount,
            'credit' => 0,
            'balance' => 0,
            'from_user_id' => $booking->customer_id,
            'to_user_id' => $providerUserId,
            'reference_note' => 'booking_partial_payment:'.$partialPaymentId,
        ]);
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

if (!function_exists('booking_confirmation_amount_per_service')) {
    function booking_confirmation_amount_per_service(): float
    {
        return max(0.0, (float) (business_config('booking_confirmation_amount_per_service', 'booking_setup')?->live_values ?? 100));
    }
}

if (!function_exists('require_booking_upfront_payment')) {
    function require_booking_upfront_payment(): bool
    {
        return (int) (business_config('require_booking_upfront_payment', 'booking_setup')?->live_values ?? 1) === 1;
    }
}

if (!function_exists('max_wallet_spend_per_transaction')) {
    function max_wallet_spend_per_transaction(): float
    {
        return max(0.0, (float) (business_config('max_wallet_spend_per_transaction', 'customer_config')?->live_values ?? 0));
    }
}

if (!function_exists('wallet_spend_exceeds_per_transaction_limit')) {
    function wallet_spend_exceeds_per_transaction_limit(float $amount): bool
    {
        $max = max_wallet_spend_per_transaction();

        return $max > 0.009 && round(max(0.0, $amount), 2) > round($max, 2);
    }
}

if (!function_exists('cap_wallet_spend_for_single_transaction')) {
    function cap_wallet_spend_for_single_transaction(float $amount): float
    {
        $max = max_wallet_spend_per_transaction();
        $amount = round(max(0.0, $amount), 2);
        if ($max <= 0) {
            return $amount;
        }

        return round(min($amount, $max), 2);
    }
}

if (!function_exists('customer_wallet_feature_enabled')) {
    function customer_wallet_feature_enabled(): bool
    {
        return (int) (business_config('customer_wallet', 'customer_config')?->live_values ?? 0) === 1;
    }
}

if (!function_exists('customer_loyalty_point_feature_enabled')) {
    function customer_loyalty_point_feature_enabled(): bool
    {
        return (int) (business_config('customer_loyalty_point', 'customer_config')?->live_values ?? 0) === 1;
    }
}

if (!function_exists('add_to_fund_wallet_feature_enabled')) {
    function add_to_fund_wallet_feature_enabled(): bool
    {
        return (int) (business_config('add_to_fund_wallet', 'customer_config')?->live_values ?? 0) === 1;
    }
}

if (!function_exists('wallet_payment_feature_enabled')) {
    function wallet_payment_feature_enabled(): bool
    {
        if (! customer_wallet_feature_enabled()) {
            return false;
        }

        $config = business_config('wallet_payment', 'service_setup');
        if (! $config) {
            // Backward compatibility: older installs enabled customer wallet before
            // wallet_payment existed as a separate service_setup toggle.
            return true;
        }

        return (int) ($config->live_values ?? 0) === 1;
    }
}

if (!function_exists('lock_customer_user_for_wallet')) {
    function lock_customer_user_for_wallet(string $userId): \Modules\UserManagement\Entities\User
    {
        $user = \Modules\UserManagement\Entities\User::where('id', $userId)->lockForUpdate()->first();
        if (! $user) {
            throw new \RuntimeException('customer_not_found');
        }

        return $user;
    }
}

if (!function_exists('debit_customer_wallet_or_fail')) {
    function debit_customer_wallet_or_fail(\Modules\UserManagement\Entities\User $user, float $amount, ?string $bookingId = null): \Modules\UserManagement\Entities\User
    {
        $amount = round(max(0.0, $amount), 2);
        if ($amount <= 0) {
            return $user;
        }
        if ((float) $user->wallet_balance < $amount) {
            throw new \RuntimeException('insufficient_wallet_balance');
        }
        $user->wallet_balance = round((float) $user->wallet_balance - $amount, 2);
        $user->save();

        send_customer_wallet_deducted_notification($user, $amount, $bookingId);

        return $user;
    }
}

if (!function_exists('credit_customer_wallet')) {
    function credit_customer_wallet(\Modules\UserManagement\Entities\User $user, float $amount): \Modules\UserManagement\Entities\User
    {
        $amount = round(max(0.0, $amount), 2);
        if ($amount <= 0) {
            return $user;
        }
        $user->wallet_balance = round((float) $user->wallet_balance + $amount, 2);
        $user->save();

        return $user;
    }
}

if (!function_exists('debit_customer_loyalty_points_or_fail')) {
    function debit_customer_loyalty_points_or_fail(\Modules\UserManagement\Entities\User $user, float $points): \Modules\UserManagement\Entities\User
    {
        $points = round(max(0.0, $points), 2);
        if ((float) $user->loyalty_point < $points) {
            throw new \RuntimeException('insufficient_loyalty_points');
        }
        $user->loyalty_point = round((float) $user->loyalty_point - $points, 2);
        $user->save();

        return $user;
    }
}

if (!function_exists('booking_confirmation_units_for_cart')) {
  /**
   * Distinct cart service lines (line quantity does not multiply confirmation units).
   */
    function booking_confirmation_units_for_cart(string $customerUserId): int
    {
        return max(0, (int) \Modules\CartModule\Entities\Cart::query()
            ->where('customer_id', $customerUserId)
            ->where('quantity', '>', 0)
            ->count());
    }
}

if (!function_exists('booking_confirmation_units_for_cart_items')) {
    function booking_confirmation_units_for_cart_items(iterable $cartItems): int
    {
        $units = 0;
        foreach ($cartItems as $item) {
            if (max(0, (int) ($item->quantity ?? 0)) > 0) {
                $units += 1;
            }
        }

        return $units;
    }
}

if (!function_exists('booking_confirmation_amount_for_customer')) {
    function booking_confirmation_amount_for_customer(string $customerUserId): float
    {
        $units = booking_confirmation_units_for_cart($customerUserId);
        if ($units <= 0) {
            return 0.0;
        }

        return round(booking_confirmation_amount_per_service() * $units, 2);
    }
}

if (!function_exists('booking_full_checkout_amount_for_customer')) {
    function booking_full_checkout_amount_for_customer(string $customerUserId): float
    {
        return round((float) cart_total($customerUserId) + (float) getServiceFee($customerUserId), 2);
    }
}

if (!function_exists('resolve_checkout_payment_amount')) {
    /**
     * @param  string  $paymentAmountType  confirmation|full
     */
    function resolve_checkout_payment_amount(string $customerUserId, string $paymentAmountType): float
    {
        $full = booking_full_checkout_amount_for_customer($customerUserId);
        if ($paymentAmountType === 'confirmation') {
            return min($full, booking_confirmation_amount_for_customer($customerUserId));
        }

        return $full;
    }
}

if (!function_exists('wallet_checkout_debit_amount')) {
    /**
     * Wallet amount to validate against max spend per transaction at booking time.
     * Uses confirmation/advance when customer pays booking confirmation only.
     */
    function wallet_checkout_debit_amount(
        string $userId,
        $request,
        iterable $cartItemsForBooking,
        float $lineTotalBookingAmount,
        bool $isPartial,
        float $customerWalletBalance
    ): float {
        if ($isPartial) {
            return cap_wallet_spend_for_single_transaction($customerWalletBalance);
        }

        $paymentAmountType = is_array($request)
            ? ($request['payment_amount_type'] ?? 'full')
            : ($request->payment_amount_type ?? 'full');

        if ($paymentAmountType === 'confirmation' && require_booking_upfront_payment()) {
            $units = booking_confirmation_units_for_cart_items($cartItemsForBooking);

            return round(min($lineTotalBookingAmount, booking_confirmation_amount_per_service() * max(1, $units)), 2);
        }

        return round(max(0.0, $lineTotalBookingAmount), 2);
    }
}

if (!function_exists('map_booking_payment_paid_with')) {
    function map_booking_payment_paid_with(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'wallet_payment' => 'wallet',
            'offline_payment' => 'offline',
            default => 'digital',
        };
    }
}

if (!function_exists('wallet_transaction_booking_reference_note')) {
    function wallet_transaction_booking_reference_note($booking): ?string
    {
        if ($booking === null) {
            return null;
        }

        $readableId = '';
        if ($booking instanceof Booking) {
            $readableId = trim((string) ($booking->readable_id ?? ''));
        } elseif (is_array($booking)) {
            $readableId = trim((string) ($booking['readable_id'] ?? ''));
            if ($readableId === '' && ! empty($booking['id'])) {
                $readableId = trim((string) (Booking::query()->whereKey($booking['id'])->value('readable_id') ?? ''));
            }
        } else {
            $readableId = trim((string) ($booking->readable_id ?? ''));
        }

        if ($readableId === '') {
            return null;
        }

        return translate('Booking') . ' #' . $readableId;
    }
}

if (!function_exists('placeBookingTransactionForAdvanceDeposit')) {
    /**
     * Record customer advance (booking confirmation) payment — not the full booking total.
     */
    function placeBookingTransactionForAdvanceDeposit($booking, float $paidAmount, string $paymentMethod, ?string $partialPaymentId = null): void
    {
        $paidAmount = round(max(0.0, $paidAmount), 2);
        if ($paidAmount <= 0) {
            return;
        }
        if ($paymentMethod === 'wallet_payment' && wallet_spend_exceeds_per_transaction_limit($paidAmount)) {
            throw new \RuntimeException('wallet_max_spend_per_transaction');
        }

        $adminUserId = \Modules\UserManagement\Entities\User::where('user_type', ADMIN_USER_TYPES[0])->first()->id;

        DB::transaction(function () use ($booking, $paidAmount, $paymentMethod, $adminUserId, $partialPaymentId) {
            $account = \Modules\TransactionModule\Entities\Account::where('user_id', $adminUserId)->first();
            $account->balance_pending += $paidAmount;
            $account->save();

            \Modules\TransactionModule\Entities\Transaction::create([
                'ref_trx_id' => null,
                'booking_id' => $booking->id,
                'trx_type' => TRX_TYPE['booking_amount'],
                'company_flow' => \Modules\TransactionModule\Entities\Transaction::FLOW_IN,
                'debit' => 0,
                'credit' => $paidAmount,
                'balance' => $account->balance_pending,
                'from_user_id' => $booking->customer_id,
                'to_user_id' => $adminUserId,
                'from_user_account' => null,
                'to_user_account' => ACCOUNT_STATES[0]['value'],
                'is_guest' => $booking->is_guest,
            ]);

            $partialTransactionId = trim((string) ($booking->transaction_id ?? '')) ?: null;

            if ($paymentMethod === 'wallet_payment') {
                $user = lock_customer_user_for_wallet((string) $booking->customer_id);
                $user = debit_customer_wallet_or_fail($user, $paidAmount, (string) $booking->id);

                $walletTransaction = \Modules\TransactionModule\Entities\Transaction::create([
                    'ref_trx_id' => null,
                    'booking_id' => $booking->id,
                    'trx_type' => WALLET_TRX_TYPE['wallet_payment'],
                    'company_flow' => \Modules\TransactionModule\Entities\Transaction::FLOW_NONE,
                    'debit' => $paidAmount,
                    'credit' => 0,
                    'balance' => $user?->wallet_balance ?? 0,
                    'from_user_id' => $booking->customer_id,
                    'to_user_id' => $booking->customer_id,
                    'from_user_account' => null,
                    'to_user_account' => 'user_wallet',
                    'is_guest' => $booking->is_guest,
                    'reference_note' => wallet_transaction_booking_reference_note($booking),
                ]);
                $partialTransactionId = (string) $walletTransaction->id;
            }

            if ($partialPaymentId && $partialTransactionId) {
                BookingPartialPayment::query()
                    ->whereKey($partialPaymentId)
                    ->update(['transaction_id' => $partialTransactionId]);
            }

            ledger_record_in([
                'amount' => $paidAmount,
                'transaction_id' => $partialTransactionId,
                'booking_id' => $booking->id,
                'payment_method' => $paymentMethod === 'wallet_payment' ? 'wallet_payment' : ($paymentMethod === 'offline_payment' ? 'offline_payment' : 'digital_payment'),
                'date' => now()->toDateString(),
                'received_by' => LedgerTransaction::RECEIVED_BY_COMPANY,
                'booking_partial_payment_id' => $partialPaymentId,
            ]);
        });
    }
}

if (!function_exists('distribute_checkout_amount_equally')) {
    /**
     * Split a cart-level amount equally across multiple booking lines (remainder cents go to first lines).
     */
    function distribute_checkout_amount_equally(float $totalAmount, int $totalUnits, int $unitIndex): float
    {
        $totalUnits = max(1, $totalUnits);
        $unitIndex = max(0, min($unitIndex, $totalUnits - 1));
        $totalCents = (int) round(max(0.0, $totalAmount) * 100);
        $quotientCents = intdiv($totalCents, $totalUnits);
        $remainderCents = $totalCents % $totalUnits;
        $unitCents = $quotientCents + ($unitIndex < $remainderCents ? 1 : 0);

        return round($unitCents / 100, 2);
    }
}

if (!function_exists('resolve_checkout_wallet_digital_split')) {
    /**
     * Split checkout payment between wallet and digital for one booking line.
     * When $multiBookingSplit is set (multi-booking cart checkout), cart-level wallet and digital
     * totals are divided equally across each service line.
     *
     * @param  array{wallet_total: float, digital_total: float, total_bookings: int, booking_index: int}|null  $multiBookingSplit
     * @return array{wallet_paid: float, digital_paid: float, checkout_paid: float}
     */
    function resolve_checkout_wallet_digital_split(
        $request,
        float $checkoutPayable,
        float $customerWalletBalance,
        ?array $multiBookingSplit = null
    ): array {
        $checkoutPayable = round(max(0.0, $checkoutPayable), 2);

        if (is_array($multiBookingSplit)) {
            $totalBookings = max(1, (int) ($multiBookingSplit['total_bookings'] ?? 1));
            $bookingIndex = max(0, min((int) ($multiBookingSplit['booking_index'] ?? 0), $totalBookings - 1));
            $walletShare = distribute_checkout_amount_equally(
                (float) ($multiBookingSplit['wallet_total'] ?? 0),
                $totalBookings,
                $bookingIndex
            );
            $digitalShare = distribute_checkout_amount_equally(
                (float) ($multiBookingSplit['digital_total'] ?? 0),
                $totalBookings,
                $bookingIndex
            );

            $walletPaid = round(min($checkoutPayable, max(0.0, $walletShare)), 2);
            $digitalPaid = round(min(
                max(0.0, $checkoutPayable - $walletPaid),
                max(0.0, $digitalShare)
            ), 2);

            return [
                'wallet_paid' => $walletPaid,
                'digital_paid' => $digitalPaid,
                'checkout_paid' => round($walletPaid + $digitalPaid, 2),
            ];
        }

        $walletPaid = round((float) ($request['wallet_paid_amount'] ?? 0), 2);
        $digitalPaid = round((float) ($request['digitally_paid_amount'] ?? ($request['verified_checkout_amount'] ?? 0)), 2);

        if ($walletPaid <= 0 && $digitalPaid <= 0) {
            $walletPaid = round(min(
                cap_wallet_spend_for_single_transaction((float) $customerWalletBalance),
                $checkoutPayable
            ), 2);
            $digitalPaid = round(max(0.0, $checkoutPayable - $walletPaid), 2);
        } elseif ($walletPaid <= 0) {
            $digitalPaid = round(min($checkoutPayable, max(0.0, $digitalPaid)), 2);
            $walletPaid = round(max(0.0, $checkoutPayable - $digitalPaid), 2);
        } else {
            $walletPaid = round(min($checkoutPayable, max(0.0, $walletPaid)), 2);
            if ($digitalPaid <= 0) {
                $digitalPaid = round(max(0.0, $checkoutPayable - $walletPaid), 2);
            } else {
                $digitalPaid = round(min(max(0.0, $checkoutPayable - $walletPaid), $digitalPaid), 2);
            }
        }

        return [
            'wallet_paid' => $walletPaid,
            'digital_paid' => $digitalPaid,
            'checkout_paid' => round($walletPaid + $digitalPaid, 2),
        ];
    }
}

if (!function_exists('record_checkout_wallet_digital_partial_payments')) {
    function record_checkout_wallet_digital_partial_payments(
        Booking $booking,
        $request,
        float $totalBookingAmount,
        float $checkoutPayable,
        float $customerWalletBalance,
        ?array $multiBookingSplit = null
    ): void {
        $split = resolve_checkout_wallet_digital_split(
            $request,
            $checkoutPayable,
            $customerWalletBalance,
            $multiBookingSplit
        );
        $walletPaid = $split['wallet_paid'];
        $digitalPaid = $split['digital_paid'];
        $checkoutPaid = $split['checkout_paid'];

        if ($walletPaid > 0) {
            BookingPartialPayment::create([
                'booking_id' => $booking->id,
                'paid_with' => 'wallet',
                'paid_amount' => $walletPaid,
                'due_amount' => round(max(0.0, $checkoutPayable - $walletPaid), 2),
                'received_by' => 'company',
            ]);
        }

        if ($digitalPaid > 0 && ($request['payment_method'] ?? '') !== 'cash_after_service') {
            BookingPartialPayment::create([
                'booking_id' => $booking->id,
                'paid_with' => map_booking_payment_paid_with((string) ($request['payment_method'] ?? '')),
                'paid_amount' => $digitalPaid,
                'due_amount' => round(max(0.0, $totalBookingAmount - $checkoutPaid), 2),
                'received_by' => 'company',
            ]);
        }
    }
}

if (!function_exists('record_booking_advance_checkout_payment')) {
    /**
     * After booking create: store advance partial row + ledger when customer paid confirmation only.
     */
    function record_booking_advance_checkout_payment($booking, iterable $cartItems, $request, float $totalBookingAmount, ?float $verifiedPaidAmount = null): void
    {
        $units = booking_confirmation_units_for_cart_items($cartItems);
        $computedAdvance = round(min($totalBookingAmount, booking_confirmation_amount_per_service() * max(1, $units)), 2);
        $verifiedPaidAmount = $verifiedPaidAmount !== null ? round(max(0.0, $verifiedPaidAmount), 2) : 0.0;
        $advancePaid = $verifiedPaidAmount > 0
            ? round(min($totalBookingAmount, $verifiedPaidAmount), 2)
            : $computedAdvance;
        $dueAmount = round(max(0.0, $totalBookingAmount - $advancePaid), 2);

        $paidWith = map_booking_payment_paid_with((string) $request['payment_method']);

        $partial = BookingPartialPayment::create([
            'booking_id' => $booking->id,
            'paid_with' => $paidWith,
            'paid_amount' => $advancePaid,
            'due_amount' => $dueAmount,
            'received_by' => 'company',
        ]);

        placeBookingTransactionForAdvanceDeposit($booking, $advancePaid, (string) $request['payment_method'], (string) $partial->id);
    }
}

if (!function_exists('record_booking_full_checkout_partial_if_needed')) {
    /**
     * Full upfront checkout: ensure a partial row exists so payment history lists the payment.
     */
    function record_booking_full_checkout_partial_if_needed($booking, $request): ?BookingPartialPayment
    {
        if (! $booking instanceof Booking) {
            return null;
        }

        $booking->loadMissing('booking_partial_payments');
        if ($booking->booking_partial_payments->isNotEmpty()) {
            return null;
        }

        $payableTotal = round((float) get_booking_total_amount($booking), 2);
        if ($payableTotal <= 0) {
            return null;
        }

        return BookingPartialPayment::create([
            'booking_id' => $booking->id,
            'paid_with' => map_booking_payment_paid_with((string) ($request['payment_method'] ?? $booking->payment_method ?? '')),
            'paid_amount' => $payableTotal,
            'due_amount' => 0,
            'received_by' => 'company',
        ]);
    }
}

if (!function_exists('finalize_booking_checkout_transactions')) {
    function finalize_booking_checkout_transactions($booking, iterable $cartItems, $request, float $totalBookingAmount): void
    {
        $paymentAmountType = $request['payment_amount_type'] ?? 'full';

        if ($paymentAmountType === 'confirmation') {
            $booking->loadMissing('booking_partial_payments');
            if ($booking->booking_partial_payments->contains(fn ($partial) => ($partial->paid_with ?? '') === 'wallet')) {
                placeBookingTransactionForPartialDigital($booking);

                return;
            }

            $verified = round((float) ($request['verified_checkout_amount'] ?? 0), 2);
            record_booking_advance_checkout_payment(
                $booking,
                $cartItems,
                $request,
                $totalBookingAmount,
                $verified > 0 ? $verified : null
            );

            return;
        }

        if ($booking->booking_partial_payments->isNotEmpty()) {
            if ($booking['payment_method'] == 'cash_after_service') {
                placeBookingTransactionForPartialCas($booking);
            } elseif ($booking['payment_method'] != 'wallet_payment') {
                placeBookingTransactionForPartialDigital($booking);
            }

            return;
        }

        record_booking_full_checkout_partial_if_needed($booking, $request);

        if ($booking['payment_method'] != 'cash_after_service' && $booking['payment_method'] != 'wallet_payment') {
            placeBookingTransactionForDigitalPayment($booking);
        } elseif ($booking['payment_method'] != 'cash_after_service') {
            placeBookingTransactionForWalletPayment($booking);
        }
    }
}

if (! function_exists('booking_attach_api_change_logs')) {
    /**
     * Attach audit change logs for customer/provider mobile API responses.
     *
     * @param  Booking|BookingRepeat  $booking
     */
    function booking_attach_api_change_logs($booking, ?string $repeatId = null): void
    {
        if (! class_exists(\Modules\BookingModule\Entities\BookingChangeLog::class)) {
            return;
        }

        $parentBookingId = $booking instanceof BookingRepeat
            ? (string) ($booking->booking_id ?? '')
            : (string) ($booking->id ?? '');

        if ($parentBookingId === '') {
            return;
        }

        $query = \Modules\BookingModule\Entities\BookingChangeLog::query()
            ->with('changedBy')
            ->where('booking_id', $parentBookingId);

        if ($repeatId !== null && $repeatId !== '') {
            $repeatContext = 'booking_repeat:' . $repeatId;
            $query->where(function ($q) use ($repeatContext) {
                $q->whereNull('context')
                    ->orWhere('context', $repeatContext)
                    ->orWhere('context', 'like', 'booking_repeat_detail:%')
                    ->orWhere('property_key', 'like', 'repeat.%')
                    ->orWhere('property_key', 'like', 'booking_repeat_detail.%');
            });
            $logs = $query->orderByDesc('created_at')->get();
        } elseif ($booking->relationLoaded('change_logs')) {
            $logs = $booking->change_logs->sortByDesc('created_at')->values();
        } else {
            $logs = $query->orderByDesc('created_at')->get();
        }

        $booking->setRelation(
            'change_logs',
            $logs
                ->filter(fn ($log) => ! booking_change_log_hide_from_mobile_api($log))
                ->map(function ($log) {
                $log->event_title = booking_change_log_mobile_title((string) $log->property_key);
                $log->event_description = booking_change_log_mobile_description($log);
                $log->event_type = booking_change_log_mobile_event_type((string) $log->property_key);

                return $log;
            })
        );
    }
}

if (! function_exists('booking_change_log_hide_from_mobile_api')) {
    /**
     * Hide internal reopen bookkeeping rows from customer/provider history.
     */
    function booking_change_log_hide_from_mobile_api(\Modules\BookingModule\Entities\BookingChangeLog $log): bool
    {
        $key = (string) ($log->property_key ?? '');
        if ($key === 'booking.reopened') {
            return false;
        }

        $new = trim((string) ($log->new_value ?? ''));
        $old = trim((string) ($log->old_value ?? ''));
        $combined = $new . ' ' . $old;

        if (! booking_change_log_value_is_meaningful($old) && ! booking_change_log_value_is_meaningful($new)) {
            return true;
        }

        if ($key !== 'booking.updated.other') {
            return false;
        }

        if ($new !== '' && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $new)) {
            return true;
        }

        if (preg_match('/\b(reopened_by|last_reopen_event_at|reopen_resolved_at|reopen_resolved_by|reopen_resolve_remarks|reopen_completion_allowed|originated_from_booking_id)\b/i', $combined)) {
            return true;
        }

        return false;
    }
}

if (! function_exists('booking_change_log_value_is_meaningful')) {
    function booking_change_log_value_is_meaningful(?string $value, int $depth = 0): bool
    {
        if ($depth > 25) {
            return true;
        }

        $v = trim((string) ($value ?? ''));
        if ($v === '') {
            return false;
        }

        if (preg_match('/^[\s,;—\-–−‐‑‒―]+$/u', $v)) {
            return false;
        }

        if (str_contains($v, ':')) {
            $segments = preg_split('/;\s*/', $v) ?: [];
            $hasMeaningfulSegment = false;
            foreach ($segments as $segment) {
                $segment = trim($segment);
                if ($segment === '') {
                    continue;
                }
                if (preg_match('/^([^:]+):\s*(.+)$/u', $segment, $matches)) {
                    if (booking_change_log_value_is_meaningful($matches[2], $depth + 1)) {
                        $hasMeaningfulSegment = true;
                        break;
                    }
                    continue;
                }
                if (booking_change_log_value_is_meaningful($segment, $depth + 1)) {
                    $hasMeaningfulSegment = true;
                    break;
                }
            }

            return $hasMeaningfulSegment;
        }

        return true;
    }
}

if (! function_exists('booking_change_log_mobile_title')) {
    function booking_change_log_mobile_title(string $propertyKey): string
    {
        return match (true) {
            $propertyKey === 'booking.created' => translate('Booking_created'),
            $propertyKey === 'booking.reopened' => translate('Booking_reopened'),
            str_starts_with($propertyKey, 'booking.updated.status') => translate('Booking_status_update'),
            str_starts_with($propertyKey, 'repeat.') && str_contains($propertyKey, 'status') => translate('Booking_status_update'),
            str_starts_with($propertyKey, 'booking.updated.schedule') => translate('Booking_schedule_change'),
            str_starts_with($propertyKey, 'repeat.') && str_contains($propertyKey, 'schedule') => translate('Booking_schedule_change'),
            str_starts_with($propertyKey, 'booking.updated.assignment') => translate('Booking_provider_change'),
            str_starts_with($propertyKey, 'booking.updated.payment') => translate('Payment_update'),
            str_starts_with($propertyKey, 'booking_detail.created') => translate('Service_added'),
            str_starts_with($propertyKey, 'booking_detail.deleted') => translate('Service_removed'),
            str_starts_with($propertyKey, 'booking_detail.updated') => translate('Service_updated'),
            str_starts_with($propertyKey, 'booking_repeat_detail.updated') => translate('Service_updated'),
            str_starts_with($propertyKey, 'booking_repeat_detail.created') => translate('Service_added'),
            str_starts_with($propertyKey, 'booking_repeat_detail.deleted') => translate('Service_removed'),
            str_starts_with($propertyKey, 'booking_extra_service.created') => translate('Extra_service_added'),
            str_starts_with($propertyKey, 'booking_extra_service.deleted') => translate('Extra_service_removed'),
            str_starts_with($propertyKey, 'booking_extra_service.updated') => translate('Extra_service_updated'),
            default => translate('Booking_updated'),
        };
    }
}

if (! function_exists('booking_change_log_mobile_event_type')) {
    function booking_change_log_mobile_event_type(string $propertyKey): string
    {
        return match (true) {
            str_contains($propertyKey, 'status') => 'status',
            str_contains($propertyKey, 'schedule') => 'schedule',
            str_contains($propertyKey, 'assignment') || str_contains($propertyKey, 'provider') || str_contains($propertyKey, 'serviceman') => 'provider',
            str_contains($propertyKey, 'payment') => 'payment',
            str_contains($propertyKey, 'detail') || str_contains($propertyKey, 'extra_service') || str_contains($propertyKey, 'service') => 'service',
            default => 'other',
        };
    }
}

if (! function_exists('booking_change_log_mobile_description')) {
    function booking_change_log_mobile_description(\Modules\BookingModule\Entities\BookingChangeLog $log): ?string
    {
        $key = (string) $log->property_key;
        $new = booking_change_log_clean_value($log->new_value);
        $old = booking_change_log_clean_value($log->old_value);

        if (! booking_change_log_value_is_meaningful($old) && ! booking_change_log_value_is_meaningful($new)) {
            return null;
        }

        if (str_ends_with($key, '.deleted') || $key === '_deleted') {
            return booking_change_log_humanize_text($old);
        }
        if (str_ends_with($key, '.created') || $key === 'booking.created') {
            $text = booking_change_log_humanize_text($new) ?? $new;

            return booking_change_log_is_garbage_text($text) ? null : $text;
        }

        if (str_starts_with($key, 'booking_detail.updated')
            || str_starts_with($key, 'booking_repeat_detail.updated')
            || str_starts_with($key, 'booking_extra_service.updated')) {
            $summary = \Modules\BookingModule\Services\BookingAuditLogger::resolveServiceSummaryForDisplay($log);
            if ($summary !== null && $summary !== '') {
                return $summary;
            }
        }

        $text = booking_change_log_humanize_text($new ?? $old);

        return booking_change_log_is_garbage_text($text) ? null : $text;
    }
}

if (! function_exists('booking_change_log_clean_value')) {
    function booking_change_log_clean_value(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);

        return ($text === '' || $text === '—') ? null : $text;
    }
}

if (! function_exists('booking_change_log_humanize_text')) {
    function booking_change_log_humanize_text(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $segments = preg_split('/;\s*/', $text) ?: [];
        $humanized = [];

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            if (preg_match('/^([^:]+):\s*(.+)$/u', $segment, $matches)) {
                $fieldRaw = trim($matches[1]);
                if (! preg_match('/[a-zA-Z]/', $fieldRaw)) {
                    continue;
                }
                $field = strtolower(str_replace(' ', '_', $fieldRaw));
                $value = trim($matches[2]);

                if (in_array($field, ['evidence_photos', 'service_address_location', 'additional_charges_breakdown'], true)) {
                    continue;
                }

                if (preg_match('/^json\s*\(/i', $value)) {
                    continue;
                }

                if (in_array($field, ['booking_status', 'status'], true)) {
                    $humanized[] = translate(str_replace(' ', '_', strtolower($value)));
                    continue;
                }

                if (in_array($field, ['provider_id', 'serviceman_id', 'assignee_id'], true)) {
                    $humanized[] = $value;
                    continue;
                }

                if ($field === 'service_schedule') {
                    try {
                        $humanized[] = \Carbon\Carbon::parse($value)->format('d-M-Y h:ia');
                    } catch (\Throwable) {
                        $humanized[] = $value;
                    }
                    continue;
                }

                if (in_array($field, ['service_id'], true)) {
                    continue;
                }

                if (preg_match('/_(amount|cost|fee|charge|discount|tax)$/', $field) || in_array($field, ['quantity', 'is_paid'], true)) {
                    continue;
                }

                $humanized[] = $value;
                continue;
            }

            if (preg_match('/^json\s*\(/i', $segment) || preg_match('/^[\d.,\s]+$/', $segment)) {
                continue;
            }

            $humanized[] = booking_change_log_translate_tokens($segment);
        }

        $result = trim(implode('; ', array_filter($humanized)));

        if ($result === '' || booking_change_log_is_garbage_text($result)) {
            return null;
        }

        return $result;
    }
}

if (! function_exists('booking_change_log_is_garbage_text')) {
    function booking_change_log_is_garbage_text(?string $text): bool
    {
        if ($text === null) {
            return true;
        }
        $text = trim($text);
        if ($text === '' || ! booking_change_log_value_is_meaningful($text)) {
            return true;
        }
        if (preg_match('/^json\s*\(\d+\)$/i', $text)) {
            return true;
        }
        if (preg_match('/^[\d.,;\s]+$/', $text)) {
            return true;
        }
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $text)) {
            return true;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{1,2}:\d{2}/', $text)) {
            return true;
        }

        return false;
    }
}

if (! function_exists('booking_change_log_translate_tokens')) {
    function booking_change_log_translate_tokens(string $text): string
    {
        $replacements = [
            'pending' => translate('pending'),
            'accepted' => translate('accepted'),
            'ongoing' => translate('ongoing'),
            'completed' => translate('completed'),
            'canceled' => translate('canceled'),
            'on_hold' => translate('on_hold'),
            'paid' => translate('paid'),
            'unpaid' => translate('unpaid'),
        ];

        foreach ($replacements as $token => $label) {
            $text = preg_replace('/\b'.preg_quote($token, '/').'\b/i', $label, $text);
        }

        return str_replace('_', ' ', $text);
    }
}

if (! function_exists('booking_prepare_mobile_api_user_for_json')) {
    /**
     * Avoid expensive User appends (especially identification_image_full_path) on nested API payloads.
     *
     * @param  \Modules\UserManagement\Entities\User|null  $user
     */
    function booking_prepare_mobile_api_user_for_json($user, bool $withProfileImage = false): void
    {
        if ($user === null) {
            return;
        }

        if ($withProfileImage) {
            $user->loadMissing('storage');
            $user->setAppends(['profile_image_full_path']);
        } else {
            $user->setAppends([]);
        }
    }
}

if (! function_exists('booking_prepare_provider_api_booking_for_json')) {
    /**
     * Trim heavy Eloquent appends before serializing provider booking detail API responses.
     */
    function booking_prepare_provider_api_booking_for_json(Booking $booking): void
    {
        booking_prepare_mobile_api_user_for_json($booking->customer, true);

        if ($booking->relationLoaded('serviceman') && $booking->serviceman?->relationLoaded('user')) {
            booking_prepare_mobile_api_user_for_json($booking->serviceman->user, true);
        }

        if ($booking->relationLoaded('provider') && $booking->provider) {
            $booking->provider->setAppends([]);
        }

        if ($booking->relationLoaded('status_histories')) {
            foreach ($booking->status_histories as $history) {
                if ($history->relationLoaded('user')) {
                    booking_prepare_mobile_api_user_for_json($history->user, false);
                }
            }
        }

        if ($booking->relationLoaded('schedule_histories')) {
            foreach ($booking->schedule_histories as $history) {
                if ($history->relationLoaded('user')) {
                    booking_prepare_mobile_api_user_for_json($history->user, false);
                }
            }
        }

        if ($booking->relationLoaded('change_logs')) {
            foreach ($booking->change_logs as $log) {
                if ($log->relationLoaded('changedBy')) {
                    booking_prepare_mobile_api_user_for_json($log->changedBy, false);
                }
            }
        }
    }
}

if (! function_exists('booking_prepare_provider_api_repeat_for_json')) {
    /**
     * Trim heavy Eloquent appends before serializing provider repeat-booking detail API responses.
     */
    function booking_prepare_provider_api_repeat_for_json(BookingRepeat $repeat): void
    {
        if ($repeat->relationLoaded('serviceman') && $repeat->serviceman?->relationLoaded('user')) {
            booking_prepare_mobile_api_user_for_json($repeat->serviceman->user, true);
        }

        if ($repeat->relationLoaded('booking') && $repeat->booking) {
            if ($repeat->booking->relationLoaded('customer')) {
                booking_prepare_mobile_api_user_for_json($repeat->booking->customer, true);
            }
            if ($repeat->booking->relationLoaded('provider') && $repeat->booking->provider) {
                $repeat->booking->provider->setAppends([]);
            }
        }

        foreach (['statusHistories', 'scheduleHistories'] as $relation) {
            if (! $repeat->relationLoaded($relation)) {
                continue;
            }
            foreach ($repeat->{$relation} as $history) {
                if ($history->relationLoaded('user')) {
                    booking_prepare_mobile_api_user_for_json($history->user, false);
                }
            }
        }

        if ($repeat->relationLoaded('change_logs')) {
            foreach ($repeat->change_logs as $log) {
                if ($log->relationLoaded('changedBy')) {
                    booking_prepare_mobile_api_user_for_json($log->changedBy, false);
                }
            }
        }
    }
}
