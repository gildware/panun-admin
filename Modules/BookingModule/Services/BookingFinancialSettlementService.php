<?php

namespace Modules\BookingModule\Services;

use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Entities\SubscriptionBookingType;
use Modules\ProviderManagement\Services\CustomerLossMakingSettlementPenaltyService;

class BookingFinancialSettlementService
{
    public const OUTCOME_STANDARD = 'standard';

    public const OUTCOME_VISIT_FEE_SPLIT = 'visit_fee_split';

    public const OUTCOME_CUSTOM_COMMISSION = 'custom_commission';

    public const OUTCOME_SCALED_TO_PAYMENTS = 'scaled_to_payments';

    public const OUTCOME_VISIT_RETAINED_CANCEL = 'visit_retained_cancel';

    /**
     * Visit + optional closing “decided charges” model (cancel-after-visit vs complete-visit-only).
     */
    public static function outcomeUsesDecidedVisitCharges(?string $outcome): bool
    {
        $o = trim((string) ($outcome ?? ''));

        return $o === self::OUTCOME_VISIT_RETAINED_CANCEL || $o === self::OUTCOME_VISIT_FEE_SPLIT;
    }

    /**
     * Full list including standard (reports, badges, validation allow-list).
     */
    public static function outcomeOptions(): array
    {
        return [
            self::OUTCOME_STANDARD => translate('Bfs_label_standard'),
            self::OUTCOME_VISIT_RETAINED_CANCEL => translate('Bfs_label_cancel_keep_visit'),
            self::OUTCOME_VISIT_FEE_SPLIT => translate('Bfs_label_complete_visit_only'),
            self::OUTCOME_CUSTOM_COMMISSION => translate('Bfs_label_custom_commission'),
            self::OUTCOME_SCALED_TO_PAYMENTS => translate('Bfs_label_scaled_partial_or_bad_debt'),
        ];
    }

    /**
     * Admin “Configure special scenarios” modal only — excludes plain standard (normal commission).
     */
    public static function outcomeOptionsForSpecialScenariosModal(): array
    {
        $opts = self::outcomeOptions();
        unset($opts[self::OUTCOME_STANDARD], $opts[self::OUTCOME_CUSTOM_COMMISSION]);

        return $opts;
    }

    /**
     * Admin “Special Scenario Bookings” tabs: query key => settlement_outcome (null = all non-empty special outcomes).
     *
     * @return array<string, string|null>
     */
    public static function specialScenarioListTabOutcomes(): array
    {
        return [
            'all' => null,
            'loss_making' => self::OUTCOME_SCALED_TO_PAYMENTS,
            'cancelled_after_visit' => self::OUTCOME_VISIT_RETAINED_CANCEL,
            'little_or_no_service' => self::OUTCOME_VISIT_FEE_SPLIT,
        ];
    }

    public function defaultVisitCompanyPercent(): float
    {
        $cfg = (float) config('booking_financial.default_visit_fee_company_percent', 10);

        return max(0.0, min(100.0, $cfg));
    }

    /**
     * Gross company commission from standard tier rules (before admin promo deductions).
     * Used to prefill “custom commission for this booking only” so admins start from the default then override.
     */
    public function defaultTierAdminCommissionForBooking(Booking $booking): float
    {
        if (SubscriptionBookingType::where('booking_id', $booking->id)->where('type', 'subscription')->exists()) {
            return 0.0;
        }

        $base = calculate_commission_for_booking($booking, $booking->provider_id);

        return round((float) ($base['commission'] ?? 0), 2);
    }

    /**
     * Parent booking for repeat rows (settlement is stored on parent only).
     */
    public function mainBookingFor(Booking|BookingRepeat $booking): Booking
    {
        if ($booking instanceof BookingRepeat) {
            return $booking->booking ?? Booking::query()->findOrFail($booking->booking_id);
        }

        return $booking;
    }

    public function usesNonStandardSettlement(Booking|BookingRepeat $booking): bool
    {
        $main = $this->mainBookingFor($booking);
        $o = trim((string) ($main->settlement_outcome ?? ''));

        return $o !== '' && $o !== self::OUTCOME_STANDARD;
    }

