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
use Modules\BookingModule\Entities\Booking;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReferralEarningController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly User $user,
        private readonly Zone $zone,
    ) {}

    public function report(Request $request): View|Factory|Application
    {
        $this->authorize('customer_view');

        $this->validateReportRequest($request);

        $queryParams = $this->buildQueryParams($request);
        $referralReward = $this->referralRewardAmount();
        $baseQuery = $this->filterQuery($this->referredUsersQuery(), $request);

        $stats = $this->buildStats(clone $baseQuery, $referralReward);

        $referrals = (clone $baseQuery)
            ->with(['referred_by_user:id,first_name,last_name,phone,ref_code,email'])
            ->latest('created_at')
            ->paginate(pagination_limit())
            ->appends($queryParams);

        $zones = $this->zone->select('id', 'name')->get();
        $referrers = $this->user
            ->inCustomerDirectory()
            ->whereIn('id', User::query()->inCustomerDirectory()->whereNotNull('referred_by')->distinct()->pluck('referred_by'))
            ->select('id', 'first_name', 'last_name', 'phone', 'ref_code')
            ->orderBy('first_name')
            ->get();

        $statusFilter = $request->input('status', 'all');

        return view('customermodule::referral-earning.report', compact(
            'referrals',
            'stats',
            'queryParams',
            'zones',
            'referrers',
            'referralReward',
            'statusFilter',
        ));
    }

    public function reportDownload(Request $request): StreamedResponse|string
    {
        $this->authorize('customer_export');

        $this->validateReportRequest($request);

        $referralReward = $this->referralRewardAmount();
        $rows = $this->filterQuery($this->referredUsersQuery(), $request)
            ->with(['referred_by_user:id,first_name,last_name,phone,ref_code,email'])
            ->latest('created_at')
            ->get();

        return (new FastExcel($rows))->download(time().'-referral-report.xlsx', function (User $referredUser) use ($referralReward) {
            $completed = (int) ($referredUser->completed_bookings_count ?? 0) > 0;
            $referrer = $referredUser->referred_by_user;

            return [
                'Referred Customer' => trim($referredUser->first_name.' '.$referredUser->last_name),
                'Referred Phone' => $referredUser->phone,
                'Referred Email' => $referredUser->email,
                'Registration Date' => date('d-M-Y h:ia', strtotime($referredUser->created_at)),
                'Referrer Name' => $referrer ? trim($referrer->first_name.' '.$referrer->last_name) : null,
                'Referrer Phone' => $referrer?->phone,
                'Referral Code' => $referrer?->ref_code,
                'First Booking Status' => $completed ? 'Completed' : 'Pending',
                'First Booking Date' => $referredUser->first_completed_booking_at
                    ? date('d-M-Y h:ia', strtotime($referredUser->first_completed_booking_at))
                    : null,
                'Referrer Earned Amount' => with_currency_symbol($completed ? $referralReward : 0),
            ];
        });
    }

    private function referredUsersQuery()
    {
        return $this->user
            ->newQuery()
            ->inCustomerDirectory()
            ->whereNotNull('referred_by')
            ->withCount([
                'bookings as completed_bookings_count' => fn ($query) => $query->where('booking_status', 'completed'),
            ])
            ->select('users.*')
            ->selectSub(
                Booking::query()
                    ->select('updated_at')
                    ->whereColumn('customer_id', 'users.id')
                    ->where('booking_status', 'completed')
                    ->orderBy('updated_at')
                    ->limit(1),
                'first_completed_booking_at'
            );
    }

    private function buildStats($query, float $referralReward): array
    {
        $totalReferred = (clone $query)->count();
        $completedFirstBooking = (clone $query)
            ->whereHas('bookings', fn ($bookingQuery) => $bookingQuery->where('booking_status', 'completed'))
            ->count();
        $pendingFirstBooking = max(0, $totalReferred - $completedFirstBooking);
        $totalEarned = $completedFirstBooking * $referralReward;
        $totalPending = $pendingFirstBooking * $referralReward;
        $activeReferrers = (clone $query)->distinct('referred_by')->count('referred_by');

        return [
            'total_referred' => $totalReferred,
            'active_referrers' => $activeReferrers,
            'completed_first_booking' => $completedFirstBooking,
            'pending_first_booking' => $pendingFirstBooking,
            'total_earned' => $totalEarned,
            'total_pending' => $totalPending,
        ];
    }

    private function filterQuery($query, Request $request)
    {
        return $query
            ->when($request->filled('status') && $request->status !== 'all', function ($builder) use ($request) {
                if ($request->status === 'completed') {
                    $builder->whereHas('bookings', fn ($bookingQuery) => $bookingQuery->where('booking_status', 'completed'));
                } elseif ($request->status === 'pending') {
                    $builder->whereDoesntHave('bookings', fn ($bookingQuery) => $bookingQuery->where('booking_status', 'completed'));
                }
            })
            ->when($request->filled('referrer_ids'), function ($builder) use ($request) {
                $builder->whereIn('referred_by', (array) $request->referrer_ids);
            })
            ->when($request->filled('zone_ids'), function ($builder) use ($request) {
                $builder->where(function ($zoneQuery) use ($request) {
                    $zoneQuery->whereHas('zones', fn ($q) => $q->whereIn('zone_id', (array) $request->zone_ids))
                        ->orWhereHas('referred_by_user.zones', fn ($q) => $q->whereIn('zone_id', (array) $request->zone_ids));
                });
            })
            ->when($request->filled('search'), function ($builder) use ($request) {
                $keys = preg_split('/\s+/', trim((string) $request->search), -1, PREG_SPLIT_NO_EMPTY);

                $builder->where(function ($searchQuery) use ($keys) {
                    foreach ($keys as $key) {
                        $searchQuery->where(function ($nested) use ($key) {
                            $nested->where('first_name', 'LIKE', '%'.$key.'%')
                                ->orWhere('last_name', 'LIKE', '%'.$key.'%')
                                ->orWhere('phone', 'LIKE', '%'.$key.'%')
                                ->orWhere('email', 'LIKE', '%'.$key.'%')
                                ->orWhereHas('referred_by_user', function ($referrerQuery) use ($key) {
                                    $referrerQuery->where('first_name', 'LIKE', '%'.$key.'%')
                                        ->orWhere('last_name', 'LIKE', '%'.$key.'%')
                                        ->orWhere('phone', 'LIKE', '%'.$key.'%')
                                        ->orWhere('ref_code', 'LIKE', '%'.$key.'%');
                                });
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
        $params = array_filter([
            'search' => $request->input('search'),
            'status' => $request->input('status', 'all'),
            'date_range' => $request->input('date_range'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'zone_ids' => $request->input('zone_ids'),
            'referrer_ids' => $request->input('referrer_ids'),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);

        return $params;
    }

    private function validateReportRequest(Request $request): void
    {
        Validator::make($request->all(), [
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => 'uuid',
            'referrer_ids' => 'nullable|array',
            'referrer_ids.*' => 'uuid',
            'status' => 'nullable|in:all,completed,pending',
            'date_range' => 'nullable|in:all_time,this_week,last_week,this_month,last_month,last_15_days,this_year,last_year,last_6_month,custom_date',
            'from' => $request->input('date_range') === 'custom_date' ? 'required|date' : 'nullable|date',
            'to' => $request->input('date_range') === 'custom_date' ? 'required|date' : 'nullable|date',
        ])->validate();
    }

    private function referralRewardAmount(): float
    {
        return (float) (business_config('referral_value_per_currency_unit', 'customer_config')->live_values ?? 0);
    }
}
