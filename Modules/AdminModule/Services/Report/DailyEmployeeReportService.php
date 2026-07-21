<?php

namespace Modules\AdminModule\Services\Report;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AdminModule\Entities\StaffActivityEvent;
use Modules\AdminModule\Entities\StaffPresencePeriod;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingChangeLog;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\WhatsAppUser;

class DailyEmployeeReportService
{
    public const METRIC_KEYS = [
        'leads_added',
        'leads_assigned',
        'lead_followups',
        'whatsapp_assigned_from_ai',
        'whatsapp_assigned_from_employee',
        'whatsapp_chats_closed',
        'whatsapp_chats_replied',
        'bookings_created',
        'booking_followups',
        'booking_status_updates',
        'outbound_enquiries',
    ];

    /**
     * @param  Collection<int, User>  $employees
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     totals: array<string, int|float|string>,
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
        $employeeNames = $this->employeeNameMap($employees);

        $rangeStart = $dateFrom->copy()->startOfDay();
        $rangeEnd = $dateTo->copy()->endOfDay();

        $metrics = $this->initializeMetrics($employeeIds, $dateFrom, $dateTo);
        $this->aggregateLeadsAdded($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregateLeadFollowups($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregateBookingFollowups($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregateBookingsCreated($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregateBookingStatusUpdates($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregateOutboundEnquiries($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregateWhatsAppRepliedChats($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregateStaffActivityEvents($metrics, $employeeIds, $rangeStart, $rangeEnd);
        $this->aggregatePresenceHours($metrics, $employees, $dateFrom, $dateTo);

        $rows = [];
        $totals = $this->emptyTotals();
        $employeeTotalsMap = [];

        foreach ($employeeIds as $employeeId) {
            $employeeTotalsMap[$employeeId] = array_merge([
                'employee_id' => $employeeId,
                'employee_name' => $employeeNames[$employeeId] ?? $employeeId,
                'active_days' => 0,
                'online_seconds' => 0,
            ], $this->emptyDayMetrics());
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

                $row = [
                    'date' => $day,
                    'date_label' => $date->format('d M Y'),
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeNames[$employeeId] ?? $employeeId,
                    'online_seconds' => (int) $dayMetrics['online_seconds'],
                    'online_hours' => $this->formatDuration((int) $dayMetrics['online_seconds']),
                    'has_activity' => $hasActivity,
                ];
                foreach (self::METRIC_KEYS as $key) {
                    $row[$key] = (int) $dayMetrics[$key];
                }
                $rows[] = $row;
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
     * Full day detail for selected employees (or all when empty / all).
     *
     * @param  Collection<int, User>  $employees
     * @param  list<string>  $focusEmployeeIds
     * @return array<string, mixed>
     */
    public function buildDayDetail(Collection $employees, Carbon $day, array $focusEmployeeIds = []): array
    {
        $dayStart = $day->copy()->startOfDay();
        $dayEnd = $day->copy()->endOfDay();
        $allIds = $employees->pluck('id')->map(fn ($id) => (string) $id)->all();
        $employeeNames = $this->employeeNameMap($employees);

        $focusEmployeeIds = array_values(array_unique(array_filter(array_map('strval', $focusEmployeeIds))));
        $employeeIds = $focusEmployeeIds !== []
            ? array_values(array_filter($allIds, fn ($id) => in_array($id, $focusEmployeeIds, true)))
            : $allIds;

        if ($employeeIds === []) {
            $employeeIds = $allIds;
            $focusEmployeeIds = [];
        }

        $summaryEmployees = $employees->filter(fn (User $u) => in_array((string) $u->id, $employeeIds, true));
        $summary = $this->buildReport($summaryEmployees, $dayStart, $dayStart);
        $totals = $summary['totals'];

        if (count($focusEmployeeIds) === 1 && ! empty($summary['employee_totals'][0])) {
            $totals = $summary['employee_totals'][0];
            $totals['online_hours'] = $this->formatDuration((int) ($totals['online_seconds'] ?? 0));
        }

        $employeeName = translate('All_Employees');
        if (count($focusEmployeeIds) === 1) {
            $employeeName = $employeeNames[$focusEmployeeIds[0]] ?? $focusEmployeeIds[0];
        } elseif (count($focusEmployeeIds) > 1) {
            $names = array_map(fn ($id) => $employeeNames[$id] ?? $id, $focusEmployeeIds);
            if (count($names) <= 3) {
                $employeeName = implode(', ', $names);
            } else {
                $employeeName = count($names).' '.translate('Employees');
            }
        }

        return [
            'date' => $dayStart->toDateString(),
            'date_label' => $dayStart->format('d M Y'),
            'employee_ids' => $focusEmployeeIds,
            'employee_name' => $employeeName,
            'totals' => $totals,
            'sections' => [
                'leads_added' => $this->detailLeadsAdded($employeeIds, $dayStart, $dayEnd),
                'leads_assigned' => $this->detailLeadAssigned($employeeIds, $dayStart, $dayEnd, $employeeNames),
                'lead_followups' => $this->detailLeadFollowups($employeeIds, $dayStart, $dayEnd),
                'whatsapp_assigned_from_ai' => $this->detailWhatsAppAssigned(
                    $employeeIds,
                    $dayStart,
                    $dayEnd,
                    StaffActivityEvent::TYPE_WHATSAPP_ASSIGNED_FROM_AI,
                    $employeeNames
                ),
                'whatsapp_assigned_from_employee' => $this->detailWhatsAppAssigned(
                    $employeeIds,
                    $dayStart,
                    $dayEnd,
                    StaffActivityEvent::TYPE_WHATSAPP_ASSIGNED_FROM_EMPLOYEE,
                    $employeeNames
                ),
                'whatsapp_chats_closed' => $this->detailWhatsAppClosed($employeeIds, $dayStart, $dayEnd, $employeeNames),
                'whatsapp_chats_replied' => $this->detailWhatsAppReplied($employeeIds, $dayStart, $dayEnd),
                'bookings_created' => $this->detailBookingsCreated($employeeIds, $dayStart, $dayEnd),
                'booking_followups' => $this->detailBookingFollowups($employeeIds, $dayStart, $dayEnd),
                'booking_status_updates' => $this->detailBookingStatusUpdates($employeeIds, $dayStart, $dayEnd),
            ],
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

        return $query->get(['id', 'first_name', 'last_name', 'email', 'profile_image']);
    }

    /**
     * @param  Collection<int, User>  $employees
     * @return array<string, string>
     */
    private function employeeNameMap(Collection $employees): array
    {
        return $employees->mapWithKeys(function (User $user) {
            $name = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->email ?? (string) $user->id);

            return [(string) $user->id => $name];
        })->all();
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
        $metrics = [];
        foreach (self::METRIC_KEYS as $key) {
            $metrics[$key] = 0;
        }
        $metrics['online_seconds'] = 0;

        return $metrics;
    }

