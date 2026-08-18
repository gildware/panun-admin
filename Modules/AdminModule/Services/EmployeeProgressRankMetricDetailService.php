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
use Modules\UserManagement\Entities\User;

/**
 * Drill-down rows for employee ranking score metrics (matches EmployeeProgressScoreService definitions).
 */
class EmployeeProgressRankMetricDetailService
{
    public function __construct(
        private readonly LeadFollowupService $leadFollowupService,
        private readonly LeadDataQualityScoreService $leadDataQuality,
        private readonly LeadOpenStatusService $leadOpenStatus,
    ) {}

    /**
     * @return list<string>
     */
    public static function validMetricKeys(): array
    {
        $keys = [
            'bookings_created',
            'bookings_completed',
            'leads_handled',
            'providers_registered',
            'outbound_enquiries',
            'lead_data_quality_high',
            'lead_data_quality_mid',
            'helped_lead_followups',
            'helped_booking_followups',
            'helped_booking_updates',
            'helped_lead_updates',
        ];

        foreach (EmployeeProgressScoreService::LATE_PENALTY_BUCKETS as $bucket) {
            $keys[] = $bucket['key'];
        }

        return $keys;
    }

    /**
     * @return array{key: string, label: string, columns: list<array{key: string, label: string}>}|null
     */
    public static function definition(string $metricKey): ?array
    {
        $defs = self::definitions();

        return $defs[$metricKey] ?? null;
    }

