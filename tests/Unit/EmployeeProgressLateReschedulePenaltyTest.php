<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\AdminModule\Services\EmployeeProgressScoreService;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;
use Tests\TestCase;

class EmployeeProgressLateReschedulePenaltyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('booking_followups');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('lead_followups');
        Schema::dropIfExists('leads');

        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assignee_id')->nullable();
            $table->string('booking_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('booking_followups', function (Blueprint $table) {
            $table->id();
            $table->uuid('booking_id');
            $table->string('for')->nullable();
            $table->string('status')->default('scheduled');
            $table->dateTime('date')->nullable();
            $table->dateTime('followup_at')->nullable();
            $table->dateTime('due_followup_at')->nullable();
            $table->dateTime('rescheduled_at')->nullable();
            $table->dateTime('next_followup_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('handled_by')->nullable();
            $table->string('lead_type')->nullable();
            $table->dateTime('date_time_of_lead_received')->nullable();
            $table->dateTime('non_responsive_at')->nullable();
            $table->dateTime('next_followup_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_followups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->dateTime('followup_at')->nullable();
            $table->dateTime('due_followup_at')->nullable();
            $table->dateTime('next_followup_at')->nullable();
            $table->string('followup_status')->nullable();
            $table->timestamps();
        });
    }

    public function test_late_booking_reschedule_keeps_negative_marks(): void
    {
        Carbon::setTestNow('2026-11-24 15:00:00');

        $employeeId = '11111111-1111-1111-1111-111111111111';
        $bookingId = '22222222-2222-2222-2222-222222222222';

        Booking::query()->insert([
            'id' => $bookingId,
            'assignee_id' => $employeeId,
            'booking_status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        BookingFollowup::query()->insert([
            'booking_id' => $bookingId,
            'for' => 'customer',
            'status' => 'rescheduled',
            'date' => '2026-11-24 11:00:00',
            'due_followup_at' => '2026-11-24 11:00:00',
            'followup_at' => null,
            'rescheduled_at' => '2026-11-24 13:10:00',
            'next_followup_at' => '2026-11-24 16:00:00',
            'created_at' => '2026-11-24 13:10:00',
            'updated_at' => '2026-11-24 13:10:00',
        ]);

        // New future scheduled follow-up after reschedule (must not wipe the late mark).
        BookingFollowup::query()->insert([
            'booking_id' => $bookingId,
            'for' => 'customer',
            'status' => 'scheduled',
            'date' => '2026-11-24 16:00:00',
            'created_at' => '2026-11-24 13:10:00',
            'updated_at' => '2026-11-24 13:10:00',
        ]);

        $late = app(EmployeeProgressScoreService::class)->lateFollowupPenaltiesByEmployee(
            [$employeeId],
            Carbon::parse('2026-11-24')->startOfDay(),
            Carbon::parse('2026-11-24')->endOfDay()
        );

        $this->assertSame(1, $late[$employeeId]['total_count']);
        $this->assertSame(-3, $late[$employeeId]['total_points']);
        $this->assertSame(1, $late[$employeeId]['buckets']['late_4h']['count']);

        Carbon::setTestNow();
    }

    public function test_on_time_booking_reschedule_has_no_penalty(): void
    {
        Carbon::setTestNow('2026-11-24 15:00:00');

        $employeeId = '33333333-3333-3333-3333-333333333333';
        $bookingId = '44444444-4444-4444-4444-444444444444';

        Booking::query()->insert([
            'id' => $bookingId,
            'assignee_id' => $employeeId,
            'booking_status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        BookingFollowup::query()->insert([
            'booking_id' => $bookingId,
            'for' => 'customer',
            'status' => 'rescheduled',
            'date' => '2026-11-24 11:00:00',
            'due_followup_at' => '2026-11-24 11:00:00',
            'rescheduled_at' => '2026-11-24 10:30:00',
            'created_at' => '2026-11-24 10:30:00',
            'updated_at' => '2026-11-24 10:30:00',
        ]);

        $late = app(EmployeeProgressScoreService::class)->lateFollowupPenaltiesByEmployee(
            [$employeeId],
            Carbon::parse('2026-11-24')->startOfDay(),
            Carbon::parse('2026-11-24')->endOfDay()
        );

        $this->assertSame(0, $late[$employeeId]['total_count']);
        $this->assertSame(0, $late[$employeeId]['total_points']);

        Carbon::setTestNow();
    }
}
