<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AdminModule\Entities\StaffActivityEvent;
use Modules\AdminModule\Entities\StaffPresencePeriod;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingChangeLog;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Entities\Source;
use Modules\ProviderManagement\Entities\Provider;
use Modules\TaskBoardModule\Entities\TaskColumn;
use Modules\TaskBoardModule\Entities\TaskTicket;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\WhatsAppChatStatus;
use Modules\WhatsAppModule\Entities\WhatsAppChatThreadMeta;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\ZoneManagement\Entities\Zone;

/**
 * Seeds demo data for employee dashboard and progress report across all admin employees.
 *
 * Run: php artisan db:seed --class=EmployeeDashboardDemoSeeder
 */
class EmployeeDashboardDemoSeeder extends Seeder
{
    private const LEAD_REMARKS = 'Progress report demo lead.';

    private const BOOKING_MARKER = '[PROGRESS-DEMO]';

    private const TASK_MARKER = '[PROGRESS-DEMO]';

    public function run(): void
    {
        if (! Schema::hasTable('leads')) {
            $this->command?->warn('Leads table missing; skipping EmployeeDashboardDemoSeeder.');

            return;
        }

        $employees = User::query()
            ->where('user_type', 'admin-employee')
            ->where('is_active', 1)
            ->orderBy('email')
            ->get();

        if ($employees->isEmpty()) {
            $this->command?->error('No active admin-employee users found.');

            return;
        }

        $this->purgeDemoData();

        $sourceIds = Source::query()->where('is_active', true)->orderBy('id')->limit(3)->pluck('id')->all();
        $sourceId = $sourceIds[0] ?? Source::query()->where('is_active', true)->orderBy('id')->value('id');
        $pendingStatusId = CustomerLeadStatus::defaultPendingStatusId();
        $bookedStatusId = CustomerLeadStatus::query()->where('base_type', 'completed')->orderBy('id')->value('id');
        $cancelStatusId = CustomerLeadStatus::query()->where('base_type', 'cancel')->orderBy('id')->value('id');
        $providerPendingId = ProviderLeadStatus::defaultPendingStatusId();
        $providerCompletedId = ProviderLeadStatus::query()->where('base_type', 'completed')->orderBy('id')->value('id');
        $providerCancelId = ProviderLeadStatus::query()->where('base_type', 'cancel')->orderBy('id')->value('id');
        $futureReasonId = LeadFutureCustomerReason::query()->where('is_active', true)->orderBy('id')->value('id');
        $invalidReasonId = LeadInvalidReason::query()->where('is_active', true)->orderBy('id')->value('id');
        $cancelReasonId = LeadCancellationReason::query()->where('is_active', true)->orderBy('id')->value('id');
        $zoneId = Zone::query()->ofStatus(1)->orderBy('name')->value('id');
        $provider = Provider::query()->first();
        $customer = User::query()->inCustomerDirectory()->first();
        $now = Carbon::now();
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        DB::transaction(function () use (
            $employees,
            $sourceIds,
            $sourceId,
            $pendingStatusId,
            $bookedStatusId,
            $cancelStatusId,
            $providerPendingId,
            $providerCompletedId,
            $providerCancelId,
            $futureReasonId,
            $invalidReasonId,
            $cancelReasonId,
            $zoneId,
            $provider,
            $customer,
            $now,
            $today,
            $monthStart,
        ) {
            foreach ($employees as $index => $employee) {
                $phonePrefix = '+1998'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

                $this->seedLeadsForEmployee(
                    $employee,
                    $phonePrefix,
                    $sourceIds,
                    $sourceId,
                    $pendingStatusId,
                    $bookedStatusId,
                    $cancelStatusId,
                    $providerPendingId,
                    $providerCompletedId,
                    $providerCancelId,
                    $futureReasonId,
                    $invalidReasonId,
                    $cancelReasonId,
                    $zoneId,
                    $today,
                    $now,
                    $monthStart,
                    $index,
                );

                $bookings = $this->seedBookingsForEmployee(
                    $employee,
                    $provider,
                    $customer,
                    $zoneId,
                    $today,
                    $now,
                    $monthStart,
                    $index,
                );

                $this->seedOutboundForEmployee($employee, $phonePrefix, $today, $now, $monthStart, $index);
                $this->seedMonthActivityForEmployee($employee, $phonePrefix, $today, $monthStart, $index, $bookings);
                $this->seedPresenceForEmployee($employee, $today, $monthStart, $index);
                $this->seedTasksForEmployee($employee, $today, $now, $index);
                $this->seedWhatsAppForEmployee($employee, $phonePrefix, $now, $index);
            }
        });

        $this->command?->info(sprintf(
            'Progress report demo seeded for %d employee(s): %s',
            $employees->count(),
            $employees->pluck('email')->implode(', '),
        ));
        $this->command?->info('View at /admin/my-progress (employee) or /admin/my-progress?employee_id=... (admin).');
    }