    /**
     * @return array<string, array{key: string, label: string, columns: list<array{key: string, label: string}>}>
     */
    public static function definitions(): array
    {
        return [
            'bookings_created' => [
                'key' => 'bookings_created',
                'label' => translate('New_Bookings_Created') ?? 'New Bookings created',
                'columns' => [
                    ['key' => 'readable_id', 'label' => translate('Booking_ID') ?? 'Booking ID'],
                    ['key' => 'customer', 'label' => translate('Customer') ?? 'Customer'],
                    ['key' => 'status', 'label' => translate('Status') ?? 'Status'],
                    ['key' => 'at', 'label' => translate('Created_at') ?? 'Created'],
                ],
            ],
            'bookings_completed' => [
                'key' => 'bookings_completed',
                'label' => translate('Bookings_Completed') ?? 'Bookings completed',
                'columns' => [
                    ['key' => 'readable_id', 'label' => translate('Booking_ID') ?? 'Booking ID'],
                    ['key' => 'customer', 'label' => translate('Customer') ?? 'Customer'],
                    ['key' => 'at', 'label' => translate('Created_at') ?? 'Created'],
                ],
            ],
            'leads_handled' => [
                'key' => 'leads_handled',
                'label' => translate('New_Leads_Handled') ?? 'New Leads handled',
                'columns' => [
                    ['key' => 'lead', 'label' => translate('Lead') ?? 'Lead'],
                    ['key' => 'phone', 'label' => translate('Phone') ?? 'Phone'],
                    ['key' => 'type', 'label' => translate('Type') ?? 'Type'],
                    ['key' => 'at', 'label' => translate('Received_at') ?? 'Received'],
                ],
            ],
            'providers_registered' => [
                'key' => 'providers_registered',
                'label' => translate('New_Providers_Registered') ?? 'New Providers registered',
                'columns' => [
                    ['key' => 'lead', 'label' => translate('Lead') ?? 'Lead'],
                    ['key' => 'phone', 'label' => translate('Phone') ?? 'Phone'],
                    ['key' => 'at', 'label' => translate('Received_at') ?? 'Received'],
                ],
            ],
            'outbound_enquiries' => [
                'key' => 'outbound_enquiries',
                'label' => translate('Outbound_Enquiries') ?? 'Outbound Enquiries',
                'columns' => [
                    ['key' => 'customer', 'label' => translate('Customer') ?? 'Customer'],
                    ['key' => 'phone', 'label' => translate('Phone') ?? 'Phone'],
                    ['key' => 'channel', 'label' => translate('Contacted_Through') ?? 'Channel'],
                    ['key' => 'status', 'label' => translate('Status') ?? 'Status'],
                    ['key' => 'at', 'label' => translate('Date_Time') ?? 'Date'],
                ],
            ],
            'lead_data_quality_high' => [
                'key' => 'lead_data_quality_high',
                'label' => translate('Progress_lead_data_quality_high') ?? 'Lead data quality ≥80%',
                'columns' => [
                    ['key' => 'lead', 'label' => translate('Lead') ?? 'Lead'],
                    ['key' => 'score', 'label' => translate('Score') ?? 'Score'],
                    ['key' => 'closed_at', 'label' => translate('Closed') ?? 'Closed'],
                ],
            ],
            'lead_data_quality_mid' => [
                'key' => 'lead_data_quality_mid',
                'label' => translate('Progress_lead_data_quality_mid') ?? 'Lead data quality 50–79%',
                'columns' => [
                    ['key' => 'lead', 'label' => translate('Lead') ?? 'Lead'],
                    ['key' => 'score', 'label' => translate('Score') ?? 'Score'],
                    ['key' => 'closed_at', 'label' => translate('Closed') ?? 'Closed'],
                ],
            ],
            'helped_lead_followups' => [
                'key' => 'helped_lead_followups',
                'label' => translate('Progress_helped_lead_followups') ?? 'Lead follow-ups for others',
                'columns' => [
                    ['key' => 'lead', 'label' => translate('Lead') ?? 'Lead'],
                    ['key' => 'assignee', 'label' => translate('Assignee') ?? 'Assignee'],
                    ['key' => 'remarks', 'label' => translate('Remarks') ?? 'Remarks'],
                    ['key' => 'at', 'label' => translate('Follow_up_at') ?? 'Follow-up at'],
                ],
            ],
            'helped_booking_followups' => [
                'key' => 'helped_booking_followups',
                'label' => translate('Progress_helped_booking_followups') ?? 'Booking follow-ups for others',
                'columns' => [
                    ['key' => 'readable_id', 'label' => translate('Booking_ID') ?? 'Booking ID'],
                    ['key' => 'assignee', 'label' => translate('Assignee') ?? 'Assignee'],
                    ['key' => 'for', 'label' => translate('For') ?? 'For'],
                    ['key' => 'remarks', 'label' => translate('Remarks') ?? 'Remarks'],
                    ['key' => 'at', 'label' => translate('Follow_up_at') ?? 'Follow-up at'],
                ],
            ],
            'helped_booking_updates' => [
                'key' => 'helped_booking_updates',
                'label' => translate('Progress_helped_booking_updates') ?? 'Booking updates for others',
                'columns' => [
                    ['key' => 'readable_id', 'label' => translate('Booking_ID') ?? 'Booking ID'],
                    ['key' => 'assignee', 'label' => translate('Assignee') ?? 'Assignee'],
                    ['key' => 'status', 'label' => translate('Status') ?? 'Status'],
                    ['key' => 'at', 'label' => translate('Updated_at') ?? 'Updated'],
                ],
            ],
            'helped_lead_updates' => [
                'key' => 'helped_lead_updates',
                'label' => translate('Progress_helped_lead_updates') ?? 'Lead updates for others',
                'columns' => [
                    ['key' => 'lead', 'label' => translate('Lead') ?? 'Lead'],
                    ['key' => 'assignee', 'label' => translate('Assignee') ?? 'Assignee'],
                    ['key' => 'changes', 'label' => translate('Changes') ?? 'Changes'],
                    ['key' => 'at', 'label' => translate('Updated_at') ?? 'Updated'],
                ],
            ],
            'late_1h' => self::lateDefinition('late_1h'),
            'late_2h' => self::lateDefinition('late_2h'),
            'late_4h' => self::lateDefinition('late_4h'),
            'late_8h' => self::lateDefinition('late_8h'),
            'late_over_8h' => self::lateDefinition('late_over_8h'),
        ];
    }