    /**
     * @return array{adminCommission: float, adminCommissionWithoutCost: float}
     */
    public function calculateAdminCommissionDetails($booking, int|string|null $providerId = null): array
    {
        $main = $this->mainBookingFor($booking);
        $outcome = trim((string) ($main->settlement_outcome ?? ''));
        if ($outcome === '') {
            $outcome = self::OUTCOME_STANDARD;
        }
        $config = is_array($main->settlement_config) ? $main->settlement_config : [];

        if (isset($booking->booking_id)) {
            $bookingId = $booking->booking_id;
            $bookingDetailsAmounts = BookingDetailsAmount::where('booking_repeat_id', $booking->id)->get();
        } else {
            $bookingId = $booking->id;
            $bookingDetailsAmounts = BookingDetailsAmount::where('booking_id', $booking->id)->get();
        }

        $subscriptionType = SubscriptionBookingType::where('booking_id', $bookingId)->where('type', 'subscription')->first();
        if ($subscriptionType) {
            return [
                'adminCommission' => 0.0,
                'adminCommissionWithoutCost' => 0.0,
            ];
        }

        $promotionalCostByAdmin = 0.0;
        foreach ($bookingDetailsAmounts as $bookingDetailsAmount) {
            $promotionalCostByAdmin += (float) ($bookingDetailsAmount['discount_by_admin'] ?? 0)
                + (float) ($bookingDetailsAmount['coupon_discount_by_admin'] ?? 0)
                + (float) ($bookingDetailsAmount['campaign_discount_by_admin'] ?? 0);
        }

        if ($main->admin_commission_override !== null) {
            $adminCommission = round(max(0.0, (float) $main->admin_commission_override), 2);
            $adminCommissionWithoutCost = max(0.0, $adminCommission - $promotionalCostByAdmin);

            return [
                'adminCommission' => $adminCommission,
                'adminCommissionWithoutCost' => $adminCommissionWithoutCost,
            ];
        }

        if ($outcome === self::OUTCOME_STANDARD) {
            $baseResult = calculate_commission_for_booking($booking, $providerId);
            $adminFull = (float) $baseResult['commission'];
            $adminCommissionWithoutCost = max(0.0, $adminFull - $promotionalCostByAdmin);

            return [
                'adminCommission' => $adminFull,
                'adminCommissionWithoutCost' => $adminCommissionWithoutCost,
            ];
        }

        if (self::outcomeUsesDecidedVisitCharges($outcome)) {
            $visitPaid = $this->resolveVisitChargesPaid($main, $config);
            $closingPaid = $this->resolveClosingAmountPaid($main, $config);
            $visitSplit = $this->resolveVisitLineCompanyProviderShares($booking, $visitPaid, $config, $providerId);
            $adminVisit = $visitSplit[0];

            $adminClosing = 0.0;
            if ($closingPaid > 0) {
                [$coClosing] = $this->resolveClosingCompanyProviderShares($booking, $closingPaid, $config, $providerId);
                $adminClosing = $coClosing;
            }

            $adminCommission = round($adminVisit + $adminClosing, 2);
            $adminCommissionWithoutCost = max(0.0, $adminCommission - $promotionalCostByAdmin);

            return [
                'adminCommission' => $adminCommission,
                'adminCommissionWithoutCost' => $adminCommissionWithoutCost,
            ];
        }

        if ($outcome === self::OUTCOME_CUSTOM_COMMISSION) {
            $adminCommission = round((float) ($config['custom_admin_commission'] ?? 0), 2);
            $adminCommission = max(0.0, $adminCommission);
            $adminCommissionWithoutCost = max(0.0, $adminCommission - $promotionalCostByAdmin);

            return [
                'adminCommission' => $adminCommission,
                'adminCommissionWithoutCost' => $adminCommissionWithoutCost,
            ];
        }

        // Loss-making (scaled): commission tiers run on the parent booking’s full payable total, never on customer paid amount.
        if ($outcome === self::OUTCOME_SCALED_TO_PAYMENTS && $booking instanceof BookingRepeat) {
            $mainForRepeat = $this->mainBookingFor($booking);
            $parentResult = calculate_commission_for_booking($mainForRepeat, $providerId);
            $adminFullParent = (float) $parentResult['commission'];
            $w = $this->scaledLossRepeatLineWeight($booking, $mainForRepeat);
            $adminFull = round($adminFullParent * $w, 2);
            $adminCommissionWithoutCost = max(0.0, $adminFull - $promotionalCostByAdmin);

            return [
                'adminCommission' => $adminFull,
                'adminCommissionWithoutCost' => $adminCommissionWithoutCost,
            ];
        }

        $baseResult = calculate_commission_for_booking($booking, $providerId);
        $adminFull = (float) $baseResult['commission'];

        $adminCommissionWithoutCost = max(0.0, $adminFull - $promotionalCostByAdmin);

        return [
            'adminCommission' => $adminFull,
            'adminCommissionWithoutCost' => $adminCommissionWithoutCost,
        ];
    }

