<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\CustomerLeadTag;
use Modules\LeadManagement\Entities\District;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\LeadChangeLog;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadProviderChecklist;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\Source;
use Modules\LeadManagement\Entities\ProviderCancellationReason;
use Modules\LeadManagement\Entities\ProviderChecklistItem;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Services\CustomerLeadReportAnalyticsService;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\LeadManagement\Services\ProviderLeadReportAnalyticsService;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\WhatsAppConversation;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\ZoneManagement\Entities\Zone;

class AdminBusinessAiLeadInsightService
{
    private const TIMING_ANALYSIS_SCAN_LIMIT = 5000;

    /** @var array<string, Collection<int, WhatsAppMessage>> */
    private array $waMessagesByNormPhone = [];

    public function __construct(
        protected LeadOpenStatusService $leadOpenStatus,
        protected CustomerLeadReportAnalyticsService $customerLeadReports,
        protected ProviderLeadReportAnalyticsService $providerLeadReports,
    ) {}

    /**
     * @param  Collection<int, Lead>  $leads
     * @return list<array<string, mixed>>
     */
    public function enrichSummaries(Collection $leads): array
    {
        if ($leads->isEmpty()) {
            return [];
        }

        $profiles = $this->buildProfilesForLeads($leads);

        return $leads->map(function (Lead $lead) use ($profiles) {
            $base = [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone_number,
                'lead_type' => $lead->lead_type,
                'source' => $lead->source?->name,
                'ad_source' => $lead->adSource?->name,
                'handled_by' => $lead->handled_by,
                'handled_by_name' => $this->resolveHandlerName($lead->handled_by),
                'received_at' => $lead->date_time_of_lead_received?->toIso8601String(),
                'next_followup_at' => $lead->next_followup_at?->toIso8601String(),
                'remarks' => $lead->remarks,
                'tags' => $lead->relationLoaded('customerLeadTags')
                    ? $lead->customerLeadTags->pluck('name')->all()
                    : [],
            ];

            return array_merge($base, $profiles[(int) $lead->id] ?? []);
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function enrichDetail(Lead $lead): array
    {
        $lead->load([
            'source', 'adSource', 'createdBy', 'customerLeadTags',
            'followups', 'changeLogs.changedByUser', 'providerChecklist',
        ]);

        $statusMeta = $this->leadOpenStatus->buildLeadStatusMeta(collect([$lead]));
        $meta = $statusMeta[(int) $lead->id] ?? ['is_open' => false, 'label' => '—'];
        $profile = $this->buildProfilesForLeads(collect([$lead]))[(int) $lead->id] ?? [];

        $histories = LeadTypeHistory::query()
            ->where('lead_id', $lead->id)
            ->orderByDesc('created_at')
            ->get();

        $adminIds = $histories->pluck('created_by')
            ->merge($lead->changeLogs->pluck('changed_by'))
            ->merge($lead->followups->pluck('created_by'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $adminNames = $this->adminNamesById($adminIds);
        $lookup = $this->buildLookupTables($histories, collect([$lead]));
        $checklistItems = ProviderChecklistItem::query()
            ->whereIn('id', $lead->providerChecklist->pluck('provider_checklist_item_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $resolvedHistory = $histories->map(
            fn (LeadTypeHistory $h) => $this->resolveTypeHistoryEntry($h, $lookup, $adminNames)
        )->values()->all();

        $detail = [
            'id' => $lead->id,
            'name' => $lead->name,
            'phone' => $lead->phone_number,
            'lead_type' => $lead->lead_type,
            'source' => $lead->source?->name,
            'source_id' => $lead->source_id,
            'ad_source' => $lead->adSource?->name,
            'ad_source_id' => $lead->ad_source_id,
            'handled_by' => $lead->handled_by,
            'handled_by_name' => $this->resolveHandlerName($lead->handled_by),
            'remarks' => $lead->remarks,
            'received_at' => $lead->date_time_of_lead_received?->toIso8601String(),
            'next_followup_at' => $lead->next_followup_at?->toIso8601String(),
            'created_at' => $lead->created_at?->toIso8601String(),
            'updated_at' => $lead->updated_at?->toIso8601String(),
            'created_by' => $lead->createdBy ? trim($lead->createdBy->first_name.' '.$lead->createdBy->last_name) : null,
            'is_open' => (bool) ($meta['is_open'] ?? false),
            'pipeline_status_label' => $meta['label'] ?? null,
            'tags' => $lead->customerLeadTags->map(fn ($t) => [
                'name' => $t->name,
                'color' => $t->color ?? null,
            ])->values()->all(),
            'type_profile' => $profile,
            'all_fields' => $this->flattenLeadAdminFields($lead, $profile),
            'type_history' => $resolvedHistory,
            'followups' => $lead->followups->map(fn (LeadFollowup $f) => [
                'id' => $f->id,
                'followup_at' => $f->followup_at?->toIso8601String(),
                'remarks' => $f->remarks,
                'next_followup_at' => $f->next_followup_at?->toIso8601String(),
                'created_by' => $adminNames[(string) $f->created_by] ?? null,
                'created_at' => $f->created_at?->toIso8601String(),
            ])->values()->all(),
            'change_logs' => $lead->changeLogs->take(30)->map(fn (LeadChangeLog $c) => [
                'at' => $c->created_at?->toIso8601String(),
                'changed_by' => $c->changedByUser
                    ? trim($c->changedByUser->first_name.' '.$c->changedByUser->last_name)
                    : null,
                'changes' => is_array($c->changes) ? $c->changes : [],
            ])->values()->all(),
            'provider_checklist' => $lead->providerChecklist->map(fn (LeadProviderChecklist $c) => [
                'item_id' => $c->provider_checklist_item_id,
                'item_name' => $checklistItems->get($c->provider_checklist_item_id)?->name,
                'is_done' => (bool) $c->is_done,
            ])->values()->all(),
            'linked_bookings' => Booking::query()
                ->where('lead_id', $lead->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['id', 'readable_id', 'booking_status', 'total_booking_amount', 'created_at'])
                ->map(fn (Booking $b) => [
                    'readable_id' => $b->readable_id,
                    'status' => $b->booking_status,
                    'amount' => (float) ($b->total_booking_amount ?? 0),
                    'created_at' => $b->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'activity_summary' => $this->buildActivitySummary($lead, $histories, $lead->followups, $lead->changeLogs),
            'status_timeline' => $this->buildStatusTimeline($histories, $lookup),
            'whatsapp_activity' => $this->buildWhatsAppActivity($lead->phone_number),
        ];

        return $detail;
    }

    /**
     * Resolve lead IDs whose current status name matches (e.g. "No Response", "Pending").
     *
     * @return list<int>
     */
    public function leadIdsMatchingStatus(string $leadType, string $statusSearch, int $maxScan = 2000): array
    {
        $needle = strtolower(trim($statusSearch));
        if ($needle === '') {
            return [];
        }

        $leads = Lead::query()
            ->where('lead_type', $leadType)
            ->orderByDesc('date_time_of_lead_received')
            ->limit($maxScan)
            ->get(['id', 'lead_type']);

        if ($leads->isEmpty()) {
            return [];
        }

        $profiles = $this->buildProfilesForLeads($leads);
        $key = $leadType === Lead::TYPE_PROVIDER ? 'provider' : 'customer';

        return $leads
            ->filter(function (Lead $lead) use ($profiles, $key, $needle) {
                $block = $profiles[(int) $lead->id][$key] ?? null;
                if (! is_array($block)) {
                    return false;
                }
                $status = strtolower((string) ($block['status'] ?? ''));

                return $status !== '' && str_contains($status, $needle);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  Builder<Lead>  $q
     * @param  array<string, mixed>  $args
     */
    public function applyDimensionFilters(Builder $q, array $args): void
    {
        if (! empty($args['source'])) {
            $name = trim((string) $args['source']);
            $sourceId = Source::query()->where('name', 'like', '%'.$name.'%')->value('id');
            if ($sourceId) {
                $q->where('source_id', $sourceId);
            } else {
                $q->whereRaw('1 = 0');
            }
        }
        if (! empty($args['tag'])) {
            $tag = trim((string) $args['tag']);
            $q->whereHas('customerLeadTags', fn ($tq) => $tq->where('name', 'like', '%'.$tag.'%'));
        }
        if (! empty($args['zone']) || ! empty($args['category'])) {
            $leadIds = $this->leadIdsMatchingDimensions(
                (string) ($args['zone'] ?? ''),
                (string) ($args['category'] ?? ''),
                (string) ($args['lead_type'] ?? '')
            );
            $q->whereIn('id', $leadIds !== [] ? $leadIds : [-1]);
        }
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function inboundLeadReport(array $args): array
    {
        $reportType = strtolower(trim((string) ($args['report_type'] ?? 'customer')));
        $from = ! empty($args['date_from']) ? Carbon::parse((string) $args['date_from'])->startOfDay() : null;
        $to = ! empty($args['date_to']) ? Carbon::parse((string) $args['date_to'])->endOfDay() : null;

        $base = Lead::query();
        if ($from) {
            $base->where('date_time_of_lead_received', '>=', $from);
        }
        if ($to) {
            $base->where('date_time_of_lead_received', '<=', $to);
        }

        $data = match ($reportType) {
            'customer' => $this->customerLeadReports->build(clone $base, $from, $to),
            'provider' => $this->providerLeadReports->build(clone $base, $from, $to),
            default => null,
        };

        if ($data === null) {
            return [
                'ok' => false,
                'error' => 'unknown_report_type',
                'allowed' => ['customer', 'provider'],
            ];
        }

        return [
            'ok' => true,
            'report_type' => $reportType,
            'date_from' => $from?->toDateString(),
            'date_to' => $to?->toDateString(),
            'data' => $data,
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function employeeLeadProductivity(array $args): array
    {
        $userId = ! empty($args['employee_id']) ? (string) $args['employee_id'] : null;
        if (! $userId) {
            return ['ok' => false, 'error' => 'employee_id_required'];
        }

        $from = ! empty($args['date_from']) ? Carbon::parse((string) $args['date_from'])->startOfDay() : null;
        $to = ! empty($args['date_to']) ? Carbon::parse((string) $args['date_to'])->endOfDay() : null;

        $user = User::query()->find($userId);
        $base = Lead::query()
            ->where('handled_by', $userId)
            ->when($from, fn ($q) => $q->where('date_time_of_lead_received', '>=', $from))
            ->when($to, fn ($q) => $q->where('date_time_of_lead_received', '<=', $to));

        $byType = (clone $base)->selectRaw('lead_type, count(*) as cnt')->groupBy('lead_type')->pluck('cnt', 'lead_type');
        $leadIds = (clone $base)->pluck('id')->all();
        $bookingsFromLeads = $leadIds !== [] ? Booking::query()->whereIn('lead_id', $leadIds)->count() : 0;
        $overdueFollowups = (clone $base)->whereNotNull('next_followup_at')->where('next_followup_at', '<', now())->count();
        $upcomingFollowups = (clone $base)->whereNotNull('next_followup_at')->where('next_followup_at', '>=', now())->count();
        $openMeta = $this->leadOpenStatus->buildLeadStatusMeta((clone $base)->get(['id', 'lead_type']));
        $openCount = collect($openMeta)->filter(fn ($m) => (bool) ($m['is_open'] ?? false))->count();

        return [
            'ok' => true,
            'employee_id' => $userId,
            'employee_name' => $user ? trim($user->first_name.' '.$user->last_name) : null,
            'date_from' => $from?->toDateString(),
            'date_to' => $to?->toDateString(),
            'leads_handled' => (clone $base)->count(),
            'open_leads' => $openCount,
            'by_lead_type' => $byType,
            'bookings_from_leads' => $bookingsFromLeads,
            'overdue_followups' => $overdueFollowups,
            'upcoming_followups' => $upcomingFollowups,
        ];
    }

    public function applyStatusFilters(Builder $q, array $args): void
    {
        if (! empty($args['customer_status'])) {
            $ids = $this->leadIdsMatchingStatus(Lead::TYPE_CUSTOMER, (string) $args['customer_status']);
            $q->whereIn('id', $ids !== [] ? $ids : [-1]);
        }
        if (! empty($args['provider_status'])) {
            $ids = $this->leadIdsMatchingStatus(Lead::TYPE_PROVIDER, (string) $args['provider_status']);
            $q->whereIn('id', $ids !== [] ? $ids : [-1]);
        }
        if (! empty($args['status_search'])) {
            $needle = strtolower(trim((string) $args['status_search']));
            $leads = Lead::query()
                ->whereIn('lead_type', [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER])
                ->orderByDesc('date_time_of_lead_received')
                ->limit(2000)
                ->get(['id', 'lead_type']);
            $profiles = $this->buildProfilesForLeads($leads);
            $ids = $leads->filter(function (Lead $lead) use ($profiles, $needle) {
                $key = $lead->lead_type === Lead::TYPE_PROVIDER ? 'provider' : 'customer';
                $block = $profiles[(int) $lead->id][$key] ?? null;
                if (! is_array($block)) {
                    return false;
                }

                return str_contains(strtolower((string) ($block['status'] ?? '')), $needle);
            })->pluck('id')->all();
            $q->whereIn('id', $ids !== [] ? $ids : [-1]);
        }
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function analyze(array $args): array
    {
        $analysis = strtolower(trim((string) ($args['analysis'] ?? 'full_lead_overview')));

        if ($analysis === 'invalid_to_active_lead_progression') {
            return array_merge(
                ['ok' => true, 'analysis' => $analysis],
                $this->aggregateInvalidToActiveLeadProgression($args)
            );
        }

        if ($analysis === 'phones_with_multiple_leads') {
            return array_merge(
                ['ok' => true, 'analysis' => $analysis],
                $this->aggregatePhonesWithMultipleLeads($args)
            );
        }

        $leadType = (string) ($args['lead_type'] ?? 'customer');
        if ($leadType === 'all') {
            $leadType = '';
        }

        [$leads, $scanNote] = $this->resolveLeadsForAnalysis($args, $analysis, $leadType);
        $profiles = $this->buildProfilesForLeads($leads);

        $payload = [
            'ok' => true,
            'analysis' => $analysis,
            'leads_in_scope' => $leads->count(),
            'lead_type_filter' => $leadType !== '' ? $leadType : 'all',
            'cohort' => ! empty($args['cohort']) ? (string) $args['cohort'] : null,
            'scan_note' => $scanNote,
        ];

        return match ($analysis) {
            'customer_cancellation_reasons' => array_merge($payload, $this->aggregateCustomerCancellations($leads, $profiles)),
            'provider_cancellation_reasons' => array_merge($payload, $this->aggregateProviderCancellations($leads, $profiles)),
            'invalid_reasons' => array_merge($payload, $this->aggregateSimpleReasons($leads, $profiles, 'invalid_reason', Lead::TYPE_INVALID)),
            'future_customer_reasons' => array_merge($payload, $this->aggregateSimpleReasons($leads, $profiles, 'future_customer_reason', Lead::TYPE_FUTURE_CUSTOMER)),
            'customer_status_breakdown' => array_merge($payload, $this->aggregateStatusBreakdown($leads, $profiles, Lead::TYPE_CUSTOMER)),
            'provider_status_breakdown' => array_merge($payload, $this->aggregateStatusBreakdown($leads, $profiles, Lead::TYPE_PROVIDER)),
            'no_response_leads' => array_merge($payload, $this->aggregateNoResponseLeads($leads, $profiles)),
            'no_response_timing_report' => array_merge($payload, $this->aggregateLeadTimingReport(
                $leads,
                $profiles,
                fn (Lead $lead, array $profile) => $this->detectNonResponsiveMatch($lead, $profile) !== null
            )),
            'lead_timing_report' => array_merge($payload, $this->aggregateLeadTimingReport(
                $leads,
                $profiles,
                fn (Lead $lead, array $profile) => $this->leadMatchesTimingCohort($lead, $profile, (string) ($args['cohort'] ?? 'all'))
            )),
            'lead_activity_report' => array_merge($payload, $this->aggregateLeadActivityReport($leads, $profiles)),
            'full_lead_overview' => array_merge($payload, [
                'customer_cancellation_reasons' => $this->aggregateCustomerCancellations(
                    $leads->where('lead_type', Lead::TYPE_CUSTOMER),
                    $profiles
                ),
                'invalid_reasons' => $this->aggregateSimpleReasons($leads, $profiles, 'invalid_reason', Lead::TYPE_INVALID),
                'future_customer_reasons' => $this->aggregateSimpleReasons($leads, $profiles, 'future_customer_reason', Lead::TYPE_FUTURE_CUSTOMER),
                'customer_status_breakdown' => $this->aggregateStatusBreakdown($leads, $profiles, Lead::TYPE_CUSTOMER),
                'by_lead_type' => $leads->groupBy('lead_type')->map->count(),
            ]),
            default => [
                'ok' => false,
                'error' => 'unknown_analysis',
                'allowed' => [
                    'customer_cancellation_reasons',
                    'provider_cancellation_reasons',
                    'invalid_reasons',
                    'future_customer_reasons',
                    'customer_status_breakdown',
                    'provider_status_breakdown',
                    'no_response_leads',
                    'no_response_timing_report',
                    'lead_timing_report',
                    'lead_activity_report',
                    'full_lead_overview',
                    'invalid_to_active_lead_progression',
                    'phones_with_multiple_leads',
                ],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{
     *     leads: Collection<int, Lead>,
     *     by_phone: array<string, list<Lead>>,
     *     total_matching: int,
     *     scan_note: string|null,
     *     wa_phone_set: array<string, true>
     * }
     */
    private function scanLeadsGroupedByPhone(array $args): array
    {
        $scanLimit = self::TIMING_ANALYSIS_SCAN_LIMIT;

        $q = Lead::query()
            ->select(['id', 'name', 'phone_number', 'lead_type', 'date_time_of_lead_received', 'created_at', 'handled_by'])
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '');

        if (! empty($args['date_from'])) {
            $q->where('date_time_of_lead_received', '>=', Carbon::parse((string) $args['date_from'])->startOfDay());
        }
        if (! empty($args['date_to'])) {
            $q->where('date_time_of_lead_received', '<=', Carbon::parse((string) $args['date_to'])->endOfDay());
        }

        $totalMatching = (clone $q)->count();
        $leads = $q->orderBy('date_time_of_lead_received')->orderBy('id')->limit($scanLimit)->get();

        /** @var array<string, list<Lead>> $byPhone */
        $byPhone = [];
        foreach ($leads as $lead) {
            $norm = $this->normalizePhone($lead->phone_number);
            if ($norm === null) {
                continue;
            }
            $byPhone[$norm][] = $lead;
        }

        foreach ($byPhone as $norm => $phoneLeads) {
            usort($phoneLeads, fn (Lead $a, Lead $b): int => $this->compareLeadsChronologically($a, $b));
            $byPhone[$norm] = $phoneLeads;
        }

        $scanNote = $totalMatching > $leads->count()
            ? "Analyzed {$leads->count()} of {$totalMatching} matching leads (scan cap {$scanLimit}). Counts may be understated if older leads for the same phone were outside the scanned set."
            : null;

        return [
            'leads' => $leads,
            'by_phone' => $byPhone,
            'total_matching' => $totalMatching,
            'scan_note' => $scanNote,
            'wa_phone_set' => $this->whatsappNormalizedPhoneSet(),
        ];
    }

    private function compareLeadsChronologically(Lead $a, Lead $b): int
    {
        $at = $a->date_time_of_lead_received ?? $a->created_at;
        $bt = $b->date_time_of_lead_received ?? $b->created_at;
        if ($at === null && $bt === null) {
            return (int) $a->id <=> (int) $b->id;
        }
        if ($at === null) {
            return -1;
        }
        if ($bt === null) {
            return 1;
        }
        $cmp = $at <=> $bt;

        return $cmp !== 0 ? $cmp : ((int) $a->id <=> (int) $b->id);
    }

    /**
     * Phones where an invalid CRM lead was followed by a later customer/provider/future_customer lead.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function aggregateInvalidToActiveLeadProgression(array $args): array
    {
        $activeTypes = [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER, Lead::TYPE_FUTURE_CUSTOMER];
        $scan = $this->scanLeadsGroupedByPhone($args);
        $byPhone = $scan['by_phone'];
        $waPhoneSet = $scan['wa_phone_set'];

        $progressions = [];
        $byNextType = array_fill_keys($activeTypes, 0);
        $phonesWithMultipleLeads = 0;
        $phonesWithInvalidThenActive = [];

        foreach ($byPhone as $norm => $phoneLeads) {
            if (count($phoneLeads) < 2) {
                continue;
            }
            $phonesWithMultipleLeads++;

            for ($i = 0; $i < count($phoneLeads) - 1; $i++) {
                $invalidLead = $phoneLeads[$i];
                $nextLead = $phoneLeads[$i + 1];
                if ($invalidLead->lead_type !== Lead::TYPE_INVALID) {
                    continue;
                }
                if (! in_array($nextLead->lead_type, $activeTypes, true)) {
                    continue;
                }

                $byNextType[$nextLead->lead_type]++;
                $phonesWithInvalidThenActive[$norm] = true;
                $progressions[] = [
                    'phone' => $norm,
                    'has_whatsapp_chat' => isset($waPhoneSet[$norm]),
                    'invalid_lead_id' => (int) $invalidLead->id,
                    'invalid_lead_name' => $invalidLead->name,
                    'invalid_received_at' => ($invalidLead->date_time_of_lead_received ?? $invalidLead->created_at)?->toIso8601String(),
                    'next_lead_id' => (int) $nextLead->id,
                    'next_lead_name' => $nextLead->name,
                    'next_lead_type' => $nextLead->lead_type,
                    'next_received_at' => ($nextLead->date_time_of_lead_received ?? $nextLead->created_at)?->toIso8601String(),
                    'next_handled_by' => $this->resolveHandlerName($nextLead->handled_by),
                ];
            }
        }

        $withWhatsApp = count(array_filter($progressions, fn (array $p): bool => ! empty($p['has_whatsapp_chat'])));

        return [
            'total_leads_scanned' => $scan['leads']->count(),
            'total_leads_in_database' => Lead::query()->count(),
            'phones_with_multiple_leads' => $phonesWithMultipleLeads,
            'invalid_then_active_progressions' => count($progressions),
            'unique_phones_invalid_then_active' => count($phonesWithInvalidThenActive),
            'progressions_with_whatsapp_chat' => $withWhatsApp,
            'by_next_lead_type' => $byNextType,
            'scan_note' => $scan['scan_note'],
            'note' => 'Counts CRM leads on the same normalized phone (last 10 digits) where an invalid lead is immediately followed chronologically by a customer, provider, or future_customer lead.',
            'sample_progressions' => array_slice($progressions, 0, 20),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function aggregatePhonesWithMultipleLeads(array $args): array
    {
        $scan = $this->scanLeadsGroupedByPhone($args);
        $waPhoneSet = $scan['wa_phone_set'];
        $multiPhoneRows = [];
        $byLeadCount = ['2' => 0, '3' => 0, '4+' => 0];
        $whatsappUsersWithMultipleLeads = 0;

        foreach ($scan['by_phone'] as $norm => $phoneLeads) {
            $count = count($phoneLeads);
            if ($count < 2) {
                continue;
            }

            $bucket = $count >= 4 ? '4+' : (string) $count;
            $byLeadCount[$bucket]++;
            $hasWhatsApp = isset($waPhoneSet[$norm]);
            if ($hasWhatsApp) {
                $whatsappUsersWithMultipleLeads++;
            }

            $multiPhoneRows[] = [
                'phone' => $norm,
                'lead_count' => $count,
                'has_whatsapp_chat' => $hasWhatsApp,
                'lead_ids' => array_map(static fn (Lead $l): int => (int) $l->id, $phoneLeads),
                'lead_types' => array_map(static fn (Lead $l): string => (string) $l->lead_type, $phoneLeads),
            ];
        }

        usort($multiPhoneRows, static fn (array $a, array $b): int => ($b['lead_count'] <=> $a['lead_count']) ?: strcmp((string) $a['phone'], (string) $b['phone']));

        return [
            'total_leads_scanned' => $scan['leads']->count(),
            'total_leads_in_database' => Lead::query()->count(),
            'phones_with_multiple_crm_leads' => count($multiPhoneRows),
            'whatsapp_users_with_multiple_crm_leads' => $whatsappUsersWithMultipleLeads,
            'by_lead_count' => $byLeadCount,
            'scan_note' => $scan['scan_note'],
            'note' => 'Groups all CRM leads by normalized phone (last 10 digits). whatsapp_users_with_multiple_crm_leads = phones with a WhatsApp thread AND 2+ CRM leads. Use this instead of query_whatsapp_conversations for multi-lead counts.',
            'sample_phones' => array_slice($multiPhoneRows, 0, 25),
        ];
    }

    /**
     * @return array<string, true>
     */
    private function whatsappNormalizedPhoneSet(): array
    {
        $set = [];
        foreach (WhatsAppConversation::query()->pluck('phone') as $phone) {
            $norm = $this->normalizePhone((string) $phone);
            if ($norm !== null) {
                $set[$norm] = true;
            }
        }

        return $set;
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: Collection<int, Lead>, 1: string|null}
     */
    private function resolveLeadsForAnalysis(array $args, string $analysis, string $leadType): array
    {
        $timingAnalyses = [
            'no_response_leads',
            'no_response_timing_report',
            'lead_timing_report',
            'lead_activity_report',
        ];
        $limit = in_array($analysis, $timingAnalyses, true)
            ? self::TIMING_ANALYSIS_SCAN_LIMIT
            : 2000;

        $q = Lead::query();
        $multiTypeAnalyses = ['no_response_leads', 'no_response_timing_report', 'lead_timing_report'];

        if (in_array($analysis, $multiTypeAnalyses, true)) {
            if ($leadType !== '' && $leadType !== 'all') {
                $q->where('lead_type', $leadType);
            } else {
                $q->whereIn('lead_type', [
                    Lead::TYPE_CUSTOMER,
                    Lead::TYPE_INVALID,
                    Lead::TYPE_PROVIDER,
                    Lead::TYPE_FUTURE_CUSTOMER,
                    Lead::TYPE_UNKNOWN,
                ]);
            }
        } elseif ($leadType !== '') {
            $q->where('lead_type', $leadType);
        }

        if (! empty($args['date_from'])) {
            $q->where('date_time_of_lead_received', '>=', Carbon::parse((string) $args['date_from'])->startOfDay());
        }
        if (! empty($args['date_to'])) {
            $q->where('date_time_of_lead_received', '<=', Carbon::parse((string) $args['date_to'])->endOfDay());
        }

        $totalMatching = (clone $q)->count();
        $leads = $q->orderByDesc('date_time_of_lead_received')->limit($limit)->get();
        $note = $totalMatching > $leads->count()
            ? "Analyzed newest {$leads->count()} of {$totalMatching} matching leads (scan cap {$limit}). Totals and timing stats are for the analyzed set."
            : null;

        return [$leads, $note];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @return array<string, array<string, mixed>>
     */
    private function buildProfilesForLeads(Collection $leads): array
    {
        if ($leads->isEmpty()) {
            return [];
        }

        $leadIds = $leads->pluck('id')->all();
        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id');

        $statusMeta = $this->leadOpenStatus->buildLeadStatusMeta($leads);

        $lookup = $this->buildLookupTables($histories->flatten(1), $leads);

        $profiles = [];
        foreach ($leads as $lead) {
            $leadHistories = $histories->get($lead->id, collect());
            $latestForType = $leadHistories->first(fn (LeadTypeHistory $h) => $h->type === $lead->lead_type)
                ?? $leadHistories->first();

            $profiles[(int) $lead->id] = $this->resolveProfileForLead(
                $lead,
                $latestForType,
                $leadHistories,
                $lookup,
                $statusMeta[(int) $lead->id] ?? []
            );
        }

        return $profiles;
    }

    /**
     * @param  Collection<int, LeadTypeHistory>  $histories
     * @param  Collection<int, Lead>  $leads
     * @return array<string, mixed>
     */
    private function buildLookupTables(Collection $histories, Collection $leads): array
    {
        $customerStatusIds = [];
        $providerStatusIds = [];
        $zoneIds = [];
        $categoryIds = [];
        $serviceIds = [];
        $customerCancelIds = [];
        $providerCancelIds = [];
        $invalidReasonIds = [];
        $futureReasonIds = [];
        $bookingIds = [];
        $districtIds = [];

        foreach ($histories as $h) {
            $d = is_array($h->data) ? $h->data : [];
            if (! empty($d['customer_lead_status_id'])) {
                $customerStatusIds[] = (int) $d['customer_lead_status_id'];
            }
            if (! empty($d['provider_lead_status_id'])) {
                $providerStatusIds[] = (int) $d['provider_lead_status_id'];
            }
            foreach (['zone_id', 'zone_ids'] as $zk) {
                if (! empty($d[$zk])) {
                    $v = $d[$zk];
                    if (is_array($v)) {
                        foreach ($v as $zid) {
                            $zoneIds[] = $zid;
                        }
                    } else {
                        $zoneIds[] = $v;
                    }
                }
            }
            foreach (['service_category', 'service_subcategory', 'provider_service_category', 'provider_service_subcategory'] as $ck) {
                if (! empty($d[$ck])) {
                    $categoryIds[] = $d[$ck];
                }
            }
            if (! empty($d['service_name'])) {
                $serviceIds[] = $d['service_name'];
            }
            if (! empty($d['cancellation_reason_id'])) {
                $customerCancelIds[] = (int) $d['cancellation_reason_id'];
            }
            if (! empty($d['provider_cancellation_reason_id'])) {
                $providerCancelIds[] = (int) $d['provider_cancellation_reason_id'];
            }
            if (! empty($d['invalid_reason_id'])) {
                $invalidReasonIds[] = (int) $d['invalid_reason_id'];
            }
            if (! empty($d['future_customer_reason_id'])) {
                $futureReasonIds[] = (int) $d['future_customer_reason_id'];
            }
            if (! empty($d['booking_id'])) {
                $bookingIds[] = $d['booking_id'];
            }
            if (! empty($d['district_id'])) {
                $districtIds[] = (int) $d['district_id'];
            }
        }

        return [
            'customer_statuses' => CustomerLeadStatus::query()->whereIn('id', array_unique($customerStatusIds))->get()->keyBy('id'),
            'provider_statuses' => ProviderLeadStatus::query()->whereIn('id', array_unique($providerStatusIds))->get()->keyBy('id'),
            'zones' => $zoneIds !== [] ? Zone::withoutGlobalScopes()->whereIn('id', array_unique($zoneIds))->get()->keyBy('id') : collect(),
            'categories' => $categoryIds !== [] ? Category::withoutGlobalScopes()->whereIn('id', array_unique($categoryIds))->get()->keyBy('id') : collect(),
            'services' => $serviceIds !== [] ? Service::withoutGlobalScopes()->whereIn('id', array_unique($serviceIds))->get()->keyBy('id') : collect(),
            'customer_cancel_reasons' => $customerCancelIds !== [] ? LeadCancellationReason::query()->whereIn('id', array_unique($customerCancelIds))->get()->keyBy('id') : collect(),
            'provider_cancel_reasons' => $providerCancelIds !== [] ? ProviderCancellationReason::query()->whereIn('id', array_unique($providerCancelIds))->get()->keyBy('id') : collect(),
            'invalid_reasons' => $invalidReasonIds !== [] ? LeadInvalidReason::query()->whereIn('id', array_unique($invalidReasonIds))->get()->keyBy('id') : collect(),
            'future_reasons' => $futureReasonIds !== [] ? LeadFutureCustomerReason::query()->whereIn('id', array_unique($futureReasonIds))->get()->keyBy('id') : collect(),
            'districts' => $districtIds !== [] ? District::query()->whereIn('id', array_unique($districtIds))->get()->keyBy('id') : collect(),
            'bookings' => $bookingIds !== [] ? Booking::query()->whereIn('id', array_unique($bookingIds))->get(['id', 'readable_id', 'booking_status'])->keyBy('id') : collect(),
            'latest_booking_by_lead' => Booking::query()
                ->whereIn('lead_id', $leads->pluck('id')->all())
                ->orderByDesc('created_at')
                ->get(['id', 'lead_id', 'readable_id', 'booking_status'])
                ->groupBy('lead_id')
                ->map(fn ($g) => $g->first()),
        ];
    }

    /**
     * @param  Collection<int, LeadTypeHistory>  $allHistories
     * @param  array<string, mixed>  $lookup
     * @param  array<string, mixed>  $statusMeta
     * @return array<string, mixed>
     */
    private function resolveProfileForLead(
        Lead $lead,
        ?LeadTypeHistory $latest,
        Collection $allHistories,
        array $lookup,
        array $statusMeta,
    ): array {
        $d = ($latest && is_array($latest->data)) ? $latest->data : [];

        $profile = [
            'is_open' => (bool) ($statusMeta['is_open'] ?? false),
            'pipeline_status' => $statusMeta['label'] ?? null,
            'history_entries' => $allHistories->count(),
        ];

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $status = $lookup['customer_statuses']->get((int) ($d['customer_lead_status_id'] ?? 0));
            $cancelId = (int) ($d['cancellation_reason_id'] ?? 0);
            $cancel = $cancelId ? $lookup['customer_cancel_reasons']->get($cancelId) : null;
            $booking = ! empty($d['booking_id'])
                ? $lookup['bookings']->get($d['booking_id'])
                : $lookup['latest_booking_by_lead']->get($lead->id);

            $profile['customer'] = [
                'status' => $status?->name,
                'status_base_type' => $status?->base_type,
                'zone' => $this->resolveName($lookup['zones'], $d['zone_id'] ?? null),
                'service_category' => $this->resolveName($lookup['categories'], $d['service_category'] ?? null),
                'service_subcategory' => $this->resolveName($lookup['categories'], $d['service_subcategory'] ?? null),
                'service' => $this->resolveName($lookup['services'], $d['service_name'] ?? null),
                'service_description' => $d['service_description'] ?? null,
                'variant_key' => $d['variant_key'] ?? null,
                'estimated_service_at' => $d['estimated_service_at'] ?? null,
                'booking_status' => $d['booking_status'] ?? null,
                'booking_id' => $booking?->readable_id ?? $booking?->id,
                'system_booking_status' => $booking?->booking_status,
                'cancellation_reason' => $cancel?->name,
                'cancellation_remarks' => $d['cancellation_remarks'] ?? null,
                'is_cancelled' => strtolower((string) ($status?->base_type ?? '')) === 'cancel' || $cancelId > 0,
            ];
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $status = $lookup['provider_statuses']->get((int) ($d['provider_lead_status_id'] ?? 0));
            $cancelId = (int) ($d['provider_cancellation_reason_id'] ?? 0);
            $cancel = $cancelId ? $lookup['provider_cancel_reasons']->get($cancelId) : null;
            $zoneNames = [];
            foreach ((array) ($d['zone_ids'] ?? [$d['zone_id'] ?? null]) as $zid) {
                if ($zid && ($zn = $lookup['zones']->get($zid)?->name)) {
                    $zoneNames[] = $zn;
                }
            }

            $district = $lookup['districts']->get((int) ($d['district_id'] ?? 0));

            $profile['provider'] = [
                'status' => $status?->name,
                'status_base_type' => $status?->base_type,
                'district' => $district?->name,
                'district_id' => $d['district_id'] ?? null,
                'zones' => array_values(array_unique($zoneNames)),
                'full_address' => $d['full_address'] ?? null,
                'service_areas' => $d['service_areas'] ?? null,
                'service_category' => $this->resolveName($lookup['categories'], $d['provider_service_category'] ?? null),
                'service_subcategory' => $this->resolveName($lookup['categories'], $d['provider_service_subcategory'] ?? null),
                'service_details' => $d['provider_service_details'] ?? null,
                'cancellation_reason' => $cancel?->name,
                'cancellation_remarks' => $d['provider_cancellation_remarks'] ?? null,
                'is_cancelled' => strtolower((string) ($status?->base_type ?? '')) === 'cancel' || $cancelId > 0,
            ];
        }

        if ($lead->lead_type === Lead::TYPE_INVALID) {
            $reasonId = (int) ($d['invalid_reason_id'] ?? 0);
            $profile['invalid'] = [
                'reason' => $reasonId ? ($lookup['invalid_reasons']->get($reasonId)?->name) : null,
                'remarks' => $d['invalid_remarks'] ?? null,
            ];
        }

        if ($lead->lead_type === Lead::TYPE_FUTURE_CUSTOMER) {
            $reasonId = (int) ($d['future_customer_reason_id'] ?? 0);
            $profile['future_customer'] = [
                'reason' => $reasonId ? ($lookup['future_reasons']->get($reasonId)?->name) : null,
                'remarks' => $d['future_customer_remarks'] ?? null,
            ];
        }

        return $profile;
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  array<string, array<string, mixed>>  $profiles
     * @return array<string, mixed>
     */
    private function aggregateCustomerCancellations(Collection $leads, array $profiles): array
    {
        $customerLeads = $leads->where('lead_type', Lead::TYPE_CUSTOMER);
        $byReason = [];
        $samples = [];
        $noReason = 0;
        $cancelledTotal = 0;

        $followupsByLead = $customerLeads->isEmpty()
            ? collect()
            : LeadFollowup::query()
                ->whereIn('lead_id', $customerLeads->pluck('id')->all())
                ->orderBy('followup_at')
                ->get()
                ->groupBy('lead_id');

        foreach ($customerLeads as $lead) {
            $p = $profiles[(int) $lead->id]['customer'] ?? null;
            if (! is_array($p) || ! ($p['is_cancelled'] ?? false)) {
                continue;
            }
            $cancelledTotal++;
            $reason = trim((string) ($p['cancellation_reason'] ?? ''));
            if ($reason === '') {
                $noReason++;
                $reason = '(No reason recorded)';
            }
            $byReason[$reason] = ($byReason[$reason] ?? 0) + 1;
            if (count($samples) < 40) {
                $leadFollowups = $followupsByLead->get($lead->id, collect());
                $samples[] = [
                    'lead_id' => $lead->id,
                    'name' => $lead->name,
                    'phone' => $lead->phone_number,
                    'enquiry_at' => $lead->date_time_of_lead_received?->toIso8601String(),
                    'initial_remarks' => $lead->remarks,
                    'cancellation_reason' => $p['cancellation_reason'] ?: '(No reason recorded)',
                    'cancellation_remarks' => $p['cancellation_remarks'],
                    'status' => $p['status'] ?? null,
                    'handled_by' => $lead->handled_by,
                    'followups_taken' => $leadFollowups->count(),
                    'first_followup_at' => $leadFollowups->first()?->followup_at?->toIso8601String(),
                    'followups' => $leadFollowups->take(5)->map(fn (LeadFollowup $f) => [
                        'at' => $f->followup_at?->toIso8601String(),
                        'remarks' => $f->remarks,
                        'status' => $f->status ?? null,
                    ])->values()->all(),
                ];
            }
        }

        arsort($byReason);

        $reasonRows = collect($byReason)->map(fn ($count, $reason) => [
            'reason' => $reason,
            'count' => $count,
        ])->values()->all();

        return [
            'cancelled_customer_leads' => $cancelledTotal,
            'without_recorded_reason' => $noReason,
            'by_reason' => $reasonRows,
            'samples' => $samples,
            'charts' => $reasonRows === [] ? [] : [[
                'id' => 'customer_cancellation_reasons',
                'type' => 'bar',
                'title' => 'Customer lead cancellation reasons',
                'labels' => array_map(fn ($r) => (string) $r['reason'], array_slice($reasonRows, 0, 12)),
                'series' => [[
                    'name' => 'Leads',
                    'data' => array_map(fn ($r) => (int) $r['count'], array_slice($reasonRows, 0, 12)),
                ]],
            ]],
        ];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  array<string, array<string, mixed>>  $profiles
     * @return array<string, mixed>
     */
    private function aggregateProviderCancellations(Collection $leads, array $profiles): array
    {
        $rows = $leads->where('lead_type', Lead::TYPE_PROVIDER);
        $byReason = [];
        $cancelledTotal = 0;

        foreach ($rows as $lead) {
            $p = $profiles[(int) $lead->id]['provider'] ?? null;
            if (! is_array($p) || ! ($p['is_cancelled'] ?? false)) {
                continue;
            }
            $cancelledTotal++;
            $reason = trim((string) ($p['cancellation_reason'] ?? '')) ?: '(No reason recorded)';
            $byReason[$reason] = ($byReason[$reason] ?? 0) + 1;
        }
        arsort($byReason);

        return [
            'cancelled_provider_leads' => $cancelledTotal,
            'by_reason' => collect($byReason)->map(fn ($count, $reason) => [
                'reason' => $reason,
                'count' => $count,
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  array<string, array<string, mixed>>  $profiles
     * @return array<string, mixed>
     */
    private function aggregateSimpleReasons(Collection $leads, array $profiles, string $key, string $type): array
    {
        $rows = $leads->where('lead_type', $type);
        $byReason = [];

        foreach ($rows as $lead) {
            $block = $profiles[(int) $lead->id][$type === Lead::TYPE_INVALID ? 'invalid' : 'future_customer'] ?? null;
            if (! is_array($block)) {
                continue;
            }
            $reason = trim((string) ($block['reason'] ?? '')) ?: '(No reason recorded)';
            $byReason[$reason] = ($byReason[$reason] ?? 0) + 1;
        }
        arsort($byReason);

        return [
            'total' => $rows->count(),
            'by_reason' => collect($byReason)->map(fn ($count, $reason) => [
                'reason' => $reason,
                'count' => $count,
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  array<string, array<string, mixed>>  $profiles
     * @return array<string, mixed>
     */
    private function aggregateStatusBreakdown(Collection $leads, array $profiles, string $type): array
    {
        $rows = $leads->where('lead_type', $type);
        $key = $type === Lead::TYPE_CUSTOMER ? 'customer' : 'provider';
        $byStatus = [];

        foreach ($rows as $lead) {
            $block = $profiles[(int) $lead->id][$key] ?? null;
            $status = is_array($block) ? ($block['status'] ?? '(No status)') : '(No status)';
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
        }
        arsort($byStatus);

        return [
            'total' => $rows->count(),
            'by_status' => collect($byStatus)->map(fn ($count, $status) => [
                'status' => $status,
                'count' => $count,
            ])->values()->all(),
        ];
    }

    private function resolveName(Collection $map, mixed $id): ?string
    {
        if ($id === null || $id === '') {
            return null;
        }

        return $map->get($id)?->name;
    }

    /**
     * @param  list<mixed>  $ids
     * @return array<string, string>
     */
    private function adminNamesById(array $ids): array
    {
        $humanIds = collect($ids)->filter()->unique()->values()->all();
        if ($humanIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $humanIds)
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $u) => [
                (string) $u->id => trim($u->first_name.' '.$u->last_name) ?: 'Staff',
            ])
            ->all();
    }

    /**
     * @param  Collection<int, LeadTypeHistory>  $histories
     * @param  Collection<int, LeadFollowup>  $followups
     * @param  Collection<int, LeadChangeLog>  $changeLogs
     * @return array<string, mixed>
     */
    private function buildActivitySummary(
        Lead $lead,
        Collection $histories,
        Collection $followups,
        Collection $changeLogs,
    ): array {
        $receivedAt = $lead->date_time_of_lead_received;
        $firstFollowup = $followups->sortBy('followup_at')->first();
        $lastFollowup = $followups->sortByDesc('followup_at')->first();
        $firstChange = $changeLogs->sortBy('created_at')->first();
        $lastChange = $changeLogs->sortByDesc('created_at')->first();
        $firstHistory = $histories->sortBy('created_at')->first();
        $lastHistory = $histories->sortByDesc('created_at')->first();

        $touchpoints = collect([
            $receivedAt,
            $firstFollowup?->followup_at,
            $lastFollowup?->followup_at,
            $firstChange?->created_at,
            $lastChange?->created_at,
            $firstHistory?->created_at,
            $lastHistory?->created_at,
            $lead->updated_at,
        ])->filter();

        $lastUpdated = $touchpoints->max();

        $wa = $this->buildWhatsAppActivity($lead->phone_number);

        return [
            'received_at' => $receivedAt?->toIso8601String(),
            'last_updated_at' => $lastUpdated instanceof Carbon ? $lastUpdated->toIso8601String() : null,
            'first_staff_followup_at' => $firstFollowup?->followup_at?->toIso8601String(),
            'last_staff_followup_at' => $lastFollowup?->followup_at?->toIso8601String(),
            'first_data_update_at' => $firstHistory?->created_at?->toIso8601String(),
            'last_data_update_at' => $lastHistory?->created_at?->toIso8601String(),
            'first_change_log_at' => $firstChange?->created_at?->toIso8601String(),
            'last_change_log_at' => $lastChange?->created_at?->toIso8601String(),
            'first_whatsapp_reply_at' => $wa['first_outbound_reply_at'] ?? null,
            'last_whatsapp_reply_at' => $wa['last_outbound_at'] ?? null,
            'last_customer_whatsapp_at' => $wa['last_inbound_at'] ?? null,
        ];
    }

    /**
     * @param  Collection<int, LeadTypeHistory>  $histories
     * @param  array<string, mixed>  $lookup
     * @return list<array<string, mixed>>
     */
    private function buildStatusTimeline(Collection $histories, array $lookup): array
    {
        return $histories
            ->sortBy('created_at')
            ->map(function (LeadTypeHistory $h) use ($lookup) {
                $d = is_array($h->data) ? $h->data : [];
                $statusName = null;
                if ($h->type === Lead::TYPE_CUSTOMER && ! empty($d['customer_lead_status_id'])) {
                    $statusName = $lookup['customer_statuses']->get((int) $d['customer_lead_status_id'])?->name;
                }
                if ($h->type === Lead::TYPE_PROVIDER && ! empty($d['provider_lead_status_id'])) {
                    $statusName = $lookup['provider_statuses']->get((int) $d['provider_lead_status_id'])?->name;
                }

                return [
                    'at' => $h->created_at?->toIso8601String(),
                    'lead_type' => $h->type,
                    'status' => $statusName,
                    'updated_fields' => array_keys($d),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildWhatsAppActivity(?string $phone): array
    {
        $norm = $this->normalizePhone($phone);
        if ($norm === null) {
            return [];
        }

        $messages = WhatsAppMessage::query()
            ->where(function ($q) use ($norm, $phone) {
                $q->where('phone', 'like', '%'.$norm.'%');
                if ($phone) {
                    $q->orWhere('phone', $phone);
                }
            })
            ->orderBy('created_at')
            ->get(['direction', 'created_at', 'message_text']);

        if ($messages->isEmpty()) {
            return ['has_whatsapp_thread' => false];
        }

        $inbound = $messages->where('direction', 'IN');
        $outbound = $messages->where('direction', 'OUT');

        return [
            'has_whatsapp_thread' => true,
            'first_inbound_at' => $inbound->first()?->created_at?->toIso8601String(),
            'last_inbound_at' => $inbound->last()?->created_at?->toIso8601String(),
            'first_outbound_reply_at' => $outbound->first()?->created_at?->toIso8601String(),
            'last_outbound_at' => $outbound->last()?->created_at?->toIso8601String(),
            'inbound_message_count' => $inbound->count(),
            'outbound_message_count' => $outbound->count(),
        ];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  array<string, array<string, mixed>>  $profiles
     * @return array<string, mixed>
     */
    private function aggregateNoResponseLeads(Collection $leads, array $profiles): array
    {
        $invalidLeads = [];
        $customerCancelled = [];
        $customerStatus = [];
        $providerCancelled = [];

        foreach ($leads as $lead) {
            $profile = $profiles[(int) $lead->id] ?? [];
            $match = $this->detectNonResponsiveMatch($lead, $profile);
            if ($match === null) {
                continue;
            }

            $profileBlock = match ($lead->lead_type) {
                Lead::TYPE_CUSTOMER => $profile['customer'] ?? [],
                Lead::TYPE_PROVIDER => $profile['provider'] ?? [],
                Lead::TYPE_INVALID => $profile['invalid'] ?? [],
                default => [],
            };
            $row = array_merge(
                $this->leadActivityRow($lead, is_array($profileBlock) ? $profileBlock : []),
                $match
            );

            match ($match['category']) {
                'invalid_reason' => $invalidLeads[] = $row,
                'customer_cancellation_reason' => $customerCancelled[] = $row,
                'customer_status' => $customerStatus[] = $row,
                'provider_cancellation_reason' => $providerCancelled[] = $row,
                default => null,
            };
        }

        $all = array_merge($invalidLeads, $customerCancelled, $customerStatus, $providerCancelled);
        $configured = $this->configuredNonResponsiveReasons();
        $matchedLeadIds = collect($all)->pluck('lead_id')->filter()->unique()->values();
        $matchedLeads = $leads->filter(fn (Lead $l) => $matchedLeadIds->contains($l->id))->values();
        $timingSummary = $this->aggregateLeadTimingReport(
            $matchedLeads,
            $profiles,
            fn (Lead $lead, array $profile) => $this->detectNonResponsiveMatch($lead, $profile) !== null
        );

        return [
            'summary' => [
                'total_non_responsive' => count($all),
                'invalid_leads_no_response' => count($invalidLeads),
                'customer_cancelled_no_response' => count($customerCancelled),
                'customer_status_no_response' => count($customerStatus),
                'provider_cancelled_no_response' => count($providerCancelled),
            ],
            'configured_reasons' => $configured,
            'configured_no_response_statuses' => $configured['customer_statuses'],
            'matching_leads' => count($all),
            'timing_summary' => $timingSummary['timing'] ?? $timingSummary,
            'by_category' => [
                'invalid_leads' => array_slice($invalidLeads, 0, 25),
                'customer_cancelled' => array_slice($customerCancelled, 0, 25),
                'customer_status' => array_slice($customerStatus, 0, 25),
                'provider_cancelled' => array_slice($providerCancelled, 0, 25),
            ],
            'leads' => array_slice($all, 0, 40),
            'note' => 'Non-responsive includes: invalid lead reason "No Response", customer cancellation "No Response From Customer", and any CRM status name containing no response/unresponsive. Use no_response_timing_report for full hour/lag analysis.',
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>|null
     */
    public function detectNonResponsiveMatch(Lead $lead, array $profile): ?array
    {
        if ($lead->lead_type === Lead::TYPE_INVALID) {
            $block = $profile['invalid'] ?? null;
            if (is_array($block) && $this->textMatchesNonResponsive((string) ($block['reason'] ?? ''))) {
                return [
                    'category' => 'invalid_reason',
                    'category_label' => 'Invalid lead — No Response reason',
                    'reason' => $block['reason'],
                    'remarks' => $block['remarks'] ?? null,
                ];
            }
        }

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $block = $profile['customer'] ?? null;
            if (! is_array($block)) {
                return null;
            }

            if (($block['is_cancelled'] ?? false) && $this->textMatchesNonResponsive((string) ($block['cancellation_reason'] ?? ''))) {
                return [
                    'category' => 'customer_cancellation_reason',
                    'category_label' => 'Customer lead cancelled — no response',
                    'reason' => $block['cancellation_reason'],
                    'remarks' => $block['cancellation_remarks'] ?? null,
                    'pipeline_status' => $block['status'] ?? null,
                ];
            }

            if ($this->textMatchesNonResponsive((string) ($block['status'] ?? ''))) {
                return [
                    'category' => 'customer_status',
                    'category_label' => 'Customer lead status — no response',
                    'reason' => $block['status'],
                    'pipeline_status' => $block['status'] ?? null,
                ];
            }
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $block = $profile['provider'] ?? null;
            if (is_array($block) && ($block['is_cancelled'] ?? false)
                && $this->textMatchesNonResponsive((string) ($block['cancellation_reason'] ?? ''))) {
                return [
                    'category' => 'provider_cancellation_reason',
                    'category_label' => 'Provider lead cancelled — no response',
                    'reason' => $block['cancellation_reason'],
                    'remarks' => $block['cancellation_remarks'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string, list<string>>
     */
    private function configuredNonResponsiveReasons(): array
    {
        $needles = $this->nonResponsiveNeedles();

        $like = function ($q) use ($needles) {
            foreach ($needles as $needle) {
                $q->orWhereRaw('LOWER(name) LIKE ?', ['%'.$needle.'%']);
            }
        };

        return [
            'invalid_reasons' => LeadInvalidReason::query()->where($like)->pluck('name')->all(),
            'customer_cancellation_reasons' => LeadCancellationReason::query()->where($like)->pluck('name')->all(),
            'provider_cancellation_reasons' => ProviderCancellationReason::query()->where($like)->pluck('name')->all(),
            'customer_statuses' => CustomerLeadStatus::query()->where($like)->pluck('name')->all(),
        ];
    }

    /**
     * @return list<string>
     */
    private function nonResponsiveNeedles(): array
    {
        return ['no response', 'unresponsive', 'not responding', 'no reply'];
    }

    private function textMatchesNonResponsive(string $text): bool
    {
        $hay = strtolower(trim($text));
        if ($hay === '') {
            return false;
        }
        foreach ($this->nonResponsiveNeedles() as $needle) {
            if (str_contains($hay, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Builder<Lead>  $q
     */
    public function applyNonResponsiveFilter(Builder $q): void
    {
        $leads = Lead::query()
            ->whereIn('lead_type', [Lead::TYPE_CUSTOMER, Lead::TYPE_INVALID, Lead::TYPE_PROVIDER])
            ->orderByDesc('date_time_of_lead_received')
            ->limit(self::TIMING_ANALYSIS_SCAN_LIMIT)
            ->get(['id', 'lead_type']);
        if ($leads->isEmpty()) {
            $q->whereRaw('1 = 0');

            return;
        }
        $profiles = $this->buildProfilesForLeads($leads);
        $ids = $leads->filter(function (Lead $lead) use ($profiles) {
            return $this->detectNonResponsiveMatch($lead, $profiles[(int) $lead->id] ?? []) !== null;
        })->pluck('id')->all();
        $q->whereIn('id', $ids !== [] ? $ids : [-1]);
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  array<string, array<string, mixed>>  $profiles
     * @return array<string, mixed>
     */
    private function aggregateLeadActivityReport(Collection $leads, array $profiles): array
    {
        $timing = $this->aggregateLeadTimingReport($leads, $profiles);
        $rows = array_slice($timing['sample_leads'] ?? [], 0, 50);

        return [
            'returned' => count($rows),
            'timing' => $timing['timing'] ?? $timing,
            'leads' => $rows,
        ];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  array<string, array<string, mixed>>  $profiles
     * @param  callable(Lead, array<string, mixed>): bool|null  $filter
     * @return array<string, mixed>
     */
    private function aggregateLeadTimingReport(Collection $leads, array $profiles, ?callable $filter = null): array
    {
        $this->waMessagesByNormPhone = [];
        $bulk = $this->preloadTimingBulkData($leads);
        $rows = [];

        foreach ($leads as $lead) {
            $profile = $profiles[(int) $lead->id] ?? [];
            if ($filter !== null && ! $filter($lead, $profile)) {
                continue;
            }
            $rows[] = $this->computeLeadTimingRow($lead, $profile, $bulk);
        }

        if ($rows === []) {
            return [
                'cohort_size' => 0,
                'timing' => null,
                'sample_leads' => [],
                'note' => 'No leads matched this timing cohort.',
            ];
        }

        return [
            'cohort_size' => count($rows),
            'timing' => $this->summarizeTimingRows($rows),
            'sample_leads' => array_slice($rows, 0, 30),
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function leadMatchesTimingCohort(Lead $lead, array $profile, string $cohort): bool
    {
        return match (strtolower(trim($cohort))) {
            'non_responsive', 'no_response' => $this->detectNonResponsiveMatch($lead, $profile) !== null,
            'invalid' => $lead->lead_type === Lead::TYPE_INVALID,
            'invalid_no_response' => $lead->lead_type === Lead::TYPE_INVALID
                && $this->textMatchesNonResponsive((string) (($profile['invalid']['reason'] ?? ''))),
            'customer' => $lead->lead_type === Lead::TYPE_CUSTOMER,
            'provider' => $lead->lead_type === Lead::TYPE_PROVIDER,
            'customer_cancelled' => $lead->lead_type === Lead::TYPE_CUSTOMER
                && (bool) (($profile['customer']['is_cancelled'] ?? false)),
            'customer_pending' => $lead->lead_type === Lead::TYPE_CUSTOMER
                && ! (bool) (($profile['customer']['is_cancelled'] ?? false))
                && strtolower((string) (($profile['customer']['status_base_type'] ?? 'pending'))) === 'pending',
            default => true,
        };
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @return array<string, mixed>
     */
    private function preloadTimingBulkData(Collection $leads): array
    {
        $leadIds = $leads->pluck('id')->all();
        if ($leadIds === []) {
            return ['followups' => collect(), 'change_logs' => collect(), 'histories' => collect()];
        }

        $followups = LeadFollowup::query()
            ->whereIn('lead_id', $leadIds)
            ->orderBy('followup_at')
            ->get()
            ->groupBy('lead_id');

        $changeLogs = LeadChangeLog::query()
            ->whereIn('lead_id', $leadIds)
            ->orderBy('created_at')
            ->get()
            ->groupBy('lead_id');

        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->orderBy('created_at')
            ->get()
            ->groupBy('lead_id');

        $this->preloadWhatsAppMessages($leads);

        return compact('followups', 'changeLogs', 'histories');
    }

    /**
     * @param  Collection<int, Lead>  $leads
     */
    private function preloadWhatsAppMessages(Collection $leads): void
    {
        $phones = $leads->pluck('phone_number')->filter()->unique()->values();
        if ($phones->isEmpty()) {
            return;
        }

        $messages = WhatsAppMessage::query()
            ->where(function ($q) use ($phones) {
                foreach ($phones as $phone) {
                    $q->orWhere('phone', $phone);
                    $norm = $this->normalizePhone($phone);
                    if ($norm) {
                        $q->orWhere('phone', 'like', '%'.$norm);
                    }
                }
            })
            ->orderBy('created_at')
            ->get(['phone', 'direction', 'created_at']);

        foreach ($messages as $message) {
            $key = $this->normalizePhone($message->phone) ?? (string) $message->phone;
            if (! isset($this->waMessagesByNormPhone[$key])) {
                $this->waMessagesByNormPhone[$key] = collect();
            }
            $this->waMessagesByNormPhone[$key]->push($message);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function whatsAppActivityFromPreload(?string $phone): array
    {
        $key = $this->normalizePhone($phone);
        if ($key === null || ! isset($this->waMessagesByNormPhone[$key])) {
            return ['has_whatsapp_thread' => false];
        }

        $messages = $this->waMessagesByNormPhone[$key];
        $inbound = $messages->where('direction', 'IN');
        $outbound = $messages->where('direction', 'OUT');

        return [
            'has_whatsapp_thread' => true,
            'first_inbound_at' => $inbound->first()?->created_at?->toIso8601String(),
            'last_inbound_at' => $inbound->last()?->created_at?->toIso8601String(),
            'first_outbound_reply_at' => $outbound->first()?->created_at?->toIso8601String(),
            'last_outbound_at' => $outbound->last()?->created_at?->toIso8601String(),
            'inbound_message_count' => $inbound->count(),
            'outbound_message_count' => $outbound->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $bulk
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function computeLeadTimingRow(Lead $lead, array $profile, array $bulk): array
    {
        $leadId = (int) $lead->id;
        $followups = $bulk['followups']->get($leadId, collect());
        $changeLogs = $bulk['changeLogs']->get($leadId, collect());
        $histories = $bulk['histories']->get($leadId, collect());

        $receivedAt = $lead->date_time_of_lead_received;
        $firstFollowup = $followups->sortBy('followup_at')->first();
        $firstChange = $changeLogs->sortBy('created_at')->first();
        $firstHistory = $histories->sortBy('created_at')->first();
        $lastHistory = $histories->sortByDesc('created_at')->first();
        $invalidHistory = $histories->first(fn (LeadTypeHistory $h) => $h->type === Lead::TYPE_INVALID);

        $wa = $this->whatsAppActivityFromPreload($lead->phone_number);
        $profileBlock = match ($lead->lead_type) {
            Lead::TYPE_CUSTOMER => $profile['customer'] ?? [],
            Lead::TYPE_PROVIDER => $profile['provider'] ?? [],
            Lead::TYPE_INVALID => $profile['invalid'] ?? [],
            default => [],
        };
        $profileBlock = is_array($profileBlock) ? $profileBlock : [];

        $touchpoints = collect([
            $receivedAt,
            $firstFollowup?->followup_at,
            $firstChange?->created_at,
            $firstHistory?->created_at,
            $lastHistory?->created_at,
            $invalidHistory?->created_at,
            isset($wa['first_outbound_reply_at']) ? Carbon::parse($wa['first_outbound_reply_at']) : null,
            $lead->updated_at,
        ])->filter();

        $handler = $lead->handled_by;
        $handlerName = $this->resolveHandlerName($handler);

        $nonResponsive = $this->detectNonResponsiveMatch($lead, $profile);

        return [
            'lead_id' => $lead->id,
            'name' => $lead->name,
            'phone' => $lead->phone_number,
            'lead_type' => $lead->lead_type,
            'status' => $profileBlock['status'] ?? ($profileBlock['reason'] ?? null),
            'cancellation_reason' => $profileBlock['cancellation_reason'] ?? null,
            'cancellation_remarks' => $profileBlock['cancellation_remarks'] ?? ($profileBlock['remarks'] ?? null),
            'initial_remarks' => $lead->remarks,
            'handled_by_name' => $handlerName,
            'received_at' => $receivedAt?->toIso8601String(),
            'enquiry_at' => $receivedAt?->toIso8601String(),
            'received_hour' => $receivedAt ? (int) $receivedAt->format('G') : null,
            'received_day' => $receivedAt?->format('D'),
            'first_whatsapp_reply_at' => $wa['first_outbound_reply_at'] ?? null,
            'first_staff_followup_at' => $firstFollowup?->followup_at?->toIso8601String(),
            'followups_taken' => $followups->count(),
            'followups' => $followups->sortBy('followup_at')->take(5)->map(fn (LeadFollowup $f) => [
                'at' => $f->followup_at?->toIso8601String(),
                'remarks' => $f->remarks,
            ])->values()->all(),
            'first_data_update_at' => $firstHistory?->created_at?->toIso8601String(),
            'marked_invalid_at' => $invalidHistory?->created_at?->toIso8601String(),
            'last_updated_at' => $touchpoints->max() instanceof Carbon ? $touchpoints->max()->toIso8601String() : null,
            'lag_hours_to_first_whatsapp_reply' => $this->hoursBetween($receivedAt, isset($wa['first_outbound_reply_at']) ? Carbon::parse($wa['first_outbound_reply_at']) : null),
            'lag_hours_to_first_followup' => $this->hoursBetween($receivedAt, $firstFollowup?->followup_at),
            'lag_hours_to_first_update' => $this->hoursBetween($receivedAt, $firstHistory?->created_at),
            'lag_hours_to_marked_invalid' => $this->hoursBetween($receivedAt, $invalidHistory?->created_at),
            'lag_hours_to_last_touch' => $this->hoursBetween($receivedAt, $touchpoints->max()),
            'has_whatsapp_thread' => (bool) ($wa['has_whatsapp_thread'] ?? false),
            'never_whatsapp_replied' => ($wa['has_whatsapp_thread'] ?? false) && empty($wa['first_outbound_reply_at']),
            'never_staff_followup' => $followups->isEmpty(),
            'non_responsive_category' => $nonResponsive['category'] ?? null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function summarizeTimingRows(array $rows): array
    {
        $receivedHours = $this->hourDistribution(array_column($rows, 'received_at'));
        $replyHours = $this->hourDistribution(
            array_values(array_filter(array_column($rows, 'first_whatsapp_reply_at')))
        );
        $followupHours = $this->hourDistribution(
            array_values(array_filter(array_column($rows, 'first_staff_followup_at')))
        );
        $invalidHours = $this->hourDistribution(
            array_values(array_filter(array_column($rows, 'marked_invalid_at')))
        );
        $dayDist = [];
        foreach ($rows as $row) {
            $day = $row['received_day'] ?? null;
            if ($day) {
                $dayDist[$day] = ($dayDist[$day] ?? 0) + 1;
            }
        }
        arsort($dayDist);

        return [
            'received_by_hour' => $receivedHours,
            'peak_received_hours' => $this->topHours($receivedHours, 5),
            'first_whatsapp_reply_by_hour' => $replyHours,
            'peak_reply_hours' => $this->topHours($replyHours, 5),
            'first_followup_by_hour' => $followupHours,
            'marked_invalid_by_hour' => $invalidHours,
            'received_by_day' => $dayDist,
            'lag_hours' => [
                'to_first_whatsapp_reply' => $this->lagStats(array_map(
                    fn ($r) => $r['lag_hours_to_first_whatsapp_reply'],
                    array_filter($rows, fn ($r) => $r['lag_hours_to_first_whatsapp_reply'] !== null)
                )),
                'to_first_staff_followup' => $this->lagStats(array_map(
                    fn ($r) => $r['lag_hours_to_first_followup'],
                    array_filter($rows, fn ($r) => $r['lag_hours_to_first_followup'] !== null)
                )),
                'to_first_data_update' => $this->lagStats(array_map(
                    fn ($r) => $r['lag_hours_to_first_update'],
                    array_filter($rows, fn ($r) => $r['lag_hours_to_first_update'] !== null)
                )),
                'to_marked_invalid' => $this->lagStats(array_map(
                    fn ($r) => $r['lag_hours_to_marked_invalid'],
                    array_filter($rows, fn ($r) => $r['lag_hours_to_marked_invalid'] !== null)
                )),
                'to_last_touch' => $this->lagStats(array_map(
                    fn ($r) => $r['lag_hours_to_last_touch'],
                    array_filter($rows, fn ($r) => $r['lag_hours_to_last_touch'] !== null)
                )),
            ],
            'coverage' => [
                'with_whatsapp_thread' => count(array_filter($rows, fn ($r) => $r['has_whatsapp_thread'] ?? false)),
                'never_whatsapp_replied' => count(array_filter($rows, fn ($r) => $r['never_whatsapp_replied'] ?? false)),
                'never_staff_followup' => count(array_filter($rows, fn ($r) => $r['never_staff_followup'] ?? false)),
                'no_reply_and_no_followup' => count(array_filter(
                    $rows,
                    fn ($r) => ($r['never_whatsapp_replied'] ?? false) && ($r['never_staff_followup'] ?? false)
                )),
            ],
            'by_handler' => $this->handlerTimingBreakdown($rows),
            'by_non_responsive_category' => collect($rows)
                ->filter(fn ($r) => ! empty($r['non_responsive_category']))
                ->groupBy('non_responsive_category')
                ->map(fn ($group, $cat) => [
                    'category' => $cat,
                    'count' => $group->count(),
                    'median_lag_hours_to_reply' => $this->lagStats(
                        $group->pluck('lag_hours_to_first_whatsapp_reply')->filter()->values()->all()
                    )['median_hours'] ?? null,
                    'median_lag_hours_to_invalid' => $this->lagStats(
                        $group->pluck('lag_hours_to_marked_invalid')->filter()->values()->all()
                    )['median_hours'] ?? null,
                ])
                ->values()
                ->all(),
            'insights' => $this->buildTimingInsights($rows, $receivedHours),
        ];
    }

    /**
     * @param  list<float|null>  $values
     * @return array<string, mixed>
     */
    private function lagStats(array $values): array
    {
        $hours = array_values(array_filter($values, fn ($v) => $v !== null && is_numeric($v)));
        sort($hours, SORT_NUMERIC);
        $n = count($hours);
        if ($n === 0) {
            return ['count' => 0, 'median_hours' => null, 'p90_hours' => null, 'avg_hours' => null, 'min_hours' => null, 'max_hours' => null];
        }

        return [
            'count' => $n,
            'median_hours' => round((float) $hours[intval(floor(($n - 1) / 2))], 2),
            'p90_hours' => round((float) $hours[intval(floor(($n - 1) * 0.9))], 2),
            'avg_hours' => round(array_sum($hours) / $n, 2),
            'min_hours' => round((float) $hours[0], 2),
            'max_hours' => round((float) $hours[$n - 1], 2),
        ];
    }

    /**
     * @param  list<string|null>  $timestamps
     * @return array{labels: list<string>, counts: list<int>, total: int}
     */
    private function hourDistribution(array $timestamps): array
    {
        $counts = array_fill(0, 24, 0);
        foreach ($timestamps as $ts) {
            if (! $ts) {
                continue;
            }
            try {
                $counts[(int) Carbon::parse($ts)->format('G')]++;
            } catch (\Throwable) {
                continue;
            }
        }

        $labels = [];
        for ($h = 0; $h < 24; $h++) {
            $labels[] = sprintf('%02d:00', $h);
        }

        return ['labels' => $labels, 'counts' => array_values($counts), 'total' => array_sum($counts)];
    }

    /**
     * @param  array{labels: list<string>, counts: list<int>}  $dist
     * @return list<array{hour: string, count: int}>
     */
    private function topHours(array $dist, int $limit): array
    {
        $pairs = [];
        foreach ($dist['labels'] as $i => $label) {
            $pairs[] = ['hour' => $label, 'count' => (int) ($dist['counts'][$i] ?? 0)];
        }
        usort($pairs, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_values(array_filter(array_slice($pairs, 0, $limit), fn ($p) => $p['count'] > 0));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function handlerTimingBreakdown(array $rows): array
    {
        return collect($rows)
            ->groupBy(fn ($r) => (string) ($r['handled_by_name'] ?? 'Unassigned'))
            ->map(function ($group, $handler) {
                $replyLags = $group->pluck('lag_hours_to_first_whatsapp_reply')->filter()->values()->all();
                $followupLags = $group->pluck('lag_hours_to_first_followup')->filter()->values()->all();

                return [
                    'handler' => $handler,
                    'leads' => $group->count(),
                    'never_whatsapp_replied' => $group->where('never_whatsapp_replied', true)->count(),
                    'never_staff_followup' => $group->where('never_staff_followup', true)->count(),
                    'median_lag_hours_to_reply' => $this->lagStats($replyLags)['median_hours'] ?? null,
                    'median_lag_hours_to_followup' => $this->lagStats($followupLags)['median_hours'] ?? null,
                ];
            })
            ->sortByDesc('leads')
            ->values()
            ->take(15)
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array{labels: list<string>, counts: list<int>}  $receivedHours
     * @return list<string>
     */
    private function buildTimingInsights(array $rows, array $receivedHours): array
    {
        $insights = [];
        $peak = $this->topHours($receivedHours, 1)[0] ?? null;
        if ($peak) {
            $insights[] = "Most leads were received around {$peak['hour']} ({$peak['count']} leads).";
        }

        $replyLag = $this->lagStats(array_map(
            fn ($r) => $r['lag_hours_to_first_whatsapp_reply'],
            array_filter($rows, fn ($r) => $r['lag_hours_to_first_whatsapp_reply'] !== null)
        ));
        if (($replyLag['median_hours'] ?? null) !== null) {
            $insights[] = "Median time from received to first WhatsApp reply: {$replyLag['median_hours']} hours (p90: {$replyLag['p90_hours']}h).";
        } else {
            $never = count(array_filter($rows, fn ($r) => $r['never_whatsapp_replied'] ?? false));
            if ($never > 0) {
                $insights[] = "{$never} leads had WhatsApp threads but no outbound staff/AI reply recorded.";
            }
        }

        $invalidLag = $this->lagStats(array_map(
            fn ($r) => $r['lag_hours_to_marked_invalid'],
            array_filter($rows, fn ($r) => $r['lag_hours_to_marked_invalid'] !== null)
        ));
        if (($invalidLag['median_hours'] ?? null) !== null) {
            $insights[] = "Median time from received to marked invalid: {$invalidLag['median_hours']} hours.";
        }

        $noTouch = count(array_filter(
            $rows,
            fn ($r) => ($r['never_whatsapp_replied'] ?? false) && ($r['never_staff_followup'] ?? false)
        ));
        if ($noTouch > 0) {
            $insights[] = "{$noTouch} leads had no WhatsApp reply and no staff followup — highest lag risk.";
        }

        return $insights;
    }

    private function hoursBetween(mixed $from, mixed $to): ?float
    {
        if (! $from || ! $to) {
            return null;
        }
        $start = $from instanceof Carbon ? $from : Carbon::parse((string) $from);
        $end = $to instanceof Carbon ? $to : Carbon::parse((string) $to);
        if ($end->lessThan($start)) {
            return null;
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }

    /**
     * @param  array<string, mixed>  $profileBlock
     * @return array<string, mixed>
     */
    private function leadActivityRow(Lead $lead, array $profileBlock): array
    {
        $booking = Booking::query()->where('lead_id', $lead->id)->orderByDesc('created_at')->first(['readable_id', 'booking_status']);
        $wa = $this->buildWhatsAppActivity($lead->phone_number);
        $handler = $lead->handled_by;
        $handlerName = 'Unassigned';
        if ($handler === Lead::HANDLED_BY_AI) {
            $handlerName = 'AI';
        } elseif (Lead::assigneeIsHuman($handler)) {
            $user = User::query()->find((string) $handler, ['first_name', 'last_name', 'email']);
            $handlerName = $user
                ? (trim($user->first_name.' '.$user->last_name) ?: ($user->email ?? 'Staff'))
                : 'Staff';
        }

        return [
            'lead_id' => $lead->id,
            'name' => $lead->name,
            'phone' => $lead->phone_number,
            'lead_type' => $lead->lead_type,
            'status' => $profileBlock['status'] ?? ($profileBlock['reason'] ?? null),
            'invalid_reason' => $profileBlock['reason'] ?? null,
            'cancellation_reason' => $profileBlock['cancellation_reason'] ?? null,
            'cancellation_remarks' => $profileBlock['cancellation_remarks'] ?? ($profileBlock['remarks'] ?? null),
            'handled_by' => $handler,
            'handled_by_name' => $handlerName,
            'received_at' => $lead->date_time_of_lead_received?->toIso8601String(),
            'next_followup_at' => $lead->next_followup_at?->toIso8601String(),
            'has_booking' => $booking !== null,
            'booking_readable_id' => $booking?->readable_id,
            'first_whatsapp_reply_at' => $wa['first_outbound_reply_at'] ?? null,
            'last_whatsapp_reply_at' => $wa['last_outbound_at'] ?? null,
            'last_customer_whatsapp_at' => $wa['last_inbound_at'] ?? null,
        ];
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) < 10) {
            return null;
        }

        return substr($digits, -10);
    }

    private function resolveHandlerName(mixed $handledBy): string
    {
        if (! Lead::assigneeIsHuman($handledBy)) {
            return $handledBy === Lead::HANDLED_BY_AI ? 'AI' : 'Unassigned';
        }
        $user = User::query()->find((string) $handledBy, ['first_name', 'last_name', 'email']);

        return $user ? (trim($user->first_name.' '.$user->last_name) ?: ($user->email ?? 'Staff')) : 'Staff';
    }

    /**
     * @param  array<string, mixed>  $lookup
     * @param  array<string, string>  $adminNames
     * @return array<string, mixed>
     */
    private function resolveTypeHistoryEntry(LeadTypeHistory $h, array $lookup, array $adminNames): array
    {
        $d = is_array($h->data) ? $h->data : [];
        $resolved = ['type' => $h->type];

        if ($h->type === Lead::TYPE_CUSTOMER) {
            $resolved = array_merge($resolved, [
                'zone' => $this->resolveName($lookup['zones'], $d['zone_id'] ?? null),
                'category' => $this->resolveName($lookup['categories'], $d['service_category'] ?? null),
                'sub_category' => $this->resolveName($lookup['categories'], $d['service_subcategory'] ?? null),
                'service' => $this->resolveName($lookup['services'], $d['service_name'] ?? null),
                'variant_key' => $d['variant_key'] ?? null,
                'service_description' => $d['service_description'] ?? null,
                'estimated_service_at' => $d['estimated_service_at'] ?? null,
                'status' => $lookup['customer_statuses']->get((int) ($d['customer_lead_status_id'] ?? 0))?->name,
                'cancellation_reason' => $lookup['customer_cancel_reasons']->get((int) ($d['cancellation_reason_id'] ?? 0))?->name,
                'cancellation_remarks' => $d['cancellation_remarks'] ?? null,
                'booking_status' => $d['booking_status'] ?? null,
                'booking_id' => $lookup['bookings']->get($d['booking_id'] ?? '')?->readable_id ?? ($d['booking_id'] ?? null),
            ]);
        } elseif ($h->type === Lead::TYPE_PROVIDER) {
            $zoneNames = [];
            foreach ((array) ($d['zone_ids'] ?? [$d['zone_id'] ?? null]) as $zid) {
                if ($zid && ($zn = $lookup['zones']->get($zid)?->name)) {
                    $zoneNames[] = $zn;
                }
            }
            $resolved = array_merge($resolved, [
                'district' => $lookup['districts']->get((int) ($d['district_id'] ?? 0))?->name,
                'zones' => array_values(array_unique($zoneNames)),
                'full_address' => $d['full_address'] ?? null,
                'service_areas' => $d['service_areas'] ?? null,
                'service_category' => $this->resolveName($lookup['categories'], $d['provider_service_category'] ?? null),
                'service_subcategory' => $this->resolveName($lookup['categories'], $d['provider_service_subcategory'] ?? null),
                'service_details' => $d['provider_service_details'] ?? null,
                'status' => $lookup['provider_statuses']->get((int) ($d['provider_lead_status_id'] ?? 0))?->name,
                'cancellation_reason' => $lookup['provider_cancel_reasons']->get((int) ($d['provider_cancellation_reason_id'] ?? 0))?->name,
                'cancellation_remarks' => $d['provider_cancellation_remarks'] ?? null,
            ]);
        } elseif ($h->type === Lead::TYPE_INVALID) {
            $resolved = array_merge($resolved, [
                'invalid_reason' => $lookup['invalid_reasons']->get((int) ($d['invalid_reason_id'] ?? 0))?->name,
                'invalid_remarks' => $d['invalid_remarks'] ?? null,
            ]);
        } elseif ($h->type === Lead::TYPE_FUTURE_CUSTOMER) {
            $resolved = array_merge($resolved, [
                'future_customer_reason' => $lookup['future_reasons']->get((int) ($d['future_customer_reason_id'] ?? 0))?->name,
                'future_customer_remarks' => $d['future_customer_remarks'] ?? null,
            ]);
        }

        return array_merge($resolved, [
            'at' => $h->created_at?->toIso8601String(),
            'created_by' => $adminNames[(string) $h->created_by] ?? null,
            'raw_data' => $d,
        ]);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    /**
     * @return list<int>
     */
    private function leadIdsMatchingDimensions(string $zoneSearch, string $categorySearch, string $leadTypeFilter): array
    {
        if ($zoneSearch === '' && $categorySearch === '') {
            return [];
        }

        $zoneId = null;
        if ($zoneSearch !== '') {
            $zoneId = Zone::withoutGlobalScopes()->where('name', 'like', '%'.trim($zoneSearch).'%')->value('id');
            if (! $zoneId) {
                return [];
            }
        }

        $categoryId = null;
        if ($categorySearch !== '') {
            $categoryId = Category::withoutGlobalScopes()->where('name', 'like', '%'.trim($categorySearch).'%')->value('id');
            if (! $categoryId) {
                return [];
            }
        }

        $types = $leadTypeFilter !== '' && $leadTypeFilter !== 'all'
            ? [$leadTypeFilter]
            : [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER];

        $leads = Lead::query()
            ->whereIn('lead_type', $types)
            ->orderByDesc('date_time_of_lead_received')
            ->limit(self::TIMING_ANALYSIS_SCAN_LIMIT)
            ->get(['id', 'lead_type']);

        if ($leads->isEmpty()) {
            return [];
        }

        $profiles = $this->buildProfilesForLeads($leads);

        return $leads->filter(function (Lead $lead) use ($profiles, $zoneId, $categoryId) {
            $key = $lead->lead_type === Lead::TYPE_PROVIDER ? 'provider' : 'customer';
            $block = $profiles[(int) $lead->id][$key] ?? null;
            if (! is_array($block)) {
                return false;
            }
            if ($zoneId !== null) {
                if ($key === 'customer') {
                    $leadZone = Zone::withoutGlobalScopes()->find($zoneId)?->name;
                    if (! $leadZone || ($block['zone'] ?? '') !== $leadZone) {
                        return false;
                    }
                } else {
                    $zones = (array) ($block['zones'] ?? []);
                    $leadZone = Zone::withoutGlobalScopes()->find($zoneId)?->name;
                    if (! $leadZone || ! in_array($leadZone, $zones, true)) {
                        return false;
                    }
                }
            }
            if ($categoryId !== null) {
                $catName = Category::withoutGlobalScopes()->find($categoryId)?->name;
                $leadCat = $block['service_category'] ?? $block['service_subcategory'] ?? null;
                if (! $catName || stripos((string) $leadCat, (string) $catName) === false) {
                    return false;
                }
            }

            return true;
        })->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function flattenLeadAdminFields(Lead $lead, array $profile): array
    {
        $flat = [
            'id' => $lead->id,
            'name' => $lead->name,
            'phone' => $lead->phone_number,
            'lead_type' => $lead->lead_type,
            'source' => $lead->source?->name,
            'ad_source' => $lead->adSource?->name,
            'handled_by' => $this->resolveHandlerName($lead->handled_by),
            'remarks' => $lead->remarks,
            'received_at' => $lead->date_time_of_lead_received?->toIso8601String(),
            'next_followup_at' => $lead->next_followup_at?->toIso8601String(),
            'is_open' => $profile['is_open'] ?? null,
            'pipeline_status' => $profile['pipeline_status'] ?? null,
        ];

        foreach (['customer', 'provider', 'invalid', 'future_customer'] as $block) {
            if (! empty($profile[$block]) && is_array($profile[$block])) {
                foreach ($profile[$block] as $key => $value) {
                    $flat[$block.'_'.$key] = $value;
                }
            }
        }

        return $flat;
    }
}
