<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingCompensation;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\ProviderManagement\Entities\Provider;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;

class ProviderPaymentTabDataService
{
    private const ALLOWED_SUBS = ['ledger', 'recorded', 'earning', 'special_earning', 'disputed'];

    /**
     * Summary widgets matching admin provider payment tab (read-only for provider app).
     *
     * @return array<string, mixed>
     */
    public function overview(Provider $provider): array
    {
        $ctx = $this->buildRevenueContext($provider);

        $providerPayable = (float) ($provider->owner->account->account_payable ?? 0);
        $providerReceivable = (float) ($provider->owner->account->account_receivable ?? 0);
        $withdrawableBalance = provider_withdrawable_balance($providerReceivable, $providerPayable);
        $activeWithdrawTotal = provider_active_withdraw_request_total((string) $provider->user_id);
        $netPayableAmount = (float) $ctx['booking_settlement_net'];
        $companyPaysProvider = $netPayableAmount > 0.009;
        $providerPaysCompany = $netPayableAmount < -0.009;
        $collectFormMax = $providerPaysCompany ? min($providerPayable, max(0.0, -$netPayableAmount)) : 0.0;
        $effectiveWithdrawable = provider_effective_withdrawable_balance(
            (string) $provider->id,
            (string) $provider->user_id,
            $providerReceivable,
            $providerPayable
        );
        $displayNetBalance = provider_net_balance_amount_after_active_withdraws(
            $netPayableAmount,
            $activeWithdrawTotal,
            $companyPaysProvider
        );
        $requestMaxAmount = $effectiveWithdrawable;
        $canRequestAmount = $effectiveWithdrawable > 0.009;

        $ppLedger = provider_payment_ledger_context([
            'collect_in_total' => (float) ($ctx['ledger_manual_totals']['collect_in_total'] ?? 0),
            'payout_out_total' => (float) ($ctx['ledger_manual_totals']['payout_out_total'] ?? 0),
            'booking_settlement_net_before_ledger' => (float) $ctx['booking_settlement_net_before_ledger'],
            'booking_settlement_net_after_ledger' => (float) $ctx['booking_settlement_net'],
            'provider_account_payable' => $providerPayable,
            'provider_account_receivable' => $providerReceivable,
        ]);

        return [
            'net_balance' => [
                'amount' => $displayNetBalance,
                'direction' => $companyPaysProvider ? 'company_pays_provider' : ($providerPaysCompany ? 'provider_pays_company' : 'settled'),
                'can_request_amount' => $canRequestAmount,
                'request_max_amount' => $requestMaxAmount,
                'can_pay' => $providerPaysCompany && $collectFormMax > 0.009,
                'pay_max_amount' => round($collectFormMax, 2),
                'withdrawable_balance' => $withdrawableBalance,
                'active_withdraw_total' => $activeWithdrawTotal,
            ],
            'total_revenue' => round((float) $ctx['total_revenue'], 2),
            'provider_net_earning' => round((float) $ctx['provider_net_earning'], 2),
            'total_company_commission' => round((float) $ctx['total_company_commission'], 2),
            'provider_loss_absorbed_total' => round((float) $ctx['scaled_loss_provider_share_total'], 2),
            'company_loss_absorbed_total' => round((float) $ctx['scaled_loss_company_share_total'], 2),
            'compensation' => [
                'provider_compensated_to_customers' => round((float) $ctx['provider_compensated_to_customers_total'], 2),
                'company_compensated_to_provider' => round((float) $ctx['company_compensated_to_provider_total'], 2),
            ],
            'receipts' => [
                'from_company' => round((float) $ctx['provider_received_from_company_total'], 2),
                'from_customer' => round((float) $ctx['provider_received_from_customer_total'], 2),
                'total' => round((float) $ctx['provider_received_total_all_sources'], 2),
            ],
            'customer_refund_due_total' => round((float) $ctx['customer_refund_due_total'], 2),
            'ledger_context' => [
                'amount_paid_to_provider' => (float) ($ppLedger['amount_paid_to_provider'] ?? 0),
                'amount_collected_from_provider' => (float) ($ppLedger['amount_collected_from_provider'] ?? 0),
                'balance_after_payment_collected' => (float) ($ppLedger['balance_after_payment_collected'] ?? 0),
                'balance_remaining_to_pay_to_provider' => (float) ($ppLedger['balance_remaining_to_pay_to_provider'] ?? 0),
            ],
            'account' => [
                'account_payable' => $providerPayable,
                'account_receivable' => $providerReceivable,
                'withdrawable_balance' => $withdrawableBalance,
                'balance_pending' => (float) ($provider->owner->account->balance_pending ?? 0),
                'active_withdraw_total' => $activeWithdrawTotal,
            ],
        ];
    }

