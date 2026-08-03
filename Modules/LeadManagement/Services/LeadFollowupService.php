<?php

namespace Modules\LeadManagement\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;

class LeadFollowupService
{
    /** Show “Follow-up due” when scheduled time is within this many hours (and still in the future). */
    public const FOLLOWUP_DUE_HOURS = 2;

    public const FOLLOWUP_DUE_SOON_HOURS = 24;
    /**
     * Default next follow-up: tomorrow at 10:00 (app timezone).
     */
    public function defaultNextFollowupAt(?Carbon $from = null): Carbon
    {
        $base = $from ?? Carbon::now();

        return $base->copy()->addDay()->setTime(10, 0, 0);
    }

    public function leadTypeRequiresMandatoryFollowup(string $leadType): bool
    {
        return ! in_array($leadType, [Lead::TYPE_INVALID, Lead::TYPE_FUTURE_CUSTOMER], true);
    }

    /**
     * Set {@see Lead::$next_followup_at} for new open-type leads (unknown, customer, provider).
     */
    public function applyInitialNextFollowupOnLeadCreate(Lead $lead, ?string $leadType = null): void
    {
        $type = (string) ($leadType ?? $lead->lead_type);
        if (! $this->leadTypeRequiresMandatoryFollowup($type)) {
            return;
        }

        $lead->next_followup_at = $this->defaultNextFollowupAt();
        $lead->save();
    }

    /**
     * Whether this open lead has a follow-up due today or earlier (matches pending-till-today lists).
     */
    public function leadHasPendingFollowup(Lead $lead, bool $isOpen): bool
    {
        if (! $isOpen || ! $lead->next_followup_at) {
            return false;
        }

        return $lead->next_followup_at->toDateString() <= Carbon::today()->toDateString();
    }

    /**
     * Scheduled follow-up datetime has passed (missed / overdue), including earlier today.
     */
    public function pendingFollowupIsOverdue(Carbon $nextFollowupAt): bool
    {
        return $nextFollowupAt->isPast();
    }

    /**
     * Follow-up is still in the future but due within the next N hours (default 2).
     */
    public function pendingFollowupIsDue(Carbon $nextFollowupAt, ?int $hours = null): bool
    {
        if ($nextFollowupAt->isPast()) {
            return false;
        }

        $hours = $hours ?? self::FOLLOWUP_DUE_HOURS;

        return $nextFollowupAt->lte(Carbon::now()->addHours(max(1, $hours)));
    }

    /**
     * Show missed / due badges and alerts (not merely scheduled later today).
     */
    public function leadFollowupNeedsAttention(?Carbon $nextFollowupAt, bool $isOpen, ?string $leadType = null): bool
    {
        if (! $isOpen || ! $nextFollowupAt) {
            return false;
        }

        if ($leadType !== null && ! $this->leadTypeRequiresMandatoryFollowup($leadType)) {
            return false;
        }

        $next = $nextFollowupAt instanceof Carbon
            ? $nextFollowupAt
            : Carbon::parse($nextFollowupAt);

        return $this->pendingFollowupIsOverdue($next) || $this->pendingFollowupIsDue($next);
    }

    /**
     * Follow-up badges for admin lead list (missed, due today, due soon).
     *
     * @param  Collection<int, Lead>  $leads
     * @param  array<int, array{is_open: bool, label: string, badge_class: string}>  $leadStatusMeta
     * @return array<int, array{status: string, label: string, badge_class: string}>
     */
    public function buildLeadFollowupListMeta(Collection $leads, array $leadStatusMeta, int $dueSoonHours = 24): array
    {
        $meta = [];
        $now = Carbon::now();
        $dueSoonUntil = $now->copy()->addHours(max(1, $dueSoonHours));

        foreach ($leads as $lead) {
            $leadId = (int) $lead->id;
            $isOpen = (bool) ($leadStatusMeta[$leadId]['is_open'] ?? false);

            if (! $isOpen || ! $lead->next_followup_at || ! $this->leadTypeRequiresMandatoryFollowup((string) $lead->lead_type)) {
                continue;
            }

            $next = $lead->next_followup_at instanceof Carbon
                ? $lead->next_followup_at
                : Carbon::parse($lead->next_followup_at);

            if ($this->pendingFollowupIsOverdue($next)) {
                $meta[$leadId] = [
                    'status' => 'missed',
                    'label' => 'Missed_Follow_up',
                    'badge_class' => 'bg-danger',
                ];

                continue;
            }

            if ($this->pendingFollowupIsDue($next)) {
                $meta[$leadId] = [
                    'status' => 'due',
                    'label' => 'Follow_up_due',
                    'badge_class' => 'bg-warning text-dark',
                ];

                continue;
            }

            if ($next->isFuture() && $next->lte($dueSoonUntil)) {
                $meta[$leadId] = [
                    'status' => 'due_soon',
                    'label' => 'Follow_up_due_soon',
                    'badge_class' => 'bg-warning-subtle text-warning-emphasis border border-warning',
                ];
            }
        }

        return $meta;
    }

    /**
     * @param  Collection<int, LeadFollowup>  $followups
     * @return array<int, array{due_at: ?Carbon, on_time: ?bool, delay_minutes: ?int, delay_label: ?string}>
     */
    public function buildFollowupDelayMeta(Lead $lead, Collection $followups): array
    {
        if ($followups->isEmpty()) {
            return [];
        }

        $receivedAt = $lead->date_time_of_lead_received instanceof Carbon
            ? $lead->date_time_of_lead_received
            : ($lead->date_time_of_lead_received ? Carbon::parse($lead->date_time_of_lead_received) : null);

        $previousDue = $receivedAt ? $this->defaultNextFollowupAt($receivedAt) : null;
        $meta = [];

        foreach ($followups->sortBy('followup_at') as $followup) {
            $takenAt = $followup->followup_at instanceof Carbon
                ? $followup->followup_at
                : ($followup->followup_at ? Carbon::parse($followup->followup_at) : null);

            $storedDue = $followup->due_followup_at ?? null;
            if ($storedDue && ! $storedDue instanceof Carbon) {
                $storedDue = Carbon::parse($storedDue);
            }
            $dueAt = $storedDue ?? $previousDue;

            $onTime = null;
            $delayMinutes = null;
            $delayLabel = null;

            if ($dueAt && $takenAt) {
                if ($takenAt->lte($dueAt)) {
                    $onTime = true;
                    $delayMinutes = 0;
                } else {
                    $onTime = false;
                    $delayMinutes = (int) round($dueAt->diffInMinutes($takenAt));
                    $delayLabel = $this->formatDelayDuration($delayMinutes);
                }
            }

            $meta[(int) $followup->id] = [
                'due_at' => $dueAt,
                'on_time' => $onTime,
                'delay_minutes' => $delayMinutes,
                'delay_label' => $delayLabel,
            ];

            $previousDue = $followup->next_followup_at instanceof Carbon
                ? $followup->next_followup_at
                : ($followup->next_followup_at ? Carbon::parse($followup->next_followup_at) : null);
        }

        return $meta;
    }

    public function formatDelayDuration(int $totalMinutes): string
    {
        $days = intdiv($totalMinutes, 1440);
        $hours = intdiv($totalMinutes % 1440, 60);

        if ($days > 0 && $hours > 0) {
            return $days.' '.translate('days').' '.$hours.' '.translate('hours');
        }
        if ($days > 0) {
            return $days.' '.translate('days');
        }
        if ($hours > 0) {
            return $hours.' '.translate('hours');
        }

        return translate('less_than_an_hour');
    }
}
