<?php

namespace Modules\CustomerModule\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WelcomeBonusController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly Transaction $transaction,
        private readonly User $user,
        private readonly Zone $zone,
    ) {}

    public function report(Request $request): View|Factory|Application
    {
        $this->authorize('welcome_bonus_view');

        $this->validateReportRequest($request);

        $queryParams = $this->buildQueryParams($request);
        $baseQuery = $this->filterQuery($this->welcomeBonusQuery(), $request);
        $stats = $this->buildStats(clone $baseQuery);

        $bonuses = (clone $baseQuery)
            ->with(['to_user:id,first_name,last_name,phone,email,created_at'])
            ->latest('created_at')
            ->paginate(pagination_limit())
            ->appends($queryParams);

        $zones = $this->zone->select('id', 'name')->get();
        $customers = $this->user
            ->inCustomerDirectory()
            ->whereIn('id', $this->welcomeBonusQuery()->distinct()->pluck('to_user_id'))
            ->select('id', 'first_name', 'last_name', 'phone')
            ->orderBy('first_name')
            ->get();

        $welcomeBonusEnabled = (int) (business_config('customer_welcome_bonus', 'customer_config')->live_values ?? 0) === 1;
        $configuredAmount = (float) (business_config('customer_welcome_bonus_amount', 'customer_config')->live_values ?? 0);

        return view('customermodule::welcome-bonus.report', compact(
            'bonuses',
            'stats',
            'queryParams',
            'zones',
            'customers',
            'welcomeBonusEnabled',
            'configuredAmount',
        ));
    }

    public function reportDownload(Request $request): StreamedResponse|string
    {
        $this->authorize('welcome_bonus_export');

        $this->validateReportRequest($request);

        $rows = $this->filterQuery($this->welcomeBonusQuery(), $request)
            ->with(['to_user:id,first_name,last_name,phone,email,created_at'])
            ->latest('created_at')
            ->get();

        return (new FastExcel($rows))->download(time().'-welcome-bonus-report.xlsx', function (Transaction $transaction) {
            $customer = $transaction->to_user;

            return [
                'Customer Name' => $customer ? trim($customer->first_name.' '.$customer->last_name) : null,
                'Customer Phone' => $customer?->phone,
                'Customer Email' => $customer?->email,
                'Registration Date' => $customer?->created_at
                    ? date('d-M-Y h:ia', strtotime($customer->created_at))
                    : null,
                'Bonus Credited Date' => date('d-M-Y h:ia', strtotime($transaction->created_at)),
                'Bonus Amount' => with_currency_symbol($transaction->credit),
                'Wallet Balance After' => with_currency_symbol($transaction->balance),
            ];
        });
    }

    private function welcomeBonusQuery()
    {
        return $this->transaction
            ->newQuery()
            ->where('trx_type', TRX_TYPE['welcome_bonus'])
            ->where('to_user_account', 'user_wallet');
    }

    private function buildStats($query): array
    {
        $totalGranted = (clone $query)->count();
        $totalAmount = (float) (clone $query)->sum('credit');
        $thisMonthQuery = (clone $query)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year);

        return [
            'total_granted' => $totalGranted,
            'total_amount' => $totalAmount,
            'this_month_granted' => (clone $thisMonthQuery)->count(),
            'this_month_amount' => (float) (clone $thisMonthQuery)->sum('credit'),
        ];
    }

    private function filterQuery($query, Request $request)
    {
        return $query
            ->when($request->filled('customer_ids'), function ($builder) use ($request) {
                $builder->whereIn('to_user_id', (array) $request->customer_ids);
            })
            ->when($request->filled('zone_ids'), function ($builder) use ($request) {
                $builder->whereHas('to_user.zones', fn ($zoneQuery) => $zoneQuery->whereIn('zone_id', (array) $request->zone_ids));
            })
            ->when($request->filled('search'), function ($builder) use ($request) {
                $keys = preg_split('/\s+/', trim((string) $request->search), -1, PREG_SPLIT_NO_EMPTY);

                $builder->whereHas('to_user', function ($searchQuery) use ($keys) {
                    foreach ($keys as $key) {
                        $searchQuery->where(function ($nested) use ($key) {
                            $nested->where('first_name', 'LIKE', '%'.$key.'%')
                                ->orWhere('last_name', 'LIKE', '%'.$key.'%')
                                ->orWhere('phone', 'LIKE', '%'.$key.'%')
                                ->orWhere('email', 'LIKE', '%'.$key.'%');
                        });
                    }
                });
            })
            ->when($request->filled('date_range') && $request->date_range === 'custom_date', function ($builder) use ($request) {
                $builder->whereBetween('created_at', [
                    Carbon::parse($request->from)->startOfDay(),
                    Carbon::parse($request->to)->endOfDay(),
                ]);
            })
            ->when($request->filled('date_range') && $request->date_range !== 'custom_date', function ($builder) use ($request) {
                $this->applyPresetDateRange($builder, (string) $request->date_range);
            });
    }

    private function applyPresetDateRange($query, string $dateRange): void
    {
        match ($dateRange) {
            'this_week' => $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]),
            'last_week' => $query->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]),
            'this_month' => $query->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year),
            'last_month' => $query->whereMonth('created_at', Carbon::now()->subMonth()->month)->whereYear('created_at', Carbon::now()->subMonth()->year),
            'last_15_days' => $query->whereBetween('created_at', [Carbon::now()->subDays(15), Carbon::now()]),
            'this_year' => $query->whereYear('created_at', Carbon::now()->year),
            'last_year' => $query->whereYear('created_at', Carbon::now()->subYear()->year),
            'last_6_month' => $query->whereBetween('created_at', [Carbon::now()->subMonths(6), Carbon::now()]),
            default => null,
        };
    }

    private function buildQueryParams(Request $request): array
    {
        return array_filter([
            'search' => $request->input('search'),
            'date_range' => $request->input('date_range'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'zone_ids' => $request->input('zone_ids'),
            'customer_ids' => $request->input('customer_ids'),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    private function validateReportRequest(Request $request): void
    {
        Validator::make($request->all(), [
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => 'uuid',
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'uuid',
            'date_range' => 'nullable|in:all_time,this_week,last_week,this_month,last_month,last_15_days,this_year,last_year,last_6_month,custom_date',
            'from' => $request->input('date_range') === 'custom_date' ? 'required|date' : 'nullable|date',
            'to' => $request->input('date_range') === 'custom_date' ? 'required|date' : 'nullable|date',
        ])->validate();
    }
}
