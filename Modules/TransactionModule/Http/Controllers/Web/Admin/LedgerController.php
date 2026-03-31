<?php

namespace Modules\TransactionModule\Http\Controllers\Web\Admin;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;
use Modules\TransactionModule\Entities\LedgerTransaction;

class LedgerController extends Controller
{
    use AuthorizesRequests;

    /**
     * Company money-in / money-out ledger.
     */
    public function index(Request $request): View
    {
        $this->authorize('ledger_view');

        $type = $request->get('type', 'all'); // all | IN | OUT
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

        if ($type === LedgerTransaction::TYPE_IN) {
            $query->in();
        } elseif ($type === LedgerTransaction::TYPE_OUT) {
            $query->out();
        }

        $entries = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(pagination_limit())->withQueryString();

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

        return view('transactionmodule::admin.ledger', [
            'entries' => $entries,
            'totalIn' => round($totalIn, 2),
            'totalOut' => round($totalOut, 2),
            'type' => $type,
            'from_date' => $from,
            'to_date' => $to,
        ]);
    }
}
