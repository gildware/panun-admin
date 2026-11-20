<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\BookingChangeLog;
use Modules\BookingModule\Services\BookingAuditLogger;
use Tests\TestCase;

class BookingFollowupMissedAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('booking_change_logs');
        Schema::create('booking_change_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('booking_id');
            $table->uuid('changed_by')->nullable();
            $table->string('actor_name', 255)->nullable();
            $table->string('property_key', 191);
            $table->string('property_label', 255)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('context', 255)->nullable();
            $table->timestamps();
        });
    }

    public function test_reschedule_after_due_logs_missed_and_the_move(): void
    {
        $bookingId = 'f21a831a-0d17-4cce-9050-9fe27f84e066';
        $due = Carbon::parse('2026-11-20 11:00:00');
        $movedTo = Carbon::parse('2026-11-20 17:00:00');
        $actedAt = Carbon::parse('2026-11-20 13:10:18');

        $this->assertTrue(BookingAuditLogger::recordFollowupMissedIfPast($bookingId, $due, $actedAt, 'customer'));
        BookingAuditLogger::recordFollowupRescheduled($bookingId, $due, $movedTo, 'busy', 'customer');

        $this->assertFalse(BookingAuditLogger::recordFollowupMissedIfPast($bookingId, $due, Carbon::parse('2026-11-20 14:00:00'), 'customer'));

        $logs = BookingChangeLog::query()->where('booking_id', $bookingId)->orderBy('id')->get();
        $this->assertCount(2, $logs);

        $missed = $logs[0];
        $this->assertSame('followup.missed', $missed->property_key);
        $this->assertSame('due:2026-11-20 11:00:00|for:customer', $missed->context);
        $this->assertSame(translate('Missed_Follow_up'), $missed->new_value);

        $moved = $logs[1];
        $this->assertSame('followup.rescheduled', $moved->property_key);
        $this->assertSame('20 November 2026 11:00 am', $moved->old_value);
        $this->assertSame('20 November 2026 05:00 pm', $moved->new_value);
    }

    public function test_reschedule_before_due_does_not_log_missed(): void
    {
        $bookingId = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
        $due = Carbon::parse('2026-11-20 17:00:00');
        $actedAt = Carbon::parse('2026-11-20 13:00:00');

        $this->assertFalse(BookingAuditLogger::recordFollowupMissedIfPast($bookingId, $due, $actedAt, 'provider'));
        BookingAuditLogger::recordFollowupRescheduled($bookingId, $due, Carbon::parse('2026-11-20 18:00:00'), null, 'provider');

        $logs = BookingChangeLog::query()->where('booking_id', $bookingId)->get();
        $this->assertCount(1, $logs);
        $this->assertSame('followup.rescheduled', $logs[0]->property_key);
    }

    public function test_customer_and_provider_misses_are_logged_separately(): void
    {
        $bookingId = '11111111-2222-3333-4444-555555555555';
        $due = Carbon::parse('2026-11-20 11:00:00');
        $actedAt = Carbon::parse('2026-11-20 13:10:00');

        $this->assertTrue(BookingAuditLogger::recordFollowupMissedIfPast($bookingId, $due, $actedAt, 'customer'));
        $this->assertTrue(BookingAuditLogger::recordFollowupMissedIfPast($bookingId, $due, $actedAt, 'provider'));

        $this->assertSame(2, BookingChangeLog::query()->where('booking_id', $bookingId)->where('property_key', 'followup.missed')->count());
    }

    public function test_taken_on_time_logs_due_and_taken_without_a_miss(): void
    {
        $bookingId = '22222222-3333-4444-5555-666666666666';
        $due = Carbon::parse('2026-11-20 11:00:00');
        $taken = Carbon::parse('2026-11-20 10:30:00');

        $this->assertFalse(BookingAuditLogger::recordFollowupMissedIfPast($bookingId, $due, $taken, 'customer'));
        BookingAuditLogger::recordFollowupTaken($bookingId, $due, $taken, 'Spoke to customer', 'customer');

        $log = BookingChangeLog::query()->where('booking_id', $bookingId)->first();
        $this->assertNotNull($log);
        $this->assertSame('followup.taken', $log->property_key);
        $this->assertSame('20 November 2026 11:00 am', $log->old_value);
        $this->assertSame('20 November 2026 10:30 am', $log->new_value);
        $this->assertSame('for:customer|reason:Spoke to customer', $log->context);
    }

    public function test_taken_after_due_logs_missed_then_taken(): void
    {
        $bookingId = '33333333-4444-5555-6666-777777777777';
        $due = Carbon::parse('2026-11-20 11:00:00');
        $taken = Carbon::parse('2026-11-20 12:15:00');

        BookingAuditLogger::recordFollowupMissedIfPast($bookingId, $due, $taken, 'provider');
        BookingAuditLogger::recordFollowupTaken($bookingId, $due, $taken, null, 'provider');

        $logs = BookingChangeLog::query()->where('booking_id', $bookingId)->orderBy('id')->get();
        $this->assertCount(2, $logs);
        $this->assertSame('followup.missed', $logs[0]->property_key);
        $this->assertSame('followup.taken', $logs[1]->property_key);
        $this->assertSame('20 November 2026 12:15 pm', $logs[1]->new_value);
    }

    public function test_edited_logs_previous_and_new_time(): void
    {
        $bookingId = '44444444-5555-6666-7777-888888888888';
        $from = Carbon::parse('2026-11-20 11:00:00');
        $to = Carbon::parse('2026-11-20 16:00:00');

        BookingAuditLogger::recordFollowupEdited($bookingId, $from, $to, 'date', 'customer');

        $log = BookingChangeLog::query()->where('booking_id', $bookingId)->first();
        $this->assertNotNull($log);
        $this->assertSame('followup.edited', $log->property_key);
        $this->assertSame(translate('Follow_up_edited'), $log->property_label);
        $this->assertSame('20 November 2026 11:00 am', $log->old_value);
        $this->assertSame('20 November 2026 04:00 pm', $log->new_value);
        $this->assertSame('for:customer|field:date', $log->context);
    }

    public function test_backdated_taken_at_still_logs_miss_when_acted_now_is_after_due(): void
    {
        $bookingId = 'aaaaaaaa-bbbb-cccc-dddd-ffffffffffff';
        $due = Carbon::parse('2026-11-20 11:00:00');
        $fakeTakenAt = Carbon::parse('2026-11-20 10:50:00');
        $submitNow = Carbon::parse('2026-11-20 13:10:00');

        // Old loophole: compare only against fake taken-at → no miss.
        $this->assertFalse(BookingAuditLogger::recordFollowupMissedIfPast($bookingId, $due, $fakeTakenAt, 'customer'));

        // Fix: compare against submit time → miss is recorded.
        $this->assertTrue(BookingAuditLogger::recordFollowupMissedIfPast($bookingId, $due, $submitNow, 'customer'));
        $this->assertSame(1, BookingChangeLog::query()->where('booking_id', $bookingId)->where('property_key', 'followup.missed')->count());
    }
}
