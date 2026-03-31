<?php

namespace Modules\TransactionModule\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Payment ledger: all money-in / money-out records (bookings, payouts, refunds, etc.).
     * Query trx_type: all | credit (IN) | debit (OUT) for backward-compatible URLs.
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

        $query = LedgerTransaction::query()->with([
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

        $transactions = $query->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(pagination_limit())
            ->withQueryString();

        $baseQuery = LedgerTransaction::query();
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
     * Export payment ledger rows (same filters as list).
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

        $query = LedgerTransaction::query()->with([
            'booking' => fn ($q) => $q->select('id', 'readable_id'),
            'bookingPartialPayment' => fn ($q) => $q->select('id', 'paid_with', 'booking_id'),
            'creator' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'email'),
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

        $rows = $query->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (LedgerTransaction $e) {
                $flow = '';
                if ($e->type === LedgerTransaction::TYPE_IN) {
                    if ($e->received_by === LedgerTransaction::RECEIVED_BY_PROVIDER) {
                        $flow = 'Customer paid to provider';
                    } elseif ($e->received_by === LedgerTransaction::RECEIVED_BY_COMPANY) {
                        $flow = 'Customer paid to company';
                    }
                } elseif ($e->type === LedgerTransaction::TYPE_OUT) {
                    if ($e->reason === LedgerTransaction::REASON_REFUND) {
                        $flow = 'Company paid to customer';
                    } elseif ($e->reason === LedgerTransaction::REASON_PROVIDER_PAYOUT) {
                        $flow = 'Provider payout';
                    }
                }

                return [
                    'date' => $e->date?->format('Y-m-d'),
                    'created_at' => $e->created_at?->toDateTimeString(),
                    'type' => $e->type,
                    'flow' => $flow,
                    'channel' => $e->payment_method,
                    'amount' => $e->amount,
                    'transaction_id' => $e->transaction_id,
                    'booking_readable_id' => $e->booking?->readable_id,
                    'reason' => $e->reason,
                    'received_by' => $e->received_by,
                    'reference_note' => $e->reference_note,
                    'entry_by' => $e->resolvedEntryByLabel(),
                ];
            });

        return (new FastExcel($rows))->download(time() . '-payment-transactions.xlsx');
    }
}
