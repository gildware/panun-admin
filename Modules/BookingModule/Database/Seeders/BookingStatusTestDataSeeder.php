<?php

namespace Modules\BookingModule\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingReopenEvent;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class BookingStatusTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $provider = Provider::query()->first();
        $customer = User::query()->inCustomerDirectory()->first();
        $adminActor = User::query()
            ->whereIn('user_type', ['super-admin', 'admin-employee', 'provider-admin'])
            ->first();

        if (! $provider || ! $customer) {
            $this->command?->warn('BookingStatusTestDataSeeder skipped: missing provider or customer rows.');
            $this->command?->warn('Create at least 1 provider and 1 customer, then re-run this seeder.');
            return;
        }

        $zoneId = (string) ($provider->zone_id ?? '');
        if ($zoneId === '') {
            $this->command?->warn('BookingStatusTestDataSeeder skipped: provider.zone_id is empty.');
            return;
        }

        DB::transaction(function () use ($provider, $customer, $adminActor, $zoneId) {
            $now = now();

            // Use stable, searchable description markers so you can filter in admin lists quickly.
            $mk = fn (string $tag): string => "[TEST-STATUS:$tag] Auto-generated on " . $now->format('Y-m-d H:i:s');

            // 1) Ongoing (normal)
            $ongoingA = $this->createBooking([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'booking_status' => 'ongoing',
                'payment_method' => 'cash_after_service',
                'is_paid' => 0,
                'service_schedule' => Carbon::now()->addHours(2),
                'service_description' => $mk('ongoing-normal-A'),
            ]);
            $this->seedStatusHistory($ongoingA, 'ongoing', $adminActor?->id, $mk('status-history'));

            // 2) Ongoing (reopen dispute special-case: follow-up booking spawned from a completed job)
            $completedParentForDispute = $this->createBooking([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'booking_status' => 'completed',
                'payment_method' => 'digital_payment',
                'is_paid' => 1,
                'service_schedule' => Carbon::now()->subDays(2),
                'service_description' => $mk('completed-parent-for-dispute'),
            ]);
            $this->seedStatusHistory($completedParentForDispute, 'completed', $adminActor?->id, $mk('status-history'));

            $disputeChild = $this->createBooking([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'booking_status' => 'ongoing',
                'payment_method' => 'cash_after_service',
                'is_paid' => 0,
                'service_schedule' => Carbon::now()->addDays(1),
                'service_description' => $mk('ongoing-reopen-dispute-B'),
                'originated_from_booking_id' => $completedParentForDispute->id,
                'last_reopen_event_at' => $now,
                'reopened_by' => $adminActor?->id,
                'reopen_disputed_snapshot' => [
                    'scenario' => 'dispute',
                    'notes' => 'Seeded dispute snapshot for UI testing.',
                ],
                'reopen_completion_allowed' => false,
            ]);
            $this->seedStatusHistory($disputeChild, 'ongoing', $adminActor?->id, $mk('status-history'));

            BookingReopenEvent::query()->create([
                'source_booking_id' => $completedParentForDispute->id,
                'actor_user_id' => $adminActor?->id,
                'resolution' => BookingReopenEvent::RESOLUTION_NEW_BOOKING,
                'complaint_notes' => 'Seeded dispute event for test coverage.',
                'child_booking_id' => $disputeChild->id,
                'target_status' => 'ongoing',
            ]);

            // 3) Canceled (normal)
            $canceledA = $this->createBooking([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'booking_status' => 'canceled',
                'payment_method' => 'cash_after_service',
                'is_paid' => 0,
                'service_schedule' => Carbon::now()->addHours(5),
                'service_description' => $mk('canceled-normal-A'),
            ]);
            $this->seedStatusHistory($canceledA, 'canceled', $adminActor?->id, $mk('status-history'));

            // 4) Canceled (after-visit settlement special-case)
            $canceledAfterVisit = $this->createBooking([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'booking_status' => 'canceled',
                'payment_method' => 'cash_after_service',
                'is_paid' => 0,
                'service_schedule' => Carbon::now()->subHours(3),
                'service_description' => $mk('canceled-after-visit-B'),
                'after_visit_cancel' => true,
                'settlement_outcome' => BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL,
                'settlement_remarks' => 'Seeded: cancel but keep visit fee.',
            ]);
            $this->seedStatusHistory($canceledAfterVisit, 'canceled', $adminActor?->id, $mk('status-history'));

            // 5) Completed (normal)
            $completedA = $this->createBooking([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'booking_status' => 'completed',
                'payment_method' => 'digital_payment',
                'is_paid' => 1,
                'service_schedule' => Carbon::now()->subDay(),
                'service_description' => $mk('completed-normal-A'),
            ]);
            $this->seedStatusHistory($completedA, 'completed', $adminActor?->id, $mk('status-history'));

            // 6) Completed (reopen resolved special-case)
            $completedParentForResolved = $this->createBooking([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'booking_status' => 'completed',
                'payment_method' => 'digital_payment',
                'is_paid' => 1,
                'service_schedule' => Carbon::now()->subDays(5),
                'service_description' => $mk('completed-parent-for-resolved'),
            ]);
            $this->seedStatusHistory($completedParentForResolved, 'completed', $adminActor?->id, $mk('status-history'));

            $resolvedChild = $this->createBooking([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'booking_status' => 'completed',
                'payment_method' => 'digital_payment',
                'is_paid' => 1,
                'service_schedule' => Carbon::now()->subDays(1),
                'service_description' => $mk('completed-reopen-resolved-B'),
                'originated_from_booking_id' => $completedParentForResolved->id,
                'last_reopen_event_at' => $now->copy()->subHours(6),
                'reopened_by' => $adminActor?->id,
                'reopen_completion_allowed' => true,
                'reopen_resolved_at' => $now,
                'reopen_resolved_by' => $adminActor?->id,
                'reopen_resolve_remarks' => 'Seeded: reopen resolved for test coverage.',
            ]);
            $this->seedStatusHistory($resolvedChild, 'completed', $adminActor?->id, $mk('status-history'));

            BookingReopenEvent::query()->create([
                'source_booking_id' => $completedParentForResolved->id,
                'actor_user_id' => $adminActor?->id,
                'resolution' => BookingReopenEvent::RESOLUTION_NEW_BOOKING,
                'complaint_notes' => 'Seeded reopen-resolved event for test coverage.',
                'child_booking_id' => $resolvedChild->id,
                'target_status' => 'completed',
            ]);

            // 7) Disputed + closed (refund-all => status refunded)
            $completedParentForDisputedRefunded = $this->createBooking([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'booking_status' => 'completed',
                'payment_method' => 'digital_payment',
                'is_paid' => 1,
                'service_schedule' => Carbon::now()->subDays(3),
                'service_description' => $mk('completed-parent-for-disputed-refunded'),
            ]);
            $this->seedStatusHistory($completedParentForDisputedRefunded, 'completed', $adminActor?->id, $mk('status-history'));

            $disputedRefunded = $this->createBooking([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'booking_status' => 'refunded',
                'payment_method' => 'digital_payment',
                'is_paid' => 1,
                'service_schedule' => Carbon::now()->subDays(1),
                'service_description' => $mk('disputed-closed-refunded-A'),
                'originated_from_booking_id' => $completedParentForDisputedRefunded->id,
                'last_reopen_event_at' => $now->copy()->subHours(10),
                'reopened_by' => $adminActor?->id,
                'reopen_completion_allowed' => false,
                'reopen_disputed_snapshot' => [
                    'type' => 'reopen_disputed_refund',
                    'seeded' => true,
                    'refund_total' => 100.00,
                    'refund_company_amount' => 60.00,
                    'refund_provider_amount' => 40.00,
                ],
                'reopen_resolved_at' => $now,
                'reopen_resolved_by' => $adminActor?->id,
                'reopen_resolve_remarks' => 'Seeded: disputed refund (all) and case closed.',
            ]);
            $this->seedStatusHistory($disputedRefunded, 'refunded', $adminActor?->id, 'reopen_disputed: seeded refund all');

            BookingReopenEvent::query()->create([
                'source_booking_id' => $completedParentForDisputedRefunded->id,
                'actor_user_id' => $adminActor?->id,
                'resolution' => BookingReopenEvent::RESOLUTION_NEW_BOOKING,
                'complaint_notes' => 'Seeded disputed refund (all) event.',
                'child_booking_id' => $disputedRefunded->id,
                'target_status' => 'refunded',
            ]);

            // 8) Disputed + closed (partial refund => status canceled)
            $completedParentForDisputedCanceled = $this->createBooking([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'booking_status' => 'completed',
                'payment_method' => 'digital_payment',
                'is_paid' => 1,
                'service_schedule' => Carbon::now()->subDays(4),
                'service_description' => $mk('completed-parent-for-disputed-canceled'),
            ]);
            $this->seedStatusHistory($completedParentForDisputedCanceled, 'completed', $adminActor?->id, $mk('status-history'));

            $disputedCanceled = $this->createBooking([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'booking_status' => 'canceled',
                'payment_method' => 'digital_payment',
                'is_paid' => 1,
                'service_schedule' => Carbon::now()->subDays(1),
                'service_description' => $mk('disputed-closed-canceled-B'),
                'originated_from_booking_id' => $completedParentForDisputedCanceled->id,
                'last_reopen_event_at' => $now->copy()->subHours(8),
                'reopened_by' => $adminActor?->id,
                'reopen_completion_allowed' => false,
                'reopen_disputed_snapshot' => [
                    'type' => 'reopen_disputed_refund',
                    'seeded' => true,
                    'refund_total' => 20.00,
                    'refund_company_amount' => 20.00,
                    'refund_provider_amount' => 0.00,
                    'retained_from_customer' => 80.00,
                ],
                'reopen_resolved_at' => $now,
                'reopen_resolved_by' => $adminActor?->id,
                'reopen_resolve_remarks' => 'Seeded: disputed partial refund and case closed (canceled).',
            ]);
            $this->seedStatusHistory($disputedCanceled, 'canceled', $adminActor?->id, 'reopen_disputed: seeded partial refund');

            BookingReopenEvent::query()->create([
                'source_booking_id' => $completedParentForDisputedCanceled->id,
                'actor_user_id' => $adminActor?->id,
                'resolution' => BookingReopenEvent::RESOLUTION_NEW_BOOKING,
                'complaint_notes' => 'Seeded disputed partial refund event.',
                'child_booking_id' => $disputedCanceled->id,
                'target_status' => 'canceled',
            ]);

            // 9) Closed reopen (in-place reopen event on same booking)
            $inPlaceReopenClosed = $this->createBooking([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'booking_status' => 'completed',
                'payment_method' => 'digital_payment',
                'is_paid' => 1,
                'service_schedule' => Carbon::now()->subDays(2),
                'service_description' => $mk('closed-reopen-in-place-C'),
                'last_reopen_event_at' => $now->copy()->subDay(),
                'reopened_by' => $adminActor?->id,
                'reopen_completion_allowed' => false,
                'reopen_resolved_at' => $now,
                'reopen_resolved_by' => $adminActor?->id,
                'reopen_resolve_remarks' => 'Seeded: in-place reopen then case closed.',
            ]);
            $this->seedStatusHistory($inPlaceReopenClosed, 'completed', $adminActor?->id, 'reopen_resolved_complete: seeded');

            BookingReopenEvent::query()->create([
                'source_booking_id' => $inPlaceReopenClosed->id,
                'actor_user_id' => $adminActor?->id,
                'resolution' => BookingReopenEvent::RESOLUTION_REOPEN_IN_PLACE,
                'complaint_notes' => 'Seeded in-place reopen event.',
                'child_booking_id' => null,
                'target_status' => 'ongoing',
            ]);
        });

        $this->command?->info('Seeded test bookings for ongoing/canceled/completed + dispute/resolved + disputed-closed/refunded cases.');
        $this->command?->info('Search marker: [TEST-STATUS:...] in booking service_description.');
    }

    /**
     * Create booking using model create (keeps readable_id allocator, module hooks, etc.).
     * We intentionally set booking_status on create to avoid update-time validations.
     *
     * @param array<string,mixed> $attrs
     */
    private function createBooking(array $attrs): Booking
    {
        $defaults = [
            'total_booking_amount' => 0,
            'total_tax_amount' => 0,
            'total_discount_amount' => 0,
            // bookings.service_location is NOT NULL with default('customer') in older migrations.
            // Set explicitly so we don't override the DB default with NULL.
            'service_location' => 'customer',
            'service_address_location' => null,
            'booking_source' => 'admin_seed',
            'is_verified' => 1,
            'is_checked' => 1,
        ];

        /** @var Booking $booking */
        $booking = Booking::query()->create(array_merge($defaults, $attrs));
        return $booking->fresh();
    }

    private function seedStatusHistory(Booking $booking, string $status, ?string $changedBy, ?string $remarks = null): void
    {
        BookingStatusHistory::query()->create([
            'booking_id' => $booking->id,
            'changed_by' => $changedBy,
            'booking_status' => $status,
            'status_change_remarks' => $remarks,
        ]);
    }
}