    /**
     * Paginated list for a payment sub-tab.
     *
     * @return array<string, mixed>
     */
    public function list(Provider $provider, string $paymentSub, int $offset, int $limit): array
    {
        $paymentSub = in_array($paymentSub, self::ALLOWED_SUBS, true) ? $paymentSub : 'ledger';
        $offset = max(1, $offset);
        $limit = min(50, max(1, $limit));
        $providerId = (string) $provider->id;

        return match ($paymentSub) {
            'ledger' => $this->listLedger($providerId, $offset, $limit),
            'recorded' => $this->listRecorded($providerId, $offset, $limit),
            'earning' => $this->listEarning($provider, $offset, $limit, false),
            'special_earning' => $this->listEarning($provider, $offset, $limit, true),
            'disputed' => $this->listDisputed($providerId, $offset, $limit),
            default => $this->listLedger($providerId, $offset, $limit),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRevenueContext(Provider $provider): array
    {
        $providerId = (string) $provider->id;
        $splits = $this->resolveBookingSplits($providerId);

        $paymentSummary = aggregate_provider_payment_summary_for_completed_jobs(
            $splits['one_time_bookings_for_revenue'],
            $splits['repeats_for_revenue']
        );

        $bookingSettlementAggregate = aggregate_provider_booking_settlement_net_for_completed_jobs(
            $splits['one_time_bookings_for_revenue'],
            $splits['repeats_for_revenue']
        );
        $ledgerManualTotals = provider_ledger_manual_flow_totals_for_provider($providerId);
        $bookingSettlementNetBeforeLedger = (float) $bookingSettlementAggregate['settlement_net'];
        $bookingSettlementNet = round(
            $bookingSettlementNetBeforeLedger - $ledgerManualTotals['payout_out_total'] + $ledgerManualTotals['collect_in_total'],
            2
        );

        $bookingEarningReport = $this->buildNormalEarningReport(
            $splits['normal_one_time_ids'],
            $splits['normal_repeat_ids'],
            $splits['repeat_grand_sums_by_parent']
        );
        $specialBookingEarningReport = $this->buildSpecialEarningReport(
            $splits['special_one_time_ids'],
            $splits['special_repeat_ids'],
            $splits['repeat_grand_sums_by_parent']
        );

        $providerReceivedFromCompanyTotal = (float) ($ledgerManualTotals['payout_out_total'] ?? 0);
        $providerReceivedFromCustomerTotal = round(
            (float) $bookingEarningReport->sum(fn ($r) => (float) ($r->amount_received_by_provider ?? 0))
            + (float) $specialBookingEarningReport->sum(fn ($r) => (float) ($r->amount_received_by_provider ?? 0)),
            2
        );

        return [
            'total_revenue' => $paymentSummary['total_revenue'],
            'total_company_commission' => $paymentSummary['total_company_commission'],
            'provider_net_earning' => $paymentSummary['provider_net_earning'],
            'scaled_loss_provider_share_total' => (float) ($paymentSummary['scaled_loss_provider_share_total'] ?? 0),
            'scaled_loss_company_share_total' => (float) ($paymentSummary['scaled_loss_company_share_total'] ?? 0),
            'booking_settlement_net' => $bookingSettlementNet,
            'booking_settlement_net_before_ledger' => $bookingSettlementNetBeforeLedger,
            'ledger_manual_totals' => $ledgerManualTotals,
            'customer_refund_due_total' => $bookingSettlementAggregate['customer_refund_due_total'],
            'provider_received_from_company_total' => $providerReceivedFromCompanyTotal,
            'provider_received_from_customer_total' => $providerReceivedFromCustomerTotal,
            'provider_received_total_all_sources' => round($providerReceivedFromCompanyTotal + $providerReceivedFromCustomerTotal, 2),
            'provider_compensated_to_customers_total' => (float) BookingCompensation::query()
                ->where('provider_id', $providerId)
                ->where('from_party', BookingCompensation::PARTY_PROVIDER)
                ->where('to_party', BookingCompensation::PARTY_CUSTOMER)
                ->sum('amount'),
            'company_compensated_to_provider_total' => (float) BookingCompensation::query()
                ->where('provider_id', $providerId)
                ->where('from_party', BookingCompensation::PARTY_COMPANY)
                ->where('to_party', BookingCompensation::PARTY_PROVIDER)
                ->sum('amount'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveBookingSplits(string $providerId): array
    {
        $providerBookingIds = DB::table('bookings')->where('provider_id', $providerId)->pluck('id')->toArray();
        $bookingIdsWithRepeats = DB::table('booking_repeats')->whereNotNull('booking_id')->distinct()->pluck('booking_id')->toArray();

        $oneTimeQuery = DB::table('bookings')->where('provider_id', $providerId)->where(function ($q) {
            provider_payment_tab_one_time_revenue_bookings_inner($q);
        });
        if (!empty($bookingIdsWithRepeats)) {
            $oneTimeQuery->whereNotIn('id', $bookingIdsWithRepeats);
        }
        $completedOneTimeBookingIds = $oneTimeQuery->pluck('id');

        $oneTimeSettlementById = $completedOneTimeBookingIds->isEmpty()
            ? collect()
            : Booking::whereIn('id', $completedOneTimeBookingIds)->pluck('settlement_outcome', 'id');

        $specialOneTimeIds = $completedOneTimeBookingIds->filter(function ($bid) use ($oneTimeSettlementById) {
            return trim((string) $oneTimeSettlementById->get($bid)) !== '';
        })->values();
        $normalOneTimeIds = $completedOneTimeBookingIds->diff($specialOneTimeIds)->values();

        $completedRepeatIds = collect();
        if (!empty($providerBookingIds)) {
            $completedRepeatIds = DB::table('booking_repeats')->where('booking_status', 'completed')->whereIn('booking_id', $providerBookingIds)->pluck('id');
        }

        $normalRepeatIds = collect();
        $specialRepeatIds = collect();
        if ($completedRepeatIds->isNotEmpty()) {
            foreach (BookingRepeat::whereIn('id', $completedRepeatIds)->with('booking')->get() as $repeatRow) {
                if (trim((string) ($repeatRow->booking->settlement_outcome ?? '')) !== '') {
                    $specialRepeatIds->push($repeatRow->id);
                } else {
                    $normalRepeatIds->push($repeatRow->id);
                }
            }
        }

        $oneTimeBookingsForRevenue = Booking::whereIn('id', $completedOneTimeBookingIds)->with('extra_services')->get();
        $repeatsForRevenue = $completedRepeatIds->isNotEmpty()
            ? BookingRepeat::whereIn('id', $completedRepeatIds)->with('booking.extra_services')->get()
            : collect();

        return [
            'provider_booking_ids' => $providerBookingIds,
            'normal_one_time_ids' => $normalOneTimeIds,
            'normal_repeat_ids' => $normalRepeatIds,
            'special_one_time_ids' => $specialOneTimeIds,
            'special_repeat_ids' => $specialRepeatIds,
            'one_time_bookings_for_revenue' => $oneTimeBookingsForRevenue,
            'repeats_for_revenue' => $repeatsForRevenue,
            'repeat_grand_sums_by_parent' => provider_payment_tab_sum_repeat_line_totals_by_parent_booking_id($repeatsForRevenue),
        ];
    }

    private function buildNormalEarningReport(Collection $oneTimeIds, Collection $repeatIds, array $repeatGrandSumsByParent): Collection
    {
        $report = collect();
        $oneTimeBookings = Booking::whereIn('id', $oneTimeIds)->with(['details_amounts', 'extra_services', 'booking_partial_payments'])->get();
        foreach ($oneTimeBookings as $b) {
            $report->push($this->mapNormalEarningRow($b, null, 1.0));
        }
        $repeats = BookingRepeat::whereIn('id', $repeatIds)->with(['details_amounts', 'booking.extra_services', 'booking.booking_partial_payments'])->get();
        foreach ($repeats as $r) {
            $parentKey = (string) $r->booking_id;
            $den = (float) ($repeatGrandSumsByParent[$parentKey] ?? get_booking_total_amount($r));
            $report->push($this->mapNormalEarningRow($r, $den, null));
        }

        return $report;
    }

    private function mapNormalEarningRow(Booking|BookingRepeat $row, ?float $repeatDen, ?float $oneTimeWeight): object
    {
        if ($row instanceof Booking) {
            $totalAmount = (float) get_provider_payment_tab_revenue_amount_for_booking($row);
            $partsCharges = (float) get_booking_revenue_reporting_spare_parts_amount($row);
            $extraServicesTotal = (float) ($row->extra_services->sum('total') ?? 0);
            $extraServiceCharges = (float) ($row->extra_fee ?? 0) + ($extraServicesTotal - $partsCharges);
            $serviceCharges = (float) $row->total_booking_amount;
            $pair = provider_payment_tab_earning_commission_pair($row);
            $settlementCols = provider_payment_tab_earning_report_settlement_columns_for_booking($row);

            return (object) [
                'readable_id' => $row->readable_id ?? $row->id,
                'booking_id' => $row->id,
                'total_amount' => $totalAmount,
                'service_charges' => $serviceCharges,
                'extra_service_charges' => $extraServiceCharges,
                'parts_charges' => $partsCharges,
                'provider_earning' => $pair['provider_earning'],
                'admin_commission' => $pair['admin_commission'],
                'amount_received_by_company' => $settlementCols['amount_received_by_company'],
                'amount_received_by_provider' => $settlementCols['amount_received_by_provider'],
                'provider_owes_company' => $settlementCols['provider_owes_company'],
                'company_owes_provider' => $settlementCols['company_owes_provider'],
            ];
        }

        $den = max(0.01, (float) $repeatDen);
        $totalAmount = (float) get_provider_payment_tab_revenue_amount_for_repeat($row, $den);
        $partsCharges = (float) get_booking_spare_parts_amount($row);
        $extraServicesTotal = (float) ($row->booking->extra_services->sum('total') ?? 0);
        $extraServiceCharges = (float) ($row->extra_fee ?? 0) + ($extraServicesTotal - $partsCharges);
        $serviceCharges = (float) $row->total_booking_amount;
        $pair = provider_payment_tab_earning_commission_pair($row);
        $settlementCols = provider_payment_tab_earning_report_settlement_columns_for_repeat($row, $den);

        return (object) [
            'readable_id' => $row->readable_id ?? $row->id,
            'booking_id' => $row->booking_id,
            'total_amount' => $totalAmount,
            'service_charges' => $serviceCharges,
            'extra_service_charges' => $extraServiceCharges,
            'parts_charges' => $partsCharges,
            'provider_earning' => $pair['provider_earning'],
            'admin_commission' => $pair['admin_commission'],
            'amount_received_by_company' => $settlementCols['amount_received_by_company'],
            'amount_received_by_provider' => $settlementCols['amount_received_by_provider'],
            'provider_owes_company' => $settlementCols['provider_owes_company'],
            'company_owes_provider' => $settlementCols['company_owes_provider'],
        ];
    }

    private function buildSpecialEarningReport(Collection $oneTimeIds, Collection $repeatIds, array $repeatGrandSumsByParent): Collection
    {
        $settlementService = app(BookingFinancialSettlementService::class);
        $report = collect();

        $specialOneTimeBookings = Booking::whereIn('id', $oneTimeIds)->with(['details_amounts', 'booking_partial_payments'])->get();
        foreach ($specialOneTimeBookings as $b) {
            $report->push($this->mapSpecialEarningRow($b, null, 1.0, $settlementService));
        }

        $specialRepeats = BookingRepeat::whereIn('id', $repeatIds)->with(['details_amounts', 'booking.booking_partial_payments'])->get();
        foreach ($specialRepeats as $r) {
            $parentKey = (string) $r->booking_id;
            $den = (float) ($repeatGrandSumsByParent[$parentKey] ?? get_booking_total_amount($r));
            $report->push($this->mapSpecialEarningRow($r, $den, null, $settlementService));
        }

        return $report;
    }

    private function mapSpecialEarningRow(Booking|BookingRepeat $row, ?float $repeatDen, ?float $oneTimeWeight, BookingFinancialSettlementService $settlementService): object
    {
        if ($row instanceof Booking) {
            $main = $row;
            $config = is_array($main->settlement_config) ? $main->settlement_config : [];
            $lineW = 1.0;
            $scaledLossMakingSplit = trim((string) ($main->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
            $pair = provider_payment_tab_earning_commission_pair($main);
            $providerEarning = $pair['provider_earning'];
            $adminCommission = $pair['admin_commission'];
            $scaledCompanyLossLine = 0.0;
            $scaledProviderLossLine = 0.0;
            $scaledWriteoffLine = 0.0;
            $scaledWriteoffCompanyLine = 0.0;
            $scaledWriteoffProviderLine = 0.0;
            if ($scaledLossMakingSplit) {
                $lossPreview = $settlementService->buildPreview($main);
                $scaledCompanyLossLine = round((float) ($lossPreview['scaled_loss_company_share'] ?? 0), 2);
                $scaledProviderLossLine = round((float) ($lossPreview['scaled_loss_provider_share'] ?? 0), 2);
                $scaledWriteoffLine = round(max(0.0, (float) ($lossPreview['scaled_loss_writeoff_amount'] ?? 0)), 2);
                $cfg = is_array($main->settlement_config) ? $main->settlement_config : [];
                $scaledWriteoffCompanyLine = isset($cfg['scaled_loss_writeoff_company_amount']) && is_numeric($cfg['scaled_loss_writeoff_company_amount'])
                    ? round(max(0.0, (float) $cfg['scaled_loss_writeoff_company_amount']), 2) : 0.0;
                $scaledWriteoffProviderLine = isset($cfg['scaled_loss_writeoff_provider_amount']) && is_numeric($cfg['scaled_loss_writeoff_provider_amount'])
                    ? round(max(0.0, (float) $cfg['scaled_loss_writeoff_provider_amount']), 2) : 0.0;
                $grossOnFullBooking = provider_payment_tab_loss_making_earning_display_for_scaled($main, 1.0);
                if ($grossOnFullBooking !== null) {
                    $providerEarning = (float) ($grossOnFullBooking['provider_earning_before_loss'] ?? 0);
                    $adminCommission = (float) ($grossOnFullBooking['admin_commission_before_loss'] ?? 0);
                }
            }
            $settlementCols = provider_payment_tab_earning_report_settlement_columns_for_booking($main);

            return (object) [
                'readable_id' => $main->readable_id ?? $main->id,
                'booking_id' => $main->id,
                'total_amount' => (float) get_provider_payment_tab_revenue_amount_for_booking($main),
                'visiting_charges' => (float) $settlementService->resolveVisitChargesPaid($main, $config),
                'closing_amount' => (float) $settlementService->resolveClosingAmountPaid($main, $config),
                'provider_earning' => $providerEarning,
                'admin_commission' => $adminCommission,
                'scaled_loss_making_split' => $scaledLossMakingSplit,
                'scaled_company_loss_line' => $scaledCompanyLossLine,
                'scaled_provider_loss_line' => $scaledProviderLossLine,
                'scaled_writeoff_line' => $scaledWriteoffLine,
                'scaled_writeoff_company_line' => $scaledWriteoffCompanyLine,
                'scaled_writeoff_provider_line' => $scaledWriteoffProviderLine,
                'amount_received_by_company' => $settlementCols['amount_received_by_company'],
                'amount_received_by_provider' => $settlementCols['amount_received_by_provider'],
                'provider_owes_company' => $settlementCols['provider_owes_company'],
                'company_owes_provider' => $settlementCols['company_owes_provider'],
            ];
        }

        $main = $row->booking;
        $config = is_array($main?->settlement_config) ? $main->settlement_config : [];
        $den = max(0.01, (float) $repeatDen);
        $lineW = get_booking_total_amount($row) / $den;
        $scaledLossMakingSplit = $main instanceof Booking
            && trim((string) ($main->settlement_outcome ?? '')) === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
        $pair = provider_payment_tab_earning_commission_pair($row);
        $providerEarning = $pair['provider_earning'];
        $adminCommission = $pair['admin_commission'];
        $scaledCompanyLossLine = 0.0;
        $scaledProviderLossLine = 0.0;
        $scaledWriteoffLine = 0.0;
        $scaledWriteoffCompanyLine = 0.0;
        $scaledWriteoffProviderLine = 0.0;
        if ($scaledLossMakingSplit && $main instanceof Booking) {
            $lossPreview = $settlementService->buildPreview($main);
            $scaledCompanyLossLine = round((float) ($lossPreview['scaled_loss_company_share'] ?? 0) * $lineW, 2);
            $scaledProviderLossLine = round((float) ($lossPreview['scaled_loss_provider_share'] ?? 0) * $lineW, 2);
            $scaledWriteoffLine = round(max(0.0, (float) ($lossPreview['scaled_loss_writeoff_amount'] ?? 0)) * $lineW, 2);
            $cfg = is_array($main->settlement_config) ? $main->settlement_config : [];
            $scaledWriteoffCompanyLine = isset($cfg['scaled_loss_writeoff_company_amount']) && is_numeric($cfg['scaled_loss_writeoff_company_amount'])
                ? round(max(0.0, (float) $cfg['scaled_loss_writeoff_company_amount']) * $lineW, 2) : 0.0;
            $scaledWriteoffProviderLine = isset($cfg['scaled_loss_writeoff_provider_amount']) && is_numeric($cfg['scaled_loss_writeoff_provider_amount'])
                ? round(max(0.0, (float) $cfg['scaled_loss_writeoff_provider_amount']) * $lineW, 2) : 0.0;
            $grossOnFullBooking = provider_payment_tab_loss_making_earning_display_for_scaled($main, $lineW);
            if ($grossOnFullBooking !== null) {
                $providerEarning = (float) ($grossOnFullBooking['provider_earning_before_loss'] ?? 0);
                $adminCommission = (float) ($grossOnFullBooking['admin_commission_before_loss'] ?? 0);
            }
        }
        $settlementCols = provider_payment_tab_earning_report_settlement_columns_for_repeat($row, $den);

        return (object) [
            'readable_id' => $row->readable_id ?? $row->id,
            'booking_id' => $row->booking_id,
            'total_amount' => (float) get_provider_payment_tab_revenue_amount_for_repeat($row, $den),
            'visiting_charges' => $main ? (float) $settlementService->resolveVisitChargesPaid($main, $config) : 0.0,
            'closing_amount' => $main ? (float) $settlementService->resolveClosingAmountPaid($main, $config) : 0.0,
            'provider_earning' => $providerEarning,
            'admin_commission' => $adminCommission,
            'scaled_loss_making_split' => $scaledLossMakingSplit,
            'scaled_company_loss_line' => $scaledCompanyLossLine,
            'scaled_provider_loss_line' => $scaledProviderLossLine,
            'scaled_writeoff_line' => $scaledWriteoffLine,
            'scaled_writeoff_company_line' => $scaledWriteoffCompanyLine,
            'scaled_writeoff_provider_line' => $scaledWriteoffProviderLine,
            'amount_received_by_company' => $settlementCols['amount_received_by_company'],
            'amount_received_by_provider' => $settlementCols['amount_received_by_provider'],
            'provider_owes_company' => $settlementCols['provider_owes_company'],
            'company_owes_provider' => $settlementCols['company_owes_provider'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listLedger(string $providerId, int $offset, int $limit): array
    {
        $providerBookingIds = DB::table('bookings')->where('provider_id', $providerId)->pluck('id')->toArray();
        $rows = admin_ledger_company_counterparty_for_provider($providerId, $providerBookingIds);
        $paginated = $this->paginateCollection($rows, $offset, $limit);

        return [
            'payment_sub' => 'ledger',
            'data' => $paginated['data']->map(fn (LedgerTransaction $entry) => [
                'date' => $entry->created_at?->toIso8601String(),
                'booking_id' => $entry->booking_id,
                'readable_id' => $entry->booking?->readable_id ?? $entry->booking_id,
                'type' => $this->providerLedgerFlowFromCompanyLedgerType((string) $entry->type),
                'counterparty_flow' => $entry->counterpartyFlowKey(),
                'counterparty_flow_label' => payment_counterparty_flow_arrow_text($entry->counterpartyFlowKey()),
                'payment_method' => $entry->formatPaymentMethodForDisplay(),
                'amount' => round((float) $entry->amount, 2),
                'transaction_id' => (string) ($entry->transaction_id ?? ''),
                'reference' => (string) ($entry->reference_note ?? ''),
                'repeat_readable_id' => $entry->repeat?->readable_id,
                'entry_by' => $entry->resolvedEntryByLabel(),
            ])->values()->all(),
            'total' => $paginated['total'],
            'per_page' => $limit,
            'current_page' => $offset,
            'last_page' => $paginated['last_page'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listRecorded(string $providerId, int $offset, int $limit): array
    {
        $providerBookingIds = DB::table('bookings')->where('provider_id', $providerId)->pluck('id')->toArray();
        $bookingMap = $providerBookingIds === []
            ? collect()
            : Booking::whereIn('id', $providerBookingIds)->get()->keyBy('id');
        $rows = admin_merged_payment_events_for_provider($providerId, $providerBookingIds, $bookingMap);
        $paginated = $this->paginateCollection($rows, $offset, $limit);

        return [
            'payment_sub' => 'recorded',
            'data' => $paginated['data']->map(function ($row) {
                $cf = (string) ($row->company_flow ?? '');
                $cfKey = match ($cf) {
                    Transaction::FLOW_IN => 'in',
                    Transaction::FLOW_OUT => 'out',
                    Transaction::FLOW_NONE => 'none',
                    default => 'unknown',
                };

                return [
                    'date' => $row->date ? \Illuminate\Support\Carbon::parse($row->date)->toIso8601String() : null,
                    'booking_id' => $row->booking_id,
                    'readable_id' => $row->booking_readable_id ?? $row->booking_id,
                    'company_flow' => $cfKey,
                    'provider_flow' => $this->providerFlowFromCompanyFlow($cfKey),
                    'counterparty_flow' => (string) ($row->counterparty_flow ?? ''),
                    'counterparty_flow_label' => payment_counterparty_flow_arrow_text((string) ($row->counterparty_flow ?? 'unknown')),
                    'channel' => (string) ($row->channel ?? ''),
                    'transaction_id' => (string) ($row->transaction_id ?? ''),
                    'amount' => round((float) ($row->amount ?? 0), 2),
                ];
            })->values()->all(),
            'total' => $paginated['total'],
            'per_page' => $limit,
            'current_page' => $offset,
            'last_page' => $paginated['last_page'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listEarning(Provider $provider, int $offset, int $limit, bool $special): array
    {
        $splits = $this->resolveBookingSplits((string) $provider->id);
        $report = $special
            ? $this->buildSpecialEarningReport($splits['special_one_time_ids'], $splits['special_repeat_ids'], $splits['repeat_grand_sums_by_parent'])
            : $this->buildNormalEarningReport($splits['normal_one_time_ids'], $splits['normal_repeat_ids'], $splits['repeat_grand_sums_by_parent']);

        $paginated = $this->paginateCollection($report, $offset, $limit);
        $totals = $this->sumEarningTotals($report, $special);

        return [
            'payment_sub' => $special ? 'special_earning' : 'earning',
            'data' => $paginated['data']->map(fn ($row) => $this->formatEarningRow($row, $special))->values()->all(),
            'totals' => $totals,
            'total' => $paginated['total'],
            'per_page' => $limit,
            'current_page' => $offset,
            'last_page' => $paginated['last_page'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listDisputed(string $providerId, int $offset, int $limit): array
    {
        $query = Booking::query()
            ->where('provider_id', $providerId)
            ->whereNotNull('reopen_disputed_snapshot')
            ->orderByDesc('reopen_resolved_at')
            ->orderByDesc('updated_at');

        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $limit));
        $items = $query->forPage($offset, $limit)->get();

        $totals = [
            'total_booking_amount' => 0.0,
            'refund_company_amount' => 0.0,
            'refund_provider_amount' => 0.0,
            'provider_owes_company' => 0.0,
            'company_owes_provider' => 0.0,
            'retained_from_customer' => 0.0,
            'final_admin_commission' => 0.0,
            'final_provider_earning' => 0.0,
        ];
        foreach (Booking::query()
            ->where('provider_id', $providerId)
            ->whereNotNull('reopen_disputed_snapshot')
            ->cursor() as $disputedRow) {
            $snap = is_array($disputedRow->reopen_disputed_snapshot ?? null) ? $disputedRow->reopen_disputed_snapshot : [];
            $totals['total_booking_amount'] += (float) ($disputedRow->total_booking_amount ?? 0);
            $totals['refund_company_amount'] += (float) ($snap['refund_company_amount'] ?? 0);
            $totals['refund_provider_amount'] += (float) ($snap['refund_provider_amount'] ?? 0);
            $totals['provider_owes_company'] += (float) ($snap['provider_owes_company'] ?? 0);
            $totals['company_owes_provider'] += (float) ($snap['company_owes_provider'] ?? 0);
            $totals['retained_from_customer'] += (float) ($snap['retained_from_customer'] ?? $snap['final_net_to_customer'] ?? 0);
            $totals['final_admin_commission'] += (float) ($snap['final_admin_commission'] ?? 0);
            $totals['final_provider_earning'] += (float) ($snap['final_provider_earning'] ?? 0);
        }
        foreach (array_keys($totals) as $k) {
            $totals[$k] = round($totals[$k], 2);
        }

        return [
            'payment_sub' => 'disputed',
            'data' => $items->map(function (Booking $b) {
                $snap = is_array($b->reopen_disputed_snapshot ?? null) ? $b->reopen_disputed_snapshot : [];
                $disputedAt = $b->reopen_resolved_at ?? $b->updated_at;

                return [
                    'booking_id' => $b->id,
                    'readable_id' => $b->readable_id ?? $b->id,
                    'booking_status' => (string) $b->booking_status,
                    'total_booking_amount' => round((float) ($b->total_booking_amount ?? 0), 2),
                    'refund_company_amount' => round((float) ($snap['refund_company_amount'] ?? 0), 2),
                    'refund_provider_amount' => round((float) ($snap['refund_provider_amount'] ?? 0), 2),
                    'provider_owes_company' => round((float) ($snap['provider_owes_company'] ?? 0), 2),
                    'company_owes_provider' => round((float) ($snap['company_owes_provider'] ?? 0), 2),
                    'retained_from_customer' => round((float) ($snap['retained_from_customer'] ?? $snap['final_net_to_customer'] ?? 0), 2),
                    'final_admin_commission' => round((float) ($snap['final_admin_commission'] ?? 0), 2),
                    'final_provider_earning' => round((float) ($snap['final_provider_earning'] ?? 0), 2),
                    'disputed_at' => $disputedAt?->toIso8601String(),
                ];
            })->values()->all(),
            'totals' => $totals,
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $offset,
            'last_page' => $lastPage,
        ];
    }

    /**
     * @return array<string, float>
     */
    private function sumEarningTotals(Collection $report, bool $special): array
    {
        $keys = $special
            ? [
                'total_amount', 'provider_earning', 'admin_commission', 'scaled_company_loss_line',
                'scaled_provider_loss_line', 'scaled_writeoff_line', 'scaled_writeoff_company_line',
                'scaled_writeoff_provider_line', 'amount_received_by_company', 'amount_received_by_provider',
                'provider_owes_company', 'company_owes_provider',
            ]
            : [
                'total_amount', 'service_charges', 'extra_service_charges', 'parts_charges',
                'provider_earning', 'admin_commission', 'amount_received_by_company', 'amount_received_by_provider',
                'provider_owes_company', 'company_owes_provider',
            ];

        $out = [];
        foreach ($keys as $key) {
            $out[$key] = round((float) $report->sum(fn ($r) => (float) ($r->{$key} ?? 0)), 2);
        }

        if ($special) {
            $visitTotal = 0.0;
            $closeTotal = 0.0;
            $seen = [];
            foreach ($report as $specialRow) {
                $bid = (string) ($specialRow->booking_id ?? '');
                if ($bid === '' || isset($seen[$bid])) {
                    continue;
                }
                $seen[$bid] = true;
                $visitTotal += (float) ($specialRow->visiting_charges ?? 0);
                $closeTotal += (float) ($specialRow->closing_amount ?? 0);
            }
            $out['visiting_charges'] = round($visitTotal, 2);
            $out['closing_amount'] = round($closeTotal, 2);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatEarningRow(object $row, bool $special): array
    {
        $base = [
            'readable_id' => $row->readable_id,
            'booking_id' => $row->booking_id,
            'total_amount' => round((float) ($row->total_amount ?? 0), 2),
            'provider_earning' => round((float) ($row->provider_earning ?? 0), 2),
            'admin_commission' => round((float) ($row->admin_commission ?? 0), 2),
            'amount_received_by_company' => round((float) ($row->amount_received_by_company ?? 0), 2),
            'amount_received_by_provider' => round((float) ($row->amount_received_by_provider ?? 0), 2),
            'provider_owes_company' => round((float) ($row->provider_owes_company ?? 0), 2),
            'company_owes_provider' => round((float) ($row->company_owes_provider ?? 0), 2),
        ];

        if ($special) {
            return array_merge($base, [
                'visiting_charges' => round((float) ($row->visiting_charges ?? 0), 2),
                'closing_amount' => round((float) ($row->closing_amount ?? 0), 2),
                'scaled_loss_making_split' => (bool) ($row->scaled_loss_making_split ?? false),
                'scaled_company_loss_line' => round((float) ($row->scaled_company_loss_line ?? 0), 2),
                'scaled_provider_loss_line' => round((float) ($row->scaled_provider_loss_line ?? 0), 2),
                'scaled_writeoff_line' => round((float) ($row->scaled_writeoff_line ?? 0), 2),
                'scaled_writeoff_company_line' => round((float) ($row->scaled_writeoff_company_line ?? 0), 2),
                'scaled_writeoff_provider_line' => round((float) ($row->scaled_writeoff_provider_line ?? 0), 2),
            ]);
        }

        return array_merge($base, [
            'service_charges' => round((float) ($row->service_charges ?? 0), 2),
            'extra_service_charges' => round((float) ($row->extra_service_charges ?? 0), 2),
            'parts_charges' => round((float) ($row->parts_charges ?? 0), 2),
        ]);
    }

    /**
     * Provider-app ledger IN/OUT is the mirror of company ledger rows (company OUT → provider IN).
     */
    private function providerLedgerFlowFromCompanyLedgerType(string $companyLedgerType): string
    {
        return $companyLedgerType === LedgerTransaction::TYPE_IN ? 'out' : 'in';
    }

    /**
     * @return string in|out|none|unknown
     */
    private function providerFlowFromCompanyFlow(string $companyFlow): string
    {
        return match ($companyFlow) {
            'in' => 'out',
            'out' => 'in',
            'none' => 'none',
            default => 'unknown',
        };
    }

    /**
     * @return array{data: Collection, total: int, last_page: int}
     */
    private function paginateCollection(Collection $collection, int $page, int $perPage): array
    {
        $total = $collection->count();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return [
            'data' => $collection->forPage($page, $perPage)->values(),
            'total' => $total,
            'last_page' => $lastPage,
        ];
    }
}