    /**
     * Share of parent “full booking” commission/earning for this repeat line (loss-making parent only).
     */
    public function scaledLossRepeatLineWeight(BookingRepeat $repeat, Booking $main): float
    {
        $line = round(max(0.0, get_booking_total_amount($repeat)), 2);
        $siblings = BookingRepeat::query()
            ->where('booking_id', $main->id)
            ->get();
        $siblingSum = 0.0;
        foreach ($siblings as $s) {
            $siblingSum += get_booking_total_amount($s);
        }
        $siblingSum = round(max(0.0, $siblingSum), 2);
        $parentGrand = round(max(0.0, get_booking_total_amount($main)), 2);
        $den = round(max($siblingSum, $parentGrand, 0.01), 2);

        return min(1.0, max(0.0, $line / $den));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPreview(Booking $booking): array
    {
        $outcome = trim((string) ($booking->settlement_outcome ?? ''));
        if ($outcome === '') {
            $outcome = self::OUTCOME_STANDARD;
        }
        $config = is_array($booking->settlement_config) ? $booking->settlement_config : [];

        $grandTotal = get_booking_total_amount($booking);
        $paid = $this->totalPaidForMainBooking($booking);
        $visitFee = round((float) ($booking->extra_fee ?? 0), 2);
        $pct = $this->resolveVisitCompanyPercent($booking, $config);

        $details = $this->calculateAdminCommissionDetails($booking, $booking->provider_id);

        $retained = 0.0;
        $visitPaidResolved = 0.0;
        $closingPaidResolved = 0.0;
        $visitProviderPct = $this->resolveVisitProviderPercent($booking, $config);
        $adminFromVisit = 0.0;
        $providerFromVisit = 0.0;
        $adminFromClosing = 0.0;
        $providerFromClosing = 0.0;

        if (self::outcomeUsesDecidedVisitCharges($outcome)) {
            $retained = $this->resolveRetainedVisitAmount($booking, $config);
            $visitPaidResolved = $this->resolveVisitChargesPaid($booking, $config);
            $closingPaidResolved = $this->resolveClosingAmountPaid($booking, $config);
            [$adminFromVisit, $providerFromVisit] = $this->resolveVisitLineCompanyProviderShares(
                $booking,
                $visitPaidResolved,
                $config,
                $booking->provider_id
            );

            if ($closingPaidResolved > 0) {
                [$adminFromClosing, $providerFromClosing] = $this->resolveClosingCompanyProviderShares(
                    $booking,
                    $closingPaidResolved,
                    $config,
                    $booking->provider_id
                );
            }
        }

        $refundHint = 0.0;
        if (self::outcomeUsesDecidedVisitCharges($outcome)) {
            $refundHint = round(max(0.0, $paid - $retained), 2);
        }

        $booking->loadMissing('booking_partial_payments');
        $receivedSettlement = get_booking_received_and_settlement($booking);
        $dueFromCustomer = get_booking_invoice_due_amount($booking);
        $previewBookingTotal = round($grandTotal, 2);

        if (self::outcomeUsesDecidedVisitCharges($outcome)) {
            $previewBookingTotal = round($retained, 2);
            $dueFromCustomer = max(0.0, round($retained - $paid, 2));
        }

        $scaledLossBlock = [];
        if ($outcome === self::OUTCOME_SCALED_TO_PAYMENTS) {
            [$sx, $sLoss, $sy, $sz] = $this->resolveScaledLossBreakdown($booking, $config, $grandTotal, $paid);
            $commissionWo = (float) $details['adminCommissionWithoutCost'];
            $providerGross = round(max(0.0, $grandTotal - $commissionWo), 2);
            $netCompany = round($commissionWo - $sy, 2);
            $netProvider = round($providerGross - $sz, 2);
            $scaledLossBlock = [
                'scaled_loss_mode' => true,
                'scaled_total_booking_amount' => round($grandTotal, 2),
                // Display should reflect actual customer payments recorded, not the declared scaled cap.
                'scaled_customer_paid_amount' => round(min($grandTotal, max(0.0, (float) $paid)), 2),
                'scaled_loss_amount' => $sLoss,
                'scaled_loss_writeoff_amount' => isset($config['scaled_loss_writeoff_amount']) && is_numeric($config['scaled_loss_writeoff_amount'])
                    ? round(max(0.0, (float) $config['scaled_loss_writeoff_amount']), 2)
                    : 0.0,
                'scaled_loss_company_share' => $sy,
                'scaled_loss_provider_share' => $sz,
                'scaled_bad_debt_balance_not_due' => $sLoss,
                'scaled_gross_company_commission_without_cost' => round($commissionWo, 2),
                'scaled_gross_provider_share' => $providerGross,
                'scaled_net_company_share' => $netCompany,
                'scaled_net_provider_share' => $netProvider,
            ];
        }

        return array_merge([
            'outcome' => $outcome,
            'decided_visit_charges_mode' => self::outcomeUsesDecidedVisitCharges($outcome),
            'booking_total' => $previewBookingTotal,
            'visit_extra_fee' => $visitFee,
            'collected_from_customer' => round($paid, 2),
            'amount_received_by_company' => (float) $receivedSettlement['amount_received_by_company'],
            'amount_received_by_provider' => (float) $receivedSettlement['amount_received_by_provider'],
            'company_share' => (float) $receivedSettlement['company_share'],
            'provider_share' => (float) $receivedSettlement['provider_share'],
            'amount_to_collect_from_customer' => round(max(0.0, $dueFromCustomer), 2),
            'refund_to_customer' => $refundHint,
            'retained_on_cancel' => round($retained, 2),
            'allow_complete_without_full_payment' => ($outcome === self::OUTCOME_SCALED_TO_PAYMENTS),
            'visit_fee_company_percent' => $pct,
            'visit_charges_paid' => round($visitPaidResolved, 2),
            'closing_amount_paid' => round($closingPaidResolved, 2),
            'visit_fee_provider_percent' => $visitProviderPct,
            'company_amount_from_visit' => round($adminFromVisit, 2),
            'provider_amount_from_visit' => round($providerFromVisit, 2),
            'company_amount_from_closing' => round($adminFromClosing, 2),
            'provider_amount_from_closing' => round($providerFromClosing, 2),
            'total_company_earning_applied' => self::outcomeUsesDecidedVisitCharges($outcome)
                ? round($adminFromVisit + $adminFromClosing, 2)
                : 0.0,
            'total_provider_earning_applied' => self::outcomeUsesDecidedVisitCharges($outcome)
                ? round($providerFromVisit + $providerFromClosing, 2)
                : 0.0,
            // Legacy keys (booking details card, exports)
            'grand_total' => self::outcomeUsesDecidedVisitCharges($outcome) ? $previewBookingTotal : round($grandTotal, 2),
            'total_paid' => round($paid, 2),
            'visit_fee' => $visitFee,
            'suggested_customer_refund' => $refundHint,
            'company_commission' => round((float) $details['adminCommission'], 2),
            'company_commission_after_promos' => (float) $receivedSettlement['company_share'],
            'provider_earning' => (float) $receivedSettlement['provider_share'],
            'custom_admin_commission' => (float) ($config['custom_admin_commission'] ?? 0),
        ], $scaledLossBlock);
    }

    public function providerEarningBasisAmount(Booking $booking, float $grandTotal, float $paid): float
    {
        $outcome = trim((string) ($booking->settlement_outcome ?? ''));
        if ($outcome === '') {
            $outcome = self::OUTCOME_STANDARD;
        }
        $config = is_array($booking->settlement_config) ? $booking->settlement_config : [];

        if (self::outcomeUsesDecidedVisitCharges($outcome)) {
            return max(0.0, $this->resolveRetainedVisitAmount($booking, $config));
        }

        if ($outcome === self::OUTCOME_SCALED_TO_PAYMENTS) {
            return max(0.0, $grandTotal);
        }

        return $grandTotal;
    }

    /**
     * Loss-making (scaled) scenario: declared customer paid amount and loss split (company / provider).
     *
     * When settlement config has scaled_loss_* amounts **and** installments record loss recovery splits,
     * remaining loss is attributed by **subtracting** cumulative recovery from those nominal amounts
     * (e.g. provider share ₹250 − ₹200 recovery → ₹50), not by re-splitting total loss using allocation ratios.
     * Otherwise: rescale settlement ratio to current loss; if no settlement loss amounts, use allocation
     * ratio from installments; else 50/50.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}  [customer_paid_capped, loss_total, loss_company, loss_provider]
     */
    public function resolveScaledLossBreakdown(Booking $booking, array $config, float $grandTotal, float $actualPaid): array
    {
        $grandTotal = max(0.0, round($grandTotal, 2));
        $actual = round(max(0.0, $actualPaid), 2);
        $writeoffRaw = isset($config['scaled_loss_writeoff_amount']) && is_numeric($config['scaled_loss_writeoff_amount'])
            ? round(max(0.0, (float) $config['scaled_loss_writeoff_amount']), 2)
            : 0.0;

        $cfgPaid = isset($config['scaled_customer_paid_amount']) && is_numeric($config['scaled_customer_paid_amount'])
            ? round(max(0.0, (float) $config['scaled_customer_paid_amount']), 2)
            : null;

        // Effective customer-paid amount for loss math: must reflect **recorded** partials when they exceed the
        // saved declaration (post-completion recovery). Stored scaled_customer_paid_amount is the original declaration;
        // additional payments only increase booking_partial_payments, not settlement_config — so take max(declared, actual).
        // If a prior write-off exists, never let an inflated stored declaration override actual paid for display/math.
        if ($cfgPaid !== null && $writeoffRaw > 0.009 && $cfgPaid > $actual + 0.02) {
            $cfgPaid = $actual;
        }

        if ($cfgPaid !== null) {
            $x = round(min($grandTotal, max($cfgPaid, $actual)), 2);
        } else {
            $x = round(min($grandTotal, $actual), 2);
        }

        $lossTotal = round(max(0.0, $grandTotal - $x), 2);

        // Discount / write-off: settles remaining customer obligation without receiving payment.
        if ($writeoffRaw > 0.009) {
            $lossTotal = round(max(0.0, $lossTotal - $writeoffRaw), 2);
        }

        $yCfg = isset($config['scaled_loss_company_amount']) && is_numeric($config['scaled_loss_company_amount'])
            ? round(max(0.0, (float) $config['scaled_loss_company_amount']), 2)
            : 0.0;
        $zCfg = isset($config['scaled_loss_provider_amount']) && is_numeric($config['scaled_loss_provider_amount'])
            ? round(max(0.0, (float) $config['scaled_loss_provider_amount']), 2)
            : 0.0;

        $configLossSum = round($yCfg + $zCfg, 2);
        [$allocCo, $allocPr] = $this->summedLossRecoveryAllocationFromPartials($booking);
        $allocSum = round($allocCo + $allocPr, 2);

        if ($lossTotal <= 0.009) {
            $y = 0.0;
            $z = 0.0;
        } elseif ($configLossSum > 0.009 && ($allocCo > 0.009 || $allocPr > 0.009
                || (!empty($config['scaled_loss_writeoff_company_amount']) || !empty($config['scaled_loss_writeoff_provider_amount'])))) {
            // Deduct cumulative recovery (paid) and write-offs (discount/waiver) from nominal settlement loss amounts.
            $wCo = isset($config['scaled_loss_writeoff_company_amount']) && is_numeric($config['scaled_loss_writeoff_company_amount'])
                ? round(max(0.0, (float) $config['scaled_loss_writeoff_company_amount']), 2)
                : 0.0;
            $wPr = isset($config['scaled_loss_writeoff_provider_amount']) && is_numeric($config['scaled_loss_writeoff_provider_amount'])
                ? round(max(0.0, (float) $config['scaled_loss_writeoff_provider_amount']), 2)
                : 0.0;

            $rawY = max(0.0, round($yCfg - $allocCo - $wCo, 2));
            $rawZ = max(0.0, round($zCfg - $allocPr - $wPr, 2));
            $sumRaw = round($rawY + $rawZ, 2);
            if ($sumRaw <= 0.009) {
                $y = round($lossTotal * ($yCfg / $configLossSum), 2);
                $z = round($lossTotal - $y, 2);
            } elseif (abs($sumRaw - $lossTotal) <= 0.03) {
                $y = $rawY;
                $z = round($lossTotal - $y, 2);
            } else {
                $y = round($lossTotal * ($rawY / $sumRaw), 2);
                $z = round($lossTotal - $y, 2);
            }
        } elseif ($configLossSum > 0.009) {
            // Preserve company vs provider loss **ratio** from settlement; rescale when total loss shrinks after recovery.
            $y = round($lossTotal * ($yCfg / $configLossSum), 2);
            $z = round($lossTotal - $y, 2);
        } elseif ($allocSum > 0.009) {
            $y = round($lossTotal * ($allocCo / $allocSum), 2);
            $z = round($lossTotal - $y, 2);
        } else {
            $y = round($lossTotal / 2, 2);
            $z = round($lossTotal - $y, 2);
        }

        return [$x, $lossTotal, $y, $z];
    }

    /**
     * Sum economic loss-recovery splits from {@see booking_partial_payment_loss_allocation_split()} across installments.
     *
     * @return array{0: float, 1: float} [company_total, provider_total]
     */
    public function summedLossRecoveryAllocationFromPartials(Booking $booking): array
    {
        $booking->loadMissing('booking_partial_payments');
        $company = 0.0;
        $provider = 0.0;
        foreach ($booking->booking_partial_payments ?? [] as $p) {
            $alloc = booking_partial_payment_loss_allocation_split($p);
            if ($alloc !== null) {
                $company += $alloc['company'];
                $provider += $alloc['provider'];
            }
        }

        return [round($company, 2), round($provider, 2)];
    }

    /**
     * Scaled / loss-making: company + provider loss shares must equal total loss (booking total − amount paid by customer).
     */
    public function validateScaledLossSplit(Booking $booking, array $config): ?string
    {
        $grand = round(max(0.0, get_booking_total_amount($booking)), 2);
        $actualPaid = $this->totalPaidForMainBooking($booking);
        [, $lossTotal, $y, $z] = $this->resolveScaledLossBreakdown($booking, $config, $grand, $actualPaid);
        $sum = round($y + $z, 2);
        $lossR = round($lossTotal, 2);
        if (abs($sum - $lossR) > 0.02) {
            return translate('Bfs_scaled_loss_split_must_equal_total_loss');
        }

        return null;
    }

    /**
     * @param  array{adminCommission: float, adminCommissionWithoutCost: float}  $commissionDetails
     */
    public function syncDetailsAmounts(Booking|BookingRepeat $booking, array $commissionDetails): void
    {
        $adminCommission = (float) $commissionDetails['adminCommission'];
        $adminCommissionWithoutCost = (float) $commissionDetails['adminCommissionWithoutCost'];

        $main = $this->mainBookingFor($booking);
        $outcomeMain = trim((string) ($main->settlement_outcome ?? ''));

        if ($outcomeMain === self::OUTCOME_SCALED_TO_PAYMENTS && $booking instanceof BookingRepeat) {
            $w = $this->scaledLossRepeatLineWeight($booking, $main);
            $parentCd = $this->calculateAdminCommissionDetails($main, $booking->provider_id ?? $main->provider_id);
            $parentGrand = round(max(0.0, get_booking_total_amount($main)), 2);
            $parentWo = round(max(0.0, (float) ($parentCd['adminCommissionWithoutCost'] ?? 0)), 2);
            $providerGrossParent = round(max(0.0, $parentGrand - $parentWo), 2);
            $providerEarning = round(max(0.0, $providerGrossParent * $w), 2);
        } else {
            $grandTotal = get_booking_total_amount($booking);
            $paid = $this->totalPaidForMainBooking($main);
            $providerBasis = $this->providerEarningBasisAmount($main, $grandTotal, $paid);
            $providerEarning = round(max(0.0, $providerBasis - $adminCommissionWithoutCost), 2);
        }

        if (isset($booking->booking_id)) {
            $rows = BookingDetailsAmount::where('booking_repeat_id', $booking->id)->orderBy('id')->get();
        } else {
            $rows = BookingDetailsAmount::where('booking_id', $booking->id)->orderBy('id')->get();
        }

        if ($rows->isEmpty()) {
            return;
        }

        $first = $rows->first();
        $first->admin_commission = $adminCommission;
        $first->provider_earning = $providerEarning;
        $first->save();

        foreach ($rows->skip(1) as $row) {
            $row->admin_commission = 0;
            $row->provider_earning = 0;
            $row->save();
        }
    }

    public function totalPaidForMainBooking(Booking $booking): float
    {
        $booking->loadMissing('booking_partial_payments');
        $partials = $booking->booking_partial_payments;
        if ($partials->isNotEmpty()) {
            return round((float) $partials->sum('paid_amount'), 2);
        }

        if ((int) ($booking->is_paid ?? 0) === 1) {
            if (trim((string) ($booking->settlement_outcome ?? '')) === self::OUTCOME_SCALED_TO_PAYMENTS) {
                return 0.0;
            }

            return round((float) get_booking_total_amount($booking), 2);
        }

        return 0.0;
    }

    public function resolveVisitCompanyPercent(Booking $main, array $config): float
    {
        if (isset($config['visit_fee_company_percent']) && is_numeric($config['visit_fee_company_percent'])) {
            return max(0.0, min(100.0, (float) $config['visit_fee_company_percent']));
        }

        return $this->defaultVisitCompanyPercent();
    }

    /**
     * Cancel-after-visit: visit + closing cannot exceed the original booking total (refund / retain math).
     * Job completed — visit or call-out mainly: admin-defined visit + closing is the new bill (not clipped to cart total).
     */
    private function decidedChargesCapToOriginalBookingTotal(Booking $main): bool
    {
        $o = trim((string) ($main->settlement_outcome ?? ''));

        return $o !== self::OUTCOME_VISIT_FEE_SPLIT;
    }

    public function resolveVisitChargesPaid(Booking $main, array $config): float
    {
        $grand = round(max(0.0, get_booking_total_amount($main)), 2);
        $visitDefault = max(0.0, round((float) ($main->extra_fee ?? 0), 2));
        $capToGrand = $this->decidedChargesCapToOriginalBookingTotal($main);

        if (isset($config['visit_charges_paid']) && is_numeric($config['visit_charges_paid'])) {
            $v = round((float) $config['visit_charges_paid'], 2);

            return $capToGrand ? max(0.0, min($v, $grand)) : max(0.0, $v);
        }

        $hasExplicitClosing = isset($config['closing_amount_paid']) && is_numeric($config['closing_amount_paid']);

        if (isset($config['retained_visit_amount']) && is_numeric($config['retained_visit_amount']) && ! $hasExplicitClosing) {
            $v = round((float) $config['retained_visit_amount'], 2);

            return $capToGrand ? max(0.0, min($v, $grand)) : max(0.0, $v);
        }

        return max(0.0, $capToGrand ? min($visitDefault, $grand) : $visitDefault);
    }

    public function resolveClosingAmountPaid(Booking $main, array $config): float
    {
        $grand = round(max(0.0, get_booking_total_amount($main)), 2);
        $capToGrand = $this->decidedChargesCapToOriginalBookingTotal($main);

        if (! isset($config['closing_amount_paid']) || ! is_numeric($config['closing_amount_paid'])) {
            return 0.0;
        }
        $c = round((float) $config['closing_amount_paid'], 2);

        return $capToGrand ? max(0.0, min($c, $grand)) : max(0.0, $c);
    }

    /**
     * Total decided amount (visit + optional closing). For cancel-after-visit, capped to original booking total.
     */
    public function resolveRetainedVisitAmount(Booking $main, array $config): float
    {
        $grand = round(max(0.0, get_booking_total_amount($main)), 2);
        $visit = $this->resolveVisitChargesPaid($main, $config);
        $closing = $this->resolveClosingAmountPaid($main, $config);
        $total = round($visit + $closing, 2);
        $capToGrand = $this->decidedChargesCapToOriginalBookingTotal($main);

        if (! $capToGrand) {
            return max(0.0, $total);
        }

        return max(0.0, min($total, $grand));
    }

    public function resolveVisitProviderPercent(Booking $main, array $config): float
    {
        if (isset($config['visit_fee_provider_percent']) && is_numeric($config['visit_fee_provider_percent'])) {
            return max(0.0, min(100.0, (float) $config['visit_fee_provider_percent']));
        }

        $co = $this->resolveVisitCompanyPercent($main, $config);

        return max(0.0, min(100.0, 100.0 - $co));
    }

    /**
     * Company vs provider split on the visiting-charges line: default from visit_fee_company_percent;
     * optional monetary overrides visit_company_amount / visit_provider_amount (same pattern as closing).
     *
     * @return array{0: float, 1: float}  [company_amount, provider_amount] on the visit line
     */
    public function resolveVisitLineCompanyProviderShares($booking, float $visitPaid, array $config, int|string|null $providerId): array
    {
        $visitPaid = max(0.0, round($visitPaid, 2));
        if ($visitPaid <= 0) {
            return [0.0, 0.0];
        }

        $main = $this->mainBookingFor($booking);

        $hasCo = array_key_exists('visit_company_amount', $config) && is_numeric($config['visit_company_amount']);
        $hasPr = array_key_exists('visit_provider_amount', $config) && is_numeric($config['visit_provider_amount']);

        if (! $hasCo && ! $hasPr) {
            $coPct = $this->resolveVisitCompanyPercent($main, $config);
            $adminVisit = round($visitPaid * ($coPct / 100.0), 2);
            $providerVisit = round(max(0.0, $visitPaid - $adminVisit), 2);

            return [$adminVisit, $providerVisit];
        }

        $coPct = $this->resolveVisitCompanyPercent($main, $config);
        $defaultCo = round($visitPaid * ($coPct / 100.0), 2);
        $defaultPr = round(max(0.0, $visitPaid - $defaultCo), 2);

        $co = $hasCo ? round((float) $config['visit_company_amount'], 2) : null;
        $pr = $hasPr ? round((float) $config['visit_provider_amount'], 2) : null;

        $co = $co !== null ? max(0.0, min($visitPaid, $co)) : null;
        $pr = $pr !== null ? max(0.0, min($visitPaid, $pr)) : null;

        if ($co === null && $pr === null) {
            return [$defaultCo, $defaultPr];
        }

        if ($pr === null) {
            $co = (float) $co;

            return [round(min($co, $visitPaid), 2), round(max(0.0, $visitPaid - $co), 2)];
        }

        if ($co === null) {
            $pr = (float) $pr;

            return [round(max(0.0, $visitPaid - $pr), 2), round(min($pr, $visitPaid), 2)];
        }

        $co = (float) $co;
        $pr = (float) $pr;
        $sum = $co + $pr;
        $eps = 0.01;
        if ($sum > $visitPaid + $eps) {
            $scale = $visitPaid / max($sum, 1e-9);
            $co = round($co * $scale, 2);
            $pr = round($visitPaid - $co, 2);
        } elseif ($sum < $visitPaid - $eps) {
            $pr = round($visitPaid - $co, 2);
        }

        return [
            round($co, 2),
            round($pr, 2),
        ];
    }

    /**
     * Company vs provider split on the closing amount: commission tiers when overrides are absent;
     * optional monetary overrides in settlement_config (closing_company_share, closing_provider_share).
     *
     * @return array{0: float, 1: float}  [company_amount, provider_amount] on the closing line
     */
    public function resolveClosingCompanyProviderShares($booking, float $closingPaid, array $config, int|string|null $providerId): array
    {
        $closingPaid = max(0.0, round($closingPaid, 2));
        if ($closingPaid <= 0) {
            return [0.0, 0.0];
        }

        $setup = resolve_commission_tier_setup_for_booking($booking, $providerId);
        $serviceGroup = [
            'mode' => $setup['service']['mode'] ?? 'tiered',
            'fixed_amount' => (float) ($setup['service']['fixed_amount'] ?? 0),
            'tiers' => is_array($setup['service']['tiers'] ?? null) ? $setup['service']['tiers'] : [],
        ];
        $line = commission_calc_line_preview($closingPaid, $serviceGroup);
        $tierCo = round((float) $line['admin_commission'], 2);
        $tierPr = round((float) $line['provider_earning'], 2);

        $hasCo = array_key_exists('closing_company_share', $config) && is_numeric($config['closing_company_share']);
        $hasPr = array_key_exists('closing_provider_share', $config) && is_numeric($config['closing_provider_share']);

        if (! $hasCo && ! $hasPr) {
            return [$tierCo, $tierPr];
        }

        $co = $hasCo ? round((float) $config['closing_company_share'], 2) : null;
        $pr = $hasPr ? round((float) $config['closing_provider_share'], 2) : null;

        $co = $co !== null ? max(0.0, min($closingPaid, $co)) : null;
        $pr = $pr !== null ? max(0.0, min($closingPaid, $pr)) : null;

        if ($co === null && $pr === null) {
            return [$tierCo, $tierPr];
        }

        if ($pr === null) {
            $co = (float) $co;

            return [round(min($co, $closingPaid), 2), round(max(0.0, $closingPaid - $co), 2)];
        }

        if ($co === null) {
            $pr = (float) $pr;

            return [round(max(0.0, $closingPaid - $pr), 2), round(min($pr, $closingPaid), 2)];
        }

        $co = (float) $co;
        $pr = (float) $pr;
        $sum = $co + $pr;
        $eps = 0.01;
        if ($sum > $closingPaid + $eps) {
            $scale = $closingPaid / max($sum, 1e-9);
            $co = round($co * $scale, 2);
            $pr = round($closingPaid - $co, 2);
        } elseif ($sum < $closingPaid - $eps) {
            $pr = round($closingPaid - $co, 2);
        }

        return [
            round(max(0.0, min($co, $closingPaid)), 2),
            round(max(0.0, min($pr, $closingPaid)), 2),
        ];
    }

    /**
     * Persist config + snapshot on parent booking.
     * Loss-making (scaled) bookings always allow marking complete without full payment.
     *
     * @param  array<string, mixed>  $config
     */
    public function saveSettlement(Booking $booking, string $outcome, array $config, ?string $remarks): void
    {
        $previousOutcome = trim((string) ($booking->getOriginal('settlement_outcome') ?? ''));
        $wasAlreadyLossMaking = $previousOutcome === self::OUTCOME_SCALED_TO_PAYMENTS;

        $booking->settlement_outcome = $outcome === self::OUTCOME_STANDARD ? null : $outcome;
        $booking->settlement_config = $outcome === self::OUTCOME_STANDARD ? null : $config;
        $booking->settlement_remarks = $remarks;
        $booking->allow_complete_without_full_payment = ($outcome === self::OUTCOME_SCALED_TO_PAYMENTS);

        if ($outcome === self::OUTCOME_STANDARD) {
            $booking->settlement_snapshot = null;
            $booking->allow_complete_without_full_payment = false;
            $booking->after_visit_cancel = false;
        } else {
            $booking->settlement_snapshot = $this->buildPreview($booking);
        }

        $booking->save();

        if ($outcome === self::OUTCOME_SCALED_TO_PAYMENTS && ! $wasAlreadyLossMaking) {
            app(CustomerLossMakingSettlementPenaltyService::class)->applyWhenBookingBecomesLossMaking($booking);
        }
    }
}
