<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\WhatsAppBooking;
use Modules\WhatsAppModule\Entities\WhatsAppChatStatus;
use Modules\WhatsAppModule\Entities\WhatsAppChatThreadMeta;
use Modules\WhatsAppModule\Entities\WhatsAppConversation;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\WhatsAppModule\Services\WhatsAppLeadLifecycleService;
use Modules\WhatsAppModule\Support\SocialInboxChannel;

/**
 * Read-only WhatsApp inbox intelligence for the admin Business AI.
 */
class AdminBusinessAiWhatsAppInsightService
{
    public function __construct(
        protected WhatsAppLeadLifecycleService $leadLifecycle,
        protected LeadOpenStatusService $leadOpenStatus,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        try {
            $rows = $this->fetchActiveChatRows();
            $enriched = $this->enrichRows($rows);

            $open = 0;
            $closed = 0;
            $unread = 0;
            $aiHandled = 0;
            $humanHandled = 0;
            $chatUnassignedHuman = 0;
            $humanSupportPending = 0;
            $withLead = 0;
            $withoutLead = 0;
            $leadHandlerBreakdown = [];
            $chatHandlerBreakdown = [];
            $customerLeadChats = 0;
            $providerLeadChats = 0;
            $unassignedChatLeadSamples = [];

            foreach ($enriched as $row) {
                $bucket = (string) ($row->status_bucket ?? 'open');
                if ($bucket === 'closed') {
                    $closed++;
                } else {
                    $open++;
                }
                if ((int) ($row->unread_count ?? 0) > 0) {
                    $unread++;
                }
                if ($row->chat_assigned_to_human) {
                    $humanHandled++;
                } else {
                    $aiHandled++;
                    $chatUnassignedHuman++;
                }
                if ($row->human_support_pending) {
                    $humanSupportPending++;
                }
                if ($row->linked_lead_id) {
                    $withLead++;
                } else {
                    $withoutLead++;
                }

                $lh = (string) ($row->lead_handler_label ?? 'Unassigned');
                $leadHandlerBreakdown[$lh] = ($leadHandlerBreakdown[$lh] ?? 0) + 1;

                $ch = (string) ($row->chat_handler_label ?? 'AI');
                $chatHandlerBreakdown[$ch] = ($chatHandlerBreakdown[$ch] ?? 0) + 1;

                if (($row->linked_lead_type ?? '') === Lead::TYPE_CUSTOMER) {
                    $customerLeadChats++;
                }
                if (($row->linked_lead_type ?? '') === Lead::TYPE_PROVIDER) {
                    $providerLeadChats++;
                }

                if (! $row->chat_assigned_to_human && count($unassignedChatLeadSamples) < 20) {
                    $unassignedChatLeadSamples[] = [
                        'phone' => $row->phone,
                        'display_name' => $row->display_name,
                        'chat_handler' => $row->chat_handler_label,
                        'chat_status' => $row->status_name,
                        'unread_count' => (int) ($row->unread_count ?? 0),
                        'human_support_pending' => (bool) $row->human_support_pending,
                        'linked_lead_id' => $row->linked_lead_id,
                        'linked_lead_type' => $row->linked_lead_type,
                        'linked_lead_open' => $row->linked_lead_open,
                        'lead_handler' => $row->lead_handler_label,
                        'last_message_preview' => mb_substr((string) ($row->message_text ?? ''), 0, 120),
                        'last_activity_at' => $row->created_at,
                    ];
                }
            }

            arsort($leadHandlerBreakdown);
            arsort($chatHandlerBreakdown);

            return [
                'ok' => true,
                'channel' => SocialInboxChannel::current(),
                'window' => 'last_30_days',
                'as_of' => now()->toIso8601String(),
                'total_active_chats' => $enriched->count(),
                'open_chats' => $open,
                'closed_chats' => $closed,
                'unread_chats' => $unread,
                'chats_handled_by_ai' => $aiHandled,
                'chats_handled_by_humans' => $humanHandled,
                'chats_not_assigned_to_human' => $chatUnassignedHuman,
                'human_support_pending' => $humanSupportPending,
                'chats_with_linked_crm_lead' => $withLead,
                'chats_without_linked_crm_lead' => $withoutLead,
                'lead_handlers_for_active_chats' => $leadHandlerBreakdown,
                'chat_handlers_for_active_chats' => $chatHandlerBreakdown,
                'chats_with_customer_crm_lead' => $customerLeadChats,
                'chats_with_provider_crm_lead' => $providerLeadChats,
                'unassigned_chat_samples_with_lead_handlers' => $unassignedChatLeadSamples,
                'notes' => [
                    'chat_not_assigned_to_human' => 'WhatsApp thread handled_by is empty, null, or AI — no admin employee owns the chat.',
                    'lead_handler' => 'CRM Lead.handled_by for the phone-matched lead (may differ from chat handler).',
                    'human_support_pending' => 'Customer asked for a human; chat still with AI until staff takes it.',
                ],
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'whatsapp_unavailable', 'message' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function queryConversations(array $args): array
    {
        try {
            $limit = max(1, min(50, (int) ($args['limit'] ?? 25)));
            $rows = $this->enrichRows($this->fetchActiveChatRows());

            if (! empty($args['search'])) {
                $needle = mb_strtolower(trim((string) $args['search']));
                $digits = preg_replace('/\D+/', '', $needle) ?? '';
                $rows = $rows->filter(function ($row) use ($needle, $digits) {
                    $hay = mb_strtolower(implode(' ', array_filter([
                        (string) ($row->display_name ?? ''),
                        (string) ($row->phone ?? ''),
                        (string) ($row->message_text ?? ''),
                        (string) ($row->lead_handler_label ?? ''),
                        (string) ($row->chat_handler_label ?? ''),
                    ])));
                    if ($needle !== '' && str_contains($hay, $needle)) {
                        return true;
                    }

                    return strlen($digits) >= 3 && str_contains(preg_replace('/\D+/', '', (string) ($row->phone ?? '')) ?? '', $digits);
                });
            }

            $handler = (string) ($args['chat_handler'] ?? 'all');
            if ($handler === 'ai') {
                $rows = $rows->filter(fn ($r) => ! $r->chat_assigned_to_human);
            } elseif ($handler === 'human') {
                $rows = $rows->filter(fn ($r) => $r->chat_assigned_to_human);
            } elseif ($handler === 'human_support_pending') {
                $rows = $rows->filter(fn ($r) => $r->human_support_pending);
            } elseif ($handler === 'unassigned') {
                $rows = $rows->filter(fn ($r) => ! $r->chat_assigned_to_human);
            }

            $bucket = (string) ($args['status_bucket'] ?? 'all');
            if ($bucket === 'open') {
                $rows = $rows->filter(fn ($r) => ($r->status_bucket ?? 'open') !== 'closed');
            } elseif ($bucket === 'closed') {
                $rows = $rows->filter(fn ($r) => ($r->status_bucket ?? '') === 'closed');
            }

            if (! empty($args['has_linked_lead'])) {
                $rows = $rows->filter(fn ($r) => ! empty($r->linked_lead_id));
            }
            if (! empty($args['lead_handler_unassigned'])) {
                $rows = $rows->filter(fn ($r) => ! $r->lead_assigned_to_human);
            }
            if (! empty($args['unread_only'])) {
                $rows = $rows->filter(fn ($r) => (int) ($r->unread_count ?? 0) > 0);
            }

            $leadTypeFilter = strtolower(trim((string) ($args['linked_lead_type'] ?? '')));
            if ($leadTypeFilter !== '' && $leadTypeFilter !== 'all') {
                $rows = $rows->filter(fn ($r) => ($r->linked_lead_type ?? '') === $leadTypeFilter);
            }

            if (! empty($args['chat_handler_employee_id'])) {
                $empId = (string) $args['chat_handler_employee_id'];
                $rows = $rows->filter(function ($r) use ($empId) {
                    $phone = (string) ($r->phone ?? '');
                    $wa = WhatsAppUser::query()->where('phone', $phone)->first(['handled_by']);

                    return $wa && (string) $wa->handled_by === $empId;
                });
            }

            $total = $rows->count();
            $items = $rows->take($limit)->map(fn ($r) => $this->rowToSummary($r))->values()->all();

            return [
                'ok' => true,
                'total_matching' => $total,
                'returned' => count($items),
                'conversations' => $items,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'whatsapp_unavailable', 'message' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function conversationDetails(array $args): array
    {
        try {
            $phone = trim((string) ($args['phone'] ?? ''));
            if ($phone === '') {
                return ['ok' => false, 'error' => 'phone_required'];
            }

            $norm = $this->leadLifecycle->normalizeLeadPhone($phone);
            $waUser = WhatsAppUser::query()->where('phone', $phone)->first();
            $conversation = WhatsAppConversation::query()->where('phone', $phone)->first();

            $messages = WhatsAppMessage::query()
                ->where('phone', $phone)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(['direction', 'message_text', 'message_type', 'created_at', 'admin_seen_at']);

            $leads = $norm
                ? Lead::query()->where('phone_number', $norm)->orderByDesc('id')->limit(10)->get()
                : collect();

            $leadMeta = $this->leadOpenStatus->buildLeadStatusMeta($leads);
            $adminNames = $this->resolveAdminNames(
                $leads->pluck('handled_by')->merge([$waUser?->handled_by])->filter()->all()
            );

            $waBookings = WhatsAppBooking::query()
                ->where('phone', $phone)
                ->orderByDesc('created_at')
                ->limit(8)
                ->get(['booking_id', 'service', 'status', 'lead_id', 'system_booking_id', 'created_at']);

            $leadIds = $leads->pluck('id')->all();
            $systemBookings = ($norm || $leadIds !== [])
                ? Booking::query()
                    ->where(function ($q) use ($norm, $leadIds) {
                        if ($norm) {
                            $q->whereHas('customer', function ($cq) use ($norm) {
                                $cq->where('phone', 'like', '%'.$norm.'%');
                            });
                        }
                        if ($leadIds !== []) {
                            $q->orWhereIn('lead_id', $leadIds);
                        }
                    })
                    ->orderByDesc('created_at')
                    ->limit(8)
                    ->get(['id', 'readable_id', 'booking_status', 'lead_id', 'assignee_id', 'created_at'])
                : collect();

            $assigneeIds = $systemBookings->pluck('assignee_id')->merge($leads->pluck('handled_by'))->filter(fn ($v) => Lead::assigneeIsHuman($v))->unique()->all();
            $assigneeNames = $this->resolveAdminNames($assigneeIds);

            $chatRow = $this->enrichRows($this->fetchActiveChatRows()->filter(fn ($r) => (string) ($r->phone ?? '') === $phone))->first();

            return [
                'ok' => true,
                'phone' => $phone,
                'normalized_phone' => $norm,
                'whatsapp_user' => $waUser ? [
                    'name' => $waUser->name,
                    'type' => $waUser->type,
                    'chat_handler' => $this->handlerLabel($waUser->handled_by, $adminNames),
                    'chat_assigned_to_human' => $this->isHumanHandler($waUser->handled_by),
                    'human_support_requested_at' => $waUser->human_support_requested_at?->toIso8601String(),
                ] : null,
                'conversation_state' => $conversation ? [
                    'active_module' => $conversation->active_module,
                    'current_step' => $conversation->current_step,
                    'active_booking_id' => $conversation->active_booking_id,
                    'active_lead_id' => $conversation->active_lead_id,
                    'ai_unclear_attempts' => $conversation->ai_unclear_attempts,
                ] : null,
                'chat_summary' => $chatRow ? $this->rowToSummary($chatRow) : null,
                'linked_crm_leads' => $leads->map(fn (Lead $l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                    'lead_type' => $l->lead_type,
                    'is_customer_lead' => $l->lead_type === Lead::TYPE_CUSTOMER,
                    'is_provider_lead' => $l->lead_type === Lead::TYPE_PROVIDER,
                    'lead_handler' => $this->handlerLabel($l->handled_by, $adminNames),
                    'lead_handler_id' => Lead::assigneeIsHuman($l->handled_by) ? (string) $l->handled_by : null,
                    'is_open' => (bool) ($leadMeta[$l->id]['is_open'] ?? false),
                    'status_label' => $leadMeta[$l->id]['label'] ?? null,
                    'next_followup_at' => $l->next_followup_at?->toIso8601String(),
                    'received_at' => $l->date_time_of_lead_received?->toIso8601String(),
                    'has_system_booking' => Booking::query()->where('lead_id', $l->id)->exists(),
                ])->values()->all(),
                'system_bookings' => $systemBookings->map(fn (Booking $b) => [
                    'readable_id' => $b->readable_id,
                    'status' => $b->booking_status,
                    'lead_id' => $b->lead_id,
                    'assignee' => $b->assignee_id ? ($assigneeNames[(string) $b->assignee_id] ?? 'Agent') : null,
                    'created_at' => $b->created_at?->toIso8601String(),
                ])->values()->all(),
                'whatsapp_booking_requests' => $waBookings->map(fn ($b) => [
                    'booking_id' => $b->booking_id,
                    'service' => $b->service,
                    'status' => $b->status,
                    'lead_id' => $b->lead_id,
                    'system_booking_id' => $b->system_booking_id,
                    'created_at' => $b->created_at?->toIso8601String(),
                ])->values()->all(),
                'recent_messages' => $messages->map(fn ($m) => [
                    'direction' => $m->direction,
                    'text' => mb_substr((string) ($m->message_text ?? ''), 0, 500),
                    'type' => $m->message_type,
                    'at' => $m->created_at?->toIso8601String(),
                    'seen' => $m->admin_seen_at !== null,
                ])->values()->all(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'whatsapp_unavailable', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return Collection<int, object>
     */
    private function fetchActiveChatRows(): Collection
    {
        $table = config('whatsappmodule.tables.messages', 'whatsapp_messages');
        $cutoff = now()->subDays(30)->format('Y-m-d H:i:s');
        $ch = SocialInboxChannel::current();

        $rows = DB::select("
            SELECT m.phone,
                   m.direction,
                   LEFT(m.message_text, 120) AS message_text,
                   m.created_at,
                   COALESCE(unread.unread_count, 0) AS unread_count
            FROM {$table} m
            INNER JOIN (
                SELECT phone, MAX(created_at) AS max_created
                FROM {$table}
                WHERE created_at >= ?
                  AND channel = ?
                GROUP BY phone
            ) t ON m.phone = t.phone AND m.created_at = t.max_created AND m.channel = ?
            LEFT JOIN (
                SELECT phone, COUNT(*) AS unread_count
                FROM {$table}
                WHERE direction = 'IN'
                  AND (admin_seen_at IS NULL)
                  AND channel = ?
                GROUP BY phone
            ) unread ON unread.phone = m.phone
            WHERE m.channel = ?
            ORDER BY m.created_at DESC
            LIMIT 150
        ", [$cutoff, $ch, $ch, $ch, $ch]);

        return collect($rows);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function enrichRows(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $phones = $rows->pluck('phone')->unique()->filter()->values()->all();
        $waUsers = WhatsAppUser::query()->whereIn('phone', $phones)->get()->keyBy('phone');

        $defaultOpen = WhatsAppChatStatus::query()
            ->where('bucket', 'open')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        $metas = Schema::hasTable('whatsapp_chat_thread_meta')
            ? WhatsAppChatThreadMeta::query()->whereIn('phone', $phones)->with('status')->get()->keyBy('phone')
            : collect();

        $normPhones = collect($phones)->map(fn ($p) => $this->leadLifecycle->normalizeLeadPhone((string) $p))->filter()->unique()->values()->all();
        $leadsByPhone = [];
        if ($normPhones !== []) {
            foreach (Lead::query()->whereIn('phone_number', $normPhones)->orderByDesc('id')->get() as $lead) {
                $key = (string) $lead->phone_number;
                $leadsByPhone[$key] ??= $lead;
            }
        }
        $leadCollection = collect($leadsByPhone)->values();
        $leadMeta = $this->leadOpenStatus->buildLeadStatusMeta($leadCollection);

        $adminIds = collect($waUsers)->pluck('handled_by')
            ->merge($leadCollection->pluck('handled_by'))
            ->filter(fn ($v) => $this->isHumanHandler($v))
            ->unique()
            ->values()
            ->all();
        $adminNames = $this->resolveAdminNames($adminIds);

        return $rows->map(function ($row) use ($waUsers, $metas, $defaultOpen, $leadsByPhone, $leadMeta, $adminNames) {
            $phone = (string) ($row->phone ?? '');
            $wa = $waUsers->get($phone);
            $norm = $this->leadLifecycle->normalizeLeadPhone($phone);
            $lead = $norm ? ($leadsByPhone[$norm] ?? null) : null;

            $handledBy = $wa?->handled_by;
            $row->chat_handler_label = $this->handlerLabel($handledBy, $adminNames);
            $row->chat_assigned_to_human = $this->isHumanHandler($handledBy);
            $row->human_support_pending = $wa
                && $wa->human_support_requested_at
                && ! $row->chat_assigned_to_human;

            $meta = $metas->get($phone);
            $status = $meta?->status ?? $defaultOpen;
            $row->status_name = $status?->name ?? 'Open';
            $row->status_bucket = $status?->bucket ?? 'open';

            $row->display_name = trim((string) ($wa?->name ?? ''));
            if ($row->display_name === '') {
                $row->display_name = $lead?->name ?? $phone;
            }

            $row->linked_lead_id = $lead?->id;
            $row->linked_lead_type = $lead?->lead_type;
            $row->linked_lead_open = $lead ? (bool) ($leadMeta[$lead->id]['is_open'] ?? false) : null;
            $row->lead_handler_label = $lead ? $this->handlerLabel($lead->handled_by, $adminNames) : 'No linked lead';
            $row->lead_assigned_to_human = $lead ? Lead::assigneeIsHuman($lead->handled_by) : false;

            return $row;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function rowToSummary(object $row): array
    {
        return [
            'phone' => $row->phone ?? '',
            'display_name' => $row->display_name ?? null,
            'last_message_preview' => $row->message_text ?? '',
            'last_activity_at' => $row->created_at ?? null,
            'direction' => $row->direction ?? null,
            'unread_count' => (int) ($row->unread_count ?? 0),
            'chat_status' => $row->status_name ?? null,
            'status_bucket' => $row->status_bucket ?? 'open',
            'chat_handler' => $row->chat_handler_label ?? 'AI',
            'chat_assigned_to_human' => (bool) ($row->chat_assigned_to_human ?? false),
            'human_support_pending' => (bool) ($row->human_support_pending ?? false),
            'linked_lead_id' => $row->linked_lead_id ?? null,
            'linked_lead_type' => $row->linked_lead_type ?? null,
            'linked_lead_is_customer' => ($row->linked_lead_type ?? '') === Lead::TYPE_CUSTOMER,
            'linked_lead_is_provider' => ($row->linked_lead_type ?? '') === Lead::TYPE_PROVIDER,
            'linked_lead_open' => $row->linked_lead_open ?? null,
            'lead_handler' => $row->lead_handler_label ?? null,
            'lead_assigned_to_human' => (bool) ($row->lead_assigned_to_human ?? false),
            'chat_handler_differs_from_lead_handler' => ($row->chat_handler_label ?? '') !== ($row->lead_handler_label ?? '')
                && ($row->lead_handler_label ?? '') !== 'No linked lead',
        ];
    }

    /**
     * @param  list<mixed>  $ids
     * @return array<string, string>
     */
    private function resolveAdminNames(array $ids): array
    {
        $humanIds = collect($ids)->filter(fn ($v) => $this->isHumanHandler($v))->unique()->values()->all();
        if ($humanIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $humanIds)
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $u) => [
                (string) $u->id => trim($u->first_name.' '.$u->last_name) ?: 'Agent',
            ])
            ->all();
    }

    private function isHumanHandler(mixed $handledBy): bool
    {
        if ($handledBy === null || $handledBy === '') {
            return false;
        }

        return (string) $handledBy !== Lead::HANDLED_BY_AI;
    }

    /**
     * @param  array<string, string>  $adminNames
     */
    private function handlerLabel(mixed $handledBy, array $adminNames): string
    {
        if (! $this->isHumanHandler($handledBy)) {
            return $handledBy === null || $handledBy === '' ? 'Unassigned (AI default)' : 'AI';
        }

        return $adminNames[(string) $handledBy] ?? 'Agent';
    }
}
