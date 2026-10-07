<?php

namespace Modules\LeadManagement\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\UserManagement\Entities\User;

/**
 * Hold and pending analysis shared by customer, provider, future-customer, and invalid lead reports.
 * Hold reason is the latest follow-up note (lead remarks if no follow-up note was saved).
 */
class LeadOpenStatusDeepBuilder
{
    private const UNSPECIFIED_KEY = '__unspecified__';

    private const FIRST_FOLLOWUP_SLA_HOURS = 24;

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{hold: array<string, mixed>, pending: array<string, mixed>, by_lead: array<int|string, array<string, mixed>>}
     */
    public function build(array $items): array
    {
        $handlerIds = [];
        foreach ($items as $item) {
            $lead = $item['lead'] ?? null;
            if ($lead instanceof Lead && Lead::assigneeIsHuman($lead->handled_by)) {
                $handlerIds[] = $lead->handled_by;
            }
        }
        $users = $handlerIds !== []
            ? User::whereIn('id', array_values(array_unique($handlerIds)))->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id')
            : collect();

        $state = [
            'hold' => $this->emptyAccum(),
            'pending' => $this->emptyAccum(),
        ];
        $byLead = [];

        foreach ($items as $item) {
            $lead = $item['lead'] ?? null;
            if (!$lead instanceof Lead) {
                continue;
            }
            $tab = (string) ($item['status_tab'] ?? '');
            if (!isset($state[$tab])) {
                continue;
            }

            $followups = $item['followups'] ?? collect();
            if (!$followups instanceof Collection) {
                $followups = collect($followups);
            }
            $engagement = is_array($item['engagement'] ?? null)
                ? $item['engagement']
                : $this->engagement($lead, $followups);

            $category = $item['category'] ?? ['key' => self::UNSPECIFIED_KEY, 'label' => translate('Not_Specified')];
            $zone = $item['zone'] ?? ['key' => self::UNSPECIFIED_KEY, 'label' => translate('Not_Specified')];
            $holdReason = $this->holdReason($lead, $followups);
            $pendingReason = $this->pendingReason($lead, $followups);
            $reason = $tab === 'hold' ? $holdReason : $pendingReason;

            $handlerKey = Lead::assigneeIsHuman($lead->handled_by) ? (string) $lead->handled_by : Lead::FILTER_UNASSIGNED_VALUE;
            $handlerLabel = (string) ($item['handled_by'] ?? $this->handlerLabel($lead->handled_by, $users));
            $source = (string) ($item['source'] ?? '—');

            $this->incrementSimple($state[$tab]['reasons'], $reason['key'], $reason['label']);
            $this->incrementSimple($state[$tab]['categories'], $category['key'], $category['label']);
            $this->incrementSimple($state[$tab]['zones'], $zone['key'], $zone['label']);
            $this->appendId($state[$tab]['reason_leads'], $reason['key'], (string) $lead->id);
            $this->appendId($state[$tab]['category_leads'], $category['key'], (string) $lead->id);
            $this->appendId($state[$tab]['zone_leads'], $zone['key'], (string) $lead->id);
            $this->incrementNested($state[$tab]['category_reason'], $category['key'], $category['label'], $reason['key'], $reason['label']);
            $this->incrementNested($state[$tab]['category_zone'], $category['key'], $category['label'], $zone['key'], $zone['label']);
            $this->incrementNested($state[$tab]['reason_zone'], $reason['key'], $reason['label'], $zone['key'], $zone['label']);

            if ($tab === 'hold' && $holdReason['key'] === self::UNSPECIFIED_KEY) {
                $state[$tab]['missing_reason']++;
            }
            if (($engagement['never_followed_up'] ?? false) === true) {
                $state[$tab]['never_followed_up']++;
            }
            if (($engagement['delayed_first_contact'] ?? false) === true) {
                $state[$tab]['delayed_first_contact']++;
            }
            if ($pendingReason['key'] === 'overdue') {
                $state[$tab]['overdue']++;
            }
            if ($pendingReason['key'] === 'no_schedule') {
                $state[$tab]['no_schedule']++;
            }

            $remarkText = $tab === 'hold' ? $holdReason['text'] : trim((string) ($item['type_remarks'] ?? ''));
            if ($remarkText === '' && $tab === 'pending') {
                $remarkText = $holdReason['text'];
            }
            if ($remarkText !== '') {
                $state[$tab]['remarks'][] = [
                    'lead_id' => $lead->id,
                    'category' => $category['label'],
                    'zone' => $zone['label'],
                    'reason' => $reason['label'],
                    'text' => $remarkText,
                    'hours_to_first_followup' => $engagement['hours_to_first_followup'] ?? null,
                    'followup_count' => $engagement['followup_count'] ?? 0,
                ];
            }

            if (!isset($state[$tab]['staff'][$handlerKey])) {
                $state[$tab]['staff'][$handlerKey] = [
                    'label' => $handlerLabel,
                    'total' => 0,
                    'missing_reason' => 0,
                    'never_followed_up' => 0,
                    'delayed_first_contact' => 0,
                    'overdue' => 0,
                    'no_schedule' => 0,
                    'followup_count_total' => 0,
                    'first_followup_hours' => [],
                ];
            }
            $staff = &$state[$tab]['staff'][$handlerKey];
            $staff['total']++;
            $staff['followup_count_total'] += (int) ($engagement['followup_count'] ?? 0);
            if ($tab === 'hold' && $holdReason['key'] === self::UNSPECIFIED_KEY) {
                $staff['missing_reason']++;
            }
            if (($engagement['never_followed_up'] ?? false) === true) {
                $staff['never_followed_up']++;
            }
            if (($engagement['delayed_first_contact'] ?? false) === true) {
                $staff['delayed_first_contact']++;
            }
            if ($pendingReason['key'] === 'overdue') {
                $staff['overdue']++;
            }
            if ($pendingReason['key'] === 'no_schedule') {
                $staff['no_schedule']++;
            }
            if (isset($engagement['hours_to_first_followup']) && $engagement['hours_to_first_followup'] !== null) {
                $staff['first_followup_hours'][] = $engagement['hours_to_first_followup'];
            }
            unset($staff);

            $row = [
                'lead_id' => $lead->id,
                'name' => $lead->name ?: '—',
                'phone' => $lead->phone_number,
                'category' => $category['label'],
                'zone' => $zone['label'],
                'area' => $zone['label'],
                'hold_reason' => $tab === 'hold' ? $holdReason['label'] : '—',
                'pending_reason' => $tab === 'pending' ? $pendingReason['label'] : '—',
                'status_remarks' => $remarkText !== '' ? $remarkText : '—',
                'type_reason' => (string) ($item['type_reason'] ?? '—'),
                'handled_by' => $handlerLabel,
                'source' => $source !== '' ? $source : '—',
                'received_at' => $lead->date_time_of_lead_received?->format('d M Y, h:i A') ?? '—',
                'next_followup_at' => $lead->next_followup_at?->format('d M Y, h:i A') ?? '—',
                'followup_count' => $engagement['followup_count'] ?? 0,
                'hours_to_first_followup' => $engagement['hours_to_first_followup'] ?? null,
                'first_followup_on_time' => $engagement['first_followup_on_time'] ?? null,
                'never_followed_up' => $engagement['never_followed_up'] ?? false,
                'delayed_first_contact' => $engagement['delayed_first_contact'] ?? false,
            ];
            $state[$tab]['rows'][] = $row;
            $byLead[$lead->id] = [
                'hold_reason' => $row['hold_reason'],
                'pending_reason' => $row['pending_reason'],
                'status_remarks' => $row['status_remarks'],
                'reason_label' => $reason['label'],
            ];
        }

        return [
            'hold' => $this->finalizeSlice($state['hold'], 'hold'),
            'pending' => $this->finalizeSlice($state['pending'], 'pending'),
            'by_lead' => $byLead,
        ];
    }