    /**
     * @return array{key: string, label: string, columns: list<array{key: string, label: string}>}
     */
    private static function lateDefinition(string $key): array
    {
        $bucket = collect(EmployeeProgressScoreService::LATE_PENALTY_BUCKETS)
            ->firstWhere('key', $key) ?? ['key' => $key, 'label' => $key];

        return [
            'key' => $key,
            'label' => EmployeeProgressScoreService::lateBucketLabel($bucket),
            'columns' => [
                ['key' => 'kind', 'label' => translate('Type') ?? 'Type'],
                ['key' => 'reference', 'label' => translate('Reference') ?? 'Reference'],
                ['key' => 'due_at', 'label' => translate('Due_at') ?? 'Due'],
                ['key' => 'followup_at', 'label' => translate('Follow_up_at') ?? 'Follow-up at'],
                ['key' => 'delay', 'label' => translate('Delay') ?? 'Delay'],
            ],
        ];
    }

    /**
     * @return array{
     *     metric_key: string,
     *     label: string,
     *     columns: list<array{key: string, label: string}>,
     *     rows: list<array<string, mixed>>,
     *     count: int
     * }
     */
    public function build(string $metricKey, string $employeeId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $definition = self::definition($metricKey);
        if ($definition === null || $employeeId === '') {
            return [
                'metric_key' => $metricKey,
                'label' => $metricKey,
                'columns' => [],
                'rows' => [],
                'count' => 0,
            ];
        }

        $rows = match ($metricKey) {
            'bookings_created' => $this->bookingsCreated($employeeId, $periodStart, $periodEnd),
            'bookings_completed' => $this->bookingsCompleted($employeeId, $periodStart, $periodEnd),
            'leads_handled' => $this->leadsHandled($employeeId, $periodStart, $periodEnd),
            'providers_registered' => $this->providersRegistered($employeeId, $periodStart, $periodEnd),
            'outbound_enquiries' => $this->outboundEnquiries($employeeId, $periodStart, $periodEnd),
            'lead_data_quality_high' => $this->leadDataQualityLeads($employeeId, $periodStart, $periodEnd, 'high'),
            'lead_data_quality_mid' => $this->leadDataQualityLeads($employeeId, $periodStart, $periodEnd, 'mid'),
            'helped_lead_followups' => $this->helpedLeadFollowups($employeeId, $periodStart, $periodEnd),
            'helped_booking_followups' => $this->helpedBookingFollowups($employeeId, $periodStart, $periodEnd),
            'helped_booking_updates' => $this->helpedBookingUpdates($employeeId, $periodStart, $periodEnd),
            'helped_lead_updates' => $this->helpedLeadUpdates($employeeId, $periodStart, $periodEnd),
            default => str_starts_with($metricKey, 'late_')
                ? $this->lateFollowups($employeeId, $periodStart, $periodEnd, $metricKey)
                : [],
        };

        return [
            'metric_key' => $metricKey,
            'label' => $definition['label'],
            'columns' => $definition['columns'],
            'rows' => $rows,
            'count' => count($rows),
        ];
    }

    /**
     * @param  array<string, string>  $periodParams
     * @param  array<string, string>  $employeeQuery
     */
    public static function detailUrl(
        string $metricKey,
        string $employeeId,
        array $periodParams,
        array $employeeQuery = [],
    ): string {
        return route('admin.my-progress.rank-metric', array_filter(array_merge(
            $employeeQuery,
            $periodParams,
            [
                'employee_id' => $employeeId,
                'metric' => $metricKey,
            ],
        )));
    }

    /**
     * @param  array<string, string>  $periodParams
     * @param  array<string, string>  $employeeQuery
     */
    public static function employeeReportUrl(
        string $employeeId,
        array $periodParams,
        array $employeeQuery = [],
    ): string {
        return route('admin.my-progress.ranking-employee', array_filter(array_merge(
            $employeeQuery,
            $periodParams,
            ['employee_id' => $employeeId],
        )));
    }

