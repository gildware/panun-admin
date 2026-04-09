<?php

namespace Modules\TransactionModule\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Full payment activity: company ledger (IN/OUT) plus direct customer→provider booking payments (NONE).
     * The separate Ledger screen stays company counterparty only.
     *
     * Query trx_type: all | credit (IN) | debit (OUT). NONE rows appear only when trx_type=all.
     */
    public function index(Request $request): Renderable
    {
        $this->authorize('transaction_view');

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'trx_type' => 'in:debit,credit,all',
        ]);

        $search = trim((string) $request->get('search', ''));
        $trxType = $request->get('trx_type', 'all');

        $from = $request->get('from_date');
        $to = $request->get('to_date');

        $ledgerQuery = $this->adminTransactionsLedgerQuery($from, $to, $trxType, $search);

        $ledgerRows = $ledgerQuery
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get();

        $merged = $ledgerRows->map(fn (LedgerTransaction $e) => $e);

        if ($trxType === 'all') {
            $crossParty = $this->adminTransactionsCrossPartyQuery($from, $to, $search)
                ->with(['booking' => fn ($q) => $q->select('id', 'readable_id')])
                ->orderByDesc('created_at')
                ->get();
            foreach ($crossParty as $txn) {
                $merged->push($txn);
            }
        }

        $merged = $merged
            ->sortByDesc(fn ($item) => $this->adminTransactionListSortTimestamp($item))
            ->values();

        $perPage = pagination_limit();
        $page = max(1, (int) $request->get('page', 1));
        $total = $merged->count();
        $slice = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        $transactions = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $baseQuery = LedgerTransaction::query()->whereCompanyCounterpartyOnly();
        if ($from && $to) {
            $baseQuery->whereBetween('date', [
                Carbon::parse($from)->startOfDay()->toDateString(),
                Carbon::parse($to)->endOfDay()->toDateString(),
            ]);
        } elseif ($from) {
            $baseQuery->whereDate('date', '>=', Carbon::parse($from)->toDateString());
        } elseif ($to) {
            $baseQuery->whereDate('date', '<=', Carbon::parse($to)->toDateString());
        }

        $totalIn = (clone $baseQuery)->in()->sum('amount');
        $totalOut = (clone $baseQuery)->out()->sum('amount');

        $data = [
            'totalIn' => round((float) $totalIn, 2),
            'totalOut' => round((float) $totalOut, 2),
        ];

        return view('transactionmodule::admin.list', [
            'transactions' => $transactions,
            'data' => $data,
            'trxType' => $trxType,
            'search' => $search,
            'from_date' => $from,
            'to_date' => $to,
        ]);
    }

    /**
     * Export same merged rows as the list (ledger + NONE when trx_type=all).
     */
    public function download(Request $request): StreamedResponse
    {
        $this->authorize('transaction_export');

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'trx_type' => 'in:debit,credit,all',
        ]);

        $search = trim((string) $request->get('search', ''));
        $trxType = $request->get('trx_type', 'all');
        $from = $request->get('from_date');
        $to = $request->get('to_date');

        $ledgerRows = $this->adminTransactionsLedgerQuery($from, $to, $trxType, $search)
            ->with([
                'booking' => fn ($q) => $q->select('id', 'readable_id'),
                'bookingPartialPayment' => fn ($q) => $q->select('id', 'paid_with', 'booking_id'),
                'creator' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'email'),
            ])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get();

        $merged = $ledgerRows->map(fn (LedgerTransaction $e) => $e);

        if ($trxType === 'all') {
            $crossParty = $this->adminTransactionsCrossPartyQuery($from, $to, $search)
                ->with(['booking' => fn ($q) => $q->select('id', 'readable_id')])
                ->orderByDesc('created_at')
                ->get();
            foreach ($crossParty as $txn) {
                $merged->push($txn);
            }
        }

        $rows = $merged
            ->sortByDesc(fn ($item) => $this->adminTransactionListSortTimestamp($item))
            ->values()
            ->map(function ($e) {
                if ($e instanceof LedgerTransaction) {
                    return [
                        'date' => $e->date?->format('Y-m-d'),
                        'created_at' => $e->created_at?->toDateTimeString(),
                        'type' => $e->type,
                        'amount' => $e->amount,
                        'flow' => payment_counterparty_flow_arrow_text($e->counterpartyFlowKey()),
                        'channel' => $e->formatPaymentMethodForDisplay(),
                        'transaction_id' => $e->transaction_id,
                        'booking_readable_id' => $e->booking?->readable_id,
                        'reason' => $e->reason,
                        'received_by' => $e->received_by,
                        'reference_note' => $e->reference_note,
                        'entry_by' => $e->resolvedEntryByLabel(),
                    ];
                }

                /** @var Transaction $e */
                $amt = round(max((float) $e->debit, (float) $e->credit), 2);

                return [
                    'date' => $e->created_at?->format('Y-m-d'),
                    'created_at' => $e->created_at?->toDateTimeString(),
                    'type' => Transaction::FLOW_NONE,
                    'amount' => $amt,
                    'flow' => payment_counterparty_flow_arrow_text('customer_to_provider'),
                    'channel' => format_booking_payment_event_channel_label($e->trx_type),
                    'transaction_id' => (string) ($e->ref_trx_id ?? $e->id),
                    'booking_readable_id' => $e->booking?->readable_id,
                    'reason' => $e->trx_type,
                    'received_by' => 'provider',
                    'reference_note' => $e->reference_note,
                    'entry_by' => '—',
                ];
            });

        return (new FastExcel($rows))->download(time() . '-payment-transactions.xlsx');
    }

    private function adminTransactionsLedgerQuery(?string $from, ?string $to, string $trxType, string $search)
    {
        $query = LedgerTransaction::query()
            ->whereCompanyCounterpartyOnly()
            ->with([
                'booking' => fn ($q) => $q->select('id', 'readable_id'),
                'bookingPartialPayment' => fn ($q) => $q->select('id', 'paid_with', 'booking_id'),
                'creator' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'email'),
                'provider' => fn ($q) => $q->select('id', 'company_name'),
            ]);

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

        if ($trxType === 'credit') {
            $query->in();
        } elseif ($trxType === 'debit') {
            $query->out();
        }

        if ($search !== '') {
            $keys = array_filter(explode(' ', $search));
            $query->where(function ($q) use ($keys) {
                foreach ($keys as $key) {
                    $q->where(function ($q2) use ($key) {
                        $q2->where('transaction_id', 'LIKE', '%' . $key . '%')
                            ->orWhere('reference_note', 'LIKE', '%' . $key . '%')
                            ->orWhere('payment_method', 'LIKE', '%' . $key . '%')
                            ->orWhere('id', 'LIKE', '%' . $key . '%')
                            ->orWhereHas('booking', fn ($bq) => $bq->where('readable_id', 'LIKE', '%' . $key . '%'));
                    });
                }
            });
        }

        return $query;
    }

    private function adminTransactionsCrossPartyQuery(?string $from, ?string $to, string $search)
    {
        $query = Transaction::query()
            ->where('trx_type', TRX_TYPE['cross_party_booking_payment']);

        if ($from && $to) {
            $query->whereBetween('created_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ]);
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
                        $q2->where('reference_note', 'LIKE', '%' . $key . '%')
                            ->orWhere('id', 'LIKE', '%' . $key . '%')
                            ->orWhere('ref_trx_id', 'LIKE', '%' . $key . '%')
                            ->orWhereHas('booking', fn ($bq) => $bq->where('readable_id', 'LIKE', '%' . $key . '%'));
                    });
                }
            });
        }

        return $query;
    }

    private function adminTransactionListSortTimestamp(LedgerTransaction|Transaction $item): int
    {
        if ($item instanceof LedgerTransaction) {
            $t = $item->created_at ?? $item->date;

            return $t instanceof Carbon ? $t->getTimestamp() : (int) strtotime((string) $t);
        }

        $t = $item->created_at;

        return $t instanceof Carbon ? $t->getTimestamp() : 0;
    }
}
