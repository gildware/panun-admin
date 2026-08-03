<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\AdminModule\Entities\WorkflowStepCompletion;
use Modules\AdminModule\Services\WorkflowGate;
use Modules\AdminModule\Services\WorkflowNextStepService;
use Modules\AdminModule\Support\WorkflowStepDefinitions;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Services\LeadChangeLogService;

class WorkflowStepController extends Controller
{
    public function __construct(
        private readonly WorkflowNextStepService $workflow,
        private readonly WorkflowGate $gate,
    ) {}

    public function stuckIndex(): View
    {
        $stuckLeads = $this->workflow->findStuckLeads(50);
        $stuckBookings = $this->workflow->findStuckBookings(50);

        return view('adminmodule::admin.workflow.stuck', compact('stuckLeads', 'stuckBookings'));
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|in:lead,booking',
            'entity_id' => 'required|integer|min:1',
            'step_key' => 'required|string|max:128',
            'is_done' => 'required|boolean',
        ]);

        $entityType = $validated['entity_type'];
        $entityId = (int) $validated['entity_id'];
        $stepKey = $validated['step_key'];

        if (WorkflowStepDefinitions::step($stepKey) === null) {
            return response()->json(['message' => translate('Invalid_workflow_step')], 422);
        }

        if ($entityType === WorkflowStepCompletion::ENTITY_LEAD) {
            $this->authorizeLeadUpdate($entityId);
        } else {
            $this->authorizeBookingUpdate();
        }

        $entry = WorkflowStepCompletion::query()->firstOrNew([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'step_key' => $stepKey,
        ]);
        $wasDone = (bool) $entry->is_done;
        $entry->is_done = $validated['is_done'];
        $entry->done_by = $validated['is_done'] ? Auth::id() : null;
        $entry->done_at = $validated['is_done'] ? now() : null;
        $entry->save();

        if ($entityType === WorkflowStepCompletion::ENTITY_LEAD) {
            $def = WorkflowStepDefinitions::step($stepKey);
            app(LeadChangeLogService::class)->record($entityId, [
                'workflow_step_'.$stepKey => [
                    'label' => $def['label'] ?? $stepKey,
                    'old' => $entry->wasRecentlyCreated ? translate('Pending') : ($entry->getOriginal('is_done') ? translate('Done') : translate('Pending')),
                    'new' => $entry->is_done ? translate('Done') : translate('Pending'),
                ],
            ]);
        }

        $context = $entityType === WorkflowStepCompletion::ENTITY_LEAD
            ? $this->workflow->forLead(Lead::findOrFail($entityId))
            : $this->workflow->forBooking(Booking::findOrFail($entityId));

        return response()->json([
            'success' => true,
            'workflow' => $context,
        ]);
    }

    public function confirmBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|in:lead,booking',
            'entity_id' => 'required|integer|min:1',
            'step_keys' => 'required|array|min:1',
            'step_keys.*' => 'string|max:128',
            'action' => 'nullable|string|max:64',
        ]);

        $entityType = $validated['entity_type'];
        $entityId = (int) $validated['entity_id'];

        if ($entityType === WorkflowStepCompletion::ENTITY_LEAD) {
            $this->authorizeLeadUpdate($entityId);
        } else {
            $this->authorizeBookingUpdate();
        }

        foreach ($validated['step_keys'] as $stepKey) {
            if (WorkflowStepDefinitions::step($stepKey) === null) {
                continue;
            }
            WorkflowStepCompletion::query()->updateOrCreate(
                [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'step_key' => $stepKey,
                ],
                [
                    'is_done' => true,
                    'done_by' => Auth::id(),
                    'done_at' => now(),
                ],
            );
        }

        return response()->json(['success' => true]);
    }

    public function checkGate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|in:lead,booking',
            'entity_id' => 'required|integer|min:1',
            'action' => 'required|string|max:64',
            'confirmed' => 'nullable|boolean',
        ]);

        $confirmed = $request->boolean('confirmed');

        if ($validated['entity_type'] === 'lead') {
            $lead = Lead::findOrFail((int) $validated['entity_id']);
            $result = $this->gate->checkLeadAction($lead, $validated['action'], [], $confirmed);
        } else {
            $booking = Booking::findOrFail((int) $validated['entity_id']);
            $result = $this->gate->checkBookingAction($booking, $validated['action'], $confirmed);
        }

        return response()->json($result);
    }

    private function authorizeLeadUpdate(int $leadId): void
    {
        Lead::findOrFail($leadId);
        abort_unless(auth()->user()?->can('lead_update'), 403);
    }

    private function authorizeBookingUpdate(): void
    {
        abort_unless(
            auth()->user()?->can('booking_can_manage_status') || auth()->user()?->can('booking_view'),
            403,
        );
    }
}