    /**
     * @return array<string, int|float|string>
     */
    private function emptyTotals(): array
    {
        $totals = $this->emptyDayMetrics();
        $totals['online_hours'] = '0m';

        return $totals;
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
     * Bookings created by the employee (audit log), with assignee fallback when actor missing.
     *
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function aggregateBookingsCreated(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        if (Schema::hasTable((new BookingChangeLog)->getTable())) {
            $rows = DB::table((new BookingChangeLog)->getTable())
                ->selectRaw('DATE(created_at) as day, changed_by as user_id, COUNT(*) as total')
                ->where('property_key', 'booking.created')
                ->whereIn('changed_by', $employeeIds)
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->whereNotNull('changed_by')
                ->groupBy('day', 'user_id')
                ->get();

            if ($rows->isNotEmpty()) {
                $this->applyGroupedCounts($metrics, $rows, 'bookings_created');

                return;
            }
        }

        $rows = DB::table((new Booking)->getTable())
            ->selectRaw('DATE(created_at) as day, assignee_id as user_id, COUNT(*) as total')
            ->whereIn('assignee_id', $employeeIds)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->whereNotNull('assignee_id')
            ->groupBy('day', 'user_id')
            ->get();

        $this->applyGroupedCounts($metrics, $rows, 'bookings_created');
    }

    /**
     * Status updates after the initial create history row.
     *
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function aggregateBookingStatusUpdates(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $historyTable = (new BookingStatusHistory)->getTable();
        if (! Schema::hasTable($historyTable)) {
            return;
        }

        $rows = DB::table($historyTable.' as h')
            ->selectRaw('DATE(h.created_at) as day, h.changed_by as user_id, COUNT(*) as total')
            ->whereIn('h.changed_by', $employeeIds)
            ->whereBetween('h.created_at', [$rangeStart, $rangeEnd])
            ->whereNotNull('h.changed_by')
            ->whereNotNull('h.booking_id')
            ->whereRaw('h.id > (
                SELECT MIN(h2.id) FROM '.$historyTable.' h2
                WHERE h2.booking_id = h.booking_id
            )')
            ->groupBy('day', 'user_id')
            ->get();

        $this->applyGroupedCounts($metrics, $rows, 'booking_status_updates');
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function aggregateOutboundEnquiries(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        if (! Schema::hasTable((new LeadOutboundEnquiry)->getTable())) {
            return;
        }

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
    private function aggregateWhatsAppRepliedChats(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'sent_by_id')) {
            return;
        }

        $chatRows = DB::table($table)
            ->selectRaw('DATE(created_at) as day, sent_by_id as user_id, COUNT(DISTINCT phone) as total')
            ->where('direction', 'OUT')
            ->whereIn('sent_by_id', $employeeIds)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->whereNotNull('sent_by_id')
            ->groupBy('day', 'user_id')
            ->get();

        $this->applyGroupedCounts($metrics, $chatRows, 'whatsapp_chats_replied');
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function aggregateStaffActivityEvents(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $map = [
            StaffActivityEvent::TYPE_LEAD_ASSIGNED => 'leads_assigned',
            StaffActivityEvent::TYPE_WHATSAPP_ASSIGNED_FROM_AI => 'whatsapp_assigned_from_ai',
            StaffActivityEvent::TYPE_WHATSAPP_ASSIGNED_FROM_EMPLOYEE => 'whatsapp_assigned_from_employee',
            StaffActivityEvent::TYPE_WHATSAPP_CHAT_CLOSED => 'whatsapp_chats_closed',
        ];

        $seenTypes = [];

        if (Schema::hasTable('staff_activity_events')) {
            $rows = DB::table('staff_activity_events')
                ->selectRaw('DATE(created_at) as day, employee_id as user_id, event_type, COUNT(*) as total')
                ->whereIn('employee_id', $employeeIds)
                ->whereIn('event_type', array_keys($map))
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->groupBy('day', 'user_id', 'event_type')
                ->get();

            foreach ($rows as $row) {
                $eventType = (string) $row->event_type;
                $metricKey = $map[$eventType] ?? null;
                if ($metricKey === null) {
                    continue;
                }
                $seenTypes[$eventType] = true;
                $day = (string) $row->day;
                $userId = (string) $row->user_id;
                if (! isset($metrics[$day][$userId])) {
                    continue;
                }
                $metrics[$day][$userId][$metricKey] = (int) $row->total;
            }
        }

        // Live DB often has real assignment/close activity before events were logged.
        // Fall back to snapshot tables only when that event type has no rows in range.
        if (! isset($seenTypes[StaffActivityEvent::TYPE_LEAD_ASSIGNED])) {
            $this->aggregateLeadsAssignedFallback($metrics, $employeeIds, $rangeStart, $rangeEnd);
        }
        if (! isset($seenTypes[StaffActivityEvent::TYPE_WHATSAPP_CHAT_CLOSED])) {
            $this->aggregateWhatsAppClosedFallback($metrics, $employeeIds, $rangeStart, $rangeEnd);
        }

        // Always merge first-reply self-assigns so pre-logging Live replies count,
        // even when some WA-assign events already exist for the range.
        $this->mergeWhatsAppAssignedFromFirstReplies($metrics, $employeeIds, $rangeStart, $rangeEnd);
    }

    /**
     * Approximate "leads assigned" from leads currently handled by the employee
     * whose lead-received timestamp falls in range (pre-event Live history).
     *
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function aggregateLeadsAssignedFallback(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $rows = DB::table((new Lead)->getTable())
            ->selectRaw('DATE(date_time_of_lead_received) as day, handled_by as user_id, COUNT(*) as total')
            ->whereIn('handled_by', $employeeIds)
            ->whereBetween('date_time_of_lead_received', [$rangeStart, $rangeEnd])
            ->whereNotNull('handled_by')
            ->where('handled_by', '!=', Lead::HANDLED_BY_AI)
            ->groupBy('day', 'user_id')
            ->get();

        $this->applyGroupedCounts($metrics, $rows, 'leads_assigned');
    }

    /**
     * Approximate WA closes from thread meta last updated to a closed status.
     *
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function aggregateWhatsAppClosedFallback(array &$metrics, array $employeeIds, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        if (
            ! Schema::hasTable('whatsapp_chat_thread_meta')
            || ! Schema::hasTable('whatsapp_chat_statuses')
            || ! Schema::hasTable((new WhatsAppUser)->getTable())
        ) {
            return;
        }

        $rows = DB::table('whatsapp_chat_thread_meta as tm')
            ->join('whatsapp_chat_statuses as st', 'st.id', '=', 'tm.whatsapp_chat_status_id')
            ->join('whatsapp_users as wu', function ($join) {
                $join->on('wu.phone', '=', 'tm.phone');
                if (Schema::hasColumn('whatsapp_chat_thread_meta', 'channel')
                    && Schema::hasColumn((new WhatsAppUser)->getTable(), 'channel')
                ) {
                    $join->on('wu.channel', '=', 'tm.channel');
                }
            })
            ->selectRaw('DATE(tm.updated_at) as day, wu.handled_by as user_id, COUNT(*) as total')
            ->where('st.bucket', 'closed')
            ->whereIn('wu.handled_by', $employeeIds)
            ->whereBetween('tm.updated_at', [$rangeStart, $rangeEnd])
            ->groupBy('day', 'user_id')
            ->get();

        $this->applyGroupedCounts($metrics, $rows, 'whatsapp_chats_closed');
    }

    /**
     * Fill WA-assigned-from-AI gaps from each employee's first OUT reply per phone
     * (the historical self-assign path). Skips phones already counted via events.
     *
     * @param  array<string, array<string, array<string, int>>>  $metrics
     * @param  list<string>  $employeeIds
     */
    private function mergeWhatsAppAssignedFromFirstReplies(
        array &$metrics,
        array $employeeIds,
        Carbon $rangeStart,
        Carbon $rangeEnd
    ): void {
        $messageTable = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        if ($employeeIds === [] || ! Schema::hasTable($messageTable) || ! Schema::hasColumn($messageTable, 'sent_by_id')) {
            return;
        }

        $covered = [];
        if (Schema::hasTable('staff_activity_events')) {
            $existing = DB::table('staff_activity_events')
                ->select(['employee_id', 'subject_id', 'created_at'])
                ->whereIn('employee_id', $employeeIds)
                ->whereIn('event_type', [
                    StaffActivityEvent::TYPE_WHATSAPP_ASSIGNED_FROM_AI,
                    StaffActivityEvent::TYPE_WHATSAPP_ASSIGNED_FROM_EMPLOYEE,
                ])
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->get();

            foreach ($existing as $row) {
                $day = Carbon::parse($row->created_at)->toDateString();
                $covered[$day.'|'.(string) $row->employee_id.'|'.(string) $row->subject_id] = true;
            }
        }

        // First-ever OUT by this employee for this phone, if that first reply falls in range.
        $firstReplies = DB::table($messageTable)
            ->selectRaw('phone, sent_by_id, MIN(created_at) as first_at')
            ->where('direction', 'OUT')
            ->whereNotNull('sent_by_id')
            ->whereIn('sent_by_id', $employeeIds)
            ->groupBy('phone', 'sent_by_id')
            ->havingRaw('MIN(created_at) BETWEEN ? AND ?', [
                $rangeStart->toDateTimeString(),
                $rangeEnd->toDateTimeString(),
            ])
            ->get();

        foreach ($firstReplies as $row) {
            $day = Carbon::parse($row->first_at)->toDateString();
            $userId = (string) $row->sent_by_id;
            $phone = (string) $row->phone;
            $key = $day.'|'.$userId.'|'.$phone;

            if (isset($covered[$key]) || ! isset($metrics[$day][$userId])) {
                continue;
            }

            $covered[$key] = true;
            $metrics[$day][$userId]['whatsapp_assigned_from_ai']++;
        }
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

    /**
     * @param  list<string>  $employeeIds
     * @return list<array<string, mixed>>
     */
    private function detailLeadsAdded(array $employeeIds, Carbon $dayStart, Carbon $dayEnd): array
    {
        if ($employeeIds === []) {
            return [];
        }

        return Lead::query()
            ->with(['source:id,name'])
            ->whereIn('created_by', $employeeIds)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'phone_number', 'lead_type', 'source_id', 'handled_by', 'created_by', 'created_at'])
            ->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'name' => $lead->name ?: '—',
                'phone' => $lead->phone_number,
                'lead_type' => $lead->lead_type,
                'source' => $lead->source?->name ?: '—',
                'at' => optional($lead->created_at)->format('h:i a'),
                'url' => route('admin.lead.show', $lead->id),
            ])
            ->all();
    }

    /**
     * @param  list<string>  $employeeIds
     * @param  array<string, string>  $employeeNames
     * @return list<array<string, mixed>>
     */
    private function detailLeadAssigned(array $employeeIds, Carbon $dayStart, Carbon $dayEnd, array $employeeNames): array
    {
        if ($employeeIds === []) {
            return [];
        }

        if (Schema::hasTable('staff_activity_events')) {
            $events = StaffActivityEvent::query()
                ->where('event_type', StaffActivityEvent::TYPE_LEAD_ASSIGNED)
                ->whereIn('employee_id', $employeeIds)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->orderByDesc('created_at')
                ->get();

            if ($events->isNotEmpty()) {
                $leadIds = $events->pluck('subject_id')->filter()->unique()->values()->all();
                $leads = $leadIds === []
                    ? collect()
                    : Lead::query()->whereIn('id', $leadIds)->get(['id', 'name', 'phone_number', 'lead_type'])->keyBy('id');

                return $events->map(function (StaffActivityEvent $event) use ($leads, $employeeNames) {
                    $lead = $leads->get($event->subject_id);
                    $from = $event->meta['from_handler'] ?? null;
                    $fromLabel = 'AI / Unassigned';
                    if (Lead::assigneeIsHuman($from)) {
                        $fromLabel = $employeeNames[(string) $from] ?? (string) $from;
                    }

                    return [
                        'id' => $event->subject_id,
                        'name' => $lead?->name ?: '—',
                        'phone' => $lead?->phone_number ?? '—',
                        'lead_type' => $lead?->lead_type ?? '—',
                        'from' => $fromLabel,
                        'employee' => $employeeNames[(string) $event->employee_id] ?? (string) $event->employee_id,
                        'at' => optional($event->created_at)->format('h:i a'),
                        'url' => $lead ? route('admin.lead.show', $lead->id) : null,
                    ];
                })->all();
            }
        }

        // Live fallback: leads received that day and currently assigned to the employee.
        return Lead::query()
            ->whereIn('handled_by', $employeeIds)
            ->whereBetween('date_time_of_lead_received', [$dayStart, $dayEnd])
            ->whereNotNull('handled_by')
            ->where('handled_by', '!=', Lead::HANDLED_BY_AI)
            ->orderByDesc('date_time_of_lead_received')
            ->get(['id', 'name', 'phone_number', 'lead_type', 'handled_by', 'date_time_of_lead_received'])
            ->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'name' => $lead->name ?: '—',
                'phone' => $lead->phone_number,
                'lead_type' => $lead->lead_type,
                'from' => '—',
                'employee' => $employeeNames[(string) $lead->handled_by] ?? (string) $lead->handled_by,
                'at' => optional($lead->date_time_of_lead_received)->format('h:i a'),
                'url' => route('admin.lead.show', $lead->id),
            ])
            ->all();
    }

    /**
     * @param  list<string>  $employeeIds
     * @return list<array<string, mixed>>
     */
    private function detailLeadFollowups(array $employeeIds, Carbon $dayStart, Carbon $dayEnd): array
    {
        if ($employeeIds === []) {
            return [];
        }

        return LeadFollowup::query()
            ->with(['lead:id,name,phone_number'])
            ->whereIn('created_by', $employeeIds)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (LeadFollowup $fu) => [
                'id' => $fu->id,
                'lead_id' => $fu->lead_id,
                'name' => $fu->lead?->name ?: '—',
                'phone' => $fu->lead?->phone_number ?? '—',
                'remarks' => $fu->remarks ?: '—',
                'urgency' => $fu->urgency ?: '—',
                'at' => optional($fu->created_at)->format('h:i a'),
                'url' => $fu->lead_id ? route('admin.lead.show', $fu->lead_id) : null,
            ])
            ->all();
    }

    /**
     * @param  list<string>  $employeeIds
     * @param  array<string, string>  $employeeNames
     * @return list<array<string, mixed>>
     */
    private function detailWhatsAppAssigned(
        array $employeeIds,
        Carbon $dayStart,
        Carbon $dayEnd,
        string $eventType,
        array $employeeNames
    ): array {
        if ($employeeIds === []) {
            return [];
        }

        $rows = [];
        $coveredPhones = [];

        if (Schema::hasTable('staff_activity_events')) {
            $events = StaffActivityEvent::query()
                ->where('event_type', $eventType)
                ->whereIn('employee_id', $employeeIds)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->orderByDesc('created_at')
                ->get();

            foreach ($events as $event) {
                $phone = (string) ($event->meta['phone'] ?? $event->subject_id ?? '');
                $from = $event->meta['from_handler'] ?? null;
                $fromLabel = 'AI / Unassigned';
                if (Lead::assigneeIsHuman($from)) {
                    $fromLabel = $employeeNames[(string) $from] ?? (string) $from;
                }

                $rows[] = [
                    'phone' => $phone,
                    'from' => $fromLabel,
                    'employee' => $employeeNames[(string) $event->employee_id] ?? (string) $event->employee_id,
                    'at' => optional($event->created_at)->format('h:i a'),
                    'url' => $phone !== '' ? route('admin.whatsapp.conversations.chat', ['channel' => 'whatsapp', 'phone' => $phone]) : null,
                ];
                if ($phone !== '') {
                    $coveredPhones[(string) $event->employee_id.'|'.$phone] = true;
                }
            }
        }

        // Historical self-assign via reply only maps to "from AI" (typical takeover).
        if ($eventType !== StaffActivityEvent::TYPE_WHATSAPP_ASSIGNED_FROM_AI) {
            return $rows;
        }

        $messageTable = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        if (! Schema::hasTable($messageTable) || ! Schema::hasColumn($messageTable, 'sent_by_id')) {
            return $rows;
        }

        // Also mark phones already logged under from-employee so we don't double-list.
        if (Schema::hasTable('staff_activity_events')) {
            $other = StaffActivityEvent::query()
                ->where('event_type', StaffActivityEvent::TYPE_WHATSAPP_ASSIGNED_FROM_EMPLOYEE)
                ->whereIn('employee_id', $employeeIds)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->get(['employee_id', 'subject_id']);
            foreach ($other as $event) {
                $phone = (string) $event->subject_id;
                if ($phone !== '') {
                    $coveredPhones[(string) $event->employee_id.'|'.$phone] = true;
                }
            }
        }

        $firstReplies = DB::table($messageTable)
            ->selectRaw('phone, sent_by_id, MIN(created_at) as first_at')
            ->where('direction', 'OUT')
            ->whereNotNull('sent_by_id')
            ->whereIn('sent_by_id', $employeeIds)
            ->groupBy('phone', 'sent_by_id')
            ->havingRaw('MIN(created_at) BETWEEN ? AND ?', [
                $dayStart->toDateTimeString(),
                $dayEnd->toDateTimeString(),
            ])
            ->orderByDesc('first_at')
            ->get();

        foreach ($firstReplies as $row) {
            $phone = (string) $row->phone;
            $employeeId = (string) $row->sent_by_id;
            $key = $employeeId.'|'.$phone;
            if ($phone === '' || isset($coveredPhones[$key])) {
                continue;
            }
            $coveredPhones[$key] = true;
            $rows[] = [
                'phone' => $phone,
                'from' => 'AI / Unassigned',
                'employee' => $employeeNames[$employeeId] ?? $employeeId,
                'at' => Carbon::parse($row->first_at)->format('h:i a'),
                'url' => route('admin.whatsapp.conversations.chat', ['channel' => 'whatsapp', 'phone' => $phone]),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<string>  $employeeIds
     * @param  array<string, string>  $employeeNames
     * @return list<array<string, mixed>>
     */
    private function detailWhatsAppClosed(array $employeeIds, Carbon $dayStart, Carbon $dayEnd, array $employeeNames): array
    {
        if ($employeeIds === []) {
            return [];
        }

        if (Schema::hasTable('staff_activity_events')) {
            $events = StaffActivityEvent::query()
                ->where('event_type', StaffActivityEvent::TYPE_WHATSAPP_CHAT_CLOSED)
                ->whereIn('employee_id', $employeeIds)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->orderByDesc('created_at')
                ->get();

            if ($events->isNotEmpty()) {
                return $events->map(function (StaffActivityEvent $event) use ($employeeNames) {
                    $phone = (string) ($event->meta['phone'] ?? $event->subject_id ?? '');

                    return [
                        'phone' => $phone,
                        'status' => $event->meta['status_name'] ?? 'Closed',
                        'employee' => $employeeNames[(string) $event->employee_id] ?? (string) $event->employee_id,
                        'at' => optional($event->created_at)->format('h:i a'),
                        'url' => $phone !== '' ? route('admin.whatsapp.conversations.chat', ['channel' => 'whatsapp', 'phone' => $phone]) : null,
                    ];
                })->all();
            }
        }

        if (
            ! Schema::hasTable('whatsapp_chat_thread_meta')
            || ! Schema::hasTable('whatsapp_chat_statuses')
            || ! Schema::hasTable((new WhatsAppUser)->getTable())
        ) {
            return [];
        }

        $query = DB::table('whatsapp_chat_thread_meta as tm')
            ->join('whatsapp_chat_statuses as st', 'st.id', '=', 'tm.whatsapp_chat_status_id')
            ->join('whatsapp_users as wu', function ($join) {
                $join->on('wu.phone', '=', 'tm.phone');
                if (Schema::hasColumn('whatsapp_chat_thread_meta', 'channel')
                    && Schema::hasColumn((new WhatsAppUser)->getTable(), 'channel')
                ) {
                    $join->on('wu.channel', '=', 'tm.channel');
                }
            })
            ->select([
                'tm.phone',
                'st.name as status_name',
                'wu.handled_by',
                'tm.updated_at',
            ])
            ->where('st.bucket', 'closed')
            ->whereIn('wu.handled_by', $employeeIds)
            ->whereBetween('tm.updated_at', [$dayStart, $dayEnd])
            ->orderByDesc('tm.updated_at');

        return $query->get()->map(function ($row) use ($employeeNames) {
            $phone = (string) $row->phone;

            return [
                'phone' => $phone,
                'status' => $row->status_name ?: 'Closed',
                'employee' => $employeeNames[(string) $row->handled_by] ?? (string) $row->handled_by,
                'at' => $row->updated_at ? Carbon::parse($row->updated_at)->format('h:i a') : '—',
                'url' => route('admin.whatsapp.conversations.chat', ['channel' => 'whatsapp', 'phone' => $phone]),
            ];
        })->all();
    }

    /**
     * @param  list<string>  $employeeIds
     * @return list<array<string, mixed>>
     */
    private function detailWhatsAppReplied(array $employeeIds, Carbon $dayStart, Carbon $dayEnd): array
    {
        $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        if ($employeeIds === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'sent_by_id')) {
            return [];
        }

        $rows = DB::table($table)
            ->selectRaw('phone, sent_by_id, COUNT(*) as reply_count, MAX(created_at) as last_at')
            ->where('direction', 'OUT')
            ->whereIn('sent_by_id', $employeeIds)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->whereNotNull('sent_by_id')
            ->groupBy('phone', 'sent_by_id')
            ->orderByDesc('last_at')
            ->get();

        $phones = $rows->pluck('phone')->filter()->unique()->values()->all();
        $names = [];
        if ($phones !== [] && Schema::hasTable((new WhatsAppUser)->getTable())) {
            $names = WhatsAppUser::query()
                ->whereIn('phone', $phones)
                ->pluck('name', 'phone')
                ->all();
        }

        return $rows->map(function ($row) use ($names) {
            $phone = (string) $row->phone;

            return [
                'phone' => $phone,
                'name' => $names[$phone] ?? '—',
                'replies' => (int) $row->reply_count,
                'at' => $row->last_at ? Carbon::parse($row->last_at)->format('h:i a') : '—',
                'url' => route('admin.whatsapp.conversations.chat', ['channel' => 'whatsapp', 'phone' => $phone]),
            ];
        })->all();
    }

    /**
     * @param  list<string>  $employeeIds
     * @return list<array<string, mixed>>
     */
    private function detailBookingsCreated(array $employeeIds, Carbon $dayStart, Carbon $dayEnd): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $bookingIds = [];
        if (Schema::hasTable((new BookingChangeLog)->getTable())) {
            $bookingIds = BookingChangeLog::query()
                ->where('property_key', 'booking.created')
                ->whereIn('changed_by', $employeeIds)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->pluck('booking_id')
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $query = Booking::query()->with(['customer:id,first_name,last_name,phone']);
        if ($bookingIds !== []) {
            $query->whereIn('id', $bookingIds);
        } else {
            $query->whereIn('assignee_id', $employeeIds)
                ->whereBetween('created_at', [$dayStart, $dayEnd]);
        }

        return $query->orderByDesc('created_at')
            ->get(['id', 'readable_id', 'customer_id', 'booking_status', 'assignee_id', 'lead_id', 'created_at'])
            ->map(function (Booking $booking) {
                $customerName = trim(($booking->customer->first_name ?? '').' '.($booking->customer->last_name ?? ''));

                return [
                    'id' => $booking->id,
                    'readable_id' => $booking->readable_id ?: $booking->id,
                    'customer' => $customerName !== '' ? $customerName : '—',
                    'phone' => $booking->customer->phone ?? '—',
                    'status' => $booking->booking_status,
                    'from_lead' => $booking->lead_id ? translate('Yes') : translate('No'),
                    'at' => optional($booking->created_at)->format('h:i a'),
                    'url' => route('admin.booking.details', $booking->id),
                ];
            })
            ->all();
    }

    /**
     * @param  list<string>  $employeeIds
     * @return list<array<string, mixed>>
     */
    private function detailBookingFollowups(array $employeeIds, Carbon $dayStart, Carbon $dayEnd): array
    {
        if ($employeeIds === []) {
            return [];
        }

        return BookingFollowup::query()
            ->with(['booking:id,readable_id'])
            ->whereIn('created_by', $employeeIds)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (BookingFollowup $fu) => [
                'id' => $fu->id,
                'booking_id' => $fu->booking_id,
                'readable_id' => $fu->booking?->readable_id ?: $fu->booking_id,
                'reason' => $fu->reason ?: '—',
                'for' => $fu->for ?: '—',
                'status' => $fu->status ?: '—',
                'at' => optional($fu->created_at)->format('h:i a'),
                'url' => $fu->booking_id ? route('admin.booking.details', $fu->booking_id) : null,
            ])
            ->all();
    }

    /**
     * @param  list<string>  $employeeIds
     * @return list<array<string, mixed>>
     */
    private function detailBookingStatusUpdates(array $employeeIds, Carbon $dayStart, Carbon $dayEnd): array
    {
        $historyTable = (new BookingStatusHistory)->getTable();
        if ($employeeIds === [] || ! Schema::hasTable($historyTable)) {
            return [];
        }

        $rows = DB::table($historyTable.' as h')
            ->leftJoin('bookings as b', 'b.id', '=', 'h.booking_id')
            ->select([
                'h.id',
                'h.booking_id',
                'h.booking_status',
                'h.created_at',
                'h.changed_by',
                'b.readable_id',
            ])
            ->whereIn('h.changed_by', $employeeIds)
            ->whereBetween('h.created_at', [$dayStart, $dayEnd])
            ->whereNotNull('h.booking_id')
            ->whereRaw('h.id > (
                SELECT MIN(h2.id) FROM '.$historyTable.' h2
                WHERE h2.booking_id = h.booking_id
            )')
            ->orderByDesc('h.created_at')
            ->get();

        return $rows->map(fn ($row) => [
            'id' => $row->id,
            'booking_id' => $row->booking_id,
            'readable_id' => $row->readable_id ?: $row->booking_id,
            'status' => $row->booking_status,
            'at' => $row->created_at ? Carbon::parse($row->created_at)->format('h:i a') : '—',
            'url' => $row->booking_id ? route('admin.booking.details', $row->booking_id) : null,
        ])->all();
    }
}
