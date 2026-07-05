<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingCompensation;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Services\CustomerPerformanceService;
use Modules\ProviderManagement\Services\ProviderPerformanceService;
use Modules\ServiceManagement\Entities\Service;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\UserManagement\Entities\User;

class AdminBusinessAiDashboardInsightService
{
    public function __construct(
        protected LeadOpenStatusService $leadOpenStatus,
        protected ProviderPerformanceService $providerPerformance,
        protected CustomerPerformanceService $customerPerformance,
    ) {}

    /**
     * Full admin dashboard snapshot — mirrors dashboard widgets and top cards.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $financial = admin_dashboard_financial_summary_metrics();
        $topCards = $this->topCards();
        $year = (int) date('Y');
        $earningChart = $this->earningChartForYear($year);

        $recentLedger = LedgerTransaction::query()
            ->whereCompanyCounterpartyOnly()
            ->with(['booking:id,readable_id', 'creator:id,first_name,last_name,email'])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->take(8)
            ->get()
            ->map(fn ($t) => [
                'amount' => (float) ($t->amount ?? 0),
                'type' => $t->transaction_type,
                'booking_readable_id' => $t->booking?->readable_id,
                'date' => $t->date,
                'created_by' => $t->creator
                    ? trim($t->creator->first_name.' '.$t->creator->last_name)
                    : null,
            ])
            ->values()
            ->all();

        $compensation = [
            'company_to_customers' => round((float) BookingCompensation::query()
                ->where('from_party', BookingCompensation::PARTY_COMPANY)
                ->where('to_party', BookingCompensation::PARTY_CUSTOMER)
                ->sum('amount'), 2),
            'company_to_providers' => round((float) BookingCompensation::query()
                ->where('from_party', BookingCompensation::PARTY_COMPANY)
                ->where('to_party', BookingCompensation::PARTY_PROVIDER)
                ->sum('amount'), 2),
        ];

        $pendingBookings = Booking::query()
            ->with(['customer:id,first_name,last_name', 'provider:id,company_name', 'detail.service:id,name'])
            ->where('booking_status', 'pending')
            ->latest()
            ->take(8)
            ->get()
            ->map(fn (Booking $b) => [
                'readable_id' => $b->readable_id,
                'customer' => $b->customer ? trim($b->customer->first_name.' '.$b->customer->last_name) : null,
                'provider' => $b->provider?->company_name,
                'service' => $b->detail->first()?->service?->name,
                'created_at' => $b->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $bookingFollowups = $this->todaysPendingBookingFollowups();
        $leadFollowups = $this->todaysPendingLeadFollowups();

        return [
            'ok' => true,
            'as_of' => now()->toIso8601String(),
            'top_cards' => $topCards,
            'financial_summary' => $financial,
            'compensation_totals' => $compensation,
            'recent_ledger_transactions' => $recentLedger,
            'this_month_ledger_count' => LedgerTransaction::query()
                ->whereCompanyCounterpartyOnly()
                ->whereYear('date', Carbon::now()->year)
                ->whereMonth('date', Carbon::now()->month)
                ->count(),
            'pending_bookings_sample' => $pendingBookings,
            'top_providers' => $this->topProviders(8),
            'top_customers' => $this->topCustomers(8),
            'todays_pending_booking_followups' => $bookingFollowups['items'],
            'todays_pending_booking_followups_total' => $bookingFollowups['total'],
            'todays_pending_lead_followups' => $leadFollowups['items'],
            'todays_pending_lead_followups_total' => $leadFollowups['total'],
            'earning_chart_year' => $year,
            'earning_chart' => $earningChart,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function topCards(): array
    {
        $baseQuery = BookingDetailsAmount::whereHas('booking', function ($query) {
            $query->forRevenueReporting();
        })->orWhereHas('repeat', function ($subQuery) {
            $subQuery->ofBookingStatus('completed');
        });
        $scaledAdj = admin_dashboard_scaled_admin_commission_adjustments(null);
        $adminCommission = (float) $baseQuery->sum('admin_commission') + (float) ($scaledAdj['total'] ?? 0);
        $ourEarning = $adminCommission
            - (float) $baseQuery->sum('discount_by_admin')
            - (float) $baseQuery->sum('coupon_discount_by_admin')
            - (float) $baseQuery->sum('campaign_discount_by_admin');

        $allCompletedRepeats = BookingRepeat::ofBookingStatus('completed')->with('booking.extra_services')->get();
        $repeatLineTotalByParentId = provider_payment_tab_sum_repeat_line_totals_by_parent_booking_id($allCompletedRepeats);

        $totalRevenue = 0.0;
        $sparePartsTotal = 0.0;
        foreach (Booking::query()->forRevenueReporting()->with('extra_services')->cursor() as $b) {
            $slice = get_admin_dashboard_reporting_total_and_spare_for_booking($b);
            $totalRevenue += $slice['reported_total'];
            $sparePartsTotal += $slice['spare_parts'];
        }
        foreach ($allCompletedRepeats as $r) {
            $parentKey = (string) $r->booking_id;
            $den = (float) ($repeatLineTotalByParentId[$parentKey] ?? get_booking_total_amount($r));
            $slice = get_admin_dashboard_reporting_total_and_spare_for_repeat($r, $den);
            $totalRevenue += $slice['reported_total'];
            $sparePartsTotal += $slice['spare_parts'];
        }

        $financial = admin_dashboard_financial_summary_metrics();

        return [
            'total_revenue' => round($totalRevenue, 2),
            'service_charges_total' => round($totalRevenue - $sparePartsTotal, 2),
            'spare_parts_total' => round($sparePartsTotal, 2),
            'our_earning' => round($ourEarning, 2),
            'payable_to_providers' => $financial['payable_to_providers'] ?? 0,
            'unsettled_withdraws_total' => $financial['unsettled_withdraws_total'] ?? 0,
            'unsettled_withdraws_pending' => $financial['unsettled_withdraws_pending'] ?? 0,
            'unsettled_withdraws_approved' => $financial['unsettled_withdraws_approved'] ?? 0,
            'payable_to_customers' => $financial['payable_to_customers'] ?? 0,
            'balance_with_providers' => $financial['balance_with_providers'] ?? 0,
            'total_amount_received_by_company' => $financial['total_amount_received_by_company'] ?? 0,
            'total_loss_in_all_bookings' => $financial['total_loss_in_all_bookings'] ?? 0,
            'total_bad_debt_with_customers' => $financial['total_bad_debt_with_customers'] ?? 0,
            'total_write_off_company' => $financial['total_write_off_company'] ?? 0,
            'total_write_off_provider' => $financial['total_write_off_provider'] ?? 0,
            'total_customers' => User::query()->where('user_type', 'customer')->count(),
            'total_providers_approved' => Provider::query()->where('is_approved', 1)->count(),
            'total_services' => Service::query()->count(),
            'total_leads' => Lead::query()->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topProviders(int $limit): array
    {
        $providers = Provider::query()
            ->with(['owner:id,first_name,last_name'])
            ->where('is_approved', 1)
            ->withCount(['bookings as completed_bookings_count' => function ($query) {
                $query->forRevenueReporting();
            }])
            ->having('completed_bookings_count', '>', 0)
            ->get();

        if ($providers->isEmpty()) {
            return [];
        }

        $metrics = $this->providerPerformance->getAggregatedProviderPerformanceMetrics(
            $providers->pluck('id')->all()
        );

        return $providers
            ->sort(function ($a, $b) use ($metrics) {
                $sa = (int) ($metrics->get($a->id)->performance_score ?? 0);
                $sb = (int) ($metrics->get($b->id)->performance_score ?? 0);
                if ($sa !== $sb) {
                    return $sb <=> $sa;
                }

                return ($b->completed_bookings_count ?? 0) <=> ($a->completed_bookings_count ?? 0);
            })
            ->take($limit)
            ->map(fn ($p) => [
                'id' => $p->id,
                'company' => $p->company_name,
                'performance_score' => (int) ($metrics->get($p->id)->performance_score ?? 0),
                'completed_bookings' => (int) ($p->completed_bookings_count ?? 0),
                'avg_rating' => (float) ($p->avg_rating ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topCustomers(int $limit): array
    {
        $customers = User::query()
            ->where('user_type', 'customer')
            ->withCount(['bookings as completed_bookings_count' => function ($query) {
                $query->where('booking_status', 'completed');
            }])
            ->having('completed_bookings_count', '>', 0)
            ->get();

        if ($customers->isEmpty()) {
            return [];
        }

        $metrics = $this->customerPerformance->getAggregatedCustomerPerformanceMetrics(
            $customers->pluck('id')->all()
        );

        return $customers
            ->sort(function ($a, $b) use ($metrics) {
                $sa = (int) ($metrics->get($a->id)->performance_score ?? 0);
                $sb = (int) ($metrics->get($b->id)->performance_score ?? 0);
                if ($sa !== $sb) {
                    return $sb <=> $sa;
                }

                return ($b->completed_bookings_count ?? 0) <=> ($a->completed_bookings_count ?? 0);
            })
            ->take($limit)
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => trim($c->first_name.' '.$c->last_name),
                'performance_score' => (int) ($metrics->get($c->id)->performance_score ?? 0),
                'completed_bookings' => (int) ($c->completed_bookings_count ?? 0),
                'phone' => $c->phone,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{total: int, items: list<array<string, mixed>>}
     */
    private function todaysPendingBookingFollowups(): array
    {
        $base = BookingFollowup::query()
            ->where('status', 'scheduled')
            ->whereDate('date', '<=', Carbon::today())
            ->whereHas('booking', function ($bookingQuery) {
                $bookingQuery->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
            });

        $total = (clone $base)->count();
        $items = (clone $base)
            ->with(['booking.assignee', 'booking.customer', 'booking.provider'])
            ->orderBy('date')
            ->take(8)
            ->get()
            ->map(fn (BookingFollowup $f) => [
                'date' => $f->date?->toIso8601String(),
                'for' => $f->for,
                'reason' => $f->reason,
                'booking_readable_id' => $f->booking?->readable_id,
                'customer' => $f->booking?->customer
                    ? trim($f->booking->customer->first_name.' '.$f->booking->customer->last_name)
                    : null,
                'provider' => $f->booking?->provider?->company_name,
                'assignee' => $f->booking?->assignee
                    ? trim($f->booking->assignee->first_name.' '.$f->booking->assignee->last_name)
                    : null,
            ])
            ->values()
            ->all();

        return ['total' => $total, 'items' => $items];
    }

