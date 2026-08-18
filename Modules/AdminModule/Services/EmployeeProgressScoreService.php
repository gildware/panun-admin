<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadChangeLog;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Services\LeadFollowupService;
use Modules\LeadManagement\Services\LeadOpenStatusService;

/**
 * Employee ranking score: quantity of work (+), lead data quality (+), and late follow-up penalties (−).
 */
class EmployeeProgressScoreService
{
    public const POINTS_BOOKINGS_CREATED = 2;

    public const POINTS_BOOKINGS_COMPLETED = 10;

    public const POINTS_LEADS_HANDLED = 3;

    public const POINTS_PROVIDERS_REGISTERED = 10;

    public const POINTS_OUTBOUND_ENQUIRIES = 1;

    public const POINTS_HELPED_LEAD_FOLLOWUP = 1;

    public const POINTS_HELPED_BOOKING_FOLLOWUP = 1;

    public const POINTS_HELPED_BOOKING_UPDATE = 2;

    public const POINTS_HELPED_LEAD_UPDATE = 1;

    /** @var list<array{key: string, max_minutes: int|null, points: int, label: string}> */
    public const LATE_PENALTY_BUCKETS = [
        ['key' => 'late_1h', 'max_minutes' => 60, 'points' => 1, 'label' => 'Follow up late by ≤1 hour'],
        ['key' => 'late_2h', 'max_minutes' => 120, 'points' => 2, 'label' => 'Follow up late by ≤2 hours'],
        ['key' => 'late_4h', 'max_minutes' => 240, 'points' => 3, 'label' => 'Follow up late by ≤4 hours'],
        ['key' => 'late_8h', 'max_minutes' => 480, 'points' => 5, 'label' => 'Follow up late by ≤8 hours'],
        ['key' => 'late_over_8h', 'max_minutes' => null, 'points' => 10, 'label' => 'Follow up late by >8 hours'],
    ];

    public function __construct(
        private readonly LeadFollowupService $leadFollowupService,
        private readonly LeadDataQualityScoreService $leadDataQuality,
        private readonly LeadOpenStatusService $leadOpenStatus,
    ) {}

    /**
     * @return list<array{key: string, label: string, points: int, sign: string}>
     */
    public static function weightLegend(): array
    {
        $legend = [
            ['key' => 'bookings_created', 'label' => translate('New_Bookings_Created') ?? 'New Bookings created', 'points' => self::POINTS_BOOKINGS_CREATED, 'sign' => '+'],
            ['key' => 'bookings_completed', 'label' => translate('Bookings_Completed') ?? 'Bookings completed', 'points' => self::POINTS_BOOKINGS_COMPLETED, 'sign' => '+'],
            ['key' => 'leads_handled', 'label' => translate('New_Leads_Handled') ?? 'New Leads handled', 'points' => self::POINTS_LEADS_HANDLED, 'sign' => '+'],
            ['key' => 'providers_registered', 'label' => translate('New_Providers_Registered') ?? 'New Providers registered', 'points' => self::POINTS_PROVIDERS_REGISTERED, 'sign' => '+'],
            ['key' => 'outbound_enquiries', 'label' => translate('Outbound_Enquiries') ?? 'Outbound Enquiries', 'points' => self::POINTS_OUTBOUND_ENQUIRIES, 'sign' => '+'],
            [
                'key' => 'lead_data_quality_high',
                'label' => translate('Progress_lead_data_quality_high') ?? 'Lead data quality ≥80%',
                'points' => LeadDataQualityScoreService::MARKS_HIGH,
                'sign' => '+',
            ],
            [
                'key' => 'lead_data_quality_mid',
                'label' => translate('Progress_lead_data_quality_mid') ?? 'Lead data quality 50–79%',
                'points' => LeadDataQualityScoreService::MARKS_MID,
                'sign' => '+',
            ],
        ];

        foreach (self::LATE_PENALTY_BUCKETS as $bucket) {
            $legend[] = [
                'key' => $bucket['key'],
                'label' => self::lateBucketLabel($bucket),
                'points' => $bucket['points'],
                'sign' => '−',
            ];
        }

        $legend[] = ['key' => 'helped_lead_followups', 'label' => translate('Progress_helped_lead_followups') ?? 'Lead follow-ups for others', 'points' => self::POINTS_HELPED_LEAD_FOLLOWUP, 'sign' => '+'];
        $legend[] = ['key' => 'helped_booking_followups', 'label' => translate('Progress_helped_booking_followups') ?? 'Booking follow-ups for others', 'points' => self::POINTS_HELPED_BOOKING_FOLLOWUP, 'sign' => '+'];
        $legend[] = ['key' => 'helped_booking_updates', 'label' => translate('Progress_helped_booking_updates') ?? 'Booking updates for others', 'points' => self::POINTS_HELPED_BOOKING_UPDATE, 'sign' => '+'];
        $legend[] = ['key' => 'helped_lead_updates', 'label' => translate('Progress_helped_lead_updates') ?? 'Lead updates for others', 'points' => self::POINTS_HELPED_LEAD_UPDATE, 'sign' => '+'];

        return $legend;
    }