    public function isHold(Lead $lead, string $statusName): bool
    {
        if ($statusName !== '' && str_contains(strtolower($statusName), 'hold')) {
            return true;
        }

        $next = $lead->next_followup_at;

        return $next instanceof Carbon && $next->isFuture();
    }

    /**
     * @param  Collection<int, LeadFollowup>  $followups
     * @return array{key: string, label: string, text: string}
     */
    public function holdReason(Lead $lead, Collection $followups): array
    {
        $latest = $followups->sortByDesc(function ($followup) {
            $at = $followup->followup_at ?? null;

            return $at instanceof Carbon ? $at->timestamp : (is_string($at) ? strtotime($at) : 0);
        })->first();

        $text = trim((string) ($latest->remarks ?? ''));
        if ($text === '') {
            $text = trim((string) ($lead->remarks ?? ''));
        }
        if ($text === '') {
            return [
                'key' => self::UNSPECIFIED_KEY,
                'label' => translate('Not_Specified'),
                'text' => '',
            ];
        }

        $normalized = preg_replace('/\s+/', ' ', $text) ?? $text;
        $label = mb_strlen($normalized) > 80 ? mb_substr($normalized, 0, 77).'…' : $normalized;

        return [
            'key' => 'r:'.substr(md5(mb_strtolower($normalized)), 0, 12),
            'label' => $label,
            'text' => $normalized,
        ];
    }

