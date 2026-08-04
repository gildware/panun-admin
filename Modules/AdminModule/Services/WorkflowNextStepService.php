<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\AdminModule\Entities\WorkflowStepCompletion;
use Modules\AdminModule\Support\WorkflowStepDefinitions;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Services\ProviderLeadPanelMatchService;

class WorkflowNextStepService
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function forLead(Lead $lead, array $context = []): array
    {
        $lead->loadMissing(['followups']);
        $typeHistory = $context['typeHistory'] ?? LeadTypeHistory::where('lead_id', $lead->id)
            ->where('type', $lead->lead_type)
            ->latest()
            ->first();
        $customerData = ($typeHistory && is_array($typeHistory->data ?? null)) ? $typeHistory->data : [];
        $leadBooking = $context['leadBooking'] ?? null;
        $isPendingCustomer = $context['isPendingCustomerStatus'] ?? true;

        $scenario = $this->resolveLeadScenario($lead, $customerData, $leadBooking, $isPendingCustomer);
        if ($scenario === null) {
            return $this->emptyContext('lead', (int) $lead->id);
        }

        $completions = $this->completionMap(WorkflowStepCompletion::ENTITY_LEAD, (int) $lead->id);
        $detectCtx = [
            'lead' => $lead,
            'typeHistory' => $typeHistory,
            'customerData' => $customerData,
            'leadBooking' => $leadBooking,
        ];

        return $this->buildContext(
            WorkflowStepCompletion::ENTITY_LEAD,
            (int) $lead->id,
            $scenario,
            $completions,
            $detectCtx,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function forBooking(Booking $booking): array
    {
        $booking->loadMissing(['followups']);
        $status = (string) ($booking->booking_status ?? '');

        if (in_array($status, ['completed', 'canceled', 'refunded'], true)) {
            return $this->emptyContext('booking', (string) $booking->id);
        }

        $scenario = in_array($status, ['accepted', 'ongoing', 'on_hold', 'pending'], true)
            ? 'booking.active'
            : 'booking.active';

        $completions = $this->completionMap(WorkflowStepCompletion::ENTITY_BOOKING, (string) $booking->id);
        $detectCtx = ['booking' => $booking];

        $ctx = $this->buildContext(
            WorkflowStepCompletion::ENTITY_BOOKING,
            (string) $booking->id,
            $scenario,
            $completions,
            $detectCtx,
        );

        if (in_array($status, ['pending', 'accepted', 'ongoing', 'on_hold'], true)) {
            $closeSteps = $this->resolveSteps('booking.close', $completions, $detectCtx);
            $ctx['close_steps'] = $closeSteps;
            $ctx['close_next'] = $this->findNext($closeSteps);
        }

        return $ctx;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findStuckLeads(int $limit = 100): array
    {
        $leads = Lead::query()
            ->whereIn('lead_type', [Lead::TYPE_UNKNOWN, Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER])
            ->where(function ($q) {
                $q->whereNull('next_followup_at')
                    ->orWhere('next_followup_at', '<=', now());
            })
            ->orderByRaw('next_followup_at IS NULL DESC')
            ->orderBy('next_followup_at')
            ->limit($limit * 3)
            ->get();

        $stuck = [];
        foreach ($leads as $lead) {
            $ctx = $this->forLead($lead);
            if (empty($ctx['scenario']) || empty($ctx['next'])) {
                continue;
            }
            $pending = collect($ctx['steps'] ?? [])->where('status', 'pending')->count();
            if ($pending < 1) {
                continue;
            }
            $stuck[] = [
                'entity_type' => 'lead',
                'entity_id' => $lead->id,
                'name' => $lead->name ?: $lead->phone_number,
                'phone' => $lead->phone_number,
                'lead_type' => $lead->lead_type,
                'handled_by' => $lead->handled_by,
                'next_followup_at' => $lead->next_followup_at,
                'next_step' => $ctx['next'],
                'pending_count' => $pending,
                'url' => route('admin.lead.show', $lead->id),
            ];
            if (count($stuck) >= $limit) {
                break;
            }
        }

        return $stuck;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findStuckBookings(int $limit = 100): array
    {
        $bookings = Booking::query()
            ->whereNotIn('booking_status', ['completed', 'canceled', 'refunded'])
            ->orderBy('service_schedule')
            ->limit($limit * 3)
            ->get(['id', 'readable_id', 'booking_status', 'assignee_id', 'service_schedule', 'customer_id']);

        $stuck = [];
        foreach ($bookings as $booking) {
            $ctx = $this->forBooking($booking);
            if (empty($ctx['scenario']) || empty($ctx['next'])) {
                continue;
            }
            $pending = collect($ctx['steps'] ?? [])->where('status', 'pending')->count();
            if ($pending < 1) {
                continue;
            }
            $stuck[] = [
                'entity_type' => 'booking',
                'entity_id' => $booking->id,
                'readable_id' => $booking->readable_id,
                'booking_status' => $booking->booking_status,
                'assignee_id' => $booking->assignee_id,
                'service_schedule' => $booking->service_schedule,
                'next_step' => $ctx['next'],
                'pending_count' => $pending,
                'url' => route('admin.booking.details', $booking->id),
            ];
            if (count($stuck) >= $limit) {
                break;
            }
        }

        return $stuck;
    }

    /**
     * @param  array<string, bool>  $completions
     * @param  array<string, mixed>  $detectCtx
     * @return array<string, mixed>
     */
    private function buildContext(
        string $entityType,
        string|int $entityId,
        string $scenario,
        array $completions,
        array $detectCtx,
    ): array {
        $steps = $this->resolveSteps($scenario, $completions, $detectCtx);
        $steps = $this->augmentStepDetails($scenario, $steps, $detectCtx);
        $next = $this->findNext($steps);
        $progress = $this->progressPercent($steps);

        return [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'scenario' => $scenario,
            'scenario_label' => $this->scenarioLabel($scenario),
            'steps' => $steps,
            'next' => $next,
            'progress_percent' => $progress,
            'blockers' => collect($steps)->where('status', 'pending')->pluck('label')->values()->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     * @param  array<string, mixed>  $detectCtx
     * @return array<int, array<string, mixed>>
     */
    private function augmentStepDetails(string $scenario, array $steps, array $detectCtx): array
    {
        if ($scenario !== 'lead.unknown') {
            return $steps;
        }

        /** @var Lead|null $lead */
        $lead = $detectCtx['lead'] ?? null;
        if (! $lead) {
            return $steps;
        }

        foreach ($steps as &$step) {
            if (($step['key'] ?? '') !== 'lead.unknown.log_followup' || ! empty($step['done'])) {
                continue;
            }
            if ($lead->next_followup_at && $lead->followups->isEmpty()) {
                $step['detail'] = 'Followup On is set on the lead ('.$lead->next_followup_at->format('d M Y h:i A').') — still add a follow-up row in Activity → Follow-ups so the call is logged.';
            }
        }
        unset($step);

        return $steps;
    }

    /**
     * @param  array<string, bool>  $completions
     * @param  array<string, mixed>  $detectCtx
     * @return array<int, array<string, mixed>>
     */
    private function resolveSteps(string $scenario, array $completions, array $detectCtx): array
    {
        $keys = WorkflowStepDefinitions::scenarioStepKeys($scenario);
        $steps = [];
        $foundCurrent = false;

        foreach ($keys as $key) {
            $def = WorkflowStepDefinitions::step($key);
            if ($def === null) {
                continue;
            }
            $done = $this->isStepDone($key, $def, $completions, $detectCtx);
            $status = $done ? 'done' : ($foundCurrent ? 'pending' : 'current');
            if (! $done && ! $foundCurrent) {
                $status = 'current';
                $foundCurrent = true;
            } elseif (! $done) {
                $status = 'pending';
            }

            $steps[] = [
                'key' => $key,
                'label' => $def['label'],
                'detail' => $def['detail'] ?? '',
                'manual' => (bool) ($def['manual'] ?? false),
                'status' => $status,
                'done' => $done,
            ];
        }

        return $steps;
    }

    /**
     * @param  array<int, string>  $stepKeys
     * @return array<int, string>
     */
    public function doneStepKeysForLead(Lead $lead, array $stepKeys): array
    {
        $lead->loadMissing(['followups']);
        $completions = $this->completionMap(WorkflowStepCompletion::ENTITY_LEAD, (int) $lead->id);
        $detectCtx = ['lead' => $lead];

        $done = [];
        foreach ($stepKeys as $key) {
            $def = WorkflowStepDefinitions::step($key);
            if ($def === null) {
                continue;
            }
            if ($this->isStepDone($key, $def, $completions, $detectCtx)) {
                $done[] = $key;
            }
        }

        return $done;
    }

    /**
     * @param  array<int, string>  $stepKeys
     * @return array<int, string>
     */
    public function doneStepKeysForBooking(Booking $booking, array $stepKeys): array
    {
        $booking->loadMissing(['followups', 'booking_partial_payments']);
        $completions = $this->completionMap(WorkflowStepCompletion::ENTITY_BOOKING, (string) $booking->id);
        $detectCtx = ['booking' => $booking];

        $done = [];
        foreach ($stepKeys as $key) {
            $def = WorkflowStepDefinitions::step($key);
            if ($def === null) {
                continue;
            }
            if ($this->isStepDone($key, $def, $completions, $detectCtx)) {
                $done[] = $key;
            }
        }

        return $done;
    }

    /**
     * @param  array<string, mixed>  $def
     * @param  array<string, bool>  $completions
     * @param  array<string, mixed>  $detectCtx
     */
    private function isStepDone(string $key, array $def, array $completions, array $detectCtx): bool
    {
        if (! empty($completions[$key])) {
            return true;
        }
        $auto = $def['auto'] ?? null;
        if ($auto === null) {
            return false;
        }

        return $this->runAutoDetector($auto, $detectCtx);
    }

    /**
     * @param  array<string, mixed>  $detectCtx
     */
    private function runAutoDetector(string $detector, array $detectCtx): bool
    {
        return match ($detector) {
            'lead_has_outbound_contact' => $this->leadHasOutboundContact($detectCtx),
            'lead_has_followup_logged' => $this->leadHasFollowupLogged($detectCtx),
            'lead_not_unknown' => $this->leadNotUnknown($detectCtx),
            'lead_has_qualification_data' => $this->leadHasQualificationData($detectCtx),
            'lead_path_decided' => $this->leadPathDecided($detectCtx),
            'lead_has_booking' => $this->leadHasBooking($detectCtx),
            'booking_has_followup' => $this->bookingHasFollowup($detectCtx),
            'booking_due_zero' => $this->bookingDueZero($detectCtx),
            'lead_provider_in_panel' => $this->leadProviderInPanel($detectCtx),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $detectCtx
     */
    private function leadHasOutboundContact(array $detectCtx): bool
    {
        /** @var Lead|null $lead */
        $lead = $detectCtx['lead'] ?? null;
        if (! $lead) {
            return false;
        }

        if ($lead->followups->whereIn('followup_status', ['taken', 'reschedule'])->isNotEmpty()) {
            return true;
        }

        if ($lead->lead_type !== Lead::TYPE_UNKNOWN) {
            return true;
        }

        if (strlen(trim((string) ($lead->remarks ?? ''))) >= 3) {
            return true;
        }

        // Lead has follow-up date scheduled — employee already touched the lead after create.
        if ($lead->next_followup_at) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $detectCtx
     */
    private function leadHasFollowupLogged(array $detectCtx): bool
    {
        /** @var Lead|null $lead */
        $lead = $detectCtx['lead'] ?? null;

        return $lead && $lead->followups->isNotEmpty();
    }

    /**
     * @param  array<string, mixed>  $detectCtx
     */
    private function leadNotUnknown(array $detectCtx): bool
    {
        /** @var Lead|null $lead */
        $lead = $detectCtx['lead'] ?? null;

        return $lead && $lead->lead_type !== Lead::TYPE_UNKNOWN;
    }

    /**
     * @param  array<string, mixed>  $detectCtx
     */
    private function leadHasQualificationData(array $detectCtx): bool
    {
        $data = $detectCtx['customerData'] ?? [];
        if (! is_array($data) || $data === []) {
            return false;
        }

        return ! empty($data['zone_id'])
            || ! empty($data['service_category'])
            || ! empty($data['service_description'])
            || ! empty($data['estimated_service_at']);
    }

    /**
     * @param  array<string, mixed>  $detectCtx
     */
    private function leadPathDecided(array $detectCtx): bool
    {
        /** @var Lead|null $lead */
        $lead = $detectCtx['lead'] ?? null;
        if (! $lead) {
            return false;
        }

        return strlen(trim((string) ($lead->remarks ?? ''))) >= 3;
    }

    /**
     * @param  array<string, mixed>  $detectCtx
     */
    private function leadHasBooking(array $detectCtx): bool
    {
        if (! empty($detectCtx['leadBooking'])) {
            return true;
        }
        $data = $detectCtx['customerData'] ?? [];

        return ! empty($data['booking_id']);
    }

    /**
     * @param  array<string, mixed>  $detectCtx
     */
    private function leadProviderInPanel(array $detectCtx): bool
    {
        if (! empty($detectCtx['panelProviderMatch'])) {
            return true;
        }

        /** @var Lead|null $lead */
        $lead = $detectCtx['lead'] ?? null;
        if (! $lead) {
            return false;
        }

        return app(ProviderLeadPanelMatchService::class)->matchForLead($lead) !== null;
    }

    /**
     * @param  array<string, mixed>  $detectCtx
     */
    private function bookingHasFollowup(array $detectCtx): bool
    {
        /** @var Booking|null $booking */
        $booking = $detectCtx['booking'] ?? null;

        return $booking && $booking->followups->isNotEmpty();
    }

    /**
     * @param  array<string, mixed>  $detectCtx
     */
    private function bookingDueZero(array $detectCtx): bool
    {
        /** @var Booking|null $booking */
        $booking = $detectCtx['booking'] ?? null;
        if (! $booking) {
            return false;
        }
        if (function_exists('booking_can_be_completed') && booking_can_be_completed($booking)) {
            return true;
        }
        if (function_exists('booking_remaining_due')) {
            return (float) booking_remaining_due($booking) <= 0.01;
        }

        return (float) ($booking->total_booking_amount ?? 0) <= (float) ($booking->total_paid_amount ?? $booking->paid_amount ?? 0);
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     * @return array<string, mixed>|null
     */
    private function findNext(array $steps): ?array
    {
        foreach ($steps as $step) {
            if (($step['status'] ?? '') === 'current' || (! ($step['done'] ?? false) && ($step['status'] ?? '') !== 'done')) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     */
    private function progressPercent(array $steps): int
    {
        if ($steps === []) {
            return 100;
        }
        $done = collect($steps)->where('done', true)->count();

        return (int) round(($done / count($steps)) * 100);
    }

    /**
     * @param  array<string, mixed>  $customerData
     */
    private function resolveLeadScenario(Lead $lead, array $customerData, ?array $leadBooking, bool $isPendingCustomer): ?string
    {
        if ($lead->lead_type === Lead::TYPE_UNKNOWN) {
            return 'lead.unknown';
        }
        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            if (! $this->isOpenProviderLead($customerData)) {
                return null;
            }

            return 'lead.provider.onboarding';
        }
        if ($lead->lead_type !== Lead::TYPE_CUSTOMER) {
            return null;
        }
        if ($leadBooking || ! empty($customerData['booking_id'])) {
            return 'lead.customer.booked';
        }
        if (! $isPendingCustomer) {
            return null;
        }
        $remarks = strtolower((string) ($lead->remarks ?? ''));
        if (str_contains($remarks, 'path b') || str_contains($remarks, 'discussion')) {
            return 'lead.customer.path_b';
        }

        return 'lead.customer.path_a';
    }

    /**
     * @param  array<string, mixed>  $typeHistoryData
     */
    private function isOpenProviderLead(array $typeHistoryData): bool
    {
        $statusId = $typeHistoryData['provider_lead_status_id'] ?? null;
        if (! $statusId) {
            return true;
        }
        $status = ProviderLeadStatus::find($statusId);
        $baseType = strtolower((string) ($status?->base_type ?? 'pending'));

        return ! in_array($baseType, ['completed', 'cancel'], true);
    }

    private function scenarioLabel(string $scenario): string
    {
        return match ($scenario) {
            'lead.unknown' => 'Unknown lead qualification',
            'lead.customer.path_a' => 'Customer — Path A (direct booking)',
            'lead.customer.path_b' => 'Customer — Path B (discussion first)',
            'lead.customer.booked' => 'Customer — booking linked',
            'lead.provider.onboarding' => 'Provider — onboarding',
            'booking.active' => 'Active booking follow-up',
            'booking.close' => 'Close booking checklist',
            default => $scenario,
        };
    }

    /**
     * @return array<string, bool>
     */
    public function completionMap(string $entityType, string|int $entityId): array
    {
        return WorkflowStepCompletion::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', (string) $entityId)
            ->where('is_done', true)
            ->pluck('is_done', 'step_key')
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyContext(string $entityType, string|int $entityId): array
    {
        return [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'scenario' => null,
            'scenario_label' => null,
            'steps' => [],
            'next' => null,
            'progress_percent' => 100,
            'blockers' => [],
        ];
    }
}