    private function seedLeadsForEmployee(
        User $employee,
        string $phonePrefix,
        array $sourceIds,
        ?int $sourceId,
        ?int $pendingStatusId,
        ?int $bookedStatusId,
        ?int $cancelStatusId,
        ?int $providerPendingId,
        ?int $providerCompletedId,
        ?int $providerCancelId,
        ?int $futureReasonId,
        ?int $invalidReasonId,
        ?int $cancelReasonId,
        ?string $zoneId,
        Carbon $today,
        Carbon $now,
        Carbon $monthStart,
        int $employeeIndex,
    ): void {
        $employeeId = (string) $employee->id;
        $namePrefix = 'Progress Demo E'.($employeeIndex + 1);
        $pickSource = fn (int $offset) => $sourceIds[$offset % max(1, count($sourceIds))] ?? $sourceId;

        $plans = [
            ['suffix' => '0001', 'name' => $namePrefix.' — Missed AC', 'type' => Lead::TYPE_CUSTOMER, 'followup' => $today->copy()->subDays(2)->setTime(10, 0), 'received_days_ago' => 8, 'outcome' => 'pending'],
            ['suffix' => '0002', 'name' => $namePrefix.' — Missed Plumbing', 'type' => Lead::TYPE_CUSTOMER, 'followup' => $today->copy()->subDay()->setTime(15, 30), 'received_days_ago' => 7, 'outcome' => 'pending'],
            ['suffix' => '0003', 'name' => $namePrefix.' — Due Today Geyser', 'type' => Lead::TYPE_CUSTOMER, 'followup' => $today->copy()->setTime(11, 0), 'received_days_ago' => 5, 'outcome' => 'pending'],
            ['suffix' => '0004', 'name' => $namePrefix.' — Upcoming Painting', 'type' => Lead::TYPE_CUSTOMER, 'followup' => $today->copy()->addDays(2)->setTime(12, 0), 'received_days_ago' => 4, 'outcome' => 'pending'],
            ['suffix' => '0005', 'name' => $namePrefix.' — Unknown Lead A', 'type' => Lead::TYPE_UNKNOWN, 'followup' => $today->copy()->addDay()->setTime(14, 0), 'received_days_ago' => 6],
            ['suffix' => '0006', 'name' => $namePrefix.' — Unknown Lead B', 'type' => Lead::TYPE_UNKNOWN, 'followup' => null, 'received_days_ago' => 10],
            ['suffix' => '0007', 'name' => $namePrefix.' — Future Customer', 'type' => Lead::TYPE_FUTURE_CUSTOMER, 'followup' => null, 'received_days_ago' => 9, 'future' => true],
            ['suffix' => '0008', 'name' => '', 'type' => Lead::TYPE_CUSTOMER, 'followup' => null, 'received_days_ago' => 3, 'missing' => 'name', 'outcome' => 'pending'],
            ['suffix' => '0009', 'name' => $namePrefix.' — Missing Status', 'type' => Lead::TYPE_CUSTOMER, 'followup' => null, 'received_days_ago' => 2, 'missing' => 'status'],
            ['suffix' => '0010', 'name' => $namePrefix.' — Added This Month', 'type' => Lead::TYPE_CUSTOMER, 'followup' => $today->copy()->addDays(3), 'received_days_ago' => 1, 'outcome' => 'pending'],
            ['suffix' => '0011', 'name' => $namePrefix.' — Booked Carpentry', 'type' => Lead::TYPE_CUSTOMER, 'followup' => null, 'received_days_ago' => 11, 'outcome' => 'booked', 'source_offset' => 1],
            ['suffix' => '0012', 'name' => $namePrefix.' — Booked Plumbing', 'type' => Lead::TYPE_CUSTOMER, 'followup' => null, 'received_days_ago' => 9, 'outcome' => 'booked', 'source_offset' => 2],
            ['suffix' => '0013', 'name' => $namePrefix.' — Cancelled Price', 'type' => Lead::TYPE_CUSTOMER, 'followup' => null, 'received_days_ago' => 13, 'outcome' => 'cancelled', 'source_offset' => 0],
            ['suffix' => '0014', 'name' => $namePrefix.' — Cancelled No Reply', 'type' => Lead::TYPE_CUSTOMER, 'followup' => null, 'received_days_ago' => 15, 'outcome' => 'cancelled', 'source_offset' => 1],
            ['suffix' => '0015', 'name' => $namePrefix.' — Provider Pending', 'type' => Lead::TYPE_PROVIDER, 'followup' => $today->copy()->addDays(1), 'received_days_ago' => 6, 'provider_outcome' => 'pending'],
            ['suffix' => '0016', 'name' => $namePrefix.' — Provider Registered', 'type' => Lead::TYPE_PROVIDER, 'followup' => null, 'received_days_ago' => 12, 'provider_outcome' => 'completed'],
            ['suffix' => '0017', 'name' => $namePrefix.' — Provider Dropped', 'type' => Lead::TYPE_PROVIDER, 'followup' => null, 'received_days_ago' => 14, 'provider_outcome' => 'cancelled'],
            ['suffix' => '0018', 'name' => $namePrefix.' — Invalid Wrong Number', 'type' => Lead::TYPE_INVALID, 'followup' => null, 'received_days_ago' => 5, 'invalid' => true],
            ['suffix' => '0019', 'name' => $namePrefix.' — Invalid Duplicate', 'type' => Lead::TYPE_INVALID, 'followup' => null, 'received_days_ago' => 8, 'invalid' => true],
            ['suffix' => '0020', 'name' => $namePrefix.' — Pending Electrical', 'type' => Lead::TYPE_CUSTOMER, 'followup' => $today->copy()->addDays(4), 'received_days_ago' => 3, 'outcome' => 'pending', 'source_offset' => 2],
        ];

        foreach ($plans as $planIndex => $plan) {
            $phone = $phonePrefix.str_pad($plan['suffix'], 4, '0', STR_PAD_LEFT);
            $daysAgo = (int) ($plan['received_days_ago'] ?? 3);
            $receivedAt = $today->copy()->subDays($daysAgo)->max($monthStart)->setTime(9 + (($employeeIndex + $planIndex) % 4), 15);

            $leadId = DB::table('leads')->insertGetId([
                'name' => ($plan['missing'] ?? '') === 'name' ? null : $plan['name'],
                'phone_number' => $phone,
                'source_id' => $pickSource((int) ($plan['source_offset'] ?? $planIndex)),
                'lead_type' => $plan['type'],
                'date_time_of_lead_received' => $receivedAt,
                'handled_by' => $employeeId,
                'remarks' => self::LEAD_REMARKS,
                'next_followup_at' => $plan['followup'] ?? null,
                'created_by' => $employeeId,
                'created_at' => $receivedAt,
                'updated_at' => $now,
            ]);

            if (! empty($plan['future']) && $futureReasonId && Schema::hasTable('lead_type_histories')) {
                LeadTypeHistory::create([
                    'lead_id' => $leadId,
                    'type' => Lead::TYPE_FUTURE_CUSTOMER,
                    'data' => [
                        'future_customer_reason_id' => $futureReasonId,
                        'future_customer_remarks' => 'Demo future customer for progress report.',
                    ],
                    'created_by' => $employeeId,
                ]);
            } elseif ($plan['type'] === Lead::TYPE_INVALID && $invalidReasonId && Schema::hasTable('lead_type_histories')) {
                LeadTypeHistory::create([
                    'lead_id' => $leadId,
                    'type' => Lead::TYPE_INVALID,
                    'data' => [
                        'invalid_reason_id' => $invalidReasonId,
                        'invalid_remarks' => 'Progress demo invalid lead.',
                    ],
                    'created_by' => $employeeId,
                ]);
            } elseif ($plan['type'] === Lead::TYPE_PROVIDER && Schema::hasTable('lead_type_histories')) {
                $providerOutcome = $plan['provider_outcome'] ?? 'pending';
                $statusId = match ($providerOutcome) {
                    'completed' => $providerCompletedId ?? $providerPendingId,
                    'cancelled' => $providerCancelId ?? $providerPendingId,
                    default => $providerPendingId,
                };

                if ($statusId) {
                    $data = [
                        'provider_lead_status_id' => $statusId,
                        'service_description' => 'Demo provider lead for progress report.',
                        'zone_id' => $zoneId,
                    ];
                    if ($providerOutcome === 'cancelled' && $cancelReasonId) {
                        $data['cancellation_reason_id'] = $cancelReasonId;
                        $data['cancellation_remarks'] = 'Provider did not complete onboarding.';
                    }

                    LeadTypeHistory::create([
                        'lead_id' => $leadId,
                        'type' => Lead::TYPE_PROVIDER,
                        'data' => $data,
                        'created_by' => $employeeId,
                    ]);
                }
            } elseif ($plan['type'] === Lead::TYPE_CUSTOMER
                && ($plan['missing'] ?? '') !== 'status'
                && Schema::hasTable('lead_type_histories')) {
                $outcome = $plan['outcome'] ?? 'pending';
                $statusId = match ($outcome) {
                    'booked' => $bookedStatusId ?? $pendingStatusId,
                    'cancelled' => $cancelStatusId ?? $pendingStatusId,
                    default => $pendingStatusId,
                };

                if ($statusId) {
                    $data = [
                        'customer_lead_status_id' => $statusId,
                        'booking_status' => match ($outcome) {
                            'booked' => 'booked',
                            'cancelled' => 'cancelled',
                            default => 'pending',
                        },
                        'zone_id' => $zoneId,
                        'service_description' => 'Demo customer lead for progress report.',
                    ];

                    if ($outcome === 'cancelled' && $cancelReasonId) {
                        $data['cancellation_reason_id'] = $cancelReasonId;
                        $data['cancellation_remarks'] = 'Customer declined after quote.';
                    }

                    LeadTypeHistory::create([
                        'lead_id' => $leadId,
                        'type' => Lead::TYPE_CUSTOMER,
                        'data' => $data,
                        'created_by' => $employeeId,
                    ]);
                }
            }

            if (Schema::hasTable('staff_activity_events') && ($plan['received_days_ago'] ?? 0) <= 14) {
                StaffActivityEvent::query()->create([
                    'employee_id' => $employeeId,
                    'actor_id' => $employeeId,
                    'event_type' => StaffActivityEvent::TYPE_LEAD_ASSIGNED,
                    'subject_type' => 'lead',
                    'subject_id' => (string) $leadId,
                    'meta' => ['demo' => true],
                    'created_at' => $receivedAt,
                    'updated_at' => $receivedAt,
                ]);
            }
        }
    }