    /**
     * @param  Collection<int, LeadFollowup>  $followups
     * @return array{key: string, label: string, text: string}
     */
    public function pendingReason(Lead $lead, Collection $followups): array
    {
        if ($followups->isEmpty()) {
            return ['key' => 'never', 'label' => translate('Never_followed_up'), 'text' => ''];
        }

        $next = $lead->next_followup_at;
        if ($next instanceof Carbon && $next->isPast()) {
            return ['key' => 'overdue', 'label' => translate('Followup_overdue'), 'text' => ''];
        }
        if (!$next) {
            return ['key' => 'no_schedule', 'label' => translate('No_followup_scheduled'), 'text' => ''];
        }

        return ['key' => 'needs_action', 'label' => translate('Needs_action'), 'text' => ''];
    }

    /**
     * @param  Collection<int, LeadFollowup>  $followups
     * @return array<string, mixed>
     */
    public function engagement(Lead $lead, Collection $followups): array
    {
        $receivedAt = $lead->date_time_of_lead_received instanceof Carbon
            ? $lead->date_time_of_lead_received
            : ($lead->date_time_of_lead_received ? Carbon::parse($lead->date_time_of_lead_received) : null);

        $sorted = $followups->sortBy(function ($followup) {
            $at = $followup->followup_at ?? null;

            return $at instanceof Carbon ? $at->timestamp : (is_string($at) ? strtotime($at) : 0);
        })->values();
        $first = $sorted->first();
        $firstAt = $first?->followup_at instanceof Carbon
            ? $first->followup_at
            : ($first?->followup_at ? Carbon::parse($first->followup_at) : null);

        $hours = null;
        if ($receivedAt && $firstAt) {
            $hours = round($receivedAt->diffInMinutes($firstAt, false) / 60, 2);
        }
        $slaDueAt = $receivedAt ? app(LeadFollowupService::class)->defaultNextFollowupAt($receivedAt) : null;
        $firstOnTime = ($firstAt && $slaDueAt) ? $firstAt->lte($slaDueAt) : null;

        return [
            'followup_count' => $sorted->count(),
            'hours_to_first_followup' => $hours,
            'first_followup_on_time' => $firstOnTime,
            'never_followed_up' => $sorted->isEmpty(),
            'delayed_first_contact' => $hours !== null && $hours > self::FIRST_FOLLOWUP_SLA_HOURS,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyAccum(): array
    {
        return [
            'reasons' => [],
            'categories' => [],
            'zones' => [],
            'reason_leads' => [],
            'category_leads' => [],
            'zone_leads' => [],
            'category_reason' => [],
            'category_zone' => [],
            'reason_zone' => [],
            'remarks' => [],
            'rows' => [],
            'staff' => [],
            'missing_reason' => 0,
            'never_followed_up' => 0,
            'delayed_first_contact' => 0,
            'overdue' => 0,
            'no_schedule' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $slice
     * @return array<string, mixed>
     */
    private function finalizeSlice(array $slice, string $tab): array
    {
        $total = count($slice['rows']);

        return [
            'reasons' => $this->finalizeSimple($slice['reasons']),
            'categories' => $this->finalizeSimple($slice['categories']),
            'zones' => $this->finalizeSimple($slice['zones']),
            'reason_leads' => $slice['reason_leads'],
            'category_leads' => $slice['category_leads'],
            'zone_leads' => $slice['zone_leads'],
            'category_reason_matrix' => $this->finalizeNested($slice['category_reason']),
            'category_zone_matrix' => $this->finalizeNested($slice['category_zone']),
            'reason_zone_matrix' => $this->finalizeNested($slice['reason_zone']),
            'remarks' => array_slice($slice['remarks'], 0, 50),
            'rows' => $slice['rows'],
            'staff' => $this->finalizeStaff($slice['staff']),
            'summary' => [
                'total' => $total,
                'missing_reason' => (int) $slice['missing_reason'],
                'never_followed_up' => (int) $slice['never_followed_up'],
                'delayed_first_contact' => (int) $slice['delayed_first_contact'],
                'overdue' => (int) $slice['overdue'],
                'no_schedule' => (int) $slice['no_schedule'],
            ],
            'insights' => $this->insights($slice, $tab, $total),
        ];
    }

    /**
     * @param  array<string, mixed>  $slice
     * @return list<array{type: string, text: string}>
     */
    private function insights(array $slice, string $tab, int $total): array
    {
        if ($total === 0) {
            return [];
        }

        $insights = [];
        $noun = $tab === 'hold' ? translate('hold_leads') : translate('pending_leads');

        if ($tab === 'hold' && (int) $slice['missing_reason'] > 0) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf(
                    '%d of %d %s %s.',
                    (int) $slice['missing_reason'],
                    $total,
                    $noun,
                    translate('have_no_hold_reason')
                ),
            ];
        }
        if ((int) $slice['never_followed_up'] > 0) {
            $insights[] = [
                'type' => 'danger',
                'text' => sprintf(
                    '%d of %d %s %s.',
                    (int) $slice['never_followed_up'],
                    $total,
                    $noun,
                    translate('were_never_followed_up')
                ),
            ];
        }
        if ($tab === 'pending' && (int) $slice['overdue'] > 0) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf(
                    '%d of %d %s %s.',
                    (int) $slice['overdue'],
                    $total,
                    $noun,
                    translate('have_an_overdue_followup')
                ),
            ];
        }
        if ((int) $slice['delayed_first_contact'] > 0) {
            $insights[] = [
                'type' => 'info',
                'text' => sprintf(
                    '%d of %d %s %s (> %dh).',
                    (int) $slice['delayed_first_contact'],
                    $total,
                    $noun,
                    translate('first_contact_was_delayed'),
                    self::FIRST_FOLLOWUP_SLA_HOURS
                ),
            ];
        }

        return $insights;
    }

