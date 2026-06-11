<?php

namespace Modules\LeadManagement\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\LeadManagement\Entities\CustomerLeadTag;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupAutomationRule;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupAutomationRun;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupDispatch;
use Modules\LeadManagement\Support\VoiceCronWaAiFlow;
use Modules\WhatsAppModule\Entities\WhatsAppBooking;
use Modules\WhatsAppModule\Entities\ProviderLead;
use Modules\WhatsAppModule\Entities\WhatsAppChatStatus;
use Modules\WhatsAppModule\Entities\WhatsAppChatThreadMeta;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\WhatsAppModule\Services\WhatsAppLeadLifecycleService;
use Modules\WhatsAppModule\Support\SocialInboxChannel;

class WhatsAppFollowupCandidateQueryService
{
    public function __construct(
        private readonly WhatsAppLeadLifecycleService $leadLifecycle,
        private readonly LeadOpenStatusService $leadOpenStatus,
        private readonly WhatsAppFollowupSummaryService $summaryService,
        private readonly WhatsAppFollowupContextBuilder $contextBuilder
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, int $page = 1, int $perPage = 30): LengthAwarePaginator
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $version = (int) Cache::get($this->cacheVersionKey(), 0);
        $cacheKey = $this->cachePrefix() . ':v' . $version . ':' . md5(json_encode([
            'filters' => $filters,
            'page' => $page,
            'perPage' => $perPage,
        ]) ?: '');

        $shouldCache = ($filters['other_cron_job_mode'] ?? '') === ''
            && empty($filters['_batch_excluded_phones'])
            && empty($filters['_current_rule_id']);