    /**
     * @return array{total: int, items: list<array<string, mixed>>}
     */
    private function todaysPendingLeadFollowups(): array
    {
        $base = Lead::query()
            ->whereNotNull('next_followup_at')
            ->whereDate('next_followup_at', '<=', Carbon::today());
        $this->leadOpenStatus->restrictQueryToOpenLeads($base);

        $total = (clone $base)->count();
        $rows = (clone $base)->orderBy('next_followup_at')->take(8)->get();
        $handledByIds = $rows->pluck('handled_by')->filter()->unique()->values()->all();
        $handledByUsers = $handledByIds !== []
            ? User::query()->whereIn('id', $handledByIds)->get(['id', 'first_name', 'last_name', 'email'])->keyBy(fn ($u) => (string) $u->id)
            : collect();

        $items = $rows->map(function (Lead $lead) use ($handledByUsers) {
            $user = $lead->handled_by ? $handledByUsers->get((string) $lead->handled_by) : null;
            $fullName = $user ? trim(($user->first_name ?? '').' '.($user->last_name ?? '')) : '';

            return [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone_number,
                'lead_type' => $lead->lead_type,
                'next_followup_at' => $lead->next_followup_at?->toIso8601String(),
                'handled_by' => $fullName ?: ($user->email ?? $lead->handled_by),
            ];
        })->values()->all();

        return ['total' => $total, 'items' => $items];
    }