    /**
     * @param  array<string, array<string, mixed>>  $staff
     * @return list<array<string, mixed>>
     */
    private function finalizeStaff(array $staff): array
    {
        $rows = [];
        foreach ($staff as $bucket) {
            $total = (int) $bucket['total'];
            $hours = $bucket['first_followup_hours'] ?? [];
            $rows[] = [
                'label' => $bucket['label'],
                'total' => $total,
                'missing_reason' => (int) $bucket['missing_reason'],
                'never_followed_up' => (int) $bucket['never_followed_up'],
                'delayed_first_contact' => (int) $bucket['delayed_first_contact'],
                'overdue' => (int) $bucket['overdue'],
                'no_schedule' => (int) $bucket['no_schedule'],
                'avg_followups_per_lead' => $total > 0 ? round(((int) $bucket['followup_count_total']) / $total, 1) : 0.0,
                'median_hours_to_first_followup' => $this->median($hours),
            ];
        }
        usort($rows, fn ($a, $b) => ($b['total'] <=> $a['total']));

        return $rows;
    }

    private function handlerLabel(?string $handledBy, Collection $users): string
    {
        if (!Lead::assigneeIsHuman($handledBy)) {
            return $handledBy === Lead::HANDLED_BY_AI ? translate('AI') : translate('Unassigned');
        }
        $user = $users->get($handledBy);
        $fullName = $user ? trim(($user->first_name ?? '').' '.($user->last_name ?? '')) : '';

        return $fullName ?: ($user->email ?? (string) $handledBy);
    }