        $cached = $shouldCache ? Cache::get($cacheKey) : null;
        if (is_array($cached) && isset($cached['items'], $cached['total'])) {
            return new Paginator(
                collect($cached['items']),
                (int) $cached['total'],
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $maxScan = $this->resolveMaxScanLimit($filters);
        $rows = $this->fetchSilentAfterAiBaseRows($filters, $maxScan);
        $enriched = $this->enrichCandidates($rows);
        $filtered = $this->applyFilters($enriched, $filters);

        $filtered = $filtered->sortByDesc(fn (array $c) => $c['silent_since_ts'] ?? 0)->values();

        $total = $filtered->count();
        $slice = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

        if ($shouldCache) {
            Cache::put($cacheKey, [
                'items' => $slice->all(),
                'total' => $total,
            ], (int) config('services.omnidimension.cache_whatsapp_followup_list_ttl', 60));
        }

        return new Paginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public static function clearSearchCache(): void
    {
        Cache::increment('wa_followup_candidates:version');
    }

    public static function clearOtherCronPhonesCache(): void
    {
        Cache::increment('wa_cron_other_phones:version');
    }

    private function otherCronPhonesCacheVersionKey(): string
    {
        return 'wa_cron_other_phones:version';
    }

    private function cacheVersionKey(): string
    {
        return $this->cachePrefix() . ':version';
    }

    private function cachePrefix(): string
    {
        return 'wa_followup_candidates';
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function collectAll(array $filters, int $max = 500): Collection
    {
        $max = max(1, min(500, $max));
        $batchSize = min(200, max($max * 4, 50));
        $maxScan = $this->resolveMaxScanLimit($filters);
        $offset = 0;
        $results = collect();
        $seenPhones = [];

        while ($results->count() < $max && $offset < $maxScan) {
            $limit = min($batchSize, $maxScan - $offset);
            if ($limit <= 0) {
                break;
            }

            $rows = $this->fetchSilentAfterAiBaseRows($filters, $limit, $offset);
            if ($rows->isEmpty()) {
                break;
            }

            $enriched = $this->enrichCandidates($rows);
            $filtered = $this->applyFilters($enriched, $filters);

            foreach ($filtered as $candidate) {
                $phone = (string) ($candidate['phone'] ?? '');
                if ($phone === '' || isset($seenPhones[$phone])) {
                    continue;
                }

                $seenPhones[$phone] = true;
                $results->push($candidate);

                if ($results->count() >= $max) {
                    break 2;
                }
            }

            $offset += $rows->count();
            if ($rows->count() < $limit) {
                break;
            }
        }

        return $results->sortByDesc(fn (array $c) => $c['silent_since_ts'] ?? 0)->values();
    }

    /**
     * Re-check pending approval candidates against current filters before dispatch.
     *
     * @param  array<string, mixed>  $filters
     * @param  Collection<int, array<string, mixed>>  $candidates
     * @return Collection<int, array<string, mixed>>
     */
    public function refreshCandidatesForApproval(array $filters, Collection $candidates): Collection
    {
        $phones = $candidates
            ->pluck('phone')
            ->map(fn ($phone) => trim((string) $phone))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($phones === []) {
            return collect();
        }

        $fresh = $this->collectAll(
            array_merge($filters, ['phones' => $phones]),
            count($phones)
        )->keyBy(fn (array $candidate) => (string) ($candidate['phone'] ?? ''));

        return $candidates
            ->filter(function ($candidate) use ($fresh) {
                if (!is_array($candidate)) {
                    return false;
                }

                $phone = trim((string) ($candidate['phone'] ?? ''));

                return $phone !== '' && $fresh->has($phone);
            })
            ->map(function ($candidate) use ($fresh) {
                $phone = trim((string) ($candidate['phone'] ?? ''));

                return $fresh->get($phone, $candidate);
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolveMaxScanLimit(array $filters): int
    {
        $phoneFilter = array_values(array_filter((array) ($filters['phones'] ?? [])));
        if ($phoneFilter !== []) {
            return max(count($phoneFilter), 1);
        }

        return max(500, (int) config('services.omnidimension.cron_candidate_max_scan', 10000));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function fetchSilentAfterAiBaseRows(array $filters, ?int $limit = null, int $offset = 0): Collection
    {
        $table = (string) config('whatsappmodule.tables.messages', 'whatsapp_messages');
        $channel = SocialInboxChannel::current();

        $minMinutes = $this->resolveSilentMinMinutes($filters);
        $maxHours = isset($filters['silent_max_hours']) && $filters['silent_max_hours'] !== ''
            ? max(0, (int) $filters['silent_max_hours'])
            : null;

        $latestSub = DB::table($table)
            ->select('phone', DB::raw('MAX(id) as latest_id'))
            ->where('channel', $channel)
            ->groupBy('phone');

        $lastCustomerSub = DB::table($table)
            ->select('phone', DB::raw('MAX(created_at) as last_customer_at'))
            ->where('channel', $channel)
            ->where('direction', 'IN')
            ->groupBy('phone');

        $query = DB::table($table . ' as m')
            ->joinSub($latestSub, 'latest', 'm.id', '=', 'latest.latest_id')
            ->leftJoinSub($lastCustomerSub, 'lc', 'm.phone', '=', 'lc.phone')
            ->where('m.channel', $channel)
            ->where('m.direction', 'OUT')
            ->where(function ($q) {
                $q->where('m.sent_by', 'AI')
                    ->orWhereNull('m.sent_by')
                    ->orWhere('m.sent_by', '');
            })
            ->select([
                'm.phone',
                'm.message_text as last_ai_message',
                'm.created_at as silent_since',
                'm.status as last_outbound_status',
                'm.sent_by',
                'lc.last_customer_at',
            ]);

        if ($minMinutes > 0) {
            $query->where('m.created_at', '<=', Carbon::now()->subMinutes($minMinutes));
        }

        if ($maxHours !== null && $maxHours > 0) {
            $query->where('m.created_at', '>=', Carbon::now()->subHours($maxHours));
        }

        $phoneFilter = array_values(array_filter((array) ($filters['phones'] ?? [])));
        if ($phoneFilter !== []) {
            $query->whereIn('m.phone', $phoneFilter);
        }

        $query->orderByDesc('m.created_at');

        if ($limit !== null && $limit > 0) {
            $query->limit($limit)->offset(max(0, $offset));
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function enrichCandidates(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        $phones = $rows->pluck('phone')->filter()->unique()->values()->all();

        $waUsers = WhatsAppUser::query()
            ->whereIn('phone', $phones)
            ->get(['phone', 'name', 'handled_by', 'human_support_requested_at'])
            ->keyBy('phone');

        $leadByNormalized = $this->resolvePrimaryLeadsByPhone($phones);
        $leadIds = collect($leadByNormalized)->pluck('id')->filter()->unique()->values()->all();
        $leads = Lead::query()->whereIn('id', $leadIds)->get()->keyBy('id');
        $leadStatusMeta = $this->leadOpenStatus->buildLeadStatusMeta($leads->values());
        $pipelineStatusByLead = $this->loadLeadPipelineStatusIds($leads->values());

        $chatMeta = $this->loadChatMeta($phones);
        $customerTagsByLead = $this->loadCustomerTagsByLead($leads);
        $lastDispatch = WhatsAppVoiceFollowupDispatch::latestAttemptAtByWaPhone($phones);
        $waAiFlowByPhone = $this->loadWaAiFlowStateByPhone($phones);

        return $rows->map(function ($row) use ($waUsers, $leadByNormalized, $leads, $leadStatusMeta, $pipelineStatusByLead, $chatMeta, $customerTagsByLead, $lastDispatch, $waAiFlowByPhone) {
            $phone = (string) ($row->phone ?? '');
            $normalized = $this->leadLifecycle->normalizeLeadPhone($phone);
            $waUser = $waUsers->get($phone);
            $leadRef = $normalized ? ($leadByNormalized[$normalized] ?? null) : null;
            $lead = $leadRef ? $leads->get($leadRef['id']) : null;
            $leadId = $lead?->id;
            $leadType = $lead?->lead_type ?? Lead::TYPE_UNKNOWN;
            $leadOpen = $leadId ? (bool) ($leadStatusMeta[$leadId]['is_open'] ?? true) : true;

            $silentSince = Carbon::parse($row->silent_since);
            $silentSeconds = max(0, $silentSince->diffInSeconds(Carbon::now()));

            $meta = $chatMeta[$phone] ?? ['chat_status' => null, 'chat_tags' => []];
            $displayName = trim((string) ($lead?->name ?? $waUser?->name ?? ''));
            $statusMeta = $leadId ? ($leadStatusMeta[$leadId] ?? null) : null;
            $pipelineStatus = $leadId ? ($pipelineStatusByLead[$leadId] ?? []) : [];
            $lastAi = trim((string) ($row->last_ai_message ?? ''));
            $waAiFlow = $waAiFlowByPhone[$phone] ?? [
                'wa_ai_customer_booking_submitted' => false,
                'wa_ai_provider_lead_submitted' => false,
            ];

            $candidate = [
                'phone' => $phone,
                'normalized_phone' => $normalized,
                'display_name' => $displayName !== '' ? $displayName : ('WhatsApp ' . ($normalized ?? $phone)),
                'last_ai_message' => $lastAi,
                'last_ai_message_preview' => $this->truncateText($lastAi, 140),
                'last_outbound_status' => (string) ($row->last_outbound_status ?? ''),
                'last_outbound_read' => strtolower((string) ($row->last_outbound_status ?? '')) === 'read',
                'silent_since' => $silentSince->toDateTimeString(),
                'silent_since_label' => $silentSince->format('d M Y h:i a'),
                'silent_since_ts' => $silentSince->timestamp,
                'silent_duration_label' => $this->formatDuration($silentSeconds),
                'silent_seconds' => $silentSeconds,
                'last_customer_at' => $row->last_customer_at ? (string) $row->last_customer_at : null,
                'handled_by' => $waUser?->handled_by ?: 'AI',
                'handled_by_label' => $this->formatHandledBy($waUser?->handled_by),
                'human_support_requested_at' => $waUser?->human_support_requested_at?->toDateTimeString(),
                'lead_id' => $leadId,
                'lead_type' => $leadType,
                'lead_open' => $leadOpen,
                'customer_lead_status_id' => $pipelineStatus['customer_lead_status_id'] ?? null,
                'provider_lead_status_id' => $pipelineStatus['provider_lead_status_id'] ?? null,
                'lead_status_label' => $statusMeta['label'] ?? ($leadOpen ? 'Open' : 'Closed'),
                'lead_status_badge' => $statusMeta['badge_class'] ?? ($leadOpen ? 'bg-danger' : 'bg-success'),
                'lead_url' => $leadId ? route('admin.lead.show', $leadId) : null,
                'chat_status' => $meta['chat_status'],
                'chat_tags' => $meta['chat_tags'],
                'customer_lead_tags' => $leadId ? ($customerTagsByLead[$leadId] ?? []) : [],
                'wa_ai_customer_booking_submitted' => (bool) ($waAiFlow['wa_ai_customer_booking_submitted'] ?? false),
                'wa_ai_provider_lead_submitted' => (bool) ($waAiFlow['wa_ai_provider_lead_submitted'] ?? false),
                'wa_ai_flow_labels' => $this->waAiFlowLabelsForCandidate($waAiFlow),
                'last_followup_at' => isset($lastDispatch[$phone]) ? $lastDispatch[$phone]->toDateTimeString() : null,
                'last_followup_at_label' => isset($lastDispatch[$phone])
                    ? Carbon::parse($lastDispatch[$phone])->format('d M Y h:i a')
                    : null,
                'lead_summary_preview' => '',
                'conversation_recap' => '',
            ];

            $cachedSummary = $this->summaryService->getCachedSummary($phone);
            $candidate['cached_summary'] = $cachedSummary['summary'];
            $candidate['cached_summary_current'] = $cachedSummary['is_current'];
            $candidate['cached_summary_needs_refresh'] = $cachedSummary['needs_refresh'];

            $built = $this->contextBuilder->buildForCandidate($candidate);
            $candidate['call_context'] = $built['context'];
            $candidate['lead_summary_preview'] = $built['lead_summary_preview'];
            $candidate['conversation_recap'] = $built['conversation_recap'];

            return $candidate;
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $candidates
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function applyFilters(Collection $candidates, array $filters): Collection
    {
        $excludeCalledHours = max(0, (int) ($filters['exclude_called_within_hours'] ?? 0));
        $phonesFilter = array_values(array_filter((array) ($filters['phones'] ?? [])));
        $skipOtherCron = !empty($filters['_skip_other_cron_filter']);
        $otherCronMode = (string) ($filters['other_cron_job_mode'] ?? '');
        $otherCronIds = array_map('intval', array_filter((array) ($filters['other_cron_job_ids'] ?? [])));
        $currentRuleId = (int) ($filters['_current_rule_id'] ?? 0);

        $otherCronPhoneSet = [];
        if (!$skipOtherCron && $otherCronMode !== '') {
            if ($otherCronMode === 'exclude_all_active') {
                $otherCronPhoneSet = $this->buildPhoneExclusionSet(
                    $this->resolvePhonesForAllActiveOtherCronJobs($currentRuleId)
                );
            } elseif ($otherCronIds !== []) {
                $otherCronPhoneSet = $this->buildPhoneExclusionSet(
                    $this->resolvePhonesForOtherCronJobs($otherCronIds, $currentRuleId)
                );
            }
        }

        $batchExcludedPhoneSet = $this->buildPhoneExclusionSet((array) ($filters['_batch_excluded_phones'] ?? []));

        $ownPendingPhoneSet = [];
        if ($currentRuleId > 0) {
            $ownPendingPhoneSet = $this->buildPhoneExclusionSet(
                $this->resolvePhonesFromPendingCronRuns(onlyRuleId: $currentRuleId)
            );
        }

        return $candidates->filter(function (array $c) use (
            $filters,
            $excludeCalledHours,
            $phonesFilter,
            $otherCronMode,
            $otherCronPhoneSet,
            $batchExcludedPhoneSet,
            $ownPendingPhoneSet
        ) {
            if ($phonesFilter !== [] && !in_array((string) ($c['phone'] ?? ''), $phonesFilter, true)) {
                return false;
            }

            if (!$this->passesIncludeMatchConditions($c, $filters)) {
                return false;
            }

            if (!$this->passesExcludeMatchConditions($c, $filters)) {
                return false;
            }

            if ($excludeCalledHours > 0 && !empty($c['last_followup_at'])) {
                try {
                    $last = Carbon::parse($c['last_followup_at']);
                    if ($last->greaterThan(Carbon::now()->subHours($excludeCalledHours))) {
                        return false;
                    }
                } catch (\Throwable) {
                    // keep
                }
            }

            if ($ownPendingPhoneSet !== [] && $this->isPhoneInExclusionSet($ownPendingPhoneSet, (string) ($c['phone'] ?? ''))) {
                return false;
            }

            if ($batchExcludedPhoneSet !== [] && $this->isPhoneInExclusionSet($batchExcludedPhoneSet, (string) ($c['phone'] ?? ''))) {
                return false;
            }

            if (in_array($otherCronMode, ['exclude', 'exclude_all_active'], true)
                && $this->isPhoneInExclusionSet($otherCronPhoneSet, (string) ($c['phone'] ?? ''))) {
                return false;
            }

            if ($otherCronMode === 'include' && $otherCronPhoneSet !== []
                && !$this->isPhoneInExclusionSet($otherCronPhoneSet, (string) ($c['phone'] ?? ''))) {
                return false;
            }

            return true;
        })->values();
    }

    /**
     * @param  array<string, mixed>  $c
     * @param  array<string, mixed>  $filters
     */
    private function passesIncludeMatchConditions(array $c, array $filters): bool
    {
        $leadTypes = array_filter((array) ($filters['lead_types'] ?? []));
        if ($leadTypes !== [] && !in_array((string) ($c['lead_type'] ?? ''), $leadTypes, true)) {
            return false;
        }

        if (!$this->passesPipelineStatusIncludeFilter(
            $c,
            array_map('intval', array_filter((array) ($filters['customer_lead_status_ids'] ?? []))),
            Lead::TYPE_CUSTOMER,
            'customer_lead_status_id'
        )) {
            return false;
        }

        if (!$this->passesPipelineStatusIncludeFilter(
            $c,
            array_map('intval', array_filter((array) ($filters['provider_lead_status_ids'] ?? []))),
            Lead::TYPE_PROVIDER,
            'provider_lead_status_id'
        )) {
            return false;
        }

        $leadOpen = (string) ($filters['lead_open'] ?? '');
        if ($leadOpen === 'open' && empty($c['lead_open'])) {
            return false;
        }
        if ($leadOpen === 'closed' && !empty($c['lead_open'])) {
            return false;
        }

        $waBucket = (string) ($filters['wa_chat_bucket'] ?? '');
        $bucket = is_array($c['chat_status'] ?? null) ? (string) ($c['chat_status']['bucket'] ?? 'open') : 'open';
        if ($waBucket === 'open' && $bucket !== 'open') {
            return false;
        }
        if ($waBucket === 'closed' && $bucket !== 'closed') {
            return false;
        }

        $waTagIds = array_map('intval', array_filter((array) ($filters['wa_chat_tag_ids'] ?? [])));
        if ($waTagIds !== []) {
            $have = collect($c['chat_tags'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (array_intersect($waTagIds, $have) === []) {
                return false;
            }
        }

        $customerTagIds = array_map('intval', array_filter((array) ($filters['customer_lead_tag_ids'] ?? [])));
        if ($customerTagIds !== []) {
            $have = collect($c['customer_lead_tags'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (array_intersect($customerTagIds, $have) === []) {
                return false;
            }
        }

        $handledBy = (string) ($filters['handled_by'] ?? '');
        if ($handledBy === 'ai') {
            $hb = (string) ($c['handled_by'] ?? 'AI');
            if ($hb !== 'AI' && $hb !== '') {
                return false;
            }
        } elseif ($handledBy === 'human') {
            $hb = (string) ($c['handled_by'] ?? '');
            if ($hb === '' || $hb === 'AI') {
                return false;
            }
            $employeeIds = array_values(array_filter((array) ($filters['handled_by_employee_ids'] ?? [])));
            if ($employeeIds !== [] && !in_array($hb, $employeeIds, true)) {
                return false;
            }
        }

        if (($filters['human_support'] ?? '') === 'only' && empty($c['human_support_requested_at'])) {
            return false;
        }

        if (!$this->passesWaAiFlowIncludeFilter($c, $filters)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $c
     * @param  array<string, mixed>  $filters
     */
    private function passesExcludeMatchConditions(array $c, array $filters): bool
    {
        $leadTypes = array_filter((array) ($filters['exclude_lead_types'] ?? []));
        if ($leadTypes !== [] && in_array((string) ($c['lead_type'] ?? ''), $leadTypes, true)) {
            return false;
        }

        if ($this->matchesPipelineStatusExcludeFilter(
            $c,
            array_map('intval', array_filter((array) ($filters['exclude_customer_lead_status_ids'] ?? []))),
            Lead::TYPE_CUSTOMER,
            'customer_lead_status_id'
        )) {
            return false;
        }

        if ($this->matchesPipelineStatusExcludeFilter(
            $c,
            array_map('intval', array_filter((array) ($filters['exclude_provider_lead_status_ids'] ?? []))),
            Lead::TYPE_PROVIDER,
            'provider_lead_status_id'
        )) {
            return false;
        }

        $leadOpen = (string) ($filters['exclude_lead_open'] ?? '');
        if ($leadOpen === 'open' && !empty($c['lead_open'])) {
            return false;
        }
        if ($leadOpen === 'closed' && empty($c['lead_open'])) {
            return false;
        }

        $waBucket = (string) ($filters['exclude_wa_chat_bucket'] ?? '');
        $bucket = is_array($c['chat_status'] ?? null) ? (string) ($c['chat_status']['bucket'] ?? 'open') : 'open';
        if ($waBucket === 'open' && $bucket === 'open') {
            return false;
        }
        if ($waBucket === 'closed' && $bucket === 'closed') {
            return false;
        }

        $waTagIds = array_map('intval', array_filter((array) ($filters['exclude_wa_chat_tag_ids'] ?? [])));
        if ($waTagIds !== []) {
            $have = collect($c['chat_tags'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (array_intersect($waTagIds, $have) !== []) {
                return false;
            }
        }

        $customerTagIds = array_map('intval', array_filter((array) ($filters['exclude_customer_lead_tag_ids'] ?? [])));
        if ($customerTagIds !== []) {
            $have = collect($c['customer_lead_tags'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (array_intersect($customerTagIds, $have) !== []) {
                return false;
            }
        }

        $handledBy = (string) ($filters['exclude_handled_by'] ?? '');
        if ($handledBy === 'ai') {
            $hb = (string) ($c['handled_by'] ?? 'AI');
            if ($hb === 'AI' || $hb === '') {
                return false;
            }
        } elseif ($handledBy === 'human') {
            $hb = (string) ($c['handled_by'] ?? '');
            if ($hb !== '' && $hb !== 'AI') {
                $employeeIds = array_values(array_filter((array) ($filters['exclude_handled_by_employee_ids'] ?? [])));
                if ($employeeIds === [] || in_array($hb, $employeeIds, true)) {
                    return false;
                }
            }
        }

        if (($filters['exclude_human_support'] ?? '') === 'exclude' && !empty($c['human_support_requested_at'])) {
            return false;
        }

        if (!$this->passesWaAiFlowExcludeFilter($c, $filters)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string>  $phones
     * @return array<string, array{wa_ai_customer_booking_submitted: bool, wa_ai_provider_lead_submitted: bool}>
     */
    private function loadWaAiFlowStateByPhone(array $phones): array
    {
        if ($phones === []) {
            return [];
        }

        $customerBookingPhones = WhatsAppBooking::query()
            ->whereIn('phone', $phones)
            ->where('status', WhatsAppBooking::STATUS_TENTATIVE_PENDING_HUMAN)
            ->pluck('phone')
            ->unique()
            ->flip()
            ->all();

        $providerLeadPhones = ProviderLead::query()
            ->whereIn('phone', $phones)
            ->where('status', ProviderLead::STATUS_TENTATIVE_PENDING_HUMAN)
            ->pluck('phone')
            ->unique()
            ->flip()
            ->all();

        $result = [];
        foreach ($phones as $phone) {
            $result[$phone] = [
                'wa_ai_customer_booking_submitted' => isset($customerBookingPhones[$phone]),
                'wa_ai_provider_lead_submitted' => isset($providerLeadPhones[$phone]),
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $waAiFlow
     * @return array<int, string>
     */
    private function waAiFlowLabelsForCandidate(array $waAiFlow): array
    {
        $labels = [];
        if (!empty($waAiFlow['wa_ai_customer_booking_submitted'])) {
            $labels[] = VoiceCronWaAiFlow::label(VoiceCronWaAiFlow::CUSTOMER_BOOKING_SUBMITTED);
        }
        if (!empty($waAiFlow['wa_ai_provider_lead_submitted'])) {
            $labels[] = VoiceCronWaAiFlow::label(VoiceCronWaAiFlow::PROVIDER_LEAD_SUBMITTED);
        }

        return $labels;
    }

    /**
     * @param  array<string, mixed>  $c
     * @param  array<string, mixed>  $filters
     */
    private function passesWaAiFlowIncludeFilter(array $c, array $filters): bool
    {
        $flows = array_values(array_filter((array) ($filters['wa_ai_flows'] ?? [])));
        if ($flows === []) {
            return true;
        }

        foreach ($flows as $flow) {
            if ($this->candidateMatchesWaAiFlow($c, (string) $flow)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $c
     * @param  array<string, mixed>  $filters
     */
    private function passesWaAiFlowExcludeFilter(array $c, array $filters): bool
    {
        $flows = array_values(array_filter((array) ($filters['exclude_wa_ai_flows'] ?? [])));
        foreach ($flows as $flow) {
            if ($this->candidateMatchesWaAiFlow($c, (string) $flow)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function candidateMatchesWaAiFlow(array $c, string $flow): bool
    {
        $flag = VoiceCronWaAiFlow::candidateFlag($flow);
        if ($flag === '') {
            return false;
        }

        return !empty($c[$flag]);
    }

    /**
     * @param  array<string, mixed>  $c
     * @param  array<int, int>  $statusIds
     */
    private function passesPipelineStatusIncludeFilter(array $c, array $statusIds, string $leadType, string $statusKey): bool
    {
        if ($statusIds === []) {
            return true;
        }

        if ((string) ($c['lead_type'] ?? '') !== $leadType) {
            return true;
        }

        $statusId = (int) ($c[$statusKey] ?? 0);

        return $statusId > 0 && in_array($statusId, $statusIds, true);
    }

    /**
     * @param  array<string, mixed>  $c
     * @param  array<int, int>  $statusIds
     */
    private function matchesPipelineStatusExcludeFilter(array $c, array $statusIds, string $leadType, string $statusKey): bool
    {
        if ($statusIds === []) {
            return false;
        }

        if ((string) ($c['lead_type'] ?? '') !== $leadType) {
            return false;
        }

        $statusId = (int) ($c[$statusKey] ?? 0);

        return $statusId > 0 && in_array($statusId, $statusIds, true);
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @return array<int, array{customer_lead_status_id: int|null, provider_lead_status_id: int|null}>
     */
    private function loadLeadPipelineStatusIds(Collection $leads): array
    {
        $leadIds = $leads->pluck('id')->filter()->unique()->values()->all();
        if ($leadIds === []) {
            return [];
        }

        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->whereIn('type', [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER])
            ->orderByDesc('created_at')
            ->get();

        $latestByComposite = [];
        foreach ($histories as $history) {
            $compositeKey = $history->lead_id . '|' . $history->type;
            if (!isset($latestByComposite[$compositeKey])) {
                $latestByComposite[$compositeKey] = $history;
            }
        }

        $byLead = [];
        foreach ($leads as $lead) {
            $leadId = (int) $lead->id;
            $history = $latestByComposite[$leadId . '|' . $lead->lead_type] ?? null;
            $data = is_array($history?->data) ? $history->data : [];
            $byLead[$leadId] = [
                'customer_lead_status_id' => !empty($data['customer_lead_status_id'])
                    ? (int) $data['customer_lead_status_id']
                    : null,
                'provider_lead_status_id' => !empty($data['provider_lead_status_id'])
                    ? (int) $data['provider_lead_status_id']
                    : null,
            ];
        }

        return $byLead;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolveSilentMinMinutes(array $filters): int
    {
        if (isset($filters['silent_min_minutes']) && $filters['silent_min_minutes'] !== '') {
            return max(0, (int) $filters['silent_min_minutes']);
        }

        $unit = (string) ($filters['silent_min_unit'] ?? '');
        $value = max(0, (int) ($filters['silent_min_value'] ?? 0));
        if ($value > 0 && in_array($unit, ['minutes', 'hours', 'days'], true)) {
            return match ($unit) {
                'days' => $value * 24 * 60,
                'hours' => $value * 60,
                default => $value,
            };
        }

        return max(0, (int) ($filters['silent_min_hours'] ?? 0)) * 60;
    }

    /**
     * Phones matching any other enabled cron job rule (excluding the current rule).
     *
     * @return array<int, string>
     */
    private function resolvePhonesForAllActiveOtherCronJobs(int $excludeRuleId = 0): array
    {
        $ruleIds = WhatsAppVoiceFollowupAutomationRule::query()
            ->where('is_enabled', true)
            ->when($excludeRuleId > 0, fn ($query) => $query->where('id', '!=', $excludeRuleId))
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $phones = $this->resolvePhonesForOtherCronJobs($ruleIds, $excludeRuleId);
        $phones = array_merge($phones, $this->resolvePhonesFromPendingCronRuns(excludeRuleId: $excludeRuleId));

        return array_values(array_unique($phones));
    }

    /**
     * Phones already matched in pending-approval runs (same or other cron jobs).
     *
     * @return array<int, string>
     */
    private function resolvePhonesFromPendingCronRuns(?int $onlyRuleId = null, ?int $excludeRuleId = null): array
    {
        $query = WhatsAppVoiceFollowupAutomationRun::query()
            ->where('status', WhatsAppVoiceFollowupAutomationRun::STATUS_PENDING_APPROVAL)
            ->whereHas('rule', fn ($ruleQuery) => $ruleQuery->where('is_enabled', true));

        if ($onlyRuleId !== null && $onlyRuleId > 0) {
            $query->where('rule_id', $onlyRuleId);
        } elseif ($excludeRuleId !== null && $excludeRuleId > 0) {
            $query->where('rule_id', '!=', $excludeRuleId);
        }

        $phones = [];
        foreach ($query->get(['pending_candidates']) as $run) {
            foreach ((array) $run->pending_candidates as $candidate) {
                if (!is_array($candidate)) {
                    continue;
                }

                $phone = (string) ($candidate['phone'] ?? '');
                if ($phone !== '') {
                    $phones[] = $phone;
                }
            }
        }

        return $phones;
    }

    /**
     * @param  array<int, string>  $phones
     * @return array<string, true>
     */
    private function buildPhoneExclusionSet(array $phones): array
    {
        $set = [];
        foreach ($phones as $phone) {
            $this->addPhoneToExclusionSet($set, (string) $phone);
        }

        return $set;
    }

    /**
     * @param  array<string, true>  $set
     */
    private function addPhoneToExclusionSet(array &$set, string $phone): void
    {
        $phone = trim($phone);
        if ($phone === '') {
            return;
        }

        $set[$phone] = true;

        $normalized = $this->leadLifecycle->normalizeLeadPhone($phone);
        if ($normalized !== null && $normalized !== $phone) {
            $set[$normalized] = true;
        }
    }

    /**
     * @param  array<string, true>  $set
     */
    private function isPhoneInExclusionSet(array $set, string $phone): bool
    {
        if ($set === []) {
            return false;
        }

        $phone = trim($phone);
        if ($phone === '') {
            return false;
        }

        if (isset($set[$phone])) {
            return true;
        }

        $normalized = $this->leadLifecycle->normalizeLeadPhone($phone);

        return $normalized !== null && isset($set[$normalized]);
    }

    /**
     * Phones that match at least one of the selected cron job rule filters.
     *
     * @param  array<int, int>  $ruleIds
     * @return array<int, string>
     */
    private function resolvePhonesForOtherCronJobs(array $ruleIds, int $excludeRuleId = 0): array
    {
        $ruleIds = array_values(array_unique(array_filter(array_map('intval', $ruleIds))));
        if ($excludeRuleId > 0) {
            $ruleIds = array_values(array_filter($ruleIds, fn (int $id) => $id !== $excludeRuleId));
        }

        if ($ruleIds === []) {
            return [];
        }

        $version = (int) Cache::get($this->otherCronPhonesCacheVersionKey(), 0);
        $cacheKey = 'wa_cron_other_phones:v' . $version . ':' . md5(json_encode([
            'rule_ids' => $ruleIds,
            'exclude_rule_id' => $excludeRuleId,
        ]) ?: '');

        $ttl = max(30, (int) config('services.omnidimension.cron_other_jobs_phones_cache_ttl', 120));

        return Cache::remember($cacheKey, $ttl, function () use ($ruleIds): array {
            $rules = WhatsAppVoiceFollowupAutomationRule::query()
                ->whereIn('id', $ruleIds)
                ->orderBy('id')
                ->get();

            $phones = [];
            foreach ($rules as $rule) {
                $ruleFilters = $rule->normalizedFilters();
                $ruleFilters['_skip_other_cron_filter'] = true;

                foreach ($this->collectAll($ruleFilters, 500) as $candidate) {
                    $phone = (string) ($candidate['phone'] ?? '');
                    if ($phone !== '') {
                        $phones[$phone] = true;
                    }
                }
            }

            return array_keys($phones);
        });
    }

    /**
     * @param  array<int, string>  $phones
     * @return array<string, array{id: int, lead_type: string}>
     */
    private function resolvePrimaryLeadsByPhone(array $phones): array
    {
        $normalizedPhones = collect($phones)
            ->map(fn ($p) => $this->leadLifecycle->normalizeLeadPhone($p))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($normalizedPhones === []) {
            return [];
        }

        $query = Lead::query()
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '');

        $query->where(function ($q) use ($normalizedPhones) {
            foreach ($normalizedPhones as $np) {
                $q->orWhere('phone_number', $np)
                    ->orWhere('phone_number', 'like', '%' . $np);
            }
        });

        $allLeads = $query->orderByDesc('id')->get();

        $byNormalized = [];
        foreach ($allLeads as $lead) {
            $key = $this->leadLifecycle->normalizeLeadPhone($lead->phone_number);
            if ($key === null) {
                continue;
            }
            if (!isset($byNormalized[$key])) {
                $byNormalized[$key] = collect();
            }
            $byNormalized[$key]->push($lead);
        }

        $priority = [
            Lead::TYPE_CUSTOMER => 1,
            Lead::TYPE_UNKNOWN => 2,
            Lead::TYPE_PROVIDER => 3,
            Lead::TYPE_FUTURE_CUSTOMER => 4,
            Lead::TYPE_INVALID => 5,
        ];

        $out = [];
        foreach ($byNormalized as $key => $group) {
            $sorted = $group->sortBy(function (Lead $lead) use ($priority) {
                return $priority[$lead->lead_type] ?? 99;
            });
            $primary = $sorted->first();
            if ($primary) {
                $out[$key] = ['id' => (int) $primary->id, 'lead_type' => (string) $primary->lead_type];
            }
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $phones
     * @return array<string, array{chat_status: ?array<string, mixed>, chat_tags: array<int, array<string, mixed>>}>
     */
    private function loadChatMeta(array $phones): array
    {
        if ($phones === [] || !Schema::hasTable('whatsapp_chat_thread_meta')) {
            return [];
        }

        $defaultOpen = WhatsAppChatStatus::query()
            ->where('bucket', 'open')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        $metas = WhatsAppChatThreadMeta::query()
            ->whereIn('phone', $phones)
            ->with('status')
            ->get()
            ->keyBy('phone');

        $tagsByPhone = [];
        if (Schema::hasTable('whatsapp_chat_thread_tags')) {
            $ch = SocialInboxChannel::current();
            $pivotTags = DB::table('whatsapp_chat_thread_tags as tt')
                ->join('whatsapp_chat_tags as t', 'tt.whatsapp_chat_tag_id', '=', 't.id')
                ->whereIn('tt.phone', $phones)
                ->where('t.channel', $ch)
                ->orderBy('t.sort_order')
                ->orderBy('t.id')
                ->get(['tt.phone', 't.id', 't.name', 't.color']);

            foreach ($pivotTags as $tag) {
                $tagsByPhone[$tag->phone][] = [
                    'id' => (int) $tag->id,
                    'name' => (string) $tag->name,
                    'color' => (string) $tag->color,
                ];
            }
        }

        $out = [];
        foreach ($phones as $phone) {
            $meta = $metas->get($phone);
            $appliedId = $meta?->whatsapp_chat_status_id;
            $statusModel = $meta?->status ?? $defaultOpen;
            $chatStatus = null;
            if ($statusModel) {
                $chatStatus = [
                    'id' => (int) $statusModel->id,
                    'name' => (string) $statusModel->name,
                    'bucket' => (string) $statusModel->bucket,
                    'is_implicit' => $appliedId === null && $defaultOpen && (int) $statusModel->id === (int) $defaultOpen->id,
                ];
            }

            $out[$phone] = [
                'chat_status' => $chatStatus,
                'chat_tags' => $tagsByPhone[$phone] ?? [],
            ];
        }

        return $out;
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @return array<int, array<int, array{id: int, name: string, color: string}>>
     */
    private function loadCustomerTagsByLead(Collection $leads): array
    {
        if ($leads->isEmpty() || !Schema::hasTable('lead_customer_tag')) {
            return [];
        }

        $out = [];
        foreach ($leads as $lead) {
            if ($lead->lead_type !== Lead::TYPE_CUSTOMER) {
                continue;
            }
            $out[(int) $lead->id] = $lead->customerLeadTags()
                ->get(['customer_lead_tags.id', 'customer_lead_tags.name', 'customer_lead_tags.color'])
                ->map(fn (CustomerLeadTag $t) => [
                    'id' => (int) $t->id,
                    'name' => (string) $t->name,
                    'color' => (string) ($t->color ?? ''),
                ])
                ->values()
                ->all();
        }

        return $out;
    }

    private function formatDuration(int|float $seconds): string
    {
        $seconds = max(0, (int) round($seconds));

        if ($seconds < 3600) {
            return max(1, (int) round($seconds / 60)) . 'm';
        }
        if ($seconds < 86400) {
            return max(1, (int) round($seconds / 3600)) . 'h';
        }

        return max(1, (int) round($seconds / 86400)) . 'd';
    }

    private function formatHandledBy(mixed $handledBy): string
    {
        $value = trim((string) ($handledBy ?? ''));
        if ($value === '' || strtoupper($value) === 'AI') {
            return 'AI';
        }

        return $value;
    }

    private function truncateText(string $text, int $max): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? $text;
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1) . '…';
    }
}
