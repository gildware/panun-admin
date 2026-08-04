<?php

namespace Modules\AdminModule\Console;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\AdminModule\Entities\WorkflowStepCompletion;
use Modules\AdminModule\Services\WorkflowGate;
use Modules\AdminModule\Services\WorkflowNextStepService;
use Modules\AdminModule\Support\WorkflowStepDefinitions;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Http\Controllers\Web\Admin\LeadController;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;

class WorkflowGateTestCommand extends Command
{
    protected $signature = 'workflow:gate-test {--keep-data : Leave test rows in the database}';

    protected $description = 'Test workflow gates for all lead scenarios and gated actions';

    private string $tag;

    private WorkflowGate $gate;

    private WorkflowNextStepService $workflow;

    /** @var array<int, int> */
    private array $leadIds = [];

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Refusing to run on production.');

            return self::FAILURE;
        }

        $this->tag = 'wf-gate-'.Str::lower(Str::random(6));
        $this->gate = app(WorkflowGate::class);
        $this->workflow = app(WorkflowNextStepService::class);

        $admin = User::query()
            ->whereIn('user_type', ['super-admin', 'admin-employee'])
            ->where('is_active', 1)
            ->first();

        if (! $admin) {
            $this->error('No active admin user found.');

            return self::FAILURE;
        }

        Auth::login($admin);

        $shouldKeep = (bool) $this->option('keep-data');
        if (! $shouldKeep) {
            DB::beginTransaction();
        }

        $passed = 0;
        $failed = 0;

        $scenarios = [
            'unknown_scenario_resolves' => fn () => $this->testUnknownScenarioResolves(),
            'unknown_type_change_blocked_without_call' => fn () => $this->testUnknownTypeChangeBlockedWithoutCall(),
            'unknown_type_change_allowed_after_call_step' => fn () => $this->testUnknownTypeChangeAllowedAfterCallStep(),
            'unknown_type_change_auto_via_followup' => fn () => $this->testUnknownTypeChangeAutoViaFollowup(),
            'unknown_type_change_auto_via_remarks' => fn () => $this->testUnknownTypeChangeAutoViaRemarks(),
            'unknown_type_change_bypass_with_confirmed' => fn () => $this->testUnknownTypeChangeBypassWithConfirmed(),
            'unknown_panel_updated_soft_gates' => fn () => $this->testUnknownPanelUpdatedSoftGates(),
            'customer_path_a_scenario_resolves' => fn () => $this->testCustomerPathAScenarioResolves(),
            'customer_create_booking_blocked_without_hard_steps' => fn () => $this->testCustomerCreateBookingBlockedWithoutHardSteps(),
            'customer_create_booking_allowed_after_hard_steps' => fn () => $this->testCustomerCreateBookingAllowedAfterHardSteps(),
            'customer_path_b_includes_discussion_step' => fn () => $this->testCustomerPathBIncludesDiscussionStep(),
            'customer_status_booked_blocked_without_booking' => fn () => $this->testCustomerStatusBookedBlockedWithoutBooking(),
            'customer_status_booked_allowed_with_booking' => fn () => $this->testCustomerStatusBookedAllowedWithBooking(),
            'provider_onboarding_scenario_resolves' => fn () => $this->testProviderOnboardingScenarioResolves(),
            'customer_booked_scenario_resolves' => fn () => $this->testCustomerBookedScenarioResolves(),
            'booking_completed_blocked_without_due_zero' => fn () => $this->testBookingCompletedBlockedWithoutDueZero(),
            'booking_completed_soft_gates_confirmable' => fn () => $this->testBookingCompletedSoftGatesConfirmable(),
        ];

        $this->info("Workflow gate test [{$this->tag}]");

        foreach ($scenarios as $name => $runner) {
            $this->line('');
            $this->info($name);
            try {
                $detail = $runner();
                $this->line("  <fg=green>PASS</> {$detail}");
                $passed++;
            } catch (\Throwable $e) {
                $this->line("  <fg=red>FAIL</> {$e->getMessage()}");
                $failed++;
            }
        }

        if (! $shouldKeep) {
            DB::rollBack();
            $this->line('');
            $this->info('Test transaction rolled back.');
        } else {
            DB::commit();
            $this->warn('Test data kept. Lead IDs: '.implode(', ', $this->leadIds));
        }

        $this->line('');
        $this->info("Passed: {$passed} | Failed: {$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function createUnknownLead(): Lead
    {
        $lead = Lead::create([
            'name' => "{$this->tag} unknown",
            'phone_number' => '7'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'lead_type' => Lead::TYPE_UNKNOWN,
            'handled_by' => Auth::id(),
        ]);
        $this->leadIds[] = $lead->id;

        return $lead->fresh(['followups']);
    }

    private function createCustomerLead(array $overrides = []): Lead
    {
        $lead = Lead::create(array_merge([
            'name' => "{$this->tag} customer",
            'phone_number' => '7'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'lead_type' => Lead::TYPE_CUSTOMER,
            'handled_by' => Auth::id(),
            'remarks' => 'Customer called about plumbing job',
        ], $overrides));
        $this->leadIds[] = $lead->id;

        LeadTypeHistory::create([
            'lead_id' => $lead->id,
            'type' => Lead::TYPE_CUSTOMER,
            'data' => [
                'customer_lead_status_id' => CustomerLeadStatus::defaultPendingStatusId(),
                'booking_status' => 'pending',
            ],
            'created_by' => Auth::id(),
        ]);

        return $lead->fresh(['followups']);
    }

    private function createProviderLead(): Lead
    {
        $lead = Lead::create([
            'name' => "{$this->tag} provider",
            'phone_number' => '7'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'lead_type' => Lead::TYPE_PROVIDER,
            'handled_by' => Auth::id(),
        ]);
        $this->leadIds[] = $lead->id;

        LeadTypeHistory::create([
            'lead_id' => $lead->id,
            'type' => Lead::TYPE_PROVIDER,
            'data' => ['provider_lead_status_id' => null],
            'created_by' => Auth::id(),
        ]);

        return $lead->fresh(['followups']);
    }

    private function markStepDone(Lead|Booking $entity, string $stepKey): void
    {
        $entityType = $entity instanceof Booking
            ? WorkflowStepCompletion::ENTITY_BOOKING
            : WorkflowStepCompletion::ENTITY_LEAD;
        $entityId = $entity instanceof Booking ? (string) $entity->id : (int) $entity->id;

        WorkflowStepCompletion::updateOrCreate(
            [
                'entity_type' => $entityType,
                'entity_id' => (string) $entityId,
                'step_key' => $stepKey,
            ],
            [
                'is_done' => true,
                'done_by' => Auth::id(),
                'done_at' => now(),
            ],
        );
    }

    private function assertBlocked(array $gate, string $context): void
    {
        if ($gate['allowed']) {
            throw new \RuntimeException("Expected gate to block: {$context}");
        }
    }

    private function assertAllowed(array $gate, string $context): void
    {
        if (! $gate['allowed']) {
            throw new \RuntimeException("Expected gate to allow: {$context} — {$gate['message']}");
        }
    }

    private function assertHasHardPending(array $gate, string $stepKey): void
    {
        $keys = array_column($gate['hard_pending'] ?? [], 'key');
        if (! in_array($stepKey, $keys, true)) {
            throw new \RuntimeException("Expected hard pending step {$stepKey}, got: ".implode(', ', $keys));
        }
    }

    private function testUnknownScenarioResolves(): string
    {
        $lead = $this->createUnknownLead();
        $ctx = $this->workflow->forLead($lead);

        if (($ctx['scenario'] ?? null) !== 'lead.unknown') {
            throw new \RuntimeException('Expected lead.unknown scenario.');
        }
        if (empty($ctx['steps'])) {
            throw new \RuntimeException('Expected workflow steps for unknown lead.');
        }

        return "lead #{$lead->id}, steps=".count($ctx['steps']);
    }

    private function testUnknownTypeChangeBlockedWithoutCall(): string
    {
        $lead = $this->createUnknownLead();
        $gate = $this->gate->checkLeadAction($lead, WorkflowStepDefinitions::ACTION_LEAD_TYPE_CHANGE);

        $this->assertBlocked($gate, 'unknown type change without call');
        $this->assertHasHardPending($gate, 'lead.unknown.call');

        return "lead #{$lead->id}";
    }

    private function testUnknownTypeChangeAllowedAfterCallStep(): string
    {
        $lead = $this->createUnknownLead();
        $this->markStepDone($lead, 'lead.unknown.call');

        $gate = $this->gate->checkLeadAction($lead->fresh(['followups']), WorkflowStepDefinitions::ACTION_LEAD_TYPE_CHANGE);
        $this->assertAllowed($gate, 'unknown type change after call step');

        return "lead #{$lead->id}";
    }

    private function testUnknownTypeChangeAutoViaFollowup(): string
    {
        $lead = $this->createUnknownLead();

        LeadFollowup::create([
            'lead_id' => $lead->id,
            'followup_status' => LeadFollowup::STATUS_TAKEN,
            'followup_at' => now(),
            'contact_channel' => LeadFollowup::CHANNEL_CALL,
            'remarks' => 'Called customer — needs AC repair',
            'created_by' => Auth::id(),
        ]);

        $lead = $lead->fresh(['followups']);
        $gate = $this->gate->checkLeadAction($lead, WorkflowStepDefinitions::ACTION_LEAD_TYPE_CHANGE);
        $this->assertAllowed($gate, 'follow-up taken auto-completes outbound call step');

        return "lead #{$lead->id}, follow-up auto-detect";
    }

    private function testUnknownTypeChangeAutoViaRemarks(): string
    {
        $lead = $this->createUnknownLead();
        $lead->update(['remarks' => 'Called customer — needs plumbing help']);
        $lead = $lead->fresh(['followups']);

        $gate = $this->gate->checkLeadAction($lead, WorkflowStepDefinitions::ACTION_LEAD_TYPE_CHANGE);
        $this->assertAllowed($gate, 'initial remarks auto-completes outbound call step');

        return "lead #{$lead->id}, remarks auto-detect";
    }

    private function testUnknownTypeChangeBypassWithConfirmed(): string
    {
        $lead = $this->createUnknownLead();

        // Gate service confirmed=true does NOT bypass hard requirements (by design).
        $gate = $this->gate->checkLeadAction($lead, WorkflowStepDefinitions::ACTION_LEAD_TYPE_CHANGE, [], true);
        $this->assertBlocked($gate, 'confirmed=true must not bypass hard outbound-call step');

        // Controller workflow_confirmed skips the pre-check entirely (UI sets this after soft confirm).
        app(LeadController::class)->updateType(
            Request::create('/', 'POST', [
                'lead_type' => Lead::TYPE_CUSTOMER,
                'workflow_confirmed' => '1',
            ]),
            $lead->id,
        );

        $lead->refresh();
        if ($lead->lead_type !== Lead::TYPE_CUSTOMER) {
            throw new \RuntimeException('Controller did not convert lead with workflow_confirmed.');
        }

        return "lead #{$lead->id} converted via controller bypass";
    }

    private function testUnknownPanelUpdatedSoftGates(): string
    {
        $lead = $this->createCustomerLead(['remarks' => 'Converted from unknown']);

        $gate = $this->gate->checkLeadAction($lead, WorkflowStepDefinitions::ACTION_LEAD_PANEL_UPDATED);
        $this->assertBlocked($gate, 'panel updated without confirming soft steps');

        $pendingKeys = array_column($gate['pending'] ?? [], 'key');
        foreach (['lead.unknown.panel_whatsapp', 'lead.unknown.log_followup'] as $expected) {
            if (! in_array($expected, $pendingKeys, true)) {
                throw new \RuntimeException("Expected soft pending {$expected}.");
            }
        }

        $confirmed = $this->gate->checkLeadAction($lead, WorkflowStepDefinitions::ACTION_LEAD_PANEL_UPDATED, [], true);
        $this->assertAllowed($confirmed, 'panel updated with confirmed=true');

        return "lead #{$lead->id}, soft=".count($pendingKeys);
    }

    private function testCustomerPathAScenarioResolves(): string
    {
        $lead = $this->createCustomerLead(['remarks' => 'Path A direct booking']);
        $ctx = $this->workflow->forLead($lead);

        if (($ctx['scenario'] ?? null) !== 'lead.customer.path_a') {
            throw new \RuntimeException('Expected lead.customer.path_a scenario, got '.($ctx['scenario'] ?? 'null'));
        }

        return "lead #{$lead->id}, progress={$ctx['progress_percent']}%";
    }

    private function testCustomerCreateBookingBlockedWithoutHardSteps(): string
    {
        $lead = $this->createCustomerLead([
            'remarks' => 'Customer needs plumbing help',
        ]);
        $gate = $this->gate->checkLeadAction($lead, WorkflowStepDefinitions::ACTION_LEAD_CREATE_BOOKING);

        $this->assertBlocked($gate, 'create booking without hard steps');

        $hardKeys = array_column($gate['hard_pending'] ?? [], 'key');
        if (! in_array('lead.customer.call', $hardKeys, true)) {
            throw new \RuntimeException('Expected hard pending lead.customer.call, got: '.implode(', ', $hardKeys));
        }
        if (in_array('lead.customer.path_decided', $hardKeys, true)) {
            throw new \RuntimeException('path_decided should not be a hard gate anymore.');
        }

        return "lead #{$lead->id}, hard=".implode(',', $hardKeys);
    }

    private function testCustomerCreateBookingAllowedAfterHardSteps(): string
    {
        $lead = $this->createCustomerLead([
            'remarks' => 'Customer called about plumbing job',
        ]);

        LeadTypeHistory::where('lead_id', $lead->id)->latest()->first()?->update([
            'data' => [
                'customer_lead_status_id' => CustomerLeadStatus::defaultPendingStatusId(),
                'booking_status' => 'pending',
                'zone_id' => Zone::query()->ofStatus(1)->value('id'),
                'service_description' => 'Test service need',
            ],
        ]);

        $lead = $lead->fresh(['followups']);
        $gate = $this->gate->checkLeadAction($lead, WorkflowStepDefinitions::ACTION_LEAD_CREATE_BOOKING);

        $this->assertBlocked($gate, 'create booking still has soft steps');
        if (! empty($gate['hard_pending'])) {
            throw new \RuntimeException('Hard steps should be satisfied after qualification data.');
        }

        $confirmed = $this->gate->checkLeadAction($lead, WorkflowStepDefinitions::ACTION_LEAD_CREATE_BOOKING, [], true);
        $this->assertAllowed($confirmed, 'create booking with confirmed=true after hard steps');

        return "lead #{$lead->id}, soft=".count($gate['pending'] ?? []);
    }

    private function testCustomerPathBIncludesDiscussionStep(): string
    {
        $lead = $this->createCustomerLead(['remarks' => 'Path B — customer wants discussion first']);
        $ctx = $this->workflow->forLead($lead);

        if (($ctx['scenario'] ?? null) !== 'lead.customer.path_b') {
            throw new \RuntimeException('Expected lead.customer.path_b scenario.');
        }

        $stepKeys = array_column($ctx['steps'] ?? [], 'key');
        if (! in_array('lead.customer.path_b_discussion', $stepKeys, true)) {
            throw new \RuntimeException('Path B scenario missing path_b_discussion step.');
        }

        LeadTypeHistory::where('lead_id', $lead->id)->latest()->first()?->update([
            'data' => [
                'customer_lead_status_id' => CustomerLeadStatus::defaultPendingStatusId(),
                'booking_status' => 'pending',
                'zone_id' => Zone::query()->ofStatus(1)->value('id'),
                'service_description' => 'Test',
            ],
        ]);

        $lead = $lead->fresh(['followups']);
        $gate = $this->gate->checkLeadAction($lead, WorkflowStepDefinitions::ACTION_LEAD_CREATE_BOOKING);
        $pendingKeys = array_column($gate['pending'] ?? [], 'key');

        if (! in_array('lead.customer.path_b_discussion', $pendingKeys, true)) {
            throw new \RuntimeException('Create booking gate should include path_b_discussion for Path B.');
        }

        return "lead #{$lead->id}, path_b_discussion in gate";
    }

    private function testCustomerStatusBookedBlockedWithoutBooking(): string
    {
        $lead = $this->createCustomerLead();
        $gate = $this->gate->checkLeadAction($lead, WorkflowStepDefinitions::ACTION_LEAD_STATUS_BOOKED);

        $this->assertBlocked($gate, 'status booked without booking');
        $this->assertHasHardPending($gate, 'lead.customer.create_booking');

        return "lead #{$lead->id}";
    }

    private function testCustomerStatusBookedAllowedWithBooking(): string
    {
        $lead = $this->createCustomerLead();
        $bookingId = (string) Str::uuid();

        LeadTypeHistory::where('lead_id', $lead->id)->latest()->first()?->update([
            'data' => [
                'customer_lead_status_id' => CustomerLeadStatus::defaultPendingStatusId(),
                'booking_status' => 'pending',
                'booking_id' => $bookingId,
            ],
        ]);

        $lead = $lead->fresh(['followups']);
        $gate = $this->gate->checkLeadAction($lead, WorkflowStepDefinitions::ACTION_LEAD_STATUS_BOOKED);

        $this->assertBlocked($gate, 'status booked may still have soft provider_group');
        if (! empty($gate['hard_pending'])) {
            throw new \RuntimeException('Hard steps should pass when booking_id is set.');
        }

        $confirmed = $this->gate->checkLeadAction($lead, WorkflowStepDefinitions::ACTION_LEAD_STATUS_BOOKED, [], true);
        $this->assertAllowed($confirmed, 'status booked with confirmed after booking linked');

        return "lead #{$lead->id}, booking_id set";
    }

    private function testProviderOnboardingScenarioResolves(): string
    {
        $lead = $this->createProviderLead();
        $ctx = $this->workflow->forLead($lead);

        if (($ctx['scenario'] ?? null) !== 'lead.provider.onboarding') {
            throw new \RuntimeException('Expected lead.provider.onboarding scenario.');
        }

        $stepKeys = array_column($ctx['steps'] ?? [], 'key');
        if (! in_array('lead.provider.brief_call', $stepKeys, true)) {
            throw new \RuntimeException('Provider onboarding missing brief_call step.');
        }

        return "lead #{$lead->id}, steps=".count($stepKeys);
    }

    private function testCustomerBookedScenarioResolves(): string
    {
        $lead = $this->createCustomerLead();
        $bookingId = (string) Str::uuid();

        LeadTypeHistory::where('lead_id', $lead->id)->latest()->first()?->update([
            'data' => [
                'customer_lead_status_id' => CustomerLeadStatus::where('base_type', 'booked')->value('id')
                    ?: CustomerLeadStatus::defaultPendingStatusId(),
                'booking_status' => 'booked',
                'booking_id' => $bookingId,
            ],
        ]);

        $lead = $lead->fresh(['followups']);
        $ctx = $this->workflow->forLead($lead);

        if (($ctx['scenario'] ?? null) !== 'lead.customer.booked') {
            throw new \RuntimeException('Expected lead.customer.booked scenario, got '.($ctx['scenario'] ?? 'null'));
        }

        return "lead #{$lead->id}";
    }

    private function testBookingCompletedBlockedWithoutDueZero(): string
    {
        $booking = $this->findOrSkipBooking();
        $gate = $this->gate->checkBookingAction($booking, WorkflowStepDefinitions::ACTION_BOOKING_COMPLETED);

        $this->assertBlocked($gate, 'booking completed without due zero');

        return "booking {$booking->readable_id}";
    }

    private function testBookingCompletedSoftGatesConfirmable(): string
    {
        $booking = $this->findOrSkipBooking();

        if (function_exists('booking_remaining_due') && (float) booking_remaining_due($booking) <= 0.01) {
            $gate = $this->gate->checkBookingAction($booking, WorkflowStepDefinitions::ACTION_BOOKING_COMPLETED);
            $this->assertAllowed($gate, 'booking with zero due should allow complete');

            return "booking {$booking->readable_id} due already zero";
        }

        $this->markStepDone($booking, 'booking.close.due_zero');
        $gate = $this->gate->checkBookingAction($booking->fresh(), WorkflowStepDefinitions::ACTION_BOOKING_COMPLETED);

        if (! empty($gate['hard_pending'])) {
            throw new \RuntimeException('due_zero marked done but still hard blocked.');
        }

        $confirmed = $this->gate->checkBookingAction($booking, WorkflowStepDefinitions::ACTION_BOOKING_COMPLETED, true);
        $this->assertAllowed($confirmed, 'booking completed with confirmed=true');

        return "booking {$booking->readable_id}, soft confirmable";
    }

    private function findOrSkipBooking(): Booking
    {
        $booking = Booking::query()
            ->whereNotIn('booking_status', ['completed', 'canceled', 'refunded'])
            ->orderByDesc('created_at')
            ->first();

        if (! $booking) {
            throw new \RuntimeException('No active booking found for booking gate tests.');
        }

        return $booking->fresh(['followups', 'booking_partial_payments']);
    }
}
