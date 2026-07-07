<?php

namespace Modules\AdminModule\Services\Report;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AdminModule\Entities\StaffPresencePeriod;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\UserManagement\Entities\User;

class DailyEmployeeReportService
{
    private const METRIC_KEYS = [
        'leads_added',
        'leads_handled',
        'lead_followups',
        'booking_followups',
        'bookings_added',
        'whatsapp_chats',
        'whatsapp_replies',
        'outbound_enquiries',
    ];

    /**
     * @param  Collection<int, User>  $employees
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     totals: array<string, int|float>,
     *     employee_totals: list<array<string, mixed>>
     * }
     */
    public function buildReport(Collection $employees, Carbon $dateFrom, Carbon $dateTo): array
    {
        if ($employees->isEmpty()) {
            return [
                'rows' => [],
                'totals' => $this->emptyTotals(),
                'employee_totals' => [],
            ];
        }

        $employeeIds = $employees->pluck('id')->map(fn ($id) => (string) $id)->all();
        $employeeNames = $employees->mapWithKeys(function (User $user) {
            $name = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->email ?? (string) $user->id);

            return [(string) $user->id => $name];
        })->all();

        $rangeStart = $dateFrom->copy()->startOfDay();
        $rangeEnd = $dateTo->copy()->endOfDay();

