<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Services\LeadOpenStatusService;

/**
 * Employee ranking score: quantity of work (+) and quality penalties (−).
 */
class EmployeeProgressScoreService
{
    public const POINTS_BOOKINGS_HANDLED = 3;

    public const POINTS_LEADS_HANDLED = 3;

    public const POINTS_CHAT_REPLIES = 1;

    public const PENALTY_LATE_FOLLOWUP = 1;

    public const PENALTY_MISSED_FOLLOWUP = 1;

    public function __construct(
        private readonly LeadOpenStatusService $leadOpenStatus,
    ) {}

    /**
     * @return list<array{key: string, label: string, points: int, sign: string}>
     */
    public static function weightLegend(): array
    {
        return [
            ['key' => 'bookings_created', 'label' => translate('Bookings_created') ?? 'Bookings created', 'points' => self::POINTS_BOOKINGS_HANDLED, 'sign' => '+'],
            ['key' => 'leads_handled', 'label' => translate('Leads_Handled') ?? 'Leads handled', 'points' => self::POINTS_LEADS_HANDLED, 'sign' => '+'],
            ['key' => 'whatsapp_replies', 'label' => translate('WhatsApp_Replies') ?? 'Chat replies', 'points' => self::POINTS_CHAT_REPLIES, 'sign' => '+'],
            ['key' => 'late_followups', 'label' => translate('Progress_late_followups') ?? 'Late follow-ups', 'points' => self::PENALTY_LATE_FOLLOWUP, 'sign' => '−'],
            ['key' => 'missed_followups', 'label' => translate('Progress_missed_followups'), 'points' => self::PENALTY_MISSED_FOLLOWUP, 'sign' => '−'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $employeeTotals
     * @return list<array<string, mixed>>
     */
    public function rankEmployees(
        array $employeeTotals,
        Collection $employees,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): array {
        $employeeIds = collect($employeeTotals)
            ->map(fn (array $row) => (string) ($row['employee_id'] ?? ''))
            ->filter()
            ->values()
            ->all();

        if ($employeeIds === []) {
            $employeeIds = $employees->pluck('id')->map(fn ($id) => (string) $id)->filter()->values()->all();
        }

        $missedByEmployee = $this->missedFollowupsByEmployee($employeeIds, $periodStart, $periodEnd);
        $lateByEmployee = $this->lateFollowupsByEmployee($employeeIds, $periodStart, $periodEnd);
        $leadsByEmployee = $this->leadsHandledByEmployee($employeeIds, $periodStart, $periodEnd);
        $ranked = [];

        foreach ($employeeTotals as $employeeRow) {
            $employeeId = (string) ($employeeRow['employee_id'] ?? '');
            if ($employeeId === '') {
                continue;
            }

            $ranked[] = $this->scoreEmployeeRow(
                $employeeRow,
                (int) ($missedByEmployee[$employeeId] ?? 0),
                (int) ($leadsByEmployee[$employeeId] ?? 0),
                (int) ($lateByEmployee[$employeeId] ?? 0),
            );
        }

        usort($ranked, function (array $a, array $b) {
            $scoreCmp = ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }

            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        foreach ($ranked as $index => &$row) {
            $row['rank'] = $index + 1;
        }
        unset($row);

        return $ranked;
    }

    /**
     * @param  array<string, mixed>  $employeeRow
     * @return array<string, mixed>
     */
    public function scoreEmployeeRow(
        array $employeeRow,
        int $missedFollowups,
        ?int $leadsHandledOverride = null,
        int $lateFollowups = 0,
    ): array {
        // Match Bookings / Leads tab heroes: created bookings + assigned leads in period.
        $bookingsCreated = (int) ($employeeRow['bookings_created'] ?? 0);
        $leadsHandled = $leadsHandledOverride ?? (int) ($employeeRow['leads_assigned'] ?? $employeeRow['leads_handled'] ?? 0);
        $chatReplies = (int) ($employeeRow['whatsapp_replies'] ?? 0);

        $marks = [
            $this->markLine(
                'bookings_created',
                translate('Bookings_created') ?? 'Bookings created',
                $bookingsCreated,
                self::POINTS_BOOKINGS_HANDLED,
                true,
            ),
            $this->markLine(
                'leads_handled',
                translate('Leads_Handled') ?? 'Leads handled',
                $leadsHandled,
                self::POINTS_LEADS_HANDLED,
                true,
            ),
            $this->markLine(
                'whatsapp_replies',
                translate('WhatsApp_Replies') ?? 'Chat replies',
                $chatReplies,
                self::POINTS_CHAT_REPLIES,
                true,
            ),
            $this->markLine(
                'late_followups',
                translate('Progress_late_followups') ?? 'Late follow-ups',
                $lateFollowups,
                self::PENALTY_LATE_FOLLOWUP,
                false,
            ),
            $this->markLine(
                'missed_followups',
                translate('Progress_missed_followups'),
                $missedFollowups,
                self::PENALTY_MISSED_FOLLOWUP,
                false,
            ),
        ];

        $quantityScore = (int) ($marks[0]['points'] + $marks[1]['points'] + $marks[2]['points']);
        $penaltyScore = (int) ($marks[3]['points'] + $marks[4]['points']);
        $score = $quantityScore + $penaltyScore;

        return [
            'employee_id' => (string) ($employeeRow['employee_id'] ?? ''),
            'name' => (string) ($employeeRow['employee_name'] ?? ''),
            'bookings' => $bookingsCreated,
            'leads' => $leadsHandled,
            'chats' => $chatReplies,
            'followups' => (int) ($employeeRow['lead_followups'] ?? 0) + (int) ($employeeRow['booking_followups'] ?? 0),
            'late_followups' => $lateFollowups,
            'missed_followups' => $missedFollowups,
            'cancelled' => (int) ($employeeRow['bookings_cancelled'] ?? 0),
            'quantity_score' => $quantityScore,
            'penalty_score' => $penaltyScore,
            'score' => $score,
            'marks' => $marks,
            'revenue' => with_currency_symbol(0),
        ];
    }

    /**
     * Same definition as Leads tab: leads assigned to the employee with received date in period.
     *
     * @param  list<string>  $employeeIds
     * @return array<string, int>
     */
    public function leadsHandledByEmployee(array $employeeIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $counts = array_fill_keys($employeeIds, 0);

        if ($employeeIds === []) {
            return $counts;
        }

        $rows = Lead::query()
            ->selectRaw('handled_by, COUNT(*) as cnt')
            ->whereIn('handled_by', $employeeIds)
            ->whereBetween('date_time_of_lead_received', [
                $periodStart->copy()->startOfDay(),
                $periodEnd->copy()->endOfDay(),
            ])
            ->whereNotNull('handled_by')
            ->where('handled_by', '!=', Lead::HANDLED_BY_AI)
            ->groupBy('handled_by')
            ->pluck('cnt', 'handled_by');

        foreach ($rows as $id => $count) {
            $counts[(string) $id] = (int) $count;
        }

        return $counts;
    }

    /**
     * Follow-ups completed after their due day (lead + booking), attributed to the performer.
     *
     * @param  list<string>  $employeeIds
     * @return array<string, int>
     */
    public function lateFollowupsByEmployee(array $employeeIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $late = array_fill_keys($employeeIds, 0);

        if ($employeeIds === []) {
            return $late;
        }

        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        $leadFollowups = LeadFollowup::query()
            ->whereIn('created_by', $employeeIds)
            ->whereNotNull('followup_at')
            ->whereNotNull('due_followup_at')
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->get(['created_by', 'followup_at', 'due_followup_at']);

        foreach ($leadFollowups as $followup) {
            $due = $followup->due_followup_at;
            if (! $due || $followup->followup_at->lte($due->copy()->endOfDay())) {
                continue;
            }
            $employeeId = (string) ($followup->created_by ?? '');
            if ($employeeId === '' || ! array_key_exists($employeeId, $late)) {
                continue;
            }
            $late[$employeeId]++;
        }

        $bookingFollowups = BookingFollowup::query()
            ->whereIn('created_by', $employeeIds)
            ->whereNotNull('followup_at')
            ->whereIn('status', ['completed', 'rescheduled'])
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->get(['created_by', 'followup_at', 'due_followup_at', 'date', 'status']);

        foreach ($bookingFollowups as $followup) {
            if ($followup->isRescheduled()) {
                continue;
            }

            $dueRaw = $followup->due_followup_at ?? $followup->date;
            if (! $dueRaw) {
                continue;
            }
            $due = $dueRaw instanceof Carbon ? $dueRaw : Carbon::parse($dueRaw);
            if ($followup->followup_at->lte($due->copy()->endOfDay())) {
                continue;
            }

            $employeeId = (string) ($followup->created_by ?? '');
            if ($employeeId === '' || ! array_key_exists($employeeId, $late)) {
                continue;
            }
            $late[$employeeId]++;
        }

        return $late;
    }

    /**
     * @param  list<string>  $employeeIds
     * @return array<string, int>
     */
    public function missedFollowupsByEmployee(array $employeeIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $missed = array_fill_keys($employeeIds, 0);

        if ($employeeIds === []) {
            return $missed;
        }

        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();
        $asOf = Carbon::now()->lt($rangeEnd) ? Carbon::now() : $rangeEnd;

        $leadMissedQuery = Lead::query()
            ->whereIn('handled_by', $employeeIds)
            ->whereNotNull('next_followup_at')
            ->whereBetween('next_followup_at', [$rangeStart, $rangeEnd])
            ->where('next_followup_at', '<', $asOf)
            ->selectRaw('handled_by, COUNT(*) as cnt')
            ->groupBy('handled_by');
        $this->leadOpenStatus->restrictQueryToOpenLeads($leadMissedQuery);

        foreach ($leadMissedQuery->pluck('cnt', 'handled_by') as $id => $count) {
            $key = (string) $id;
            $missed[$key] = (int) ($missed[$key] ?? 0) + (int) $count;
        }

        $bookingMissed = BookingFollowup::query()
            ->where('status', 'scheduled')
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->where('date', '<', $asOf)
            ->whereHas('booking', function ($q) use ($employeeIds) {
                $q->whereIn('assignee_id', $employeeIds)
                    ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
            })
            ->with('booking:id,assignee_id')
            ->get(['id', 'booking_id']);

        foreach ($bookingMissed as $row) {
            $assigneeId = (string) ($row->booking?->assignee_id ?? '');
            if ($assigneeId === '' || ! array_key_exists($assigneeId, $missed)) {
                continue;
            }
            $missed[$assigneeId]++;
        }

        return $missed;
    }

    /**
     * @return array{key: string, label: string, count: int, unit_points: int, points: int, positive: bool}
     */
    private function markLine(string $key, string $label, int $count, int $unitPoints, bool $positive): array
    {
        $points = $positive
            ? $count * $unitPoints
            : -1 * ($count * $unitPoints);

        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'unit_points' => $unitPoints,
            'points' => $points,
            'positive' => $positive,
        ];
    }
}