    /**
     * @param  array<string, array{label: string, total: int, key: string}>  $bucket
     */
    private function incrementSimple(array &$bucket, string $key, string $label): void
    {
        if (!isset($bucket[$key])) {
            $bucket[$key] = ['label' => $label, 'total' => 0, 'key' => $key];
        }
        $bucket[$key]['total']++;
    }

    /**
     * @param  array<string, list<string>>  $bucket
     */
    private function appendId(array &$bucket, string $key, string $leadId): void
    {
        $bucket[$key][] = $leadId;
    }

    /**
     * @param  array<string, array{label: string, total: int, children: array<string, array{label: string, total: int}>}>  $matrix
     */
    private function incrementNested(array &$matrix, string $parentKey, string $parentLabel, string $childKey, string $childLabel): void
    {
        if (!isset($matrix[$parentKey])) {
            $matrix[$parentKey] = ['label' => $parentLabel, 'total' => 0, 'children' => []];
        }
        $matrix[$parentKey]['total']++;
        if (!isset($matrix[$parentKey]['children'][$childKey])) {
            $matrix[$parentKey]['children'][$childKey] = ['label' => $childLabel, 'total' => 0];
        }
        $matrix[$parentKey]['children'][$childKey]['total']++;
    }

    /**
     * @param  array<string, array{label: string, total: int, key: string}>  $bucket
     * @return list<array<string, mixed>>
     */
    private function finalizeSimple(array $bucket): array
    {
        $rows = array_values($bucket);
        $unspecified = translate('Not_Specified');
        usort($rows, function ($a, $b) use ($unspecified) {
            $aUnspecified = ($a['label'] ?? '') === $unspecified;
            $bUnspecified = ($b['label'] ?? '') === $unspecified;
            if ($aUnspecified !== $bUnspecified) {
                return $aUnspecified ? 1 : -1;
            }

            return ($b['total'] ?? 0) <=> ($a['total'] ?? 0);
        });

        return $rows;
    }

    /**
     * @param  array<string, array{label: string, total: int, children: array<string, array{label: string, total: int}>}>  $matrix
     * @return list<array<string, mixed>>
     */
    private function finalizeNested(array $matrix): array
    {
        $rows = [];
        foreach ($matrix as $key => $row) {
            $children = array_values($row['children'] ?? []);
            usort($children, fn ($a, $b) => ($b['total'] ?? 0) <=> ($a['total'] ?? 0));
            $rows[] = [
                'key' => (string) $key,
                'label' => $row['label'],
                'total' => (int) $row['total'],
                'breakdown' => $children,
            ];
        }
        usort($rows, fn ($a, $b) => ($b['total'] ?? 0) <=> ($a['total'] ?? 0));

        return $rows;
    }

    /**
     * @param  list<float|int>  $values
     */
    private function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }
        sort($values, SORT_NUMERIC);
        $n = count($values);

        return round((float) $values[intval(floor(($n - 1) / 2))], 2);
    }
}