    /**
     * @param  array{key: string, label: string}  $bucket
     */
    public static function lateBucketLabel(array $bucket): string
    {
        $key = 'Progress_'.$bucket['key'];
        $translated = translate($key);

        return ($translated !== '' && $translated !== $key) ? $translated : $bucket['label'];
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

        $leadsByEmployee = $this->leadsHandledByEmployee($employeeIds, $periodStart, $periodEnd);
        $bookingsCreatedByEmployee = $this->bookingsCreatedByEmployee($employeeIds, $periodStart, $periodEnd);
        $bookingsCompletedByEmployee = $this->bookingsCompletedByEmployee($employeeIds, $periodStart, $periodEnd);
        $providersByEmployee = $this->providersRegisteredByEmployee($employeeIds, $periodStart, $periodEnd);
        $outboundByEmployee = $this->outboundEnquiriesByEmployee($employeeIds, $periodStart, $periodEnd);
        $lateByEmployee = $this->lateFollowupPenaltiesByEmployee($employeeIds, $periodStart, $periodEnd);
        $qualityByEmployee = $this->leadDataQuality->summarizeForEmployees($employeeIds, $periodStart, $periodEnd);
        $helpedByEmployee = $this->helpedOthersByEmployee($employeeIds, $periodStart, $periodEnd);
        $activeByEmployee = $this->activeAssignmentsByEmployee($employeeIds);
        $ranked = [];

        foreach ($employeeTotals as $employeeRow) {
            $employeeId = (string) ($employeeRow['employee_id'] ?? '');
            if ($employeeId === '') {
                continue;
            }

            $ranked[] = $this->scoreEmployeeRow(
                $employeeRow,
                (int) ($leadsByEmployee[$employeeId] ?? 0),
                (int) ($providersByEmployee[$employeeId] ?? 0),
                $lateByEmployee[$employeeId] ?? $this->emptyLatePenalty(),
                $qualityByEmployee[$employeeId] ?? $this->leadDataQuality->emptySummary(),
                (int) ($bookingsCreatedByEmployee[$employeeId] ?? 0),
                (int) ($bookingsCompletedByEmployee[$employeeId] ?? 0),
                $helpedByEmployee[$employeeId] ?? $this->emptyHelpedOthers(),
                $activeByEmployee[$employeeId] ?? $this->emptyActiveAssignments(),
                (int) ($outboundByEmployee[$employeeId] ?? 0),
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
     * @param  array{total_count: int, total_points: int, buckets: array<string, array{count: int, unit_points: int, points: int, label: string}>}  $latePenalty
     * @param  array{
     *     closed_count?: int,
     *     avg_score?: float,
     *     quality_pct?: float,
     *     pass_count?: int,
     *     high_count?: int,
     *     mid_count?: int,
     *     low_count?: int,
     *     mark_points?: int
     * }  $dataQuality
     * @return array<string, mixed>
     */
    public function scoreEmployeeRow(
        array $employeeRow,
        ?int $leadsHandledOverride = null,
        int $providersRegistered = 0,
        array $latePenalty = [],
        array $dataQuality = [],
        ?int $bookingsCreatedOverride = null,
        ?int $bookingsCompletedOverride = null,
        array $helpedOthers = [],
        array $activeAssignments = [],
        ?int $outboundEnquiriesOverride = null,
    ): array {
        $bookingsCreated = $bookingsCreatedOverride ?? (int) ($employeeRow['bookings_created'] ?? 0);
        $bookingsCompleted = $bookingsCompletedOverride ?? (int) ($employeeRow['bookings_completed'] ?? 0);
        $leadsHandled = $leadsHandledOverride ?? (int) ($employeeRow['leads_assigned'] ?? $employeeRow['leads_handled'] ?? 0);
        $outboundEnquiries = $outboundEnquiriesOverride ?? (int) ($employeeRow['outbound_enquiries'] ?? 0);
        $latePenalty = $latePenalty === [] ? $this->emptyLatePenalty() : $latePenalty;
        $dataQuality = $dataQuality === [] ? $this->leadDataQuality->emptySummary() : $dataQuality;
        $qualityHigh = (int) ($dataQuality['high_count'] ?? 0);
        $qualityMid = (int) ($dataQuality['mid_count'] ?? 0);

        $marks = [
            $this->markLine(
                'bookings_created',
                translate('New_Bookings_Created') ?? 'New Bookings created',
                $bookingsCreated,
                self::POINTS_BOOKINGS_CREATED,
                true,
            ),
            $this->markLine(
                'bookings_completed',
                translate('Bookings_Completed') ?? 'Bookings completed',
                $bookingsCompleted,
                self::POINTS_BOOKINGS_COMPLETED,
                true,
            ),
            $this->markLine(
                'leads_handled',
                translate('New_Leads_Handled') ?? 'New Leads handled',
                $leadsHandled,
                self::POINTS_LEADS_HANDLED,
                true,
            ),
            $this->markLine(
                'providers_registered',
                translate('New_Providers_Registered') ?? 'New Providers registered',
                $providersRegistered,
                self::POINTS_PROVIDERS_REGISTERED,
                true,
            ),
            $this->markLine(
                'outbound_enquiries',
                translate('Outbound_Enquiries') ?? 'Outbound Enquiries',
                $outboundEnquiries,
                self::POINTS_OUTBOUND_ENQUIRIES,
                true,
            ),
        ];

        $marks[] = $this->markLine(
            'lead_data_quality_high',
            translate('Progress_lead_data_quality_high') ?? 'Lead data quality ≥80%',
            $qualityHigh,
            LeadDataQualityScoreService::MARKS_HIGH,
            true,
        );
        $marks[] = $this->markLine(
            'lead_data_quality_mid',
            translate('Progress_lead_data_quality_mid') ?? 'Lead data quality 50–79%',
            $qualityMid,
            LeadDataQualityScoreService::MARKS_MID,
            true,
        );

        foreach (self::LATE_PENALTY_BUCKETS as $bucket) {
            $bucketKey = $bucket['key'];
            $bucketData = $latePenalty['buckets'][$bucketKey] ?? [];
            $count = (int) ($bucketData['count'] ?? 0);
            $marks[] = $this->markLine(
                $bucketKey,
                self::lateBucketLabel($bucket),
                $count,
                (int) ($bucketData['unit_points'] ?? $bucket['points']),
                false,
            );
        }

        $helpedOthers = $helpedOthers === [] ? $this->emptyHelpedOthers() : $helpedOthers;
        $activeAssignments = $activeAssignments === [] ? $this->emptyActiveAssignments() : $activeAssignments;
        $helpedMarks = [
            $this->markLine(
                'helped_lead_followups',
                translate('Progress_helped_lead_followups') ?? 'Lead follow-ups for others',
                (int) ($helpedOthers['lead_followups'] ?? 0),
                self::POINTS_HELPED_LEAD_FOLLOWUP,
                true,
            ),
            $this->markLine(
                'helped_booking_followups',
                translate('Progress_helped_booking_followups') ?? 'Booking follow-ups for others',
                (int) ($helpedOthers['booking_followups'] ?? 0),
                self::POINTS_HELPED_BOOKING_FOLLOWUP,
                true,
            ),
            $this->markLine(
                'helped_booking_updates',
                translate('Progress_helped_booking_updates') ?? 'Booking updates for others',
                (int) ($helpedOthers['booking_updates'] ?? 0),
                self::POINTS_HELPED_BOOKING_UPDATE,
                true,
            ),
            $this->markLine(
                'helped_lead_updates',
                translate('Progress_helped_lead_updates') ?? 'Lead updates for others',
                (int) ($helpedOthers['lead_updates'] ?? 0),
                self::POINTS_HELPED_LEAD_UPDATE,
                true,
            ),
        ];

        $quantityScore = 0;
        $penaltyScore = 0;
        $helpedScore = 0;
        foreach ($marks as $mark) {
            if (! empty($mark['positive'])) {
                $quantityScore += (int) ($mark['points'] ?? 0);
            } else {
                $penaltyScore += (int) ($mark['points'] ?? 0);
            }
        }
        foreach ($helpedMarks as $mark) {
            $helpedScore += (int) ($mark['points'] ?? 0);
        }
        $score = $quantityScore + $helpedScore + $penaltyScore;

        return [
            'employee_id' => (string) ($employeeRow['employee_id'] ?? ''),
            'name' => (string) ($employeeRow['employee_name'] ?? ''),
            'bookings' => $bookingsCreated,
            'bookings_completed' => $bookingsCompleted,
            'leads' => $leadsHandled,
            'providers_registered' => $providersRegistered,
            'outbound_enquiries' => $outboundEnquiries,
            'chats' => (int) ($employeeRow['whatsapp_replies'] ?? 0),
            'followups' => (int) ($employeeRow['lead_followups'] ?? 0) + (int) ($employeeRow['booking_followups'] ?? 0),
            'late_followups' => (int) ($latePenalty['total_count'] ?? 0),
            'late_penalty_points' => (int) ($latePenalty['total_points'] ?? 0),
            'lead_data_quality' => $dataQuality,
            'missed_followups' => 0,
            'cancelled' => (int) ($employeeRow['bookings_cancelled'] ?? 0),
            'quantity_score' => $quantityScore,
            'helped_score' => $helpedScore,
            'penalty_score' => $penaltyScore,
            'score' => $score,
            'marks' => $marks,
            'helped_marks' => $helpedMarks,
            'active_open_leads' => (int) ($activeAssignments['open_leads'] ?? 0),
            'active_bookings' => (int) ($activeAssignments['active_bookings'] ?? 0),
            'active_assignments' => (int) ($activeAssignments['total'] ?? 0),
            'revenue' => with_currency_symbol(0),
        ];
    }

    /**
     * Open leads and active bookings currently assigned (not period-scoped).
     *
     * @param  list<string>  $employeeIds
     * @return array<string, array{open_leads: int, active_bookings: int, total: int}>
     */
    public function activeAssignmentsByEmployee(array $employeeIds): array
    {
        $counts = [];
        foreach ($employeeIds as $employeeId) {
            $counts[(string) $employeeId] = $this->emptyActiveAssignments();
        }

        if ($employeeIds === []) {
            return $counts;
        }

        $openLeadQuery = Lead::query()
            ->whereIn('handled_by', $employeeIds)
            ->whereNotNull('handled_by')
            ->where('handled_by', '!=', Lead::HANDLED_BY_AI);
        $this->leadOpenStatus->restrictQueryToOpenLeads($openLeadQuery);

        foreach ($openLeadQuery
            ->selectRaw('handled_by, COUNT(*) as cnt')
            ->groupBy('handled_by')
            ->pluck('cnt', 'handled_by') as $id => $count) {
            $employeeId = (string) $id;
            if (isset($counts[$employeeId])) {
                $counts[$employeeId]['open_leads'] = (int) $count;
            }
        }

        foreach (Booking::query()
            ->selectRaw('assignee_id, COUNT(*) as cnt')
            ->whereIn('assignee_id', $employeeIds)
            ->whereNotNull('assignee_id')
            ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS)
            ->groupBy('assignee_id')
            ->pluck('cnt', 'assignee_id') as $id => $count) {
            $employeeId = (string) $id;
            if (isset($counts[$employeeId])) {
                $counts[$employeeId]['active_bookings'] = (int) $count;
            }
        }

        foreach ($counts as &$row) {
            $row['total'] = $row['open_leads'] + $row['active_bookings'];
        }
        unset($row);

        return $counts;
    }

    /**
     * @return array{open_leads: int, active_bookings: int, total: int}
     */
    private function emptyActiveAssignments(): array
    {
        return [
            'open_leads' => 0,
            'active_bookings' => 0,
            'total' => 0,
        ];
    }

    /**
     * @param  list<string>  $employeeIds
     * @return array<string, array{lead_followups: int, booking_followups: int, booking_updates: int, lead_updates: int}>
     */
    public function helpedOthersByEmployee(array $employeeIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $counts = [];
        foreach ($employeeIds as $employeeId) {
            $counts[(string) $employeeId] = $this->emptyHelpedOthers();
        }

        if ($employeeIds === []) {
            return $counts;
        }

        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        LeadFollowup::query()
            ->whereIn('created_by', $employeeIds)
            ->whereNotNull('followup_at')
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->with(['lead:id,handled_by'])
            ->get(['id', 'created_by', 'lead_id'])
            ->each(function (LeadFollowup $followup) use (&$counts) {
                $employeeId = (string) ($followup->created_by ?? '');
                $assignee = (string) ($followup->lead?->handled_by ?? '');
                if ($employeeId === '' || ! isset($counts[$employeeId])) {
                    return;
                }
                if ($assignee === '' || $assignee === Lead::HANDLED_BY_AI || $assignee === $employeeId) {
                    return;
                }
                $counts[$employeeId]['lead_followups']++;
            });

        BookingFollowup::query()
            ->whereIn('created_by', $employeeIds)
            ->whereNotNull('followup_at')
            ->where('status', 'completed')
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->with(['booking:id,assignee_id'])
            ->get(['id', 'created_by', 'booking_id', 'status'])
            ->each(function (BookingFollowup $followup) use (&$counts) {
                if ($followup->isRescheduled()) {
                    return;
                }
                $employeeId = (string) ($followup->created_by ?? '');
                $assignee = (string) ($followup->booking?->assignee_id ?? '');
                if ($employeeId === '' || ! isset($counts[$employeeId])) {
                    return;
                }
                if ($assignee === '' || $assignee === $employeeId) {
                    return;
                }
                $counts[$employeeId]['booking_followups']++;
            });

        $historyTable = (new BookingStatusHistory)->getTable();
        if (Schema::hasTable($historyTable)) {
            $bookingUpdateRows = DB::table($historyTable.' as h')
                ->join('bookings as b', 'b.id', '=', 'h.booking_id')
                ->selectRaw('h.changed_by as user_id, COUNT(*) as total')
                ->whereIn('h.changed_by', $employeeIds)
                ->whereBetween('h.created_at', [$rangeStart, $rangeEnd])
                ->whereNotNull('h.changed_by')
                ->whereNotNull('b.assignee_id')
                ->whereColumn('b.assignee_id', '!=', 'h.changed_by')
                ->whereRaw('h.id > (
                    SELECT MIN(h2.id) FROM '.$historyTable.' h2
                    WHERE h2.booking_id = h.booking_id
                )')
                ->groupBy('h.changed_by')
                ->get();

            foreach ($bookingUpdateRows as $row) {
                $employeeId = (string) ($row->user_id ?? '');
                if ($employeeId !== '' && isset($counts[$employeeId])) {
                    $counts[$employeeId]['booking_updates'] = (int) $row->total;
                }
            }
        }

        $changeLogTable = (new LeadChangeLog)->getTable();
        if (Schema::hasTable($changeLogTable)) {
            LeadChangeLog::query()
                ->whereIn('changed_by', $employeeIds)
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->whereNotNull('changed_by')
                ->whereNotNull('lead_id')
                ->with(['lead:id,handled_by'])
                ->get(['id', 'changed_by', 'lead_id'])
                ->each(function (LeadChangeLog $log) use (&$counts) {
                    $employeeId = (string) ($log->changed_by ?? '');
                    $assignee = (string) ($log->lead?->handled_by ?? '');
                    if ($employeeId === '' || ! isset($counts[$employeeId])) {
                        return;
                    }
                    if ($assignee === '' || $assignee === Lead::HANDLED_BY_AI || $assignee === $employeeId) {
                        return;
                    }
                    $counts[$employeeId]['lead_updates']++;
                });
        }

        return $counts;
    }

    /**
     * @return array{lead_followups: int, booking_followups: int, booking_updates: int, lead_updates: int}
     */
    private function emptyHelpedOthers(): array
    {
        return [
            'lead_followups' => 0,
            'booking_followups' => 0,
            'booking_updates' => 0,
            'lead_updates' => 0,
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
     * Bookings assigned to the employee and created in the period.
     * Matches the Bookings tab / quantity widgets (assignee_id + created_at).
     *
     * @param  list<string>  $employeeIds
     * @return array<string, int>
     */
    public function bookingsCreatedByEmployee(array $employeeIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $counts = array_fill_keys($employeeIds, 0);

        if ($employeeIds === []) {
            return $counts;
        }

        $rows = Booking::query()
            ->selectRaw('assignee_id, COUNT(*) as cnt')
            ->whereIn('assignee_id', $employeeIds)
            ->whereNotNull('assignee_id')
            ->whereBetween('created_at', [
                $periodStart->copy()->startOfDay(),
                $periodEnd->copy()->endOfDay(),
            ])
            ->groupBy('assignee_id')
            ->pluck('cnt', 'assignee_id');

        foreach ($rows as $id => $count) {
            $counts[(string) $id] = (int) $count;
        }

        return $counts;
    }

    /**
     * Bookings assigned to the employee, created in period, currently completed.
     * Matches booking status analytics on the progress report.
     *
     * @param  list<string>  $employeeIds
     * @return array<string, int>
     */
    public function bookingsCompletedByEmployee(array $employeeIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $counts = array_fill_keys($employeeIds, 0);

        if ($employeeIds === []) {
            return $counts;
        }

        $rows = Booking::query()
            ->selectRaw('assignee_id, COUNT(*) as cnt')
            ->whereIn('assignee_id', $employeeIds)
            ->whereNotNull('assignee_id')
            ->where('booking_status', 'completed')
            ->whereBetween('created_at', [
                $periodStart->copy()->startOfDay(),
                $periodEnd->copy()->endOfDay(),
            ])
            ->groupBy('assignee_id')
            ->pluck('cnt', 'assignee_id');

        foreach ($rows as $id => $count) {
            $counts[(string) $id] = (int) $count;
        }

        return $counts;
    }

    /**
     * Outbound enquiries logged against the employee (handled_by) in the period.
     *
     * @param  list<string>  $employeeIds
     * @return array<string, int>
     */
    public function outboundEnquiriesByEmployee(array $employeeIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $counts = array_fill_keys($employeeIds, 0);

        if ($employeeIds === [] || ! Schema::hasTable((new LeadOutboundEnquiry)->getTable())) {
            return $counts;
        }

        $rows = LeadOutboundEnquiry::query()
            ->selectRaw('handled_by, COUNT(*) as cnt')
            ->whereIn('handled_by', $employeeIds)
            ->whereNotNull('handled_by')
            ->whereBetween('contacted_at', [
                $periodStart->copy()->startOfDay(),
                $periodEnd->copy()->endOfDay(),
            ])
            ->groupBy('handled_by')
            ->pluck('cnt', 'handled_by');

        foreach ($rows as $id => $count) {
            $counts[(string) $id] = (int) $count;
        }

        return $counts;
    }

    /**
     * Provider leads assigned to the employee, received in period, currently Registered.
     * Matches the Provider Leads tab (same base query + latest status outcome).
     *
     * @param  list<string>  $employeeIds
     * @return array<string, int>
     */
    public function providersRegisteredByEmployee(array $employeeIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $counts = array_fill_keys($employeeIds, 0);

        if ($employeeIds === []) {
            return $counts;
        }

        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        $completedStatusIds = ProviderLeadStatus::query()
            ->where('base_type', 'completed')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if ($completedStatusIds === []) {
            return $counts;
        }

        $leads = Lead::query()
            ->whereIn('handled_by', $employeeIds)
            ->where('lead_type', Lead::TYPE_PROVIDER)
            ->whereBetween('date_time_of_lead_received', [$rangeStart, $rangeEnd])
            ->whereNotNull('handled_by')
            ->where('handled_by', '!=', Lead::HANDLED_BY_AI)
            ->get(['id', 'handled_by']);

        if ($leads->isEmpty()) {
            return $counts;
        }

        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leads->pluck('id')->all())
            ->where('type', Lead::TYPE_PROVIDER)
            ->orderByDesc('created_at')
            ->get(['lead_id', 'data'])
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        foreach ($leads as $lead) {
            $history = $histories->get($lead->id);
            $data = is_array($history?->data) ? $history->data : [];
            $statusId = isset($data['provider_lead_status_id']) ? (string) $data['provider_lead_status_id'] : '';
            if ($statusId === '' || ! in_array($statusId, $completedStatusIds, true)) {
                continue;
            }

            $employeeId = (string) ($lead->handled_by ?? '');
            if ($employeeId !== '' && array_key_exists($employeeId, $counts)) {
                $counts[$employeeId]++;
            }
        }

        return $counts;
    }

    /**
     * Late follow-ups with hour-tier penalties (lead + booking), attributed to assignee.
     *
     * @param  list<string>  $employeeIds
     * @return array<string, array{total_count: int, total_points: int, buckets: array<string, array{count: int, unit_points: int, points: int, label: string}>}>
     */
    public function lateFollowupPenaltiesByEmployee(array $employeeIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $late = [];
        foreach ($employeeIds as $employeeId) {
            $late[$employeeId] = $this->emptyLatePenalty();
        }

        if ($employeeIds === []) {
            return $late;
        }

        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        $periodLeadFollowups = LeadFollowup::query()
            ->whereNotNull('followup_at')
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->whereHas('lead', function ($query) use ($employeeIds) {
                $query->whereIn('handled_by', $employeeIds)
                    ->whereNotNull('handled_by')
                    ->where('handled_by', '!=', Lead::HANDLED_BY_AI);
            })
            ->with(['lead:id,handled_by,date_time_of_lead_received'])
            ->get(['id', 'lead_id', 'followup_at', 'due_followup_at']);

        $leadIds = $periodLeadFollowups->pluck('lead_id')->map(fn ($id) => (string) $id)->filter()->unique()->values()->all();
        if ($leadIds !== []) {
            $leads = Lead::query()
                ->whereIn('id', $leadIds)
                ->get(['id', 'handled_by', 'date_time_of_lead_received'])
                ->keyBy(fn (Lead $lead) => (string) $lead->id);

            $historyByLead = LeadFollowup::query()
                ->whereIn('lead_id', $leadIds)
                ->whereNotNull('followup_at')
                ->orderBy('followup_at')
                ->get(['id', 'lead_id', 'followup_at', 'due_followup_at', 'next_followup_at'])
                ->groupBy(fn (LeadFollowup $followup) => (string) $followup->lead_id);

            $dueByFollowupId = [];
            foreach ($historyByLead as $leadId => $history) {
                $lead = $leads->get((string) $leadId);
                if (! $lead) {
                    continue;
                }
                foreach ($this->leadFollowupService->buildFollowupDelayMeta($lead, $history) as $followupId => $meta) {
                    $dueByFollowupId[(int) $followupId] = $meta['due_at'] ?? null;
                }
            }

            foreach ($periodLeadFollowups as $followup) {
                $due = $dueByFollowupId[(int) $followup->id] ?? $followup->due_followup_at;
                if (! $due instanceof Carbon) {
                    $due = $due ? Carbon::parse($due) : null;
                }
                if (! $due || $followup->followup_at->lte($due)) {
                    continue;
                }

                $employeeId = (string) ($followup->lead?->handled_by ?? $leads->get((string) $followup->lead_id)?->handled_by ?? '');
                if ($employeeId === '' || ! array_key_exists($employeeId, $late)) {
                    continue;
                }

                $delayMinutes = (int) round($due->diffInMinutes($followup->followup_at));
                $this->addLatePenalty($late[$employeeId], $delayMinutes);
            }
        }

        $bookingFollowups = BookingFollowup::query()
            ->whereNotNull('followup_at')
            ->whereIn('status', ['completed', 'rescheduled'])
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->whereHas('booking', function ($query) use ($employeeIds) {
                $query->whereIn('assignee_id', $employeeIds)->whereNotNull('assignee_id');
            })
            ->with(['booking:id,assignee_id'])
            ->get(['id', 'booking_id', 'followup_at', 'due_followup_at', 'date', 'status']);

        foreach ($bookingFollowups as $followup) {
            if ($followup->isRescheduled()) {
                continue;
            }

            $dueRaw = $followup->due_followup_at ?? $followup->date;
            if (! $dueRaw) {
                continue;
            }
            $due = $dueRaw instanceof Carbon ? $dueRaw : Carbon::parse($dueRaw);
            if ($followup->followup_at->lte($due)) {
                continue;
            }

            $employeeId = (string) ($followup->booking?->assignee_id ?? '');
            if ($employeeId === '' || ! array_key_exists($employeeId, $late)) {
                continue;
            }

            $delayMinutes = (int) round($due->diffInMinutes($followup->followup_at));
            $this->addLatePenalty($late[$employeeId], $delayMinutes);
        }

        return $late;
    }

    /**
     * @return array{total_count: int, total_points: int, buckets: array<string, array{count: int, unit_points: int, points: int, label: string}>}
     */
    private function emptyLatePenalty(): array
    {
        $buckets = [];
        foreach (self::LATE_PENALTY_BUCKETS as $bucket) {
            $buckets[$bucket['key']] = [
                'count' => 0,
                'unit_points' => $bucket['points'],
                'points' => 0,
                'label' => self::lateBucketLabel($bucket),
            ];
        }

        return [
            'total_count' => 0,
            'total_points' => 0,
            'buckets' => $buckets,
        ];
    }

    /**
     * @param  array{total_count: int, total_points: int, buckets: array<string, array{count: int, unit_points: int, points: int, label: string}>}  $late
     */
    private function addLatePenalty(array &$late, int $delayMinutes): void
    {
        if ($delayMinutes <= 0) {
            return;
        }

        $bucket = $this->lateBucketForMinutes($delayMinutes);
        $key = $bucket['key'];
        $unit = $bucket['points'];

        $late['buckets'][$key]['count']++;
        $late['buckets'][$key]['points'] -= $unit;
        $late['total_count']++;
        $late['total_points'] -= $unit;
    }

    /**
     * @return array{key: string, max_minutes: int|null, points: int, label: string}
     */
    private function lateBucketForMinutes(int $delayMinutes): array
    {
        foreach (self::LATE_PENALTY_BUCKETS as $bucket) {
            $max = $bucket['max_minutes'];
            if ($max === null || $delayMinutes <= $max) {
                return $bucket;
            }
        }

        return self::LATE_PENALTY_BUCKETS[array_key_last(self::LATE_PENALTY_BUCKETS)];
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
