<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingChangeLog;
use Modules\BookingModule\Entities\BookingComment;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadChangeLog;
use Modules\LeadManagement\Entities\LeadComment;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\TaskBoardModule\Entities\TaskColumn;
use Modules\TaskBoardModule\Entities\TaskTicket;
use Modules\TaskBoardModule\Entities\TaskTicketComment;
use Modules\UserManagement\Entities\User;

class OpenWorkService
{
    private const PER_SOURCE_LIMIT = 60;

    private const MAX_ITEMS = 150;

    public function __construct(
        private readonly LeadOpenStatusService $leadOpenStatus,
    ) {}

    /**
     * @return array{
     *     items: list<array<string, mixed>>,
     *     employees: list<array{id: string, name: string}>,
     *     stats: array{open: int, overdue: int, unassigned: int, oldest_hours: int},
     *     employee_scope: bool
     * }
     */
    public function build(User $user): array
    {
        $employeeScope = $user->user_type === 'admin-employee';
        $userId = (string) $user->id;
        $today = Carbon::today();
        $now = Carbon::now();

        $employees = $this->dashboardEmployees();
        $namesById = $this->employeeNameMap($employees);

        /** @var array<string, array<string, mixed>> $byKey */
        $byKey = [];

        $this->mergeLeadFollowups($byKey, $today, $now, $namesById);
        $this->mergeHuntingLeads($byKey, $today, $now, $namesById);
        $this->mergeUnassignedLeads($byKey, $today, $now, $namesById);
        $this->mergeActiveBookings($byKey, $today, $now, $namesById);
        $this->mergePendingTasks($byKey, $today, $now, $namesById);

        $items = array_values($byKey);

        if ($employeeScope) {
            $items = array_values(array_filter(
                $items,
                static function (array $item) use ($userId): bool {
                    $ownerId = (string) ($item['owner_id'] ?? '');
                    if ($ownerId === '' || $ownerId === 'unassigned') {
                        return true;
                    }
                    if ($ownerId === $userId) {
                        return true;
                    }
                    $ownerIds = $item['owner_ids'] ?? null;
                    if (is_array($ownerIds) && in_array($userId, $ownerIds, true)) {
                        return true;
                    }

                    return false;
                }
            ));
        }

        usort($items, static fn (array $a, array $b): int => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
        $items = array_slice($items, 0, self::MAX_ITEMS);
        $this->hydrateWorkDetails($items);
        $this->hydrateLastComments($items);
        $this->hydrateLastHandled($items, $namesById);

        foreach ($items as $i => &$item) {
            $item['rank'] = $i + 1;
            unset($item['owner_ids']);
        }
        unset($item);

        // Default list order: follow-up oldest first (missing due dates last).
        usort($items, static function (array $a, array $b): int {
            $aDue = $a['followup_at'] ?? null;
            $bDue = $b['followup_at'] ?? null;
            if ($aDue === null && $bDue === null) {
                return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
            }
            if ($aDue === null) {
                return 1;
            }
            if ($bDue === null) {
                return -1;
            }

            return $aDue <=> $bDue;
        });

        $overdue = count(array_filter($items, static fn (array $i) => ! empty($i['is_overdue'])));
        $unassigned = count(array_filter($items, static fn (array $i) => ($i['owner_id'] ?? '') === 'unassigned'));
        $oldest = 0;
        foreach ($items as $item) {
            $oldest = max($oldest, (int) ($item['age_hours'] ?? 0));
        }

        return [
            'items' => $items,
            'employees' => $this->formatEmployeeOptions($employees),
            'stats' => [
                'open' => count($items),
                'overdue' => $overdue,
                'unassigned' => $unassigned,
                'oldest_hours' => $oldest,
            ],
            'employee_scope' => $employeeScope,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $byKey
     * @param  array<string, string>  $namesById
     */
    private function mergeLeadFollowups(array &$byKey, Carbon $today, Carbon $now, array $namesById): void
    {
        $base = Lead::query()
            ->whereNotNull('next_followup_at')
            ->whereDate('next_followup_at', '<=', $today);

        $this->leadOpenStatus->restrictQueryToOpenLeads($base);

        $leads = (clone $base)
            ->with('latestFollowup')
            ->orderBy('next_followup_at')
            ->take(self::PER_SOURCE_LIMIT)
            ->get();

        foreach ($leads as $lead) {
            $this->putLeadItem($byKey, $lead, $today, $now, $namesById, preferHunt: false);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $byKey
     * @param  array<string, string>  $namesById
     */
    private function mergeHuntingLeads(array &$byKey, Carbon $today, Carbon $now, array $namesById): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'hunting_status')) {
            return;
        }

        $leads = Lead::query()
            ->where('hunting_status', Lead::HUNTING_PUBLISHED)
            ->with('latestFollowup')
            ->orderByDesc('hunting_started_at')
            ->take(self::PER_SOURCE_LIMIT)
            ->get();

        foreach ($leads as $lead) {
            $this->putLeadItem($byKey, $lead, $today, $now, $namesById, preferHunt: true);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $byKey
     * @param  array<string, string>  $namesById
     */
    private function mergeUnassignedLeads(array &$byKey, Carbon $today, Carbon $now, array $namesById): void
    {
        $base = Lead::query()->where(function ($w) {
            $w->whereNull('handled_by')
                ->orWhere('handled_by', '')
                ->orWhere('handled_by', Lead::HANDLED_BY_AI);
        });
        $this->leadOpenStatus->restrictQueryToOpenLeads($base);

        $leads = (clone $base)
            ->with('latestFollowup')
            ->orderByDesc('date_time_of_lead_received')
            ->take(self::PER_SOURCE_LIMIT)
            ->get();

        foreach ($leads as $lead) {
            $this->putLeadItem($byKey, $lead, $today, $now, $namesById, preferHunt: false);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $byKey
     * @param  array<string, string>  $namesById
     */
    private function putLeadItem(
        array &$byKey,
        Lead $lead,
        Carbon $today,
        Carbon $now,
        array $namesById,
        bool $preferHunt,
    ): void {
        $key = 'lead:'.$lead->id;
        $isHunt = ($lead->hunting_status ?? Lead::HUNTING_OFF) === Lead::HUNTING_PUBLISHED;
        $type = ($preferHunt || $isHunt) ? 'hunt' : 'lead';

        $openedAt = $lead->date_time_of_lead_received
            ?? $lead->hunting_started_at
            ?? $lead->created_at
            ?? $now;
        $ageHours = max(0, (int) $openedAt->diffInHours($now));
        $dueAt = $lead->next_followup_at;
        $isOverdue = $dueAt !== null && $dueAt->lt($today->copy()->startOfDay());

        $urgency = $lead->latestFollowup?->urgency
            ?? ($isOverdue || $ageHours >= 48
                ? BookingFollowup::URGENCY_HIGH
                : ($ageHours >= 24 ? BookingFollowup::URGENCY_MEDIUM : BookingFollowup::URGENCY_LOW));

        $ownerId = Lead::assigneeIsHuman($lead->handled_by) ? (string) $lead->handled_by : 'unassigned';
        $ownerLabel = $ownerId === 'unassigned'
            ? translate('Unassigned')
            : ($namesById[$ownerId] ?? translate('Unassigned'));

        $typeLabel = Lead::leadTypes()[$lead->lead_type] ?? translate('Open');
        $tempKey = (string) ($lead->lead_temperature ?? Lead::TEMPERATURE_WARM);
        $tempLabel = Lead::leadTemperatureShortLabel($lead->lead_temperature ?? null);

        [$nextStep, $nextHint] = $this->leadNextFollowupDetail($lead, $dueAt, $isOverdue, $isHunt);

        $details = [
            $this->detailChip($typeLabel, $this->leadTypeTone((string) $lead->lead_type)),
        ];
        if ($tempLabel !== '—') {
            $details[] = $this->detailChip($tempLabel, 'temp-'.$tempKey);
        }
        if ($isHunt) {
            $details[] = $this->detailChip(translate('Hunting'), 'hunt');
        }

        $item = [
            'key' => $key,
            'type' => $type,
            'lead_type' => (string) ($lead->lead_type ?: Lead::TYPE_UNKNOWN),
            'booking_status' => null,
            'ref' => 'L-'.$lead->id,
            'name' => trim((string) ($lead->name ?: $lead->phone_number ?: translate('Lead'))),
            'owner_id' => $ownerId,
            'owner_label' => $ownerLabel,
            'status' => $isHunt ? translate('Hunting') : $typeLabel,
            'status_tone' => $isHunt ? 'hunt' : $this->leadTypeTone((string) $lead->lead_type),
            'status_color' => null,
            'details' => $details,
            'next_step' => $nextStep,
            'next_hint' => $nextHint,
            'last_comment' => '',
            'last_handled_by' => '',
            'last_handled_at' => null,
            'age_hours' => $ageHours,
            'followup_at' => $this->followupTimestamp($dueAt),
            'urgency' => $urgency,
            'is_overdue' => $isOverdue || ($ownerId === 'unassigned' && $ageHours >= 24),
            'url' => route('admin.lead.show', $lead->id),
            'score' => $this->score($isOverdue || ($ownerId === 'unassigned' && $ageHours >= 24), $ownerId === 'unassigned', (string) $urgency, $ageHours),
        ];

        if (! isset($byKey[$key])) {
            $byKey[$key] = $item;

            return;
        }

        $existing = $byKey[$key];
        $merged = (($item['score'] ?? 0) > ($existing['score'] ?? 0)) ? $item : $existing;
        if (($existing['type'] ?? '') === 'hunt' || ($item['type'] ?? '') === 'hunt') {
            $merged['type'] = 'hunt';
            // Hunting stays as a Work chip only; lead status is set in hydrateWorkDetails.
        }
        $byKey[$key] = $merged;
    }

    /**
     * @param  array<string, array<string, mixed>>  $byKey
     * @param  array<string, string>  $namesById
     */
    private function mergeActiveBookings(array &$byKey, Carbon $today, Carbon $now, array $namesById): void
    {
        $bookings = Booking::query()
            ->whereIn('booking_status', Booking::STATUSES_FOR_SCHEDULED_FOLLOWUP_LISTS)
            ->with([
                'customer',
                'assignee',
                'category',
                'subCategory',
                'followups' => fn ($q) => $q->where('status', 'scheduled')->orderBy('date'),
            ])
            ->orderByDesc('created_at')
            ->take(self::PER_SOURCE_LIMIT)
            ->get();

        foreach ($bookings as $booking) {
            $key = 'booking:'.$booking->id;
            $openedAt = $booking->created_at ?? $now;
            $ageHours = max(0, (int) $openedAt->diffInHours($now));
            $followup = $booking->followups->first();
            $dueAt = $followup?->date;
            $isOverdue = $dueAt !== null && Carbon::parse($dueAt)->lt($today->copy()->startOfDay());

            $ownerId = $booking->assignee_id ? (string) $booking->assignee_id : 'unassigned';
            $ownerLabel = $ownerId === 'unassigned'
                ? translate('Unassigned')
                : $this->displayName($booking->assignee, $namesById[$ownerId] ?? translate('Unassigned'));

            $customerName = $booking->customer
                ? trim(($booking->customer->first_name ?? '').' '.($booking->customer->last_name ?? ''))
                : '';
            $name = $customerName !== ''
                ? $customerName
                : (string) ($booking->readable_id ?? translate('Booking'));

            $urgency = $followup?->urgency
                ?? ($ownerId === 'unassigned' && $ageHours >= 24
                    ? BookingFollowup::URGENCY_HIGH
                    : ($ageHours >= 48 ? BookingFollowup::URGENCY_HIGH : BookingFollowup::URGENCY_MEDIUM));

            [$nextStep, $nextHint] = $this->bookingNextFollowupDetail($followup, $dueAt, $isOverdue);

            $bookingStatusKey = strtolower((string) ($booking->booking_status ?? 'pending'));
            $bookingStatus = match ($bookingStatusKey) {
                'on_hold' => translate('On_Hold'),
                'pending' => translate('Pending'),
                'accepted' => translate('Accepted'),
                'ongoing' => translate('Ongoing'),
                default => translate(ucfirst(str_replace('_', ' ', $bookingStatusKey))),
            };
            $categoryName = trim((string) ($booking->category?->name ?? ''));
            $subCategoryName = trim((string) ($booking->subCategory?->name ?? ''));
            $details = [
                $this->detailChip($bookingStatus, 'booking-'.$bookingStatusKey),
            ];
            if ($categoryName !== '') {
                $details[] = $this->detailChip($categoryName, 'category');
            }
            if ($subCategoryName !== '') {
                $details[] = $this->detailChip($subCategoryName, 'category');
            }

            $byKey[$key] = [
                'key' => $key,
                'type' => 'booking',
                'lead_type' => null,
                'booking_status' => $bookingStatusKey,
                'ref' => 'B-'.($booking->readable_id ?: $booking->id),
                'name' => $name,
                'owner_id' => $ownerId,
                'owner_label' => $ownerLabel,
                'status' => $bookingStatus,
                'status_tone' => 'booking-'.$bookingStatusKey,
                'status_color' => null,
                'details' => $details,
                'next_step' => $nextStep,
                'next_hint' => $nextHint,
                'last_comment' => '',
                'last_handled_by' => '',
                'last_handled_at' => null,
                'age_hours' => $ageHours,
                'followup_at' => $this->followupTimestamp($dueAt),
                'urgency' => $urgency,
                'is_overdue' => $isOverdue || ($ownerId === 'unassigned' && $ageHours >= 24),
                'url' => route('admin.booking.details', [$booking->id, 'web_page' => 'followups']),
                'score' => $this->score(
                    $isOverdue || ($ownerId === 'unassigned' && $ageHours >= 24),
                    $ownerId === 'unassigned',
                    (string) $urgency,
                    $ageHours,
                ),
            ];
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $byKey
     * @param  array<string, string>  $namesById
     */
    private function mergePendingTasks(array &$byKey, Carbon $today, Carbon $now, array $namesById): void
    {
        if (! Schema::hasTable('task_tickets')) {
            return;
        }

        $doneColumnIds = TaskColumn::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%done%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%complete%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%closed%']);
            })
            ->pluck('id')
            ->all();

        $tasks = TaskTicket::query()
            ->with(['column', 'assignees'])
            ->when($doneColumnIds !== [], fn ($q) => $q->whereNotIn('column_id', $doneColumnIds))
            ->orderByRaw('CASE WHEN end_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('end_date')
            ->take(self::PER_SOURCE_LIMIT)
            ->get();

        foreach ($tasks as $task) {
            $key = 'task:'.$task->id;
            $openedAt = $task->start_date ?? $task->created_at ?? $now;
            $ageHours = max(0, (int) Carbon::parse($openedAt)->diffInHours($now));
            $isOverdue = $task->end_date !== null && $task->end_date->lt($today->copy()->startOfDay());

            $assignees = $task->assignees;
            $ownerIds = $assignees->map(fn (User $u) => (string) $u->id)->values()->all();
            $ownerId = $ownerIds[0] ?? 'unassigned';
            $ownerLabel = $assignees->isNotEmpty()
                ? $assignees->map(fn (User $u) => $this->displayName($u, ''))->filter()->join(', ')
                : translate('Unassigned');
            if ($ownerLabel === '') {
                $ownerLabel = translate('Unassigned');
                $ownerId = 'unassigned';
            }

            $urgency = $isOverdue
                ? BookingFollowup::URGENCY_HIGH
                : ($task->end_date?->isToday() ? BookingFollowup::URGENCY_MEDIUM : BookingFollowup::URGENCY_LOW);

            $byKey[$key] = [
                'key' => $key,
                'type' => 'task',
                'lead_type' => null,
                'booking_status' => null,
                'ref' => 'T-'.substr((string) $task->id, 0, 8),
                'name' => (string) ($task->title ?: translate('Task')),
                'owner_id' => $ownerId,
                'owner_ids' => $ownerIds,
                'owner_label' => $ownerLabel,
                'status' => (string) ($task->column->name ?? translate('To_Do')),
                'status_tone' => 'task',
                'status_color' => null,
                'details' => [
                    $this->detailChip((string) ($task->column->name ?? translate('To_Do')), 'task'),
                ],
                'next_step' => translate('Complete_task'),
                'next_hint' => $task->end_date
                    ? translate('Due').': '.$task->end_date->format('d M')
                    : translate('No_due_date'),
                'last_comment' => '',
                'last_handled_by' => '',
                'last_handled_at' => null,
                'age_hours' => $ageHours,
                'followup_at' => $this->followupTimestamp($task->end_date),
                'urgency' => $urgency,
                'is_overdue' => $isOverdue,
                'url' => route('admin.task-board.index'),
                'score' => $this->score($isOverdue, $ownerId === 'unassigned', (string) $urgency, $ageHours),
            ];
        }
    }

    /**
     * Enrich lead/booking rows with status, type, temperature, category, etc.
     *
     * @param  list<array<string, mixed>>  $items
     */
    private function hydrateWorkDetails(array &$items): void
    {
        $leadIds = [];
        foreach ($items as $item) {
            $key = (string) ($item['key'] ?? '');
            if (str_starts_with($key, 'lead:')) {
                $leadIds[] = (int) substr($key, 5);
            }
        }
        $leadIds = array_values(array_unique(array_filter($leadIds)));
        if ($leadIds === []) {
            return;
        }

        $leads = Lead::query()->whereIn('id', $leadIds)->get(['id', 'lead_type', 'lead_temperature']);
        $openMeta = $this->leadOpenStatus->buildLeadStatusMeta($leads);

        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->orderByDesc('created_at')
            ->get(['lead_id', 'type', 'data', 'created_at']);

        /** @var array<int, LeadTypeHistory> $latestHistory */
        $latestHistory = [];
        foreach ($histories as $history) {
            $lid = (int) $history->lead_id;
            if (! isset($latestHistory[$lid])) {
                $latestHistory[$lid] = $history;
            }
        }

        $customerStatusIds = [];
        $providerStatusIds = [];
        $categoryIds = [];
        foreach ($latestHistory as $history) {
            $data = is_array($history->data) ? $history->data : [];
            if (($history->type ?? '') === Lead::TYPE_CUSTOMER && ! empty($data['customer_lead_status_id'])) {
                $customerStatusIds[] = (int) $data['customer_lead_status_id'];
            }
            if (($history->type ?? '') === Lead::TYPE_PROVIDER && ! empty($data['provider_lead_status_id'])) {
                $providerStatusIds[] = (int) $data['provider_lead_status_id'];
            }
            foreach (['service_category', 'provider_service_category'] as $catKey) {
                if (! empty($data[$catKey])) {
                    $categoryIds[] = (int) $data[$catKey];
                }
            }
        }

        $customerStatuses = $customerStatusIds !== []
            ? CustomerLeadStatus::query()->whereIn('id', array_unique($customerStatusIds))->get()->keyBy('id')
            : collect();
        $providerStatuses = $providerStatusIds !== []
            ? ProviderLeadStatus::query()->whereIn('id', array_unique($providerStatusIds))->get()->keyBy('id')
            : collect();
        $categories = $categoryIds !== []
            ? Category::query()->whereIn('id', array_unique($categoryIds))->get()->keyBy('id')
            : collect();

        foreach ($items as &$item) {
            $key = (string) ($item['key'] ?? '');
            if (! str_starts_with($key, 'lead:')) {
                continue;
            }
            $leadId = (int) substr($key, 5);
            $details = is_array($item['details'] ?? null) ? $item['details'] : [];

            $statusChips = [];

            $openLabel = (string) ($openMeta[$leadId]['label'] ?? '');
            if ($openLabel !== '') {
                $openTone = match (strtolower($openLabel)) {
                    'closed' => 'closed',
                    'non responsive', 'non_responsive' => 'warning',
                    default => 'open',
                };
                $openDisplay = match (strtolower($openLabel)) {
                    'non responsive', 'non_responsive' => translate('Non_Responsive'),
                    'closed' => translate('Closed'),
                    'open' => translate('Open'),
                    default => $openLabel,
                };
                $item['status'] = $openDisplay;
                $item['status_tone'] = $openTone;
                $item['status_color'] = null;
                $statusChips[] = $this->detailChip($openDisplay, $openTone);
            }

            $history = $latestHistory[$leadId] ?? null;
            if ($history) {
                $data = is_array($history->data) ? $history->data : [];
                if (($history->type ?? '') === Lead::TYPE_CUSTOMER && ! empty($data['customer_lead_status_id'])) {
                    $panel = $customerStatuses->get((int) $data['customer_lead_status_id']);
                    $panelName = trim((string) ($panel?->name ?? ''));
                    if ($panelName !== '') {
                        $item['status'] = $panelName;
                        $item['status_tone'] = 'panel-status';
                        $item['status_color'] = $panel?->color ?: null;
                        $statusChips[] = $this->detailChip($panelName, 'panel-status', $panel?->color ?: null);
                    }
                }
                if (($history->type ?? '') === Lead::TYPE_PROVIDER && ! empty($data['provider_lead_status_id'])) {
                    $panel = $providerStatuses->get((int) $data['provider_lead_status_id']);
                    $panelName = trim((string) ($panel?->name ?? ''));
                    if ($panelName !== '') {
                        $item['status'] = $panelName;
                        $item['status_tone'] = 'panel-status';
                        $item['status_color'] = $panel?->color ?: null;
                        $statusChips[] = $this->detailChip($panelName, 'panel-status', $panel?->color ?: null);
                    }
                }
                foreach (['service_category', 'provider_service_category'] as $catKey) {
                    if (empty($data[$catKey])) {
                        continue;
                    }
                    $cat = $categories->get((int) $data[$catKey]);
                    $catName = trim((string) ($cat?->name ?? ''));
                    if ($catName !== '') {
                        $details[] = $this->detailChip($catName, 'category');
                    }
                }
            }

            $item['details'] = $this->uniqueDetailChips(array_merge($statusChips, $details));
        }
        unset($item);
    }

    /**
     * @return array{label: string, tone: string, color?: string}
     */
    private function detailChip(string $label, string $tone = 'neutral', ?string $color = null): array
    {
        $chip = [
            'label' => $label,
            'tone' => $tone,
        ];
        if ($color !== null && $color !== '') {
            $chip['color'] = $color;
        }

        return $chip;
    }

    private function leadTypeTone(string $leadType): string
    {
        return match ($leadType) {
            Lead::TYPE_CUSTOMER => 'lead-customer',
            Lead::TYPE_PROVIDER => 'lead-provider',
            Lead::TYPE_INVALID => 'lead-invalid',
            Lead::TYPE_FUTURE_CUSTOMER, Lead::TYPE_FUTURE_PROVIDER => 'lead-future',
            default => 'lead-unknown',
        };
    }

    /**
     * @param  list<array{label: string, tone?: string, color?: string}|string>  $details
     * @return list<array{label: string, tone: string, color?: string}>
     */
    private function uniqueDetailChips(array $details): array
    {
        $seen = [];
        $out = [];
        foreach ($details as $chip) {
            if (is_string($chip)) {
                $chip = $this->detailChip($chip);
            }
            if (! is_array($chip)) {
                continue;
            }
            $label = trim((string) ($chip['label'] ?? ''));
            if ($label === '' || isset($seen[$label])) {
                continue;
            }
            $seen[$label] = true;
            $out[] = [
                'label' => $label,
                'tone' => (string) ($chip['tone'] ?? 'neutral'),
                ...isset($chip['color']) ? ['color' => (string) $chip['color']] : [],
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function hydrateLastComments(array &$items): void
    {
        $leadIds = [];
        $bookingIds = [];
        $taskIds = [];

        foreach ($items as $item) {
            $key = (string) ($item['key'] ?? '');
            if (str_starts_with($key, 'lead:')) {
                $leadIds[] = (int) substr($key, 5);
            } elseif (str_starts_with($key, 'booking:')) {
                $bookingIds[] = substr($key, 8);
            } elseif (str_starts_with($key, 'task:')) {
                $taskIds[] = substr($key, 5);
            }
        }

        $leadMap = $this->latestCommentBodiesByParent(
            LeadComment::class,
            'lead_id',
            array_values(array_unique(array_filter($leadIds))),
        );
        $bookingMap = $this->latestCommentBodiesByParent(
            BookingComment::class,
            'booking_id',
            array_values(array_unique(array_filter($bookingIds))),
        );
        $taskMap = Schema::hasTable('task_ticket_comments')
            ? $this->latestCommentBodiesByParent(
                TaskTicketComment::class,
                'ticket_id',
                array_values(array_unique(array_filter($taskIds))),
            )
            : [];

        foreach ($items as &$item) {
            $key = (string) ($item['key'] ?? '');
            if (str_starts_with($key, 'lead:')) {
                $item['last_comment'] = $leadMap[(string) substr($key, 5)] ?? '';
            } elseif (str_starts_with($key, 'booking:')) {
                $item['last_comment'] = $bookingMap[substr($key, 8)] ?? '';
            } elseif (str_starts_with($key, 'task:')) {
                $item['last_comment'] = $taskMap[substr($key, 5)] ?? '';
            } else {
                $item['last_comment'] = '';
            }
        }
        unset($item);
    }

    /**
     * Latest person who changed the lead/booking (change log or type/status history).
     *
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, string>  $namesById
     */
    private function hydrateLastHandled(array &$items, array $namesById): void
    {
        $leadIds = [];
        $bookingIds = [];

        foreach ($items as $item) {
            $key = (string) ($item['key'] ?? '');
            if (str_starts_with($key, 'lead:')) {
                $leadIds[] = (int) substr($key, 5);
            } elseif (str_starts_with($key, 'booking:')) {
                $bookingIds[] = substr($key, 8);
            }
        }

        $leadIds = array_values(array_unique(array_filter($leadIds)));
        $bookingIds = array_values(array_unique(array_filter($bookingIds)));

        /** @var array<string, array{user_id: ?string, name: string, at: Carbon}> $latest */
        $latest = [];

        if ($leadIds !== [] && Schema::hasTable('lead_change_logs')) {
            $rows = LeadChangeLog::query()
                ->whereIn('lead_id', $leadIds)
                ->orderByDesc('created_at')
                ->get(['lead_id', 'changed_by', 'created_at']);
            foreach ($rows as $row) {
                $id = 'lead:'.(int) $row->lead_id;
                if (isset($latest[$id])) {
                    continue;
                }
                $latest[$id] = [
                    'user_id' => $row->changed_by ? (string) $row->changed_by : null,
                    'name' => '',
                    'at' => Carbon::parse($row->created_at),
                ];
            }
        }

        if ($leadIds !== [] && Schema::hasTable('lead_type_histories')) {
            $rows = LeadTypeHistory::query()
                ->whereIn('lead_id', $leadIds)
                ->orderByDesc('created_at')
                ->get(['lead_id', 'created_by', 'created_at']);
            foreach ($rows as $row) {
                $id = 'lead:'.(int) $row->lead_id;
                $at = Carbon::parse($row->created_at);
                if (isset($latest[$id]) && $latest[$id]['at']->gte($at)) {
                    continue;
                }
                $latest[$id] = [
                    'user_id' => $row->created_by ? (string) $row->created_by : null,
                    'name' => '',
                    'at' => $at,
                ];
            }
        }

        if ($bookingIds !== [] && Schema::hasTable('booking_change_logs')) {
            $rows = BookingChangeLog::query()
                ->whereIn('booking_id', $bookingIds)
                ->orderByDesc('created_at')
                ->get(['booking_id', 'changed_by', 'actor_name', 'created_at']);
            foreach ($rows as $row) {
                $id = 'booking:'.(string) $row->booking_id;
                if (isset($latest[$id])) {
                    continue;
                }
                $latest[$id] = [
                    'user_id' => $row->changed_by ? (string) $row->changed_by : null,
                    'name' => trim((string) ($row->actor_name ?? '')),
                    'at' => Carbon::parse($row->created_at),
                ];
            }
        }

        if ($bookingIds !== [] && Schema::hasTable('booking_status_histories')) {
            $rows = BookingStatusHistory::query()
                ->whereIn('booking_id', $bookingIds)
                ->whereNull('booking_repeat_id')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['booking_id', 'changed_by', 'created_at']);
            foreach ($rows as $row) {
                $id = 'booking:'.(string) $row->booking_id;
                $at = Carbon::parse($row->created_at);
                if (isset($latest[$id]) && $latest[$id]['at']->gte($at)) {
                    continue;
                }
                $latest[$id] = [
                    'user_id' => $row->changed_by ? (string) $row->changed_by : null,
                    'name' => '',
                    'at' => $at,
                ];
            }
        }

        $missingUserIds = [];
        foreach ($latest as $entry) {
            $uid = $entry['user_id'] ?? null;
            if ($uid && ! isset($namesById[$uid]) && ($entry['name'] ?? '') === '') {
                $missingUserIds[$uid] = true;
            }
        }
        if ($missingUserIds !== []) {
            $extraUsers = User::query()
                ->whereIn('id', array_keys($missingUserIds))
                ->get(['id', 'first_name', 'last_name', 'email']);
            foreach ($extraUsers as $user) {
                $namesById[(string) $user->id] = $this->displayName($user, translate('User'));
            }
        }

        foreach ($items as &$item) {
            $key = (string) ($item['key'] ?? '');
            $entry = $latest[$key] ?? null;
            if (! $entry) {
                $item['last_handled_by'] = '';
                $item['last_handled_at'] = null;
                continue;
            }
            $uid = $entry['user_id'] ?? null;
            $name = $uid && isset($namesById[$uid])
                ? $namesById[$uid]
                : (string) ($entry['name'] ?? '');
            if ($name === '') {
                $name = translate('Unknown');
            }
            $item['last_handled_by'] = $name;
            $item['last_handled_at'] = $entry['at']->timestamp;
        }
        unset($item);
    }

    /**
     * @param  class-string  $modelClass
     * @param  list<int|string>  $parentIds
     * @return array<string, string>
     */
    private function latestCommentBodiesByParent(string $modelClass, string $parentKey, array $parentIds): array
    {
        if ($parentIds === []) {
            return [];
        }

        $rows = $modelClass::query()
            ->whereIn($parentKey, $parentIds)
            ->orderByDesc('created_at')
            ->get([$parentKey, 'body', 'created_at']);

        $map = [];
        foreach ($rows as $row) {
            $id = (string) $row->{$parentKey};
            if (isset($map[$id])) {
                continue;
            }
            $map[$id] = $this->clip($this->plainCommentBody((string) ($row->body ?? '')), 140);
        }

        return $map;
    }

    private function plainCommentBody(string $body): string
    {
        $text = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Next follow-up detail for a lead (not workflow checklist).
     * Uses last follow-up notes / lead remarks — leads have no dedicated next-agenda field yet.
     *
     * @return array{0: string, 1: string}
     */
    private function leadNextFollowupDetail(Lead $lead, mixed $dueAt, bool $isOverdue, bool $isHunt): array
    {
        $latest = $lead->latestFollowup;
        $planned = trim((string) (
            $lead->next_followup_reason
            ?: $latest?->next_followup_reason
            ?: ''
        ));
        $fallback = trim((string) (
            $latest?->remarks
            ?: $latest?->recording_summary
            ?: $lead->remarks
            ?: ''
        ));
        $detail = $this->clip($planned !== '' ? $planned : $fallback, 140);

        if ($planned !== '') {
            $next = $detail;
        } elseif ($isHunt) {
            $next = $detail !== '' ? $detail : translate('Continue_provider_hunt_and_follow_up');
        } elseif ($detail !== '') {
            $next = $detail;
        } elseif ($dueAt) {
            $next = translate('Call_follow_up_no_notes_logged');
        } else {
            $next = translate('Schedule_follow_up_and_add_what_to_do');
        }

        if ($dueAt) {
            $when = $dueAt instanceof Carbon ? $dueAt : Carbon::parse($dueAt);
            $hint = ($isOverdue ? translate('Follow_up_overdue') : translate('Follow_up_due')).': '.$when->format('d M, H:i');
            if ($latest?->contact_channel) {
                $hint .= ' · '.ucfirst((string) $latest->contact_channel);
            }
        } else {
            $hint = translate('No_follow_up_scheduled');
        }

        return [$next, $hint];
    }

    /**
     * Next follow-up detail for a booking from the scheduled follow-up reason.
     *
     * @return array{0: string, 1: string}
     */
    private function bookingNextFollowupDetail(?BookingFollowup $followup, mixed $dueAt, bool $isOverdue): array
    {
        if (! $followup) {
            return [translate('No_follow_up_scheduled'), translate('Schedule_follow_up_with_reason')];
        }

        $party = translate(ucfirst((string) ($followup->for ?: 'customer')));
        $reason = trim((string) ($followup->reason ?: ''));
        $next = $reason !== ''
            ? $reason
            : (translate('Follow_up_with').' '.$party);

        if ($dueAt) {
            $when = $dueAt instanceof Carbon ? $dueAt : Carbon::parse($dueAt);
            $hint = ($isOverdue ? translate('Follow_up_overdue') : translate('Follow_up_due')).': '.$when->format('d M, H:i');
            $hint .= ' · '.$party;
        } else {
            $hint = $party;
        }

        return [$this->clip($next, 140), $hint];
    }

    private function clip(string $text, int $max): string
    {
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)).'…';
    }

    private function followupTimestamp(mixed $dueAt): ?int
    {
        if ($dueAt === null || $dueAt === '') {
            return null;
        }

        try {
            return ($dueAt instanceof Carbon ? $dueAt : Carbon::parse($dueAt))->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
    }

    private function score(bool $overdue, bool $unassigned, string $urgency, int $ageHours): int
    {
        $score = 0;
        if ($overdue) {
            $score += 1000;
        }
        if ($unassigned) {
            $score += 200;
        }
        $urgency = strtolower($urgency);
        if ($urgency === BookingFollowup::URGENCY_HIGH || $urgency === 'high') {
            $score += 100;
        } elseif ($urgency === BookingFollowup::URGENCY_MEDIUM || $urgency === 'medium') {
            $score += 40;
        }
        $score += min($ageHours, 200);

        return $score;
    }

    /**
     * @return Collection<int, User>
     */
    private function dashboardEmployees(): Collection
    {
        return User::query()
            ->where('user_type', 'admin-employee')
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }

    /**
     * @param  Collection<int, User>  $employees
     * @return array<string, string>
     */
    private function employeeNameMap(Collection $employees): array
    {
        $map = [];
        foreach ($employees as $employee) {
            $map[(string) $employee->id] = $this->displayName($employee, (string) ($employee->email ?? ''));
        }

        return $map;
    }

    /**
     * @param  Collection<int, User>  $employees
     * @return list<array{id: string, name: string}>
     */
    private function formatEmployeeOptions(Collection $employees): array
    {
        return $employees->map(fn (User $e) => [
            'id' => (string) $e->id,
            'name' => $this->displayName($e, (string) ($e->email ?? translate('Employee'))),
        ])->values()->all();
    }

    private function displayName(?User $user, string $fallback): string
    {
        if (! $user) {
            return $fallback;
        }
        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name !== '' ? $name : ($user->email ?? $fallback);
    }
}
