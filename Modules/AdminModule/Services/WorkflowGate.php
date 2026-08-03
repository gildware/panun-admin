<?php

namespace Modules\AdminModule\Services;

use Modules\AdminModule\Support\WorkflowStepDefinitions;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;

class WorkflowGate
{
    public function __construct(
        private readonly WorkflowNextStepService $workflow,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array{allowed: bool, message: string, pending: array<int, array<string, mixed>>, hard_pending: array<int, array<string, mixed>>}
     */
    public function checkLeadAction(Lead $lead, string $action, array $context = [], bool $confirmed = false): array
    {
        if ($action === WorkflowStepDefinitions::ACTION_LEAD_PANEL_UPDATED) {
            return $this->checkPostPanelUpdate($lead, $confirmed);
        }

        $workflowCtx = $this->workflow->forLead($lead, $context);
        $reqs = WorkflowStepDefinitions::actionRequirements()[$action] ?? ['hard' => [], 'soft' => []];
        $doneKeys = collect($workflowCtx['steps'] ?? [])->where('done', true)->pluck('key')->all();

        $pendingHard = $this->pendingKeys($reqs['hard'], $doneKeys);
        $pendingSoft = $confirmed ? [] : $this->pendingKeys($reqs['soft'], $doneKeys);
        $allPending = array_merge($pendingHard, $pendingSoft);

        if ($action === WorkflowStepDefinitions::ACTION_LEAD_CREATE_BOOKING) {
            if ($lead->lead_type !== Lead::TYPE_CUSTOMER) {
                return $this->deny('Lead must be Customer type before creating a booking.', []);
            }
            $scenario = $workflowCtx['scenario'] ?? '';
            if ($scenario === 'lead.customer.path_b') {
                $pathBKey = 'lead.customer.path_b_discussion';
                if (! in_array($pathBKey, $doneKeys, true) && ! $confirmed) {
                    if (! in_array($pathBKey, $allPending, true)) {
                        $allPending[] = $pathBKey;
                    }
                }
            }
        }

        $prompts = WorkflowStepDefinitions::confirmationPrompts($action, $allPending);
        $hardPrompts = array_values(array_filter($prompts, fn ($p) => $p['hard']));

        if ($hardPrompts !== []) {
            return $this->deny(
                translate('Complete_required_workflow_steps_first').': '.implode('; ', array_column($hardPrompts, 'label')),
                $prompts,
                $hardPrompts,
            );
        }

        if ($prompts !== []) {
            return $this->deny(
                translate('Confirm_previous_workflow_steps'),
                $prompts,
                [],
            );
        }

        return ['allowed' => true, 'message' => '', 'pending' => [], 'hard_pending' => []];
    }

    /**
     * After Unknown → type change: prompt WhatsApp + follow-up logging (not before panel update).
     *
     * @return array{allowed: bool, message: string, pending: array<int, array<string, mixed>>, hard_pending: array<int, array<string, mixed>>}
     */
    public function checkPostPanelUpdate(Lead $lead, bool $confirmed = false): array
    {
        $reqs = WorkflowStepDefinitions::actionRequirements()[WorkflowStepDefinitions::ACTION_LEAD_PANEL_UPDATED] ?? ['hard' => [], 'soft' => []];
        $doneKeys = $this->workflow->doneStepKeysForLead($lead, $reqs['hard'] + $reqs['soft']);

        $pendingHard = $this->pendingKeys($reqs['hard'], $doneKeys);
        $pendingSoft = $confirmed ? [] : $this->pendingKeys($reqs['soft'], $doneKeys);
        $allPending = array_merge($pendingHard, $pendingSoft);

        $prompts = WorkflowStepDefinitions::confirmationPrompts(WorkflowStepDefinitions::ACTION_LEAD_PANEL_UPDATED, $allPending);

        if ($prompts === []) {
            return ['allowed' => true, 'message' => '', 'pending' => [], 'hard_pending' => []];
        }

        return $this->deny(
            translate('You_updated_the_panel_confirm_you_also_completed'),
            $prompts,
            [],
        );
    }

    /**
     * @return array{allowed: bool, message: string, pending: array<int, array<string, mixed>>, hard_pending: array<int, array<string, mixed>>}
     */
    public function checkBookingAction(Booking $booking, string $action, bool $confirmed = false): array
    {
        $workflowCtx = $this->workflow->forBooking($booking);
        $reqs = WorkflowStepDefinitions::actionRequirements()[$action] ?? ['hard' => [], 'soft' => []];

        $doneKeys = collect($workflowCtx['steps'] ?? [])->where('done', true)->pluck('key')->all();
        $closeDone = collect($workflowCtx['close_steps'] ?? [])->where('done', true)->pluck('key')->all();
        $doneKeys = array_unique(array_merge($doneKeys, $closeDone));

        $pendingHard = $this->pendingKeys($reqs['hard'], $doneKeys);
        $pendingSoft = $confirmed ? [] : $this->pendingKeys($reqs['soft'], $doneKeys);
        $allPending = array_merge($pendingHard, $pendingSoft);

        $prompts = WorkflowStepDefinitions::confirmationPrompts($action, $allPending);
        $hardPrompts = array_values(array_filter($prompts, fn ($p) => $p['hard']));

        if ($hardPrompts !== []) {
            return $this->deny(
                translate('Complete_required_workflow_steps_first').': '.implode('; ', array_column($hardPrompts, 'label')),
                $prompts,
                $hardPrompts,
            );
        }

        if ($prompts !== []) {
            return $this->deny(
                translate('Confirm_previous_workflow_steps'),
                $prompts,
                [],
            );
        }

        return ['allowed' => true, 'message' => '', 'pending' => [], 'hard_pending' => []];
    }

    /**
     * @param  array<int, string>  $required
     * @param  array<int, string>  $doneKeys
     * @return array<int, string>
     */
    private function pendingKeys(array $required, array $doneKeys): array
    {
        return array_values(array_filter($required, fn ($k) => ! in_array($k, $doneKeys, true)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $pending
     * @param  array<int, array<string, mixed>>  $hardPending
     * @return array{allowed: bool, message: string, pending: array<int, array<string, mixed>>, hard_pending: array<int, array<string, mixed>>}
     */
    private function deny(string $message, array $pending, array $hardPending = []): array
    {
        return [
            'allowed' => false,
            'message' => $message,
            'pending' => $pending,
            'hard_pending' => $hardPending,
        ];
    }
}