        $metrics = $this->initializeMetrics($employeeIds, $dateFrom, $dateTo);
        $this->aggregateLeadsAdded($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregateLeadsHandled($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregateLeadFollowups($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregateBookingFollowups($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregateBookingsAdded($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregateOutboundEnquiries($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregateWhatsAppActivity($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregatePresenceHours($metrics, $employees, $dateFrom, $dateTo);

        $rows = [];
        $totals = $this->emptyTotals();
        $employeeTotalsMap = [];

        foreach ($employeeIds as $employeeId) {
            $employeeTotalsMap[$employeeId] = [
                'employee_id' => $employeeId,
                'employee_name' => $employeeNames[$employeeId] ?? $employeeId,
                'active_days' => 0,
                'leads_added' => 0,
                'leads_handled' => 0,
                'lead_followups' => 0,
                'booking_followups' => 0,
                'bookings_added' => 0,
                'whatsapp_chats' => 0,
                'whatsapp_replies' => 0,
                'outbound_enquiries' => 0,
                'online_seconds' => 0,
            ];
        }

        foreach (CarbonPeriod::create($dateFrom->copy()->startOfDay(), $dateTo->copy()->startOfDay()) as $date) {
            $day = $date->toDateString();

            foreach ($employeeIds as $employeeId) {
                $dayMetrics = $metrics[$day][$employeeId] ?? $this->emptyDayMetrics();
                $hasActivity = $this->rowHasActivity($dayMetrics);

                if ($hasActivity) {
                    $employeeTotalsMap[$employeeId]['active_days']++;
                }

                foreach (self::METRIC_KEYS as $key) {
                    $employeeTotalsMap[$employeeId][$key] += (int) $dayMetrics[$key];
                }
                $employeeTotalsMap[$employeeId]['online_seconds'] += (int) $dayMetrics['online_seconds'];

                $rows[] = [
                    'date' => $day,
                    'date_label' => $date->format('d M Y'),
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeNames[$employeeId] ?? $employeeId,
                    'leads_added' => (int) $dayMetrics['leads_added'],
                    'leads_handled' => (int) $dayMetrics['leads_handled'],
                    'lead_followups' => (int) $dayMetrics['lead_followups'],
                    'booking_followups' => (int) $dayMetrics['booking_followups'],
                    'bookings_added' => (int) $dayMetrics['bookings_added'],
                    'whatsapp_chats' => (int) $dayMetrics['whatsapp_chats'],
                    'whatsapp_replies' => (int) $dayMetrics['whatsapp_replies'],
                    'outbound_enquiries' => (int) $dayMetrics['outbound_enquiries'],
                    'online_seconds' => (int) $dayMetrics['online_seconds'],
                    'online_hours' => $this->formatDuration((int) $dayMetrics['online_seconds']),
                    'has_activity' => $hasActivity,
                ];
            }
        }

        usort($rows, function (array $a, array $b) {
            $dateCompare = strcmp($b['date'], $a['date']);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return strcmp($a['employee_name'], $b['employee_name']);
        });

        $employeeTotals = array_values(array_map(function (array $item) {
            $item['online_hours'] = $this->formatDuration((int) $item['online_seconds']);

            return $item;
        }, $employeeTotalsMap));

        usort($employeeTotals, fn (array $a, array $b) => strcmp($a['employee_name'], $b['employee_name']));

        foreach (self::METRIC_KEYS as $key) {
            $totals[$key] = (int) array_sum(array_column($employeeTotals, $key));
        }
        $totals['online_seconds'] = (int) array_sum(array_column($employeeTotals, 'online_seconds'));
        $totals['online_hours'] = $this->formatDuration((int) $totals['online_seconds']);

        return [
            'rows' => $rows,
            'totals' => $totals,
            'employee_totals' => $employeeTotals,
        ];
    }

    /**
     * @return Collection<int, User>
     */
    public function loadEmployees(?array $employeeIds = null): Collection
    {
        $query = User::query()
            ->whereIn('user_type', ['super-admin', 'admin-employee'])
            ->ofStatus(1)
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($employeeIds !== null && $employeeIds !== []) {
            $query->whereIn('id', $employeeIds);
        }

        return $query->get(['id', 'first_name', 'last_name', 'email']);
    }

    /**
     * @param  list<string>  $employeeIds
     * @return array<string, array<string, array<string, int>>>
     */
    private function initializeMetrics(array $employeeIds, Carbon $dateFrom, Carbon $dateTo): array
    {
        $metrics = [];

        foreach (CarbonPeriod::create($dateFrom->copy()->startOfDay(), $dateTo->copy()->startOfDay()) as $date) {
            $day = $date->toDateString();
            $metrics[$day] = [];
            foreach ($employeeIds as $employeeId) {
                $metrics[$day][$employeeId] = $this->emptyDayMetrics();
            }
        }

        return $metrics;
    }

    /**
     * @return array<string, int>
     */
    private function emptyDayMetrics(): array
    {
        return [
            'leads_added' => 0,
            'leads_handled' => 0,
            'lead_followups' => 0,
            'booking_followups' => 0,
            'bookings_added' => 0,
            'whatsapp_chats' => 0,
            'whatsapp_replies' => 0,
            'outbound_enquiries' => 0,
            'online_seconds' => 0,
        ];
    }

    /**
     * @return array<string, int|float|string>
     */
    private function emptyTotals(): array
    {
        return [
            'leads_added' => 0,
            'leads_handled' => 0,
            'lead_followups' => 0,
            'booking_followups' => 0,
            'bookings_added' => 0,
            'whatsapp_chats' => 0,
            'whatsapp_replies' => 0,
            'outbound_enquiries' => 0,
            'online_seconds' => 0,
            'online_hours' => '0m',
        ];
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function aggregateLeadsAdded(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $rows = DB::table((new Lead)->getTable())
            ->selectRaw('DATE(created_at) as day, created_by as user_id, COUNT(*) as total')
            ->whereIn('created_by', $employeeIds)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->whereNotNull('created_by')
            ->groupBy('day', 'user_id')
            ->get();

        $this->applyGroupedCounts($metrics, $rows, 'leads_added');
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function aggregateLeadsHandled(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $rows = DB::table((new Lead)->getTable())
            ->selectRaw('DATE(date_time_of_lead_received) as day, handled_by as user_id, COUNT(*) as total')
            ->whereIn('handled_by', $employeeIds)
            ->whereBetween('date_time_of_lead_received', [$rangeStart, $rangeEnd])
            ->whereNotNull('handled_by')
            ->groupBy('day', 'user_id')
            ->get();

        $this->applyGroupedCounts($metrics, $rows, 'leads_handled');
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function aggregateLeadFollowups(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $rows = DB::table((new LeadFollowup)->getTable())
            ->selectRaw('DATE(created_at) as day, created_by as user_id, COUNT(*) as total')
            ->whereIn('created_by', $employeeIds)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->whereNotNull('created_by')
            ->groupBy('day', 'user_id')
            ->get();

        $this->applyGroupedCounts($metrics, $rows, 'lead_followups');
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function aggregateBookingFollowups(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $rows = DB::table((new BookingFollowup)->getTable())
            ->selectRaw('DATE(created_at) as day, created_by as user_id, COUNT(*) as total')
            ->whereIn('created_by', $employeeIds)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->whereNotNull('created_by')
            ->groupBy('day', 'user_id')
            ->get();

        $this->applyGroupedCounts($metrics, $rows, 'booking_followups');
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function aggregateBookingsAdded(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $rows = DB::table((new Booking)->getTable())
            ->selectRaw('DATE(created_at) as day, assignee_id as user_id, COUNT(*) as total')
            ->whereIn('assignee_id', $employeeIds)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->whereNotNull('assignee_id')
            ->groupBy('day', 'user_id')
            ->get();

        $this->applyGroupedCounts($metrics, $rows, 'bookings_added');
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function aggregateOutboundEnquiries(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $rows = DB::table((new LeadOutboundEnquiry)->getTable())
            ->selectRaw('DATE(contacted_at) as day, handled_by as user_id, COUNT(*) as total')
            ->whereIn('handled_by', $employeeIds)
            ->whereBetween('contacted_at', [$rangeStart, $rangeEnd])
            ->whereNotNull('handled_by')
            ->groupBy('day', 'user_id')
            ->get();

        $this->applyGroupedCounts($metrics, $rows, 'outbound_enquiries');
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function aggregateWhatsAppActivity(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'sent_by_id')) {
            return;
        }

        $replyRows = DB::table($table)
            ->selectRaw('DATE(created_at) as day, sent_by_id as user_id, COUNT(*) as total')
            ->where('direction', 'OUT')
            ->whereIn('sent_by_id', $employeeIds)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->whereNotNull('sent_by_id')
            ->groupBy('day', 'user_id')
            ->get();

        $this->applyGroupedCounts($metrics, $replyRows, 'whatsapp_replies');

        $chatRows = DB::table($table)
            ->selectRaw('DATE(created_at) as day, sent_by_id as user_id, COUNT(DISTINCT phone) as total')
            ->where('direction', 'OUT')
            ->whereIn('sent_by_id', $employeeIds)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->whereNotNull('sent_by_id')
            ->groupBy('day', 'user_id')
            ->get();

        $this->applyGroupedCounts($metrics, $chatRows, 'whatsapp_chats');
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  Collection<int, User>  $employees
     */
    private function aggregatePresenceHours(
        array &$metrics,
        Collection $employees,
        Carbon $dateFrom,
        Carbon $dateTo
    ): void {
        foreach (CarbonPeriod::create($dateFrom->copy()->startOfDay(), $dateTo->copy()->startOfDay()) as $date) {
            $day = $date->toDateString();
            $dayStart = $date->copy()->startOfDay();
            $isToday = $dayStart->isToday();
            $dayEnd = $isToday ? now() : $dayStart->copy()->endOfDay();

            foreach ($employees as $employee) {
                $employeeId = (string) $employee->id;
                if (! isset($metrics[$day][$employeeId])) {
                    continue;
                }

                $metrics[$day][$employeeId]['online_seconds'] = $this->computeOnlineSecondsForUserDay(
                    $employeeId,
                    $dayStart,
                    $dayEnd
                );
            }
        }
    }

    private function computeOnlineSecondsForUserDay(string $userId, Carbon $dayStart, Carbon $dayEnd): int
    {
        $periods = StaffPresencePeriod::query()
            ->where('user_id', $userId)
            ->where('status', 'online')
            ->where(function ($query) use ($dayStart, $dayEnd) {
                $query->whereBetween('started_at', [$dayStart, $dayEnd])
                    ->orWhere(function ($closedQuery) use ($dayStart, $dayEnd) {
                        $closedQuery->whereNotNull('ended_at')
                            ->where('started_at', '<=', $dayEnd)
                            ->where('ended_at', '>=', $dayStart);
                    });
            })
            ->orderBy('started_at')
            ->get(['started_at', 'ended_at']);

        $intervals = [];
        foreach ($periods as $period) {
            $start = $period->started_at->copy()->max($dayStart);
            $end = ($period->ended_at ?? now())->copy()->min($dayEnd);

            if ($start->lt($end)) {
                $intervals[] = [$start->timestamp, $end->timestamp];
            }
        }

        if ($intervals === []) {
            return 0;
        }

        usort($intervals, fn (array $a, array $b) => $a[0] <=> $b[0]);

        $merged = [];
        foreach ($intervals as [$start, $end]) {
            if ($merged === [] || $start > $merged[count($merged) - 1][1]) {
                $merged[] = [$start, $end];
                continue;
            }

            $merged[count($merged) - 1][1] = max($merged[count($merged) - 1][1], $end);
        }

        $seconds = 0;
        foreach ($merged as [$start, $end]) {
            $seconds += max(0, $end - $start);
        }

        return min($seconds, 86400);
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     */
    private function applyGroupedCounts(array &$metrics, $rows, string $metricKey): void
    {
        foreach ($rows as $row) {
            $day = (string) $row->day;
            $userId = (string) $row->user_id;
            if (! isset($metrics[$day][$userId])) {
                continue;
            }

            $metrics[$day][$userId][$metricKey] = (int) $row->total;
        }
    }

    /**
     * @param  array<string, int>  $dayMetrics
     */
    private function rowHasActivity(array $dayMetrics): bool
    {
        foreach (self::METRIC_KEYS as $key) {
            if ((int) ($dayMetrics[$key] ?? 0) > 0) {
                return true;
            }
        }

        return (int) ($dayMetrics['online_seconds'] ?? 0) > 0;
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0m';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }

        return sprintf('%dm', max($minutes, 1));
    }
}
