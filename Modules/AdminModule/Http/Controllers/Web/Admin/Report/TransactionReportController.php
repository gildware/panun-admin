<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin\Report;

use Auth;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use function pagination_limit;
use function view;
use function with_currency_symbol;

class TransactionReportController extends Controller
{
    protected Zone $zone;

    protected Provider $provider;

    protected Category $categories;

    protected Booking $booking;

    protected Account $account;

    protected Service $service;

    protected User $user;

    protected Transaction $transaction;

    protected BookingDetailsAmount $booking_details_amount;

    use AuthorizesRequests;

    public function __construct(
        Zone $zone,
        Provider $provider,
        Category $categories,
        Service $service,
        Booking $booking,
        Account $account,
        User $user,
        Transaction $transaction,
        BookingDetailsAmount $booking_details_amount
    ) {
        $this->zone = $zone;
        $this->provider = $provider;
        $this->categories = $categories;
        $this->booking = $booking;

        $this->service = $service;
        $this->account = $account;
        $this->user = $user;
        $this->transaction = $transaction;
        $this->booking_details_amount = $booking_details_amount;
    }

    /**
     * Payment ledger report (same source as Transaction management → All Transactions).
     */
    public function getTransactionReport(Request $request): Renderable
    {
        $this->authorize('report_view');
        Validator::make($request->all(), [
            'zone_ids' => 'array',
            'zone_ids.*' => 'uuid',
            'provider_ids' => 'array',
            'provider_ids.*' => 'uuid',
            'date_range' => 'in:all_time, this_week, last_week, this_month, last_month, last_15_days, this_year, last_year, last_6_month, this_year_1st_quarter, this_year_2nd_quarter, this_year_3rd_quarter, this_year_4th_quarter, custom_date',
            'from' => $request['date_range'] == 'custom_date' ? 'required' : '',
            'to' => $request['date_range'] == 'custom_date' ? 'required' : '',
            'filter_by' => 'in:collect_cash,payment,withdraw,commission,all',

            'transaction_type' => 'in:all,debit,credit',
        ]);

        $zones = $this->zone->select('id', 'name')->get();
        $providers = $this->provider->ofApproval(1)->select('id', 'company_name', 'company_phone')->get();

        $queryParams = array_merge(
            [
                'transaction_type' => $request->input('transaction_type', 'all'),
                'filter_by' => $request->input('filter_by', 'all'),
            ],
            $request->only(['search', 'zone_ids', 'provider_ids', 'date_range', 'from', 'to'])
        );

        $adminAccount = Account::where('user_id', Auth::user()->id)->first();
        $commission_earning = BookingDetailsAmount::where(function ($query) {
            $query->whereHas('booking', function ($subQuery) {
                $subQuery->ofBookingStatus('completed');
            })->orWhereHas('repeat', function ($subQuery) {
                $subQuery->ofBookingStatus('completed');
            });
        })->sum('admin_commission');

        $subscription_amounts = $this->transaction->whereIn('trx_type', ['subscription_purchase', 'subscription_renew', 'subscription_shift'])->sum('credit');

        $extra_fee = $this->transaction
            ->where('trx_type', TRX_TYPE['received_extra_fee'])
            ->sum('credit');

        $adminTotalEarning = $commission_earning + $subscription_amounts + $extra_fee;

        $ledgerQuery = $this->ledgerReportBaseQuery($request);

        $ledgerTotalIn = round((float) (clone $ledgerQuery)->in()->sum('amount'), 2);
        $ledgerTotalOut = round((float) (clone $ledgerQuery)->out()->sum('amount'), 2);
        $ledgerNet = round($ledgerTotalIn - $ledgerTotalOut, 2);

        $filteredTransactions = (clone $ledgerQuery)
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(pagination_limit())
            ->withQueryString();

        return view('adminmodule::admin.report.transaction', compact(
            'zones',
            'providers',
            'filteredTransactions',
            'adminAccount',
            'commission_earning',
            'adminTotalEarning',
            'extra_fee',
            'queryParams',
            'ledgerTotalIn',
            'ledgerTotalOut',
            'ledgerNet'
        ));
    }

