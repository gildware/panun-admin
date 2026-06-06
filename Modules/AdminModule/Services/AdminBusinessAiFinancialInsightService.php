<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingCompensation;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\ProviderManagement\Entities\WithdrawRequest;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Modules\TransactionModule\Services\PendingProviderBalanceListingService;

class AdminBusinessAiFinancialInsightService
{
    public function __construct(
        protected PendingProviderBalanceListingService $pendingBalances,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function queryLedger(array $args): array
    {
        $from = ! empty($args['date_from']) ? (string) $args['date_from'] : null;
        $to = ! empty($args['date_to']) ? (string) $args['date_to'] : null;
        $type = strtolower(trim((string) ($args['type'] ?? 'all')));
        $search = trim((string) ($args['search'] ?? ''));
        $limit = max(1, min(50, (int) ($args['limit'] ?? 25)));

        $base = LedgerTransaction::query()->whereCompanyCounterpartyOnly();
        $this->applyLedgerDateFilter($base, $from, $to);

        $totalIn = (float) (clone $base)->in()->sum('amount');
        $totalOut = (float) (clone $base)->out()->sum('amount');

        $q = (clone $base)->with([
            'booking:id,readable_id',
            'bookingPartialPayment:id,paid_with,booking_id',
            'creator:id,first_name,last_name,email',
            'provider:id,company_name',
        ]);

        if ($type === 'in') {
            $q->in();
        } elseif ($type === 'out') {
            $q->out();
        }

        if ($search !== '') {
            $this->applyLedgerSearch($q, $search);
        }

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('date')->orderByDesc('created_at')->limit($limit)->get();

        return [
            'ok' => true,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'total_in' => round($totalIn, 2),
            'total_out' => round($totalOut, 2),
            'net' => round($totalIn - $totalOut, 2),
            'entries' => $rows->map(fn (LedgerTransaction $e) => $this->formatLedgerRow($e))->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function queryTransactions(array $args): array
    {
        $from = ! empty($args['date_from']) ? (string) $args['date_from'] : null;
        $to = ! empty($args['date_to']) ? (string) $args['date_to'] : null;
        $trxType = strtolower(trim((string) ($args['trx_type'] ?? 'all')));
        $search = trim((string) ($args['search'] ?? ''));
        $limit = max(1, min(50, (int) ($args['limit'] ?? 25)));

        $ledgerQuery = $this->ledgerTransactionsQuery($from, $to, $trxType, $search);
        $ledgerRows = $ledgerQuery->get();
        $merged = $ledgerRows->map(fn (LedgerTransaction $e) => $e);

        if ($trxType === 'all') {
            $crossParty = $this->crossPartyTransactionsQuery($from, $to, $search)
                ->with(['booking:id,readable_id'])
                ->get();
            foreach ($crossParty as $txn) {
                $merged->push($txn);
            }
        }

        $merged = $merged
            ->sortByDesc(fn ($item) => $this->transactionSortTimestamp($item))
            ->values();

        $base = LedgerTransaction::query()->whereCompanyCounterpartyOnly();
        $this->applyLedgerDateFilter($base, $from, $to);

        return [
            'ok' => true,
            'total_matching' => $merged->count(),
            'returned' => min($limit, $merged->count()),
            'total_in' => round((float) (clone $base)->in()->sum('amount'), 2),
            'total_out' => round((float) (clone $base)->out()->sum('amount'), 2),
            'transactions' => $merged->take($limit)->map(fn ($e) => $this->formatTransactionRow($e))->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function queryWithdrawRequests(array $args): array
    {
        $status = strtolower(trim((string) ($args['status'] ?? 'all')));
        $search = trim((string) ($args['search'] ?? ''));
        $limit = max(1, min(50, (int) ($args['limit'] ?? 25)));

        $q = WithdrawRequest::query()->with(['provider:id,company_name,company_phone', 'withdraw_method']);
        if ($status !== '' && $status !== 'all') {
            $q->where('request_status', $status);
        }
        if ($search !== '') {
            $keys = array_filter(explode(' ', $search));
            $q->whereHas('provider', function ($pq) use ($keys) {
                foreach ($keys as $key) {
                    $pq->where('company_name', 'like', '%'.$key.'%');
                }
            });
        }

        $byStatus = WithdrawRequest::query()
            ->selectRaw('request_status, count(*) as cnt')
            ->groupBy('request_status')
            ->pluck('cnt', 'request_status');

        $total = (clone $q)->count();
        $rows = $q->orderByDesc('created_at')->limit($limit)->get();

        return [
            'ok' => true,
            'total_matching' => $total,
            'returned' => $rows->count(),
            'by_status' => $byStatus,
            'pending_amount' => round((float) WithdrawRequest::query()->where('request_status', 'pending')->sum('amount'), 2),
            'withdraw_requests' => $rows->map(fn (WithdrawRequest $w) => [
                'id' => $w->id,
                'provider' => $w->provider?->company_name,
                'provider_phone' => $w->provider?->company_phone,
                'amount' => (float) ($w->amount ?? 0),
                'status' => $w->request_status,
                'method' => $w->withdraw_method?->method_name ?? null,
                'note' => $w->note,
                'admin_note' => $w->admin_note,
                'created_at' => $w->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function queryPendingProviderBalances(array $args): array
    {
        $search = trim((string) ($args['search'] ?? ''));
        $categoryId = ! empty($args['category_id']) ? (string) $args['category_id'] : null;
        $sort = (string) ($args['sort'] ?? 'balance_desc');
        $limit = max(1, min(50, (int) ($args['limit'] ?? 25)));

        $rows = $this->pendingBalances->buildRows(
            $search !== '' ? $search : null,
            $categoryId,
            in_array($sort, ['balance_desc', 'balance_asc', 'name_asc'], true) ? $sort : 'balance_desc',
        );

        $totalDue = round(array_sum(array_column($rows, 'balance_due')), 2);

        return [
            'ok' => true,
            'providers_with_balance' => count($rows),
            'total_balance_due' => $totalDue,
            'returned' => min($limit, count($rows)),
            'providers' => array_slice($rows, 0, $limit),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function reportEarning(?Carbon $from, ?Carbon $to): array
    {
        $amountsQ = BookingDetailsAmount::query()
            ->where(function ($q) {
                $q->whereHas('booking', fn ($bq) => $bq->forRevenueReporting())
                    ->orWhereHas('repeat', fn ($rq) => $rq->ofBookingStatus('completed'));
            });

        if ($from) {
            $amountsQ->where(function ($q) use ($from) {
                $q->whereHas('booking', fn ($bq) => $bq->where('created_at', '>=', $from))
                    ->orWhereHas('repeat', fn ($rq) => $rq->where('created_at', '>=', $from));
            });
        }
        if ($to) {
            $amountsQ->where(function ($q) use ($to) {
                $q->whereHas('booking', fn ($bq) => $bq->where('created_at', '<=', $to))
                    ->orWhereHas('repeat', fn ($rq) => $rq->where('created_at', '<=', $to));
            });
        }

        $adminCommission = (float) (clone $amountsQ)->sum('admin_commission');
        $providerEarning = (float) (clone $amountsQ)->sum('provider_earning');
        $discountByAdmin = (float) (clone $amountsQ)->sum('discount_by_admin');
        $couponByAdmin = (float) (clone $amountsQ)->sum('coupon_discount_by_admin');
        $campaignByAdmin = (float) (clone $amountsQ)->sum('campaign_discount_by_admin');
        $netEarning = $adminCommission - $discountByAdmin - $couponByAdmin - $campaignByAdmin;

        $ledgerBase = LedgerTransaction::query()->whereCompanyCounterpartyOnly();
        if ($from) {
            $ledgerBase->whereDate('date', '>=', $from->toDateString());
        }
        if ($to) {
            $ledgerBase->whereDate('date', '<=', $to->toDateString());
        }

        return [
            'ok' => true,
            'report_type' => 'earning',
            'date_from' => $from?->toDateString(),
            'date_to' => $to?->toDateString(),
            'data' => [
                'gross_admin_commission' => round($adminCommission, 2),
                'provider_earnings' => round($providerEarning, 2),
                'discounts_by_admin' => round($discountByAdmin, 2),
                'coupon_discount_by_admin' => round($couponByAdmin, 2),
                'campaign_discount_by_admin' => round($campaignByAdmin, 2),
                'net_company_earning' => round($netEarning, 2),
                'ledger_total_in' => round((float) (clone $ledgerBase)->in()->sum('amount'), 2),
                'ledger_total_out' => round((float) (clone $ledgerBase)->out()->sum('amount'), 2),
                'financial_summary' => admin_dashboard_financial_summary_metrics(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function reportExpense(?Carbon $from, ?Carbon $to): array
    {
        $amountsQ = BookingDetailsAmount::query()
            ->where(function ($q) {
                $q->whereHas('booking', fn ($bq) => $bq->forRevenueReporting())
                    ->orWhereHas('repeat', fn ($rq) => $rq->ofBookingStatus('completed'));
            });

        if ($from) {
            $amountsQ->where(function ($q) use ($from) {
                $q->whereHas('booking', fn ($bq) => $bq->where('created_at', '>=', $from))
                    ->orWhereHas('repeat', fn ($rq) => $rq->where('created_at', '>=', $from));
            });
        }
        if ($to) {
            $amountsQ->where(function ($q) use ($to) {
                $q->whereHas('booking', fn ($bq) => $bq->where('created_at', '<=', $to))
                    ->orWhereHas('repeat', fn ($rq) => $rq->where('created_at', '<=', $to));
            });
        }

        $compQ = BookingCompensation::query()
            ->where('from_party', BookingCompensation::PARTY_COMPANY);
        if ($from) {
            $compQ->where('created_at', '>=', $from);
        }
        if ($to) {
            $compQ->where('created_at', '<=', $to);
        }

        $toCustomers = (float) (clone $compQ)->where('to_party', BookingCompensation::PARTY_CUSTOMER)->sum('amount');
        $toProviders = (float) (clone $compQ)->where('to_party', BookingCompensation::PARTY_PROVIDER)->sum('amount');

        return [
            'ok' => true,
            'report_type' => 'expense',
            'date_from' => $from?->toDateString(),
            'date_to' => $to?->toDateString(),
            'data' => [
                'discount_by_admin' => round((float) (clone $amountsQ)->sum('discount_by_admin'), 2),
                'coupon_discount_by_admin' => round((float) (clone $amountsQ)->sum('coupon_discount_by_admin'), 2),
                'campaign_discount_by_admin' => round((float) (clone $amountsQ)->sum('campaign_discount_by_admin'), 2),
                'compensation_to_customers' => round($toCustomers, 2),
                'compensation_to_providers' => round($toProviders, 2),
                'total_promo_and_compensation' => round(
                    (float) (clone $amountsQ)->sum('discount_by_admin')
                    + (float) (clone $amountsQ)->sum('coupon_discount_by_admin')
                    + (float) (clone $amountsQ)->sum('campaign_discount_by_admin')
                    + $toCustomers
                    + $toProviders,
                    2
                ),
                'ledger_out_total' => round((float) LedgerTransaction::query()
                    ->whereCompanyCounterpartyOnly()
                    ->when($from, fn ($q) => $q->whereDate('date', '>=', $from->toDateString()))
                    ->when($to, fn ($q) => $q->whereDate('date', '<=', $to->toDateString()))
                    ->out()
                    ->sum('amount'), 2),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reportCommission(?Carbon $from, ?Carbon $to): array
    {
        $earning = $this->reportEarning($from, $to);

        $byZone = Booking::query()
            ->forRevenueReporting()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->with('zone:id,name')
            ->get(['id', 'zone_id'])
            ->groupBy(fn (Booking $b) => $b->zone?->name ?? 'Unknown')
            ->map(fn (Collection $group, string $zone) => [
                'zone' => $zone,
                'bookings' => $group->count(),
            ])
            ->sortByDesc('bookings')
            ->take(12)
            ->values()
            ->all();

        return [
            'ok' => true,
            'report_type' => 'commission_earning',
            'date_from' => $from?->toDateString(),
            'date_to' => $to?->toDateString(),
            'data' => array_merge($earning['data'] ?? [], [
                'bookings_by_zone' => $byZone,
            ]),
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<LedgerTransaction>  $query
     */
    private function applyLedgerDateFilter($query, ?string $from, ?string $to): void
    {
        if ($from && $to) {
            $query->whereBetween('date', [
                Carbon::parse($from)->startOfDay()->toDateString(),
                Carbon::parse($to)->endOfDay()->toDateString(),
            ]);
        } elseif ($from) {
            $query->whereDate('date', '>=', Carbon::parse($from)->toDateString());
        } elseif ($to) {
            $query->whereDate('date', '<=', Carbon::parse($to)->toDateString());
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<LedgerTransaction>  $query
     */
    private function applyLedgerSearch($query, string $search): void
    {
        $keys = array_filter(explode(' ', $search));
        $query->where(function ($q) use ($keys) {
            foreach ($keys as $key) {
                $q->where(function ($q2) use ($key) {
                    $q2->where('transaction_id', 'like', '%'.$key.'%')
                        ->orWhere('reference_note', 'like', '%'.$key.'%')
                        ->orWhere('payment_method', 'like', '%'.$key.'%')
                        ->orWhere('id', 'like', '%'.$key.'%')
                        ->orWhereHas('booking', fn ($bq) => $bq->where('readable_id', 'like', '%'.$key.'%'));
                });
            }
        });
    }

    private function ledgerTransactionsQuery(?string $from, ?string $to, string $trxType, string $search)
    {
        $query = LedgerTransaction::query()
            ->whereCompanyCounterpartyOnly()
            ->with([
                'booking:id,readable_id',
                'bookingPartialPayment:id,paid_with,booking_id',
                'creator:id,first_name,last_name,email',
                'provider:id,company_name',
            ]);

        $this->applyLedgerDateFilter($query, $from, $to);

        if ($trxType === 'credit') {
            $query->in();
        } elseif ($trxType === 'debit') {
            $query->out();
        }

        if ($search !== '') {
            $this->applyLedgerSearch($query, $search);
        }

        return $query;
    }

    private function crossPartyTransactionsQuery(?string $from, ?string $to, string $search)
    {
        $query = Transaction::query()->where('trx_type', TRX_TYPE['cross_party_booking_payment']);

        if ($from && $to) {
            $query->whereBetween('created_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]);
        } elseif ($from) {
            $query->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        } elseif ($to) {
            $query->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        if ($search !== '') {
            $keys = array_filter(explode(' ', $search));
            $query->where(function ($q) use ($keys) {
                foreach ($keys as $key) {
                    $q->where(function ($q2) use ($key) {
                        $q2->where('reference_note', 'like', '%'.$key.'%')
                            ->orWhere('id', 'like', '%'.$key.'%')
                            ->orWhere('ref_trx_id', 'like', '%'.$key.'%')
                            ->orWhereHas('booking', fn ($bq) => $bq->where('readable_id', 'like', '%'.$key.'%'));
                    });
                }
            });
        }

        return $query;
    }

    private function transactionSortTimestamp(LedgerTransaction|Transaction $item): int
    {
        if ($item instanceof LedgerTransaction) {
            $t = $item->created_at ?? $item->date;

            return $t instanceof Carbon ? $t->getTimestamp() : (int) strtotime((string) $t);
        }

        $t = $item->created_at;

        return $t instanceof Carbon ? $t->getTimestamp() : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatLedgerRow(LedgerTransaction $e): array
    {
        return [
            'id' => $e->id,
            'date' => $e->date?->toDateString(),
            'type' => $e->type,
            'amount' => (float) ($e->amount ?? 0),
            'payment_method' => $e->formatPaymentMethodForDisplay(),
            'transaction_id' => $e->transaction_id,
            'booking_readable_id' => $e->booking?->readable_id,
            'provider' => $e->provider?->company_name,
            'reason' => $e->reason,
            'received_by' => $e->received_by,
            'reference_note' => $e->reference_note,
            'entry_by' => $e->resolvedEntryByLabel(),
            'created_at' => $e->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTransactionRow(LedgerTransaction|Transaction $e): array
    {
        if ($e instanceof LedgerTransaction) {
            return $this->formatLedgerRow($e);
        }

        $amt = round(max((float) $e->debit, (float) $e->credit), 2);

        return [
            'id' => $e->id,
            'date' => $e->created_at?->toDateString(),
            'type' => Transaction::FLOW_NONE,
            'amount' => $amt,
            'payment_method' => format_booking_payment_event_channel_label($e->trx_type),
            'transaction_id' => (string) ($e->ref_trx_id ?? $e->id),
            'booking_readable_id' => $e->booking?->readable_id,
            'reason' => $e->trx_type,
            'received_by' => 'provider',
            'reference_note' => $e->reference_note,
            'entry_by' => '—',
            'created_at' => $e->created_at?->toIso8601String(),
        ];
    }
}
