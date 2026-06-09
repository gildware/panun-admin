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
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupAutomationRule;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupDispatch;
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

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['items'], $cached['total'])) {
            return new Paginator(
                collect($cached['items']),
                (int) $cached['total'],
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $rows = $this->fetchSilentAfterAiBaseRows($filters);
        $enriched = $this->enrichCandidates($rows);
        $filtered = $this->applyFilters($enriched, $filters);

        $filtered = $filtered->sortByDesc(fn (array $c) => $c['silent_since_ts'] ?? 0)->values();

        $total = $filtered->count();
        $slice = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

        Cache::put($cacheKey, [
            'items' => $slice->all(),
            'total' => $total,
        ], (int) config('services.omnidimension.cache_whatsapp_followup_list_ttl', 60));

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
        $page = 1;
        $all = collect();

        do {
            $remaining = $max - $all->count();
            if ($remaining <= 0) {
                break;
            }

            $paginator = $this->search($filters, $page, min(100, $remaining));
            $all = $all->merge($paginator->getCollection());

            if (!$paginator->hasMorePages()) {
                break;
            }

            $page++;
        } while (true);

        return $all->take($max)->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function fetchSilentAfterAiBaseRows(array $filters): Collection
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

        return $query->orderByDesc('m.created_at')->get();
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

        $chatMeta = $this->loadChatMeta($phones);
        $customerTagsByLead = $this->loadCustomerTagsByLead($leads);
        $lastDispatch = WhatsAppVoiceFollowupDispatch::latestAttemptAtByWaPhone($phones);

        return $rows->map(function ($row) use ($waUsers, $leadByNormalized, $leads, $leadStatusMeta, $chatMeta, $customerTagsByLead, $lastDispatch) {
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
            $lastAi = trim((string) ($row->last_ai_message ?? ''));

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
                'lead_status_label' => $statusMeta['label'] ?? ($leadOpen ? 'Open' : 'Closed'),
                'lead_status_badge' => $statusMeta['badge_class'] ?? ($leadOpen ? 'bg-danger' : 'bg-success'),
                'lead_url' => $leadId ? route('admin.lead.show', $leadId) : null,
                'chat_status' => $meta['chat_status'],
                'chat_tags' => $meta['chat_tags'],
                'customer_lead_tags' => $leadId ? ($customerTagsByLead[$leadId] ?? []) : [],
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
        $leadTypes = array_filter((array) ($filters['lead_types'] ?? []));
        $leadOpenFilter = (string) ($filters['lead_open'] ?? '');
        $waBucket = (string) ($filters['wa_chat_bucket'] ?? '');
        $waTagIds = array_map('intval', array_filter((array) ($filters['wa_chat_tag_ids'] ?? [])));
        $customerTagIds = array_map('intval', array_filter((array) ($filters['customer_lead_tag_ids'] ?? [])));
        $handledBy = (string) ($filters['handled_by'] ?? '');
        $handledByEmployeeIds = array_values(array_filter((array) ($filters['handled_by_employee_ids'] ?? [])));
        $humanSupport = (string) ($filters['human_support'] ?? '');
        $excludeCalledHours = max(0, (int) ($filters['exclude_called_within_hours'] ?? 0));
        $phonesFilter = array_values(array_filter((array) ($filters['phones'] ?? [])));
        $skipOtherCron = !empty($filters['_skip_other_cron_filter']);
        $otherCronMode = (string) ($filters['other_cron_job_mode'] ?? '');
        $otherCronIds = array_map('intval', array_filter((array) ($filters['other_cron_job_ids'] ?? [])));
        $currentRuleId = (int) ($filters['_current_rule_id'] ?? 0);

        $otherCronPhoneSet = [];
        if (!$skipOtherCron && $otherCronMode !== '') {
            if ($otherCronMode === 'exclude_all_active') {
                $otherCronPhoneSet = array_flip($this->resolvePhonesForAllActiveOtherCronJobs($currentRuleId));
            } elseif ($otherCronIds !== []) {
                $otherCronPhoneSet = array_flip($this->resolvePhonesForOtherCronJobs($otherCronIds, $currentRuleId));
            }
        }

        return $candidates->filter(function (array $c) use (
            $leadTypes,
            $leadOpenFilter,
            $waBucket,
            $waTagIds,
            $customerTagIds,
            $handledBy,
            $handledByEmployeeIds,
            $humanSupport,
            $excludeCalledHours,
            $phonesFilter,
            $otherCronMode,
            $otherCronPhoneSet
        ) {
            if ($phonesFilter !== [] && !in_array((string) ($c['phone'] ?? ''), $phonesFilter, true)) {
                return false;
            }

            if ($leadTypes !== [] && !in_array((string) ($c['lead_type'] ?? ''), $leadTypes, true)) {
                return false;
            }

            if ($leadOpenFilter === 'open' && empty($c['lead_open'])) {
                return false;
            }
            if ($leadOpenFilter === 'closed' && !empty($c['lead_open'])) {
                return false;
            }

            $bucket = is_array($c['chat_status'] ?? null) ? (string) ($c['chat_status']['bucket'] ?? 'open') : 'open';
            if ($waBucket === 'open' && $bucket !== 'open') {
                return false;
            }
            if ($waBucket === 'closed' && $bucket !== 'closed') {
                return false;
            }

            if ($waTagIds !== []) {
                $have = collect($c['chat_tags'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
                if (array_intersect($waTagIds, $have) === []) {
                    return false;
                }
            }

            if ($customerTagIds !== []) {
                $have = collect($c['customer_lead_tags'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
                if (array_intersect($customerTagIds, $have) === []) {
                    return false;
                }
            }

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
                if ($handledByEmployeeIds !== [] && !in_array($hb, $handledByEmployeeIds, true)) {
                    return false;
                }
            }

            if ($humanSupport === 'exclude' && !empty($c['human_support_requested_at'])) {
                return false;
            }
            if ($humanSupport === 'only' && empty($c['human_support_requested_at'])) {
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

            if (in_array($otherCronMode, ['exclude', 'exclude_all_active'], true) && $otherCronPhoneSet !== []) {
                if (isset($otherCronPhoneSet[(string) ($c['phone'] ?? '')])) {
                    return false;
                }
            }

            if ($otherCronMode === 'include' && $otherCronPhoneSet !== []) {
                if (!isset($otherCronPhoneSet[(string) ($c['phone'] ?? '')])) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolveSilentMinMinutes(array $filters): int
    {
        if (isset($filters['silent_min_minutes']) && $filters['silent_min_minutes'] !== '') {
            return max(0, (int) $filters['silent_min_minutes']);
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

        return $this->resolvePhonesForOtherCronJobs($ruleIds, $excludeRuleId);
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

        $rules = WhatsAppVoiceFollowupAutomationRule::query()
            ->whereIn('id', $ruleIds)
            ->orderBy('id')
            ->get();

        $phones = [];
        foreach ($rules as $rule) {
            $ruleFilters = $rule->normalizedFilters();
            $ruleFilters['_skip_other_cron_filter'] = true;

            foreach ($this->collectAll($ruleFilters, 5000) as $candidate) {
                $phone = (string) ($candidate['phone'] ?? '');
                if ($phone !== '') {
                    $phones[$phone] = true;
                }
            }
        }

        return array_keys($phones);
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

    private function formatDuration(int $seconds): string
    {
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