    public function downloadTransactionReport(Request $request): StreamedResponse|string
    {
        $this->authorize('report_export');
        Validator::make($request->all(), [
            'zone_ids' => 'array',
            'zone_ids.*' => 'uuid',
            'provider_ids' => 'array',
            'provider_ids.*' => 'uuid',
            'date_range' => 'in:all_time, this_week, last_week, this_month, last_month, last_15_days, this_year, last_year, last_6_month, this_year_1st_quarter, this_year_2nd_quarter, this_year_3rd_quarter, this_year_4th_quarter, custom_date',
            'from' => $request['date_range'] == 'custom_date' ? 'required' : '',
            'to' => $request['date_range'] == 'custom_date' ? 'required' : '',
            'filter_by' => 'in:collect_cash,payment,withdraw,commission,all',

            'transaction_type' => 'in:all,debit,credit',
        ]);

        $ledgerQuery = $this->ledgerReportBaseQuery($request);

        $rows = (clone $ledgerQuery)
            ->with(['booking' => fn ($q) => $q->select('id', 'readable_id')])
            ->orderByDesc('date')
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
                    'ledger_id' => $e->id,
                    'date' => $e->date?->format('Y-m-d'),
                    'created_at' => $e->created_at?->toDateTimeString(),
                    'type' => $e->type,
                    'flow' => $flow,
                    'channel' => $e->payment_method,
                    'amount' => $e->amount,
                    'gateway_or_reference_id' => $e->transaction_id,
                    'booking_readable_id' => $e->booking?->readable_id,
                    'reason' => $e->reason,
                    'received_by' => $e->received_by,
                    'reference_note' => $e->reference_note,
                    'entry_by' => $e->resolvedEntryByLabel(),
                ];
            });

        return (new FastExcel($rows))->download(time() . '-transaction-report-payments.xlsx');
    }

    protected function ledgerReportBaseQuery(Request $request): Builder
    {
        $query = LedgerTransaction::query()->with([
            'booking' => fn ($q) => $q->select('id', 'readable_id', 'zone_id', 'provider_id'),
            'bookingPartialPayment' => fn ($q) => $q->select('id', 'paid_with', 'booking_id'),
            'creator' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'email'),
            'provider' => fn ($q) => $q->select('id', 'company_name'),
        ]);

        $transactionType = $request->input('transaction_type', 'all');
        if ($transactionType === 'credit') {
            $query->in();
        } elseif ($transactionType === 'debit') {
            $query->out();
        }

        $zoneIds = $this->filterUuidList($request->input('zone_ids', []));
        if ($zoneIds !== []) {
            $query->whereHas('booking', fn ($b) => $b->whereIn('zone_id', $zoneIds));
        }

        $providerIds = $this->filterUuidList($request->input('provider_ids', []));
        if ($providerIds !== []) {
            $query->where(function ($q) use ($providerIds) {
                $q->whereHas('booking', fn ($b) => $b->whereIn('provider_id', $providerIds))
                    ->orWhereIn('provider_id', $providerIds);
            });
        }

        if ($request->filled('date_range')) {
            $this->applyLedgerDateRange($query, $request);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $keys = array_filter(explode(' ', $search));
            $query->where(function ($outer) use ($keys) {
                foreach ($keys as $key) {
                    $outer->where(function ($q2) use ($key) {
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

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, string>
     */
    protected function filterUuidList(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            if ($id === 'all' || $id === null || $id === '' || $id === '0') {
                continue;
            }
            $id = (string) $id;
            if (Str::isUuid($id)) {
                $out[] = $id;
            }
        }

        return array_values(array_unique($out));
    }

    protected function applyLedgerDateRange(Builder $query, Request $request): void
    {
        $dateRange = $request->input('date_range');
        if (!$dateRange || $dateRange === 'all_time') {
            return;
        }

        if ($dateRange === 'custom_date' && $request->filled('from') && $request->filled('to')) {
            $query->whereBetween('date', [
                Carbon::parse($request->from)->toDateString(),
                Carbon::parse($request->to)->toDateString(),
            ]);

            return;
        }

        switch ($dateRange) {
            case 'this_week':
                $query->whereBetween('date', [
                    Carbon::now()->startOfWeek()->toDateString(),
                    Carbon::now()->endOfWeek()->toDateString(),
                ]);
                break;
            case 'last_week':
                $startOfWeek = Carbon::now()->subWeek()->startOfWeek()->toDateString();
                $endOfWeek = Carbon::now()->subWeek()->endOfWeek()->toDateString();
                $query->whereBetween('date', [$startOfWeek, $endOfWeek]);
                break;
            case 'this_month':
                $query->whereBetween('date', [
                    Carbon::now()->startOfMonth()->toDateString(),
                    Carbon::now()->endOfMonth()->toDateString(),
                ]);
                break;
            case 'last_month':
                $query->whereBetween('date', [
                    Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                    Carbon::now()->subMonth()->endOfMonth()->toDateString(),
                ]);
                break;
            case 'last_15_days':
                $query->whereBetween('date', [
                    Carbon::now()->subDays(15)->toDateString(),
                    Carbon::now()->toDateString(),
                ]);
                break;
            case 'this_year':
                $query->whereBetween('date', [
                    Carbon::now()->startOfYear()->toDateString(),
                    Carbon::now()->endOfYear()->toDateString(),
                ]);
                break;
            case 'last_year':
                $query->whereBetween('date', [
                    Carbon::now()->subYear()->startOfYear()->toDateString(),
                    Carbon::now()->subYear()->endOfYear()->toDateString(),
                ]);
                break;
            case 'last_6_month':
                $query->whereBetween('date', [
                    Carbon::now()->subMonths(6)->toDateString(),
                    Carbon::now()->toDateString(),
                ]);
                break;
            case 'this_year_1st_quarter':
                $y = Carbon::now()->year;
                $query->whereBetween('date', [
                    Carbon::create($y, 1, 1)->toDateString(),
                    Carbon::create($y, 3, 31)->toDateString(),
                ]);
                break;
            case 'this_year_2nd_quarter':
                $y = Carbon::now()->year;
                $query->whereBetween('date', [
                    Carbon::create($y, 4, 1)->toDateString(),
                    Carbon::create($y, 6, 30)->toDateString(),
                ]);
                break;
            case 'this_year_3rd_quarter':
                $y = Carbon::now()->year;
                $query->whereBetween('date', [
                    Carbon::create($y, 7, 1)->toDateString(),
                    Carbon::create($y, 9, 30)->toDateString(),
                ]);
                break;
            case 'this_year_4th_quarter':
                $y = Carbon::now()->year;
                $query->whereBetween('date', [
                    Carbon::create($y, 10, 1)->toDateString(),
                    Carbon::create($y, 12, 31)->toDateString(),
                ]);
                break;
            default:
                break;
        }
    }
}