    /**
     * @return array{months: list<int>, total_revenue: list<float>, commission_earning: list<float>}
     */
    private function earningChartForYear(int $year): array
    {
        $amounts = BookingDetailsAmount::query()
            ->whereYear('created_at', '=', $year)
            ->where(function ($q) {
                $q->whereHas('booking', function ($query) {
                    $query->forRevenueReporting();
                })->orWhereHas('repeat', function ($subQuery) {
                    $subQuery->ofBookingStatus('completed');
                });
            })
            ->select(
                DB::raw('sum(admin_commission) as admin_commission'),
                DB::raw('sum(discount_by_admin) as discount_by_admin'),
                DB::raw('sum(coupon_discount_by_admin) as coupon_discount_by_admin'),
                DB::raw('sum(campaign_discount_by_admin) as campaign_discount_by_admin'),
                DB::raw('MONTH(created_at) month')
            )
            ->groupby('month')
            ->get()
            ->toArray();

        $adminEarningByMonth = [];
        foreach ($amounts as $item) {
            $month = (int) ($item['month'] ?? 0);
            if ($month < 1 || $month > 12) {
                continue;
            }
            $adminEarningByMonth[$month] = (float) ($item['admin_commission'] ?? 0)
                - (float) ($item['discount_by_admin'] ?? 0)
                - (float) ($item['coupon_discount_by_admin'] ?? 0)
                - (float) ($item['campaign_discount_by_admin'] ?? 0);
        }

        $scaledCommissionAdjYear = admin_dashboard_scaled_admin_commission_adjustments($year);
        foreach (range(1, 12) as $m) {
            $adminEarningByMonth[$m] = ($adminEarningByMonth[$m] ?? 0)
                + (float) (($scaledCommissionAdjYear['by_month'] ?? [])[$m] ?? 0);
        }

        $allCompletedRepeats = BookingRepeat::ofBookingStatus('completed')->with('booking.extra_services')->get();
        $repeatLineTotalByParentId = provider_payment_tab_sum_repeat_line_totals_by_parent_booking_id($allCompletedRepeats);
        $revenueByMonth = $this->reportedRevenueByMonth($year, $repeatLineTotalByParentId);

        $months = range(1, 12);
        $totalRevenue = [];
        $commissionEarning = [];
        foreach ($months as $month) {
            $totalRevenue[] = round((float) ($revenueByMonth[$month] ?? 0), 2);
            $commissionEarning[] = round((float) ($adminEarningByMonth[$month] ?? 0), 2);
        }

        return [
            'months' => $months,
            'total_revenue' => $totalRevenue,
            'commission_earning' => $commissionEarning,
        ];
    }

    /**
     * @param  array<string, float>  $repeatLineTotalByParentId
     * @return array<int, float>
     */
    private function reportedRevenueByMonth(int $year, array $repeatLineTotalByParentId): array
    {
        $byMonth = array_fill(1, 12, 0.0);

        foreach (Booking::query()->forRevenueReporting()->with('extra_services')->whereYear('created_at', $year)->cursor() as $b) {
            $month = (int) $b->created_at?->month;
            if ($month < 1 || $month > 12) {
                continue;
            }
            $slice = get_admin_dashboard_reporting_total_and_spare_for_booking($b);
            $byMonth[$month] += $slice['reported_total'];
        }

        foreach (BookingRepeat::ofBookingStatus('completed')->whereYear('created_at', $year)->with('booking.extra_services')->cursor() as $r) {
            $month = (int) $r->created_at?->month;
            if ($month < 1 || $month > 12) {
                continue;
            }
            $parentKey = (string) $r->booking_id;
            $den = (float) ($repeatLineTotalByParentId[$parentKey] ?? get_booking_total_amount($r));
            $slice = get_admin_dashboard_reporting_total_and_spare_for_repeat($r, $den);
            $byMonth[$month] += $slice['reported_total'];
        }

        return $byMonth;
    }
}
