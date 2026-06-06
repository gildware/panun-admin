<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\WhatsAppUser;

class AdminBusinessAiEmployeeInsightService
{
    public function __construct(
        protected LeadOpenStatusService $leadOpenStatus,
        protected AdminBusinessAiLeadInsightService $leadInsights,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function analyze(array $args): array
    {
        $analysis = strtolower(trim((string) ($args['analysis'] ?? 'full_employee_overview')));
        $employeeId = ! empty($args['employee_id']) ? (string) $args['employee_id'] : null;
        $dateFrom = ! empty($args['date_from']) ? Carbon::parse((string) $args['date_from'])->startOfDay() : null;
        $dateTo = ! empty($args['date_to']) ? Carbon::parse((string) $args['date_to'])->endOfDay() : null;

        $employees = $this->loadEmployees($employeeId);
        if ($employees->isEmpty()) {
            return ['ok' => false, 'error' => 'no_employees_found'];
        }

        $employeeIds = $employees->pluck('id')->map(fn ($id) => (string) $id)->all();
        $workloads = $this->buildWorkloads($employeeIds, $dateFrom, $dateTo);

        $payload = [
            'ok' => true,
            'analysis' => $analysis,
            'employees_in_scope' => $employees->count(),
            'date_from' => $dateFrom?->toDateString(),
            'date_to' => $dateTo?->toDateString(),
        ];

        return match ($analysis) {
            'workload_by_employee' => array_merge($payload, [
                'employees' => $this->formatEmployeeWorkloads($employees, $workloads),
            ]),
            'chat_assignments' => array_merge($payload, [
                'whatsapp_chat_assignments' => $this->whatsappAssignmentsByEmployee($employeeIds),
            ]),
            'incomplete_leads_by_handler' => array_merge($payload, [
                'incomplete_leads_by_handler' => $this->incompleteLeadsGroupedByHandler($employeeIds),
            ]),
            'full_employee_overview' => array_merge($payload, [
                'employees' => $this->formatEmployeeWorkloads($employees, $workloads),
                'whatsapp_chat_assignments' => $this->whatsappAssignmentsByEmployee($employeeIds),
                'incomplete_leads_by_handler' => $this->incompleteLeadsGroupedByHandler($employeeIds),
                'unassigned_summary' => $this->unassignedSummary(),
            ]),
            default => [
                'ok' => false,
                'error' => 'unknown_analysis',
                'allowed' => [
                    'workload_by_employee',
                    'chat_assignments',
                    'incomplete_leads_by_handler',
                    'full_employee_overview',
                ],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function queryIncompleteLeads(array $args): array
    {
        $limit = max(1, min(50, (int) ($args['limit'] ?? 25)));
        $leadType = (string) ($args['lead_type'] ?? 'customer');
        $handlerId = ! empty($args['handled_by']) ? (string) $args['handled_by'] : null;
        $openOnly = ! empty($args['open_only']);

        $q = Lead::query()->with(['source:id,name', 'adSource:id,name']);
        if ($leadType !== '' && $leadType !== 'all') {
            $q->where('lead_type', $leadType);
        }
        if ($handlerId) {
            $q->where('handled_by', $handlerId);
        }
        if ($openOnly) {
            $this->leadOpenStatus->restrictQueryToOpenLeads($q);
        }

        $leads = $q->orderByDesc('date_time_of_lead_received')->limit(500)->get();
        $profiles = $this->leadInsights->enrichSummaries($leads);
        $profileById = collect($profiles)->keyBy('id');

        $incomplete = [];
        foreach ($leads as $lead) {
            $profile = $profileById->get($lead->id) ?? [];
            $gaps = $this->detectDataGaps($lead, $profile);
            if ($gaps === []) {
                continue;
            }

            $booking = Booking::query()
                ->where('lead_id', $lead->id)
                ->orderByDesc('created_at')
                ->first(['id', 'readable_id', 'booking_status']);

            $handlerLabel = $this->resolveHandlerLabel($lead->handled_by);

            $incomplete[] = [
                'lead_id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone_number,
                'lead_type' => $lead->lead_type,
                'is_customer_lead' => $lead->lead_type === Lead::TYPE_CUSTOMER,
                'handled_by_id' => $lead->handled_by,
                'handled_by' => $handlerLabel,
                'is_open' => (bool) ($profile['is_open'] ?? false),
                'pipeline_status' => $profile['pipeline_status'] ?? null,
                'missing_fields' => $gaps,
                'missing_count' => count($gaps),
                'has_system_booking' => $booking !== null,
                'booking_readable_id' => $booking?->readable_id,
                'booking_status' => $booking?->booking_status,
                'received_at' => $lead->date_time_of_lead_received?->toIso8601String(),
                'next_followup_at' => $lead->next_followup_at?->toIso8601String(),
            ];
        }

        usort($incomplete, fn ($a, $b) => ($b['missing_count'] ?? 0) <=> ($a['missing_count'] ?? 0));
        $total = count($incomplete);

        return [
            'ok' => true,
            'total_incomplete' => $total,
            'returned' => min($limit, $total),
            'incomplete_leads' => array_slice($incomplete, 0, $limit),
            'notes' => [
                'missing_fields' => 'Fields not captured in lead_type_history or lead record (zone, category, status, handler, etc.).',
                'is_customer_lead' => 'lead_type === customer — may still have a system booking if converted.',
            ],
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function loadEmployees(?string $employeeId)
    {
        $q = User::query()
            ->whereIn('user_type', ['super-admin', 'admin-employee'])
            ->where('is_active', 1)
            ->orderBy('first_name');

        if ($employeeId) {
            $q->where('id', $employeeId);
        }

        return $q->get(['id', 'first_name', 'last_name', 'email', 'user_type']);
    }

    /**
     * @param  list<string>  $employeeIds
     * @return array<string, array<string, mixed>>
     */
    private function buildWorkloads(array $employeeIds, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $workloads = [];
        foreach ($employeeIds as $id) {
            $workloads[$id] = [
                'leads_total' => 0,
                'leads_customer' => 0,
                'leads_provider' => 0,
                'leads_open' => 0,
                'leads_overdue_followup' => 0,
                'bookings_as_assignee' => 0,
                'bookings_active_as_assignee' => 0,
                'booking_followups_scheduled' => 0,
                'whatsapp_chats_assigned' => 0,
                'whatsapp_chats_unread' => 0,
                'outbound_enquiries' => 0,
                'lead_followups_logged' => 0,
                'incomplete_leads' => 0,
            ];
        }

        $leadQ = Lead::query()->whereIn('handled_by', $employeeIds);
        if ($dateFrom) {
            $leadQ->where('date_time_of_lead_received', '>=', $dateFrom);
        }
        if ($dateTo) {
            $leadQ->where('date_time_of_lead_received', '<=', $dateTo);
        }

        foreach ($leadQ->get(['id', 'handled_by', 'lead_type', 'next_followup_at']) as $lead) {
            $hid = (string) $lead->handled_by;
            if (! isset($workloads[$hid])) {
                continue;
            }
            $workloads[$hid]['leads_total']++;
            if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
                $workloads[$hid]['leads_customer']++;
            }
            if ($lead->lead_type === Lead::TYPE_PROVIDER) {
                $workloads[$hid]['leads_provider']++;
            }
        }

        $openLeadQ = Lead::query()->whereIn('handled_by', $employeeIds);
        $this->leadOpenStatus->restrictQueryToOpenLeads($openLeadQ);
        foreach ($openLeadQ->get(['id', 'handled_by']) as $lead) {
            $hid = (string) $lead->handled_by;
            if (isset($workloads[$hid])) {
                $workloads[$hid]['leads_open']++;
            }
        }

        $overdueQ = Lead::query()
            ->whereIn('handled_by', $employeeIds)
            ->whereNotNull('next_followup_at')
            ->where('next_followup_at', '<', now());
        $this->leadOpenStatus->restrictQueryToOpenLeads($overdueQ);
        foreach ($overdueQ->get(['handled_by']) as $lead) {
            $hid = (string) $lead->handled_by;
            if (isset($workloads[$hid])) {
                $workloads[$hid]['leads_overdue_followup']++;
            }
        }

        $bookingQ = Booking::query()->whereIn('assignee_id', $employeeIds);
        if ($dateFrom) {
            $bookingQ->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $bookingQ->where('created_at', '<=', $dateTo);
        }
        foreach ($bookingQ->get(['assignee_id', 'booking_status']) as $b) {
            $hid = (string) $b->assignee_id;
            if (! isset($workloads[$hid])) {
                continue;
            }
            $workloads[$hid]['bookings_as_assignee']++;
            if (in_array($b->booking_status, Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS, true)) {
                $workloads[$hid]['bookings_active_as_assignee']++;
            }
        }

        $bfQ = BookingFollowup::query()
            ->where('status', 'scheduled')
            ->whereHas('booking', function ($bq) use ($employeeIds) {
                $bq->whereIn('assignee_id', $employeeIds)
                    ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS);
            });
        foreach ($bfQ->get(['booking_id']) as $f) {
            $assignee = (string) Booking::query()->where('id', $f->booking_id)->value('assignee_id');
            if (isset($workloads[$assignee])) {
                $workloads[$assignee]['booking_followups_scheduled']++;
            }
        }

        $waUsers = WhatsAppUser::query()->whereIn('handled_by', $employeeIds)->get(['handled_by', 'phone']);
        $unreadByPhone = $this->unreadCountsByPhone($waUsers->pluck('phone')->all());
        foreach ($waUsers as $wa) {
            $hid = (string) $wa->handled_by;
            if (! isset($workloads[$hid])) {
                continue;
            }
            $workloads[$hid]['whatsapp_chats_assigned']++;
            if (($unreadByPhone[$wa->phone] ?? 0) > 0) {
                $workloads[$hid]['whatsapp_chats_unread']++;
            }
        }

        $outQ = LeadOutboundEnquiry::query()->whereIn('handled_by', $employeeIds);
        if ($dateFrom) {
            $outQ->where('contacted_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $outQ->where('contacted_at', '<=', $dateTo);
        }
        foreach ($outQ->get(['handled_by']) as $row) {
            $hid = (string) $row->handled_by;
            if (isset($workloads[$hid])) {
                $workloads[$hid]['outbound_enquiries']++;
            }
        }

        $lfQ = LeadFollowup::query()->whereIn('created_by', $employeeIds);
        if ($dateFrom) {
            $lfQ->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $lfQ->where('created_at', '<=', $dateTo);
        }
        foreach ($lfQ->get(['created_by']) as $row) {
            $hid = (string) $row->created_by;
            if (isset($workloads[$hid])) {
                $workloads[$hid]['lead_followups_logged']++;
            }
        }

        $incompleteByHandler = $this->incompleteLeadsGroupedByHandler($employeeIds);
        foreach ($incompleteByHandler as $row) {
            $hid = (string) ($row['employee_id'] ?? '');
            if (isset($workloads[$hid])) {
                $workloads[$hid]['incomplete_leads'] = (int) ($row['incomplete_lead_count'] ?? 0);
            }
        }

        return $workloads;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $employees
     * @param  array<string, array<string, mixed>>  $workloads
     * @return list<array<string, mixed>>
     */
    private function formatEmployeeWorkloads($employees, array $workloads): array
    {
        return $employees->map(function (User $u) use ($workloads) {
            $id = (string) $u->id;
            $w = $workloads[$id] ?? [];

            return [
                'employee_id' => $id,
                'name' => trim($u->first_name.' '.$u->last_name) ?: $u->email,
                'email' => $u->email,
                'role' => $u->user_type,
                'leads_handled' => (int) ($w['leads_total'] ?? 0),
                'customer_leads_handled' => (int) ($w['leads_customer'] ?? 0),
                'provider_leads_handled' => (int) ($w['leads_provider'] ?? 0),
                'open_leads' => (int) ($w['leads_open'] ?? 0),
                'overdue_lead_followups' => (int) ($w['leads_overdue_followup'] ?? 0),
                'bookings_as_assignee' => (int) ($w['bookings_as_assignee'] ?? 0),
                'active_bookings_as_assignee' => (int) ($w['bookings_active_as_assignee'] ?? 0),
                'booking_followups_scheduled' => (int) ($w['booking_followups_scheduled'] ?? 0),
                'whatsapp_chats_assigned' => (int) ($w['whatsapp_chats_assigned'] ?? 0),
                'whatsapp_chats_unread' => (int) ($w['whatsapp_chats_unread'] ?? 0),
                'outbound_enquiries_handled' => (int) ($w['outbound_enquiries'] ?? 0),
                'lead_followups_logged' => (int) ($w['lead_followups_logged'] ?? 0),
                'incomplete_leads_under_handling' => (int) ($w['incomplete_leads'] ?? 0),
            ];
        })->sortByDesc('leads_handled')->values()->all();
    }

    /**
     * @param  list<string>  $employeeIds
     * @return list<array<string, mixed>>
     */
    private function whatsappAssignmentsByEmployee(array $employeeIds): array
    {
        $rows = WhatsAppUser::query()
            ->whereIn('handled_by', $employeeIds)
            ->get(['phone', 'name', 'handled_by', 'type', 'human_support_requested_at']);

        $phones = $rows->pluck('phone')->all();
        $unreadByPhone = $this->unreadCountsByPhone($phones);
        $leadByPhone = $this->linkedLeadsByPhone($phones);

        $byEmployee = [];
        foreach ($rows as $wa) {
            $hid = (string) $wa->handled_by;
            $norm = $this->normalizePhone($wa->phone);
            $lead = $norm ? ($leadByPhone[$norm] ?? null) : null;

            $byEmployee[$hid]['employee_id'] = $hid;
            $byEmployee[$hid]['chats'][] = [
                'phone' => $wa->phone,
                'display_name' => $wa->name,
                'unread_count' => (int) ($unreadByPhone[$wa->phone] ?? 0),
                'human_support_pending' => $wa->human_support_requested_at !== null,
                'linked_lead_id' => $lead?->id,
                'linked_lead_type' => $lead?->lead_type,
                'linked_lead_is_customer' => $lead?->lead_type === Lead::TYPE_CUSTOMER,
                'lead_handler' => $lead ? $this->resolveHandlerLabel($lead->handled_by) : null,
            ];
            $byEmployee[$hid]['chat_count'] = count($byEmployee[$hid]['chats']);
        }

        $employees = User::query()->whereIn('id', array_keys($byEmployee))->get()->keyBy(fn ($u) => (string) $u->id);
        $result = [];
        foreach ($byEmployee as $hid => $data) {
            $u = $employees->get($hid);
            $result[] = [
                'employee_id' => $hid,
                'employee_name' => $u ? trim($u->first_name.' '.$u->last_name) : 'Agent',
                'chat_count' => $data['chat_count'],
                'chats' => array_slice($data['chats'], 0, 15),
            ];
        }

        usort($result, fn ($a, $b) => ($b['chat_count'] ?? 0) <=> ($a['chat_count'] ?? 0));

        return $result;
    }

    /**
     * @param  list<string>  $employeeIds
     * @return list<array<string, mixed>>
     */
    private function incompleteLeadsGroupedByHandler(array $employeeIds): array
    {
        $leads = Lead::query()
            ->whereIn('handled_by', $employeeIds)
            ->orderByDesc('date_time_of_lead_received')
            ->limit(800)
            ->get();

        $profiles = collect($this->leadInsights->enrichSummaries($leads))->keyBy('id');
        $counts = [];
        $samples = [];

        foreach ($leads as $lead) {
            $profile = $profiles->get($lead->id) ?? [];
            $gaps = $this->detectDataGaps($lead, $profile);
            if ($gaps === []) {
                continue;
            }
            $hid = (string) $lead->handled_by;
            $counts[$hid] = ($counts[$hid] ?? 0) + 1;
            if (! isset($samples[$hid]) || count($samples[$hid]) < 5) {
                $booking = Booking::query()->where('lead_id', $lead->id)->value('readable_id');
                $samples[$hid][] = [
                    'lead_id' => $lead->id,
                    'name' => $lead->name,
                    'lead_type' => $lead->lead_type,
                    'is_customer' => $lead->lead_type === Lead::TYPE_CUSTOMER,
                    'missing_fields' => $gaps,
                    'has_booking' => $booking !== null,
                    'booking_readable_id' => $booking,
                ];
            }
        }

        $users = User::query()->whereIn('id', array_keys($counts))->get()->keyBy(fn ($u) => (string) $u->id);
        $rows = [];
        foreach ($counts as $hid => $cnt) {
            $u = $users->get($hid);
            $rows[] = [
                'employee_id' => $hid,
                'employee_name' => $u ? trim($u->first_name.' '.$u->last_name) : 'Agent',
                'incomplete_lead_count' => $cnt,
                'sample_incomplete_leads' => $samples[$hid] ?? [],
            ];
        }
        usort($rows, fn ($a, $b) => ($b['incomplete_lead_count'] ?? 0) <=> ($a['incomplete_lead_count'] ?? 0));

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function unassignedSummary(): array
    {
        $unassignedLeads = Lead::query()->where(function ($w) {
            $w->whereNull('handled_by')->orWhere('handled_by', '')->orWhere('handled_by', Lead::HANDLED_BY_AI);
        })->count();

        $openUnassigned = Lead::query()->where(function ($w) {
            $w->whereNull('handled_by')->orWhere('handled_by', '')->orWhere('handled_by', Lead::HANDLED_BY_AI);
        });
        $this->leadOpenStatus->restrictQueryToOpenLeads($openUnassigned);

        $waAi = WhatsAppUser::query()->where(function ($w) {
            $w->whereNull('handled_by')->orWhere('handled_by', '')->orWhere('handled_by', Lead::HANDLED_BY_AI);
        })->count();

        $bookingsNoAssignee = Booking::query()
            ->whereNull('assignee_id')
            ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS)
            ->count();

        return [
            'leads_unassigned_or_ai' => $unassignedLeads,
            'open_leads_unassigned_or_ai' => $openUnassigned->count(),
            'whatsapp_chats_ai_or_unassigned' => $waAi,
            'active_bookings_without_assignee' => $bookingsNoAssignee,
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return list<string>
     */
    private function detectDataGaps(Lead $lead, array $profile): array
    {
        $gaps = [];

        if (trim((string) $lead->name) === '') {
            $gaps[] = 'name';
        }
        if (! Lead::assigneeIsHuman($lead->handled_by)) {
            $gaps[] = 'employee_handler';
        }
        if (! $lead->source_id) {
            $gaps[] = 'source';
        }
        if (trim((string) ($lead->remarks ?? '')) === '' && $lead->lead_type === Lead::TYPE_UNKNOWN) {
            $gaps[] = 'remarks';
        }

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $c = is_array($profile['customer'] ?? null) ? $profile['customer'] : [];
            if (empty($c['status'])) {
                $gaps[] = 'customer_status';
            }
            if (empty($c['zone'])) {
                $gaps[] = 'zone';
            }
            if (empty($c['service_category'])) {
                $gaps[] = 'service_category';
            }
            if (empty($c['service_subcategory'])) {
                $gaps[] = 'service_subcategory';
            }
            if (empty($c['service']) && empty($c['service_description'])) {
                $gaps[] = 'service';
            }
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $p = is_array($profile['provider'] ?? null) ? $profile['provider'] : [];
            if (empty($p['status'])) {
                $gaps[] = 'provider_status';
            }
            if (empty($p['zones'])) {
                $gaps[] = 'zones';
            }
            if (empty($p['service_category'])) {
                $gaps[] = 'service_category';
            }
        }

        if ($lead->lead_type === Lead::TYPE_INVALID) {
            $i = is_array($profile['invalid'] ?? null) ? $profile['invalid'] : [];
            if (empty($i['reason'])) {
                $gaps[] = 'invalid_reason';
            }
        }

        if ($lead->lead_type === Lead::TYPE_FUTURE_CUSTOMER) {
            $f = is_array($profile['future_customer'] ?? null) ? $profile['future_customer'] : [];
            if (empty($f['reason'])) {
                $gaps[] = 'future_customer_reason';
            }
        }

        return $gaps;
    }

    /**
     * @param  list<string>  $phones
     * @return array<string, int>
     */
    private function unreadCountsByPhone(array $phones): array
    {
        if ($phones === []) {
            return [];
        }
        $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        $ch = config('whatsappmodule.channel', 'whatsapp');

        $rows = DB::table($table)
            ->select('phone', DB::raw('COUNT(*) as cnt'))
            ->whereIn('phone', $phones)
            ->where('direction', 'IN')
            ->whereNull('admin_seen_at')
            ->where('channel', $ch)
            ->groupBy('phone')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(string) $r->phone] = (int) $r->cnt;
        }

        return $map;
    }

    /**
     * @param  list<string>  $phones
     * @return array<string, Lead>
     */
    private function linkedLeadsByPhone(array $phones): array
    {
        $norms = collect($phones)->map(fn ($p) => $this->normalizePhone($p))->filter()->unique()->values()->all();
        if ($norms === []) {
            return [];
        }

        $map = [];
        foreach (Lead::query()->whereIn('phone_number', $norms)->orderByDesc('id')->get() as $lead) {
            $key = (string) $lead->phone_number;
            $map[$key] ??= $lead;
        }

        return $map;
    }

    private function resolveHandlerLabel(mixed $handledBy): string
    {
        if (! Lead::assigneeIsHuman($handledBy)) {
            return $handledBy === Lead::HANDLED_BY_AI ? 'AI' : 'Unassigned';
        }

        $u = User::query()->find((string) $handledBy, ['first_name', 'last_name', 'email']);

        return $u ? (trim($u->first_name.' '.$u->last_name) ?: $u->email) : 'Agent';
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
}
