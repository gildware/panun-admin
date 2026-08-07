<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Services\LeadOpenStatusService;

/**
 * Employee ranking score: quantity of work (+) and quality penalties (−).
 */
class EmployeeProgressScoreService
{
    public const POINTS_BOOKINGS_HANDLED = 3;
    public const POINTS_LEADS_HANDLED = 3;
    public const POINTS_CHAT_REPLIES = 1;
    public const PENALTY_MISSED_FOLLOWUP = 5;
    public const PENALTY_CANCELLED_BOOKING = 3;

    public function __construct(
        private readonly LeadOpenStatusService $leadOpenStatus,
    ) {}

    /**
     * @return list<array{key: string, label: string, points: int, sign: string}>
     */
    public static function weightLegend(): array
    {
        return [
            ['key' => 'bookings_handled', 'label' => translate('Bookings_Handled') ?? 'Bookings handled', 'points' => self::POINTS_BOOKINGS_HANDLED, 'sign' => '+'],
            ['key' => 'leads_handled', 'label' => translate('Leads_Handled') ?? 'Leads handled', 'points' => self::POINTS_LEADS_HANDLED, 'sign' => '+'],
            ['key' => 'whatsapp_replies', 'label' => translate('WhatsApp_Replies') ?? 'Chat replies', 'points' => self::POINTS_CHAT_REPLIES, 'sign' => '+'],
            ['key' => 'missed_followups', 'label' => translate('Progress_missed_followups'), 'points' => self::PENALTY_MISSED_FOLLOWUP, 'sign' => '−'],
            ['key' => 'bookings_cancelled', 'label' => translate('Bookings_Cancelled') ?? 'Booking cancellations', 'points' => self::PENALTY_CANCELLED_BOOKING, 'sign' => '−'],
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
        $ranked = [];

        foreach ($employeeTotals as $employeeRow) {
            $employeeId = (string) ($employeeRow['employee_id'] ?? '');
            if ($employeeId === '') {
                continue;
            }

            $ranked[] = $this->scoreEmployeeRow(
                $employeeRow,
                (int) ($missedByEmployee[$employeeId] ?? 0),
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
    public function scoreEmployeeRow(array $employeeRow, int $missedFollowups): array
    {
        $bookingsHandled = (int) ($employeeRow['bookings_handled'] ?? 0);
        $leadsHandled = (int) ($employeeRow['leads_handled'] ?? 0);
        $chatReplies = (int) ($employeeRow['whatsapp_replies'] ?? 0);
        $cancelled = (int) ($employeeRow['bookings_cancelled'] ?? 0);

        $marks = [
            $this->markLine(
                'bookings_handled',
                translate('Bookings_Handled') ?? 'Bookings handled',
                $bookingsHandled,
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
                'missed_followups',
                translate('Progress_missed_followups'),
                $missedFollowups,
                self::PENALTY_MISSED_FOLLOWUP,
                false,
            ),
            $this->markLine(
                'bookings_cancelled',
                translate('Bookings_Cancelled') ?? 'Booking cancellations',
                $cancelled,
                self::PENALTY_CANCELLED_BOOKING,
                false,
            ),
        ];

        $quantityScore = (int) ($marks[0]['points'] + $marks[1]['points'] + $marks[2]['points']);
        $penaltyScore = (int) ($marks[3]['points'] + $marks[4]['points']);
        $score = $quantityScore + $penaltyScore;

        return [
            'employee_id' => (string) ($employeeRow['employee_id'] ?? ''),
            'name' => (string) ($employeeRow['employee_name'] ?? ''),
            'bookings' => $bookingsHandled,
            'leads' => $leadsHandled,
            'chats' => $chatReplies,
            'followups' => (int) ($employeeRow['lead_followups'] ?? 0) + (int) ($employeeRow['booking_followups'] ?? 0),
            'missed_followups' => $missedFollowups,
            'cancelled' => $cancelled,
            'quantity_score' => $quantityScore,
            'penalty_score' => $penaltyScore,
            'score' => $score,
            'marks' => $marks,
            'revenue' => with_currency_symbol(0),
        ];
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