    /**
     * @return Collection<int, Booking>
     */
    private function seedBookingsForEmployee(
        User $employee,
        ?Provider $provider,
        ?User $customer,
        ?string $zoneId,
        Carbon $today,
        Carbon $now,
        Carbon $monthStart,
        int $employeeIndex,
    ): Collection {
        if (! $provider || ! $customer || ! $zoneId) {
            return collect();
        }

        $employeeId = (string) $employee->id;
        $bookedLeadIds = Lead::query()
            ->where('handled_by', $employeeId)
            ->where('remarks', self::LEAD_REMARKS)
            ->where('name', 'like', '%Booked%')
            ->orderBy('id')
            ->pluck('id');

        $bookingPlans = [
            ['tag' => 'missed-followup', 'status' => 'ongoing', 'followup' => $today->copy()->subDays(2), 'created_days_ago' => 12],
            ['tag' => 'today-followup', 'status' => 'accepted', 'followup' => $today->copy(), 'created_days_ago' => 8],
            ['tag' => 'upcoming-followup', 'status' => 'pending', 'followup' => $today->copy()->addDays(3), 'created_days_ago' => 6],
            ['tag' => 'completed-mtd', 'status' => 'completed', 'followup' => null, 'created_days_ago' => 10, 'lead_index' => 0],
            ['tag' => 'completed-mtd-2', 'status' => 'completed', 'followup' => null, 'created_days_ago' => 9, 'lead_index' => 1],
            ['tag' => 'cancelled-mtd', 'status' => 'canceled', 'followup' => null, 'created_days_ago' => 7],
            ['tag' => 'pending-mtd', 'status' => 'pending', 'followup' => $today->copy()->addDays(5), 'created_days_ago' => 5],
            ['tag' => 'accepted-mtd', 'status' => 'accepted', 'followup' => $today->copy()->addDays(1), 'created_days_ago' => 4],
            ['tag' => 'ongoing-mtd', 'status' => 'ongoing', 'followup' => $today->copy()->subDay(), 'created_days_ago' => 11],
        ];

        $created = collect();

        foreach ($bookingPlans as $index => $plan) {
            $daysAgo = (int) $plan['created_days_ago'];
            $createdAt = $today->copy()->subDays($daysAgo)->max($monthStart)->setTime(11, 0);
            $updatedAt = in_array($plan['status'], ['completed', 'canceled'], true)
                ? $today->copy()->subDays(max(1, $daysAgo - 1))->max($monthStart)->setTime(16, 0)
                : $now;

            $leadId = isset($plan['lead_index']) ? $bookedLeadIds->get((int) $plan['lead_index']) : null;

            $booking = Booking::query()->create([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'assignee_id' => $employeeId,
                'lead_id' => $leadId,
                'booking_status' => $plan['status'],
                'payment_method' => 'cash_after_service',
                'is_paid' => $plan['status'] === 'completed' ? 1 : 0,
                'total_booking_amount' => 1800 + ($employeeIndex * 200) + ($index * 150),
                'total_tax_amount' => 0,
                'total_discount_amount' => 0,
                'service_location' => 'customer',
                'service_schedule' => $now->copy()->addDays($index + 1)->setTime(10, 0),
                'service_description' => self::BOOKING_MARKER.' '.$plan['tag'].' e'.($employeeIndex + 1),
                'booking_source' => $index % 2 === 0 ? 'admin_seed' : 'lead_conversion',
                'is_verified' => 1,
                'is_checked' => 1,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);

            $created->push($booking);

            BookingStatusHistory::query()->create([
                'booking_id' => $booking->id,
                'changed_by' => $employeeId,
                'booking_status' => $plan['status'],
                'status_change_remarks' => 'Progress report demo booking.',
                'created_at' => $updatedAt,
                'updated_at' => $updatedAt,
            ]);

            if (Schema::hasTable('booking_change_logs')) {
                BookingChangeLog::query()->create([
                    'booking_id' => $booking->id,
                    'changed_by' => $employeeId,
                    'property_key' => 'booking.created',
                    'property_label' => 'Booking created',
                    'new_value' => (string) $booking->readable_id,
                    'context' => 'progress_demo_seed',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            if (in_array($plan['status'], ['ongoing', 'accepted'], true) && Schema::hasTable('booking_status_histories')) {
                BookingStatusHistory::query()->create([
                    'booking_id' => $booking->id,
                    'changed_by' => $employeeId,
                    'booking_status' => 'accepted',
                    'status_change_remarks' => 'Progress demo status update.',
                    'created_at' => $createdAt->copy()->addHours(2),
                    'updated_at' => $createdAt->copy()->addHours(2),
                ]);
            }

            if ($plan['followup']) {
                BookingFollowup::query()->create([
                    'booking_id' => $booking->id,
                    'date' => $plan['followup']->toDateString(),
                    'followup_at' => $plan['followup'],
                    'status' => 'scheduled',
                    'reason' => 'Demo scheduled follow-up',
                    'for' => 'customer',
                    'urgency' => BookingFollowup::URGENCY_MEDIUM,
                    'created_by' => $employeeId,
                    'created_at' => $createdAt->copy()->addDay(),
                    'updated_at' => $now,
                ]);
            }
        }

        return $created;
    }

    private function seedOutboundForEmployee(
        User $employee,
        string $phonePrefix,
        Carbon $today,
        Carbon $now,
        Carbon $monthStart,
        int $employeeIndex,
    ): void {
        if (! Schema::hasTable('lead_outbound_enquiries')) {
            return;
        }

        $employeeId = (string) $employee->id;
        $lead = Lead::query()
            ->where('handled_by', $employeeId)
            ->where('remarks', self::LEAD_REMARKS)
            ->first();

        for ($i = 0; $i < 5 + ($employeeIndex % 2); $i++) {
            $contactedAt = $today->copy()->subDays(2 + $i + $employeeIndex)->max($monthStart)->setTime(10 + $i, 30);

            LeadOutboundEnquiry::query()->create([
                'lead_id' => $lead?->id,
                'customer_name' => 'Progress Demo Outbound '.($i + 1),
                'phone_number' => $phonePrefix.'9'.str_pad((string) (9000 + $i), 4, '0', STR_PAD_LEFT),
                'contacted_through' => match ($i % 3) { 0 => 'call', 1 => 'message', default => 'whatsapp' },
                'remarks' => self::BOOKING_MARKER.' outbound',
                'contacted_at' => $contactedAt,
                'created_by' => $employeeId,
                'handled_by' => $employeeId,
                'created_at' => $contactedAt,
                'updated_at' => $contactedAt,
            ]);
        }
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     */
    private function seedMonthActivityForEmployee(
        User $employee,
        string $phonePrefix,
        Carbon $today,
        Carbon $monthStart,
        int $employeeIndex,
        Collection $bookings,
    ): void {
        if (! Schema::hasTable('lead_followups')) {
            return;
        }

        $employeeId = (string) $employee->id;
        $leadIds = Lead::query()
            ->where('handled_by', $employeeId)
            ->where('phone_number', 'like', $phonePrefix.'%')
            ->orderBy('id')
            ->pluck('id');

        $followupPlans = [
            ['days_ago' => 1, 'hour' => 10, 'on_time' => true, 'lead_suffix' => '0011'],
            ['days_ago' => 2, 'hour' => 11, 'on_time' => true, 'lead_suffix' => '0012'],
            ['days_ago' => 4, 'hour' => 14, 'on_time' => true, 'lead_suffix' => '0013'],
            ['days_ago' => 6, 'hour' => 15, 'on_time' => true, 'lead_suffix' => '0014'],
            ['days_ago' => 3, 'hour' => 16, 'on_time' => false, 'lead_suffix' => '0003'],
            ['days_ago' => 5, 'hour' => 12, 'on_time' => false, 'lead_suffix' => '0004'],
            ['days_ago' => 7, 'hour' => 13, 'on_time' => false, 'lead_suffix' => '0020'],
            ['days_ago' => 9, 'hour' => 17, 'on_time' => false, 'lead_suffix' => '0010'],
            ['days_ago' => 11, 'hour' => 10, 'on_time' => true, 'lead_suffix' => '0015'],
            ['days_ago' => 13, 'hour' => 11, 'on_time' => false, 'lead_suffix' => '0016'],
        ];

        foreach ($followupPlans as $planIndex => $plan) {
            $leadId = Lead::query()
                ->where('handled_by', $employeeId)
                ->where('phone_number', $phonePrefix.str_pad((string) ($plan['lead_suffix'] ?? '0001'), 4, '0', STR_PAD_LEFT))
                ->value('id');

            if (! $leadId) {
                $leadId = $leadIds->get($planIndex % max(1, $leadIds->count()));
            }
            if (! $leadId) {
                continue;
            }

            $day = $today->copy()->subDays((int) $plan['days_ago'])->max($monthStart);
            $dueAt = $day->copy()->setTime((int) $plan['hour'], 0);
            $followupAt = ! empty($plan['on_time'])
                ? $dueAt->copy()->subHour()
                : $dueAt->copy()->addDay()->setTime(11, 30);
            $loggedAt = $followupAt->copy()->addMinutes(5);

            LeadFollowup::query()->create([
                'lead_id' => $leadId,
                'followup_at' => $followupAt,
                'due_followup_at' => $dueAt,
                'remarks' => 'Progress demo follow-up logged.',
                'followup_status' => LeadFollowup::STATUS_TAKEN,
                'contact_channel' => $planIndex % 2 === 0 ? LeadFollowup::CHANNEL_CALL : LeadFollowup::CHANNEL_WHATSAPP,
                'created_by' => $employeeId,
                'created_at' => $loggedAt,
                'updated_at' => $loggedAt,
            ]);
        }

        $bookingFollowupPlans = [
            ['days_ago' => 2, 'on_time' => true, 'for' => 'customer', 'tag' => 'completed-mtd'],
            ['days_ago' => 4, 'on_time' => true, 'for' => 'provider', 'tag' => 'completed-mtd-2'],
            ['days_ago' => 6, 'on_time' => true, 'for' => 'customer', 'tag' => 'cancelled-mtd'],
            ['days_ago' => 8, 'on_time' => false, 'for' => 'customer', 'tag' => 'pending-mtd'],
            ['days_ago' => 10, 'on_time' => false, 'for' => 'provider', 'tag' => 'ongoing-mtd'],
            ['days_ago' => 12, 'on_time' => true, 'for' => 'customer', 'tag' => 'accepted-mtd', 'rescheduled' => true],
        ];

        $bookingByTag = $bookings->keyBy(fn (Booking $booking) => str_replace(self::BOOKING_MARKER.' ', '', explode(' e', (string) $booking->service_description)[0] ?? ''));
        $bookingList = $bookings->values();
        foreach ($bookingFollowupPlans as $planIndex => $plan) {
            $booking = $bookingByTag->get($plan['tag']) ?? $bookingList->get($planIndex % max(1, $bookingList->count()));
            if (! $booking) {
                continue;
            }

            $day = $today->copy()->subDays((int) $plan['days_ago'])->max($monthStart);
            $dueAt = $day->copy()->setTime(10, 0);
            $followupAt = ! empty($plan['on_time'])
                ? $dueAt->copy()->subMinutes(30)
                : $dueAt->copy()->addDay()->setTime(10, 30);
            $loggedAt = $followupAt->copy()->addMinutes(8);
            $status = ! empty($plan['rescheduled']) ? 'rescheduled' : 'completed';

            BookingFollowup::query()->create([
                'booking_id' => $booking->id,
                'date' => $dueAt->toDateString(),
                'due_followup_at' => $dueAt,
                'followup_at' => $followupAt,
                'status' => $status,
                'reason' => 'Progress demo booking follow-up '.($planIndex + 1).'.',
                'for' => $plan['for'],
                'contact_channel' => BookingFollowup::CHANNEL_CALL,
                'urgency' => BookingFollowup::URGENCY_MEDIUM,
                'created_by' => $employeeId,
                'created_at' => $loggedAt,
                'updated_at' => $loggedAt,
            ]);
        }
    }

    private function seedPresenceForEmployee(
        User $employee,
        Carbon $today,
        Carbon $monthStart,
        int $employeeIndex,
    ): void {
        if (! Schema::hasTable('staff_presence_periods')) {
            return;
        }

        $days = [$today->copy(), $today->copy()->subDays(1), $today->copy()->subDays(4)->max($monthStart)];

        foreach ($days as $day) {
            $start = $day->copy()->setTime(9, 0);
            $end = $day->copy()->setTime(17, 30 - ($employeeIndex * 15));

            StaffPresencePeriod::query()->create([
                'user_id' => $employee->id,
                'status' => 'online',
                'started_at' => $start,
                'ended_at' => $end,
            ]);
        }
    }

    private function seedTasksForEmployee(User $employee, Carbon $today, Carbon $now, int $employeeIndex): void
    {
        if (! Schema::hasTable('task_tickets')) {
            return;
        }

        $todoColumn = TaskColumn::query()
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%done%'])
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%complete%'])
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%closed%'])
            ->orderBy('position')
            ->first();

        if (! $todoColumn) {
            return;
        }

        $employeeId = (string) $employee->id;
        $taskPlans = [
            ['title' => 'Clear missed follow-ups', 'end' => $today->copy()->subDays(1)],
            ['title' => 'Confirm today visits', 'end' => $today->copy()],
        ];

        foreach ($taskPlans as $index => $plan) {
            $ticket = TaskTicket::query()->create([
                'column_id' => $todoColumn->id,
                'title' => self::TASK_MARKER.' E'.($employeeIndex + 1).' — '.$plan['title'],
                'description' => 'Demo task for progress report.',
                'start_date' => $today->copy()->subDay(),
                'end_date' => $plan['end']->toDateString(),
                'position' => $index,
                'created_by' => $employeeId,
            ]);

            DB::table('task_ticket_assignees')->insert([
                'ticket_id' => $ticket->id,
                'user_id' => $employeeId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedWhatsAppForEmployee(User $employee, string $phonePrefix, Carbon $now, int $employeeIndex): void
    {
        if (! Schema::hasTable('whatsapp_users') || ! Schema::hasTable('whatsapp_messages')) {
            return;
        }

        $employeeId = (string) $employee->id;
        $openStatusId = WhatsAppChatStatus::query()->where('bucket', '!=', 'closed')->orderBy('id')->value('id');
        $phone = $phonePrefix.'8888';
        $digits = ltrim($phone, '+');

        WhatsAppUser::query()->updateOrCreate(
            ['phone' => $phone],
            [
                'name' => 'Progress Demo WA E'.($employeeIndex + 1),
                'handled_by' => $employeeId,
                'updated_at' => $now,
            ]
        );

        if (! WhatsAppMessage::query()->where('phone', $digits)->where('wa_message_id', 'progress-demo-'.$employeeIndex)->exists()) {
            WhatsAppMessage::query()->create([
                'channel' => 'whatsapp',
                'phone' => $digits,
                'message_text' => 'Hi, checking on my service request.',
                'direction' => 'IN',
                'message_type' => 'text',
                'wa_message_id' => 'progress-demo-'.$employeeIndex,
                'created_at' => $now->copy()->subHours(2),
            ]);

            WhatsAppMessage::query()->create([
                'channel' => 'whatsapp',
                'phone' => $digits,
                'message_text' => 'Thanks, we will call you shortly.',
                'direction' => 'OUT',
                'message_type' => 'text',
                'wa_message_id' => 'progress-demo-reply-'.$employeeIndex,
                'sent_by_id' => $employeeId,
                'created_at' => $now->copy()->subHour(),
            ]);
        }

        if ($openStatusId && Schema::hasTable('whatsapp_chat_thread_meta')) {
            WhatsAppChatThreadMeta::query()->updateOrCreate(
                ['phone' => $phone],
                [
                    'whatsapp_chat_status_id' => $openStatusId,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function purgeDemoData(): void
    {
        $leadIds = DB::table('leads')
            ->where('remarks', self::LEAD_REMARKS)
            ->pluck('id')
            ->all();

        if ($leadIds !== []) {
            if (Schema::hasTable('lead_followups')) {
                DB::table('lead_followups')->whereIn('lead_id', $leadIds)->delete();
            }
            if (Schema::hasTable('lead_type_histories')) {
                DB::table('lead_type_histories')->whereIn('lead_id', $leadIds)->delete();
            }
            if (Schema::hasTable('lead_outbound_enquiries')) {
                DB::table('lead_outbound_enquiries')->whereIn('lead_id', $leadIds)->delete();
            }
            DB::table('leads')->whereIn('id', $leadIds)->delete();
        }

        if (Schema::hasTable('lead_outbound_enquiries')) {
            DB::table('lead_outbound_enquiries')
                ->where('remarks', 'like', self::BOOKING_MARKER.'%')
                ->delete();
        }

        $bookingIds = Booking::query()
            ->where('service_description', 'like', self::BOOKING_MARKER.'%')
            ->pluck('id')
            ->all();

        if ($bookingIds !== []) {
            if (Schema::hasTable('booking_followups')) {
                DB::table('booking_followups')->whereIn('booking_id', $bookingIds)->delete();
            }
            if (Schema::hasTable('booking_change_logs')) {
                DB::table('booking_change_logs')->whereIn('booking_id', $bookingIds)->delete();
            }
            if (Schema::hasTable('booking_status_histories')) {
                DB::table('booking_status_histories')->whereIn('booking_id', $bookingIds)->delete();
            }
            Booking::query()->whereIn('id', $bookingIds)->delete();
        }

        if (Schema::hasTable('staff_activity_events')) {
            DB::table('staff_activity_events')
                ->where('meta->demo', true)
                ->delete();
        }

        if (Schema::hasTable('staff_presence_periods')) {
            $demoUserIds = User::query()
                ->where('user_type', 'admin-employee')
                ->pluck('id')
                ->all();

            if ($demoUserIds !== []) {
                DB::table('staff_presence_periods')
                    ->whereIn('user_id', $demoUserIds)
                    ->where('started_at', '>=', Carbon::now()->startOfMonth()->subDay())
                    ->whereTime('started_at', '09:00:00')
                    ->delete();
            }
        }

        if (Schema::hasTable('task_tickets')) {
            $ticketIds = DB::table('task_tickets')
                ->where('title', 'like', self::TASK_MARKER.'%')
                ->pluck('id')
                ->all();

            if ($ticketIds !== []) {
                DB::table('task_ticket_assignees')->whereIn('ticket_id', $ticketIds)->delete();
                DB::table('task_tickets')->whereIn('id', $ticketIds)->delete();
            }
        }

        if (Schema::hasTable('whatsapp_users')) {
            $phones = DB::table('whatsapp_users')
                ->where('phone', 'like', '+1998%')
                ->pluck('phone')
                ->all();

            if ($phones !== []) {
                if (Schema::hasTable('whatsapp_chat_thread_meta')) {
                    DB::table('whatsapp_chat_thread_meta')->whereIn('phone', $phones)->delete();
                }
                if (Schema::hasTable('whatsapp_messages')) {
                    foreach ($phones as $phone) {
                        DB::table('whatsapp_messages')->where('phone', ltrim($phone, '+'))->delete();
                    }
                }
                DB::table('whatsapp_users')->whereIn('phone', $phones)->delete();
            }
        }
    }
}