    /**
     * @return list<array{key: string, label: string, metric_keys: list<string>}>
     */
    public static function metricGroups(): array
    {
        return [
            [
                'key' => 'self',
                'label' => translate('Progress_marks_self') ?? 'Self',
                'metric_keys' => [
                    'bookings_created',
                    'bookings_completed',
                    'leads_handled',
                    'providers_registered',
                    'outbound_enquiries',
                    'lead_data_quality_high',
                    'lead_data_quality_mid',
                ],
            ],
            [
                'key' => 'helped',
                'label' => translate('Progress_helped_others') ?? 'Helped other',
                'metric_keys' => [
                    'helped_lead_followups',
                    'helped_booking_followups',
                    'helped_booking_updates',
                    'helped_lead_updates',
                ],
            ],
            [
                'key' => 'penalties',
                'label' => translate('Penalties') ?? 'Penalties',
                'metric_keys' => array_column(EmployeeProgressScoreService::LATE_PENALTY_BUCKETS, 'key'),
            ],
        ];
    }

    /**
     * All ranking mark sections with underlying record tables for one employee.
     *
     * @return array{
     *     groups: list<array{key: string, title: string, sections: list<array<string, mixed>>}>
     * }
     */
    public function buildFullEmployeeReport(string $employeeId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $groups = [];

        foreach (self::metricGroups() as $group) {
            $sections = [];
            foreach ($group['metric_keys'] as $metricKey) {
                $sections[] = $this->build($metricKey, $employeeId, $periodStart, $periodEnd);
            }

            $groups[] = [
                'key' => $group['key'],
                'title' => $group['label'],
                'sections' => $sections,
            ];
        }

        return ['groups' => $groups];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function bookingsCreated(string $employeeId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        return Booking::query()
            ->with(['customer:id,first_name,last_name'])
            ->where('assignee_id', $employeeId)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->orderByDesc('created_at')
            ->get(['id', 'readable_id', 'customer_id', 'booking_status', 'created_at'])
            ->map(fn (Booking $booking) => [
                'readable_id' => $booking->readable_id ?: $booking->id,
                'customer' => $this->customerName($booking->customer),
                'status' => $booking->booking_status,
                'at' => optional($booking->created_at)->format('d M Y h:i a'),
                'url' => route('admin.booking.details', $booking->id),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function bookingsCompleted(string $employeeId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        return Booking::query()
            ->with(['customer:id,first_name,last_name'])
            ->where('assignee_id', $employeeId)
            ->where('booking_status', 'completed')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->orderByDesc('created_at')
            ->get(['id', 'readable_id', 'customer_id', 'created_at'])
            ->map(fn (Booking $booking) => [
                'readable_id' => $booking->readable_id ?: $booking->id,
                'customer' => $this->customerName($booking->customer),
                'at' => optional($booking->created_at)->format('d M Y h:i a'),
                'url' => route('admin.booking.details', $booking->id),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function leadsHandled(string $employeeId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        return Lead::query()
            ->where('handled_by', $employeeId)
            ->whereBetween('date_time_of_lead_received', [$rangeStart, $rangeEnd])
            ->orderByDesc('date_time_of_lead_received')
            ->get(['id', 'name', 'phone_number', 'lead_type', 'date_time_of_lead_received'])
            ->map(fn (Lead $lead) => [
                'lead' => $lead->name ?: $lead->phone_number ?: $lead->id,
                'phone' => $lead->phone_number ?: '—',
                'type' => Lead::leadTypes()[$lead->lead_type] ?? $lead->lead_type,
                'at' => optional($lead->date_time_of_lead_received)->format('d M Y h:i a'),
                'url' => route('admin.lead.show', $lead->id),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function providersRegistered(string $employeeId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        $completedStatusIds = ProviderLeadStatus::query()
            ->where('base_type', 'completed')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if ($completedStatusIds === []) {
            return [];
        }

        $leads = Lead::query()
            ->where('handled_by', $employeeId)
            ->where('lead_type', Lead::TYPE_PROVIDER)
            ->whereBetween('date_time_of_lead_received', [$rangeStart, $rangeEnd])
            ->orderByDesc('date_time_of_lead_received')
            ->get(['id', 'name', 'phone_number', 'date_time_of_lead_received']);

        if ($leads->isEmpty()) {
            return [];
        }

        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leads->pluck('id')->all())
            ->where('type', Lead::TYPE_PROVIDER)
            ->orderByDesc('created_at')
            ->get(['lead_id', 'data', 'created_at'])
            ->groupBy('lead_id')
            ->map(fn ($group) => $group->first());

        $rows = [];
        foreach ($leads as $lead) {
            $history = $histories->get($lead->id);
            $data = is_array($history?->data) ? $history->data : [];
            $statusId = isset($data['provider_lead_status_id']) ? (string) $data['provider_lead_status_id'] : '';
            if ($statusId === '' || ! in_array($statusId, $completedStatusIds, true)) {
                continue;
            }

            $rows[] = [
                'lead' => $lead->name ?: $lead->phone_number ?: $lead->id,
                'phone' => $lead->phone_number ?: '—',
                'at' => optional($lead->date_time_of_lead_received)->format('d M Y h:i a'),
                'url' => route('admin.lead.show', $lead->id),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function outboundEnquiries(string $employeeId, Carbon $periodStart, Carbon $periodEnd): array
    {
        if (! Schema::hasTable((new LeadOutboundEnquiry)->getTable())) {
            return [];
        }

        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        return LeadOutboundEnquiry::query()
            ->with(['statusConfig:id,name', 'lead:id', 'relatedLead:id', 'booking:id,readable_id'])
            ->where('handled_by', $employeeId)
            ->whereBetween('contacted_at', [$rangeStart, $rangeEnd])
            ->orderByDesc('contacted_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (LeadOutboundEnquiry $enquiry) {
                $url = null;
                if ($enquiry->lead_id) {
                    $url = route('admin.lead.show', $enquiry->lead_id);
                } elseif ($enquiry->related_lead_id) {
                    $url = route('admin.lead.show', $enquiry->related_lead_id);
                } elseif ($enquiry->booking_id) {
                    $url = route('admin.booking.details', $enquiry->booking_id);
                }

                $channel = (string) ($enquiry->contacted_through ?? '');

                return [
                    'customer' => $enquiry->customer_name ?: '—',
                    'phone' => $enquiry->phone_number ?: '—',
                    'channel' => $channel !== '' ? ucfirst($channel) : '—',
                    'status' => $enquiry->statusConfig?->name ?? ($enquiry->status ?: '—'),
                    'at' => optional($enquiry->contacted_at)->format('d M Y h:i a'),
                    'url' => $url,
                ];
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function leadDataQualityLeads(
        string $employeeId,
        Carbon $periodStart,
        Carbon $periodEnd,
        string $tier,
    ): array {
        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        $leads = Lead::query()
            ->where('handled_by', $employeeId)
            ->whereNotNull('handled_by')
            ->where('handled_by', '!=', Lead::HANDLED_BY_AI)
            ->whereBetween('date_time_of_lead_received', [$rangeStart, $rangeEnd])
            ->get(['id', 'name', 'phone_number', 'lead_type', 'remarks', 'handled_by']);

        if ($leads->isEmpty()) {
            return [];
        }

        $statusMeta = $this->leadOpenStatus->buildLeadStatusMeta($leads);
        $closedLeads = $leads->filter(function (Lead $lead) use ($statusMeta) {
            $meta = $statusMeta[(int) $lead->id] ?? null;

            return $meta !== null && ! ($meta['is_open'] ?? true);
        })->values();

        if ($closedLeads->isEmpty()) {
            return [];
        }

        $leadIds = $closedLeads->pluck('id')->map(fn ($id) => (string) $id)->all();
        $context = $this->leadDataQuality->scoringContextForLeadIds($leadIds);
        $rows = [];

        foreach ($closedLeads as $lead) {
            $leadHistories = $context['histories']->get((string) $lead->id) ?? collect();
            $typedHistories = $leadHistories
                ->filter(fn (LeadTypeHistory $history) => (string) $history->type === (string) $lead->lead_type)
                ->values();

            $result = $this->leadDataQuality->scoreLead(
                $lead,
                $typedHistories->isNotEmpty() ? $typedHistories : $leadHistories,
                $context['call_logs']->get((string) $lead->id) ?? collect(),
                $context['comments']->get((string) $lead->id) ?? collect(),
                $context['customer_statuses'],
                $context['provider_statuses'],
            );

            $score = (int) ($result['score'] ?? 0);
            $matches = match ($tier) {
                'high' => $score >= LeadDataQualityScoreService::THRESHOLD_HIGH,
                'mid' => $score >= LeadDataQualityScoreService::THRESHOLD_MID && $score < LeadDataQualityScoreService::THRESHOLD_HIGH,
                default => false,
            };

            if (! $matches) {
                continue;
            }

            $history = $leadHistories->sortByDesc('created_at')->first();
            $rows[] = [
                'lead' => $lead->name ?: $lead->phone_number ?: $lead->id,
                'score' => $score.'%',
                'closed_at' => optional($history?->created_at)->format('d M Y h:i a') ?: '—',
                'url' => route('admin.lead.show', $lead->id),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function helpedLeadFollowups(string $employeeId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();
        $names = $this->employeeNames();

        return LeadFollowup::query()
            ->where('created_by', $employeeId)
            ->whereNotNull('followup_at')
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->with(['lead:id,name,phone_number,handled_by'])
            ->orderByDesc('followup_at')
            ->get(['id', 'lead_id', 'created_by', 'followup_at', 'remarks'])
            ->filter(function (LeadFollowup $followup) use ($employeeId) {
                $assignee = (string) ($followup->lead?->handled_by ?? '');

                return $assignee !== ''
                    && $assignee !== Lead::HANDLED_BY_AI
                    && $assignee !== $employeeId;
            })
            ->map(fn (LeadFollowup $followup) => [
                'lead' => $followup->lead?->name ?: $followup->lead?->phone_number ?: $followup->lead_id,
                'assignee' => $names[(string) ($followup->lead?->handled_by ?? '')] ?? '—',
                'remarks' => $followup->remarks ?: '—',
                'at' => optional($followup->followup_at)->format('d M Y h:i a'),
                'url' => $followup->lead_id ? route('admin.lead.show', $followup->lead_id) : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function helpedBookingFollowups(string $employeeId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();
        $names = $this->employeeNames();

        return BookingFollowup::query()
            ->where('created_by', $employeeId)
            ->whereNotNull('followup_at')
            ->where('status', 'completed')
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->with(['booking:id,readable_id,assignee_id'])
            ->orderByDesc('followup_at')
            ->get(['id', 'booking_id', 'created_by', 'followup_at', 'remarks', 'for', 'status'])
            ->filter(function (BookingFollowup $followup) use ($employeeId) {
                if ($followup->isRescheduled()) {
                    return false;
                }
                $assignee = (string) ($followup->booking?->assignee_id ?? '');

                return $assignee !== '' && $assignee !== $employeeId;
            })
            ->map(fn (BookingFollowup $followup) => [
                'readable_id' => $followup->booking?->readable_id ?: $followup->booking_id,
                'assignee' => $names[(string) ($followup->booking?->assignee_id ?? '')] ?? '—',
                'for' => $followup->for ?: '—',
                'remarks' => $followup->remarks ?: '—',
                'at' => optional($followup->followup_at)->format('d M Y h:i a'),
                'url' => $followup->booking_id ? route('admin.booking.details', $followup->booking_id) : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function helpedBookingUpdates(string $employeeId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $historyTable = (new BookingStatusHistory)->getTable();
        if (! Schema::hasTable($historyTable)) {
            return [];
        }

        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();
        $names = $this->employeeNames();

        $rows = DB::table($historyTable.' as h')
            ->join('bookings as b', 'b.id', '=', 'h.booking_id')
            ->select([
                'h.id',
                'h.booking_id',
                'h.booking_status',
                'h.created_at',
                'b.readable_id',
                'b.assignee_id',
            ])
            ->where('h.changed_by', $employeeId)
            ->whereBetween('h.created_at', [$rangeStart, $rangeEnd])
            ->whereNotNull('b.assignee_id')
            ->whereColumn('b.assignee_id', '!=', 'h.changed_by')
            ->whereRaw('h.id > (
                SELECT MIN(h2.id) FROM '.$historyTable.' h2
                WHERE h2.booking_id = h.booking_id
            )')
            ->orderByDesc('h.created_at')
            ->get();

        return collect($rows)->map(function ($row) use ($names) {
            return [
                'readable_id' => $row->readable_id ?: $row->booking_id,
                'assignee' => $names[(string) ($row->assignee_id ?? '')] ?? '—',
                'status' => $row->booking_status ?: '—',
                'at' => $row->created_at ? Carbon::parse($row->created_at)->format('d M Y h:i a') : '—',
                'url' => $row->booking_id ? route('admin.booking.details', $row->booking_id) : null,
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function helpedLeadUpdates(string $employeeId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $changeLogTable = (new LeadChangeLog)->getTable();
        if (! Schema::hasTable($changeLogTable)) {
            return [];
        }

        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();
        $names = $this->employeeNames();

        return LeadChangeLog::query()
            ->where('changed_by', $employeeId)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->whereNotNull('lead_id')
            ->with(['lead:id,name,phone_number,handled_by'])
            ->orderByDesc('created_at')
            ->get(['id', 'lead_id', 'changed_by', 'changes', 'created_at'])
            ->filter(function (LeadChangeLog $log) use ($employeeId) {
                $assignee = (string) ($log->lead?->handled_by ?? '');

                return $assignee !== ''
                    && $assignee !== Lead::HANDLED_BY_AI
                    && $assignee !== $employeeId;
            })
            ->map(function (LeadChangeLog $log) use ($names) {
                $changes = is_array($log->changes) ? implode(', ', array_keys($log->changes)) : '—';

                return [
                    'lead' => $log->lead?->name ?: $log->lead?->phone_number ?: $log->lead_id,
                    'assignee' => $names[(string) ($log->lead?->handled_by ?? '')] ?? '—',
                    'changes' => $changes !== '' ? $changes : '—',
                    'at' => optional($log->created_at)->format('d M Y h:i a'),
                    'url' => $log->lead_id ? route('admin.lead.show', $log->lead_id) : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lateFollowups(
        string $employeeId,
        Carbon $periodStart,
        Carbon $periodEnd,
        string $bucketKey,
    ): array {
        $rows = [];
        $rangeStart = $periodStart->copy()->startOfDay();
        $rangeEnd = $periodEnd->copy()->endOfDay();

        $periodLeadFollowups = LeadFollowup::query()
            ->whereNotNull('followup_at')
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->whereHas('lead', function ($query) use ($employeeId) {
                $query->where('handled_by', $employeeId)
                    ->whereNotNull('handled_by')
                    ->where('handled_by', '!=', Lead::HANDLED_BY_AI);
            })
            ->with(['lead:id,name,phone_number,handled_by'])
            ->get(['id', 'lead_id', 'followup_at', 'due_followup_at']);

        $leadIds = $periodLeadFollowups->pluck('lead_id')->map(fn ($id) => (string) $id)->filter()->unique()->values()->all();
        $dueByFollowupId = [];
        if ($leadIds !== []) {
            $leads = Lead::query()->whereIn('id', $leadIds)->get(['id', 'handled_by', 'date_time_of_lead_received'])
                ->keyBy(fn (Lead $lead) => (string) $lead->id);
            $historyByLead = LeadFollowup::query()
                ->whereIn('lead_id', $leadIds)
                ->whereNotNull('followup_at')
                ->orderBy('followup_at')
                ->get(['id', 'lead_id', 'followup_at', 'due_followup_at', 'next_followup_at'])
                ->groupBy(fn (LeadFollowup $followup) => (string) $followup->lead_id);

            foreach ($historyByLead as $leadId => $history) {
                $lead = $leads->get((string) $leadId);
                if (! $lead) {
                    continue;
                }
                foreach ($this->leadFollowupService->buildFollowupDelayMeta($lead, $history) as $followupId => $meta) {
                    $dueByFollowupId[(int) $followupId] = $meta['due_at'] ?? null;
                }
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

            $delayMinutes = (int) round($due->diffInMinutes($followup->followup_at));
            $bucket = $this->lateBucketForMinutes($delayMinutes);
            if ($bucket['key'] !== $bucketKey) {
                continue;
            }

            $rows[] = [
                'kind' => translate('Lead_followup') ?? 'Lead follow-up',
                'reference' => $followup->lead?->name ?: $followup->lead?->phone_number ?: $followup->lead_id,
                'due_at' => $due->format('d M Y h:i a'),
                'followup_at' => $followup->followup_at->format('d M Y h:i a'),
                'delay' => $this->formatDelay($delayMinutes),
                'url' => $followup->lead_id ? route('admin.lead.show', $followup->lead_id) : null,
            ];
        }

        $bookingFollowups = BookingFollowup::query()
            ->whereNotNull('followup_at')
            ->whereIn('status', ['completed', 'rescheduled'])
            ->whereBetween('followup_at', [$rangeStart, $rangeEnd])
            ->whereHas('booking', function ($query) use ($employeeId) {
                $query->where('assignee_id', $employeeId)->whereNotNull('assignee_id');
            })
            ->with(['booking:id,readable_id,assignee_id'])
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

            $delayMinutes = (int) round($due->diffInMinutes($followup->followup_at));
            $bucket = $this->lateBucketForMinutes($delayMinutes);
            if ($bucket['key'] !== $bucketKey) {
                continue;
            }

            $rows[] = [
                'kind' => translate('Booking_followup') ?? 'Booking follow-up',
                'reference' => $followup->booking?->readable_id ?: $followup->booking_id,
                'due_at' => $due->format('d M Y h:i a'),
                'followup_at' => $followup->followup_at->format('d M Y h:i a'),
                'delay' => $this->formatDelay($delayMinutes),
                'url' => $followup->booking_id ? route('admin.booking.details', $followup->booking_id) : null,
            ];
        }

        usort($rows, fn (array $a, array $b) => strcmp((string) ($b['followup_at'] ?? ''), (string) ($a['followup_at'] ?? '')));

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    private function employeeNames(): array
    {
        return User::query()
            ->whereIn('user_type', ['super-admin', 'admin-employee'])
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->mapWithKeys(function (User $user) {
                $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

                return [(string) $user->id => $name !== '' ? $name : (string) $user->email];
            })
            ->all();
    }

    private function customerName(?object $customer): string
    {
        if (! $customer) {
            return '—';
        }
        $name = trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''));

        return $name !== '' ? $name : '—';
    }

    private function formatDelay(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.'m';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return $mins > 0 ? sprintf('%dh %dm', $hours, $mins) : sprintf('%dh', $hours);
    }

    /**
     * @return array{key: string, max_minutes: int|null, points: int, label: string}
     */
    private function lateBucketForMinutes(int $delayMinutes): array
    {
        foreach (EmployeeProgressScoreService::LATE_PENALTY_BUCKETS as $bucket) {
            $max = $bucket['max_minutes'];
            if ($max === null || $delayMinutes <= $max) {
                return $bucket;
            }
        }

        return EmployeeProgressScoreService::LATE_PENALTY_BUCKETS[array_key_last(EmployeeProgressScoreService::LATE_PENALTY_BUCKETS)];
    }
}
