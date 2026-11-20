<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingChangeLog;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\BookingModule\Services\BookingFollowupMissedRecorder;
use Tests\TestCase;

class BookingFollowupMissedRecorderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('booking_change_logs');
        Schema::dropIfExists('booking_followups');
        Schema::dropIfExists('bookings');

        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
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
            $table->timestamps();
        });

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

    public function test_records_miss_for_open_overdue_followup_only(): void
    {
        Carbon::setTestNow('2026-11-20 13:00:00');

        $openId = 'f21a831a-0d17-4cce-9050-9fe27f84e066';
        $closedId = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

        Booking::query()->insert([
            ['id' => $openId, 'booking_status' => 'accepted', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $closedId, 'booking_status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
        ]);

        BookingFollowup::query()->insert([
            [
                'booking_id' => $openId,
                'for' => 'customer',
                'status' => 'scheduled',
                'date' => '2026-11-20 11:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'booking_id' => $closedId,
                'for' => 'customer',
                'status' => 'scheduled',
                'date' => '2026-11-20 11:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $recorded = (new BookingFollowupMissedRecorder)->recordOverdueMisses();

        $this->assertSame(1, $recorded);
        $this->assertSame(1, BookingChangeLog::query()->where('booking_id', $openId)->where('property_key', 'followup.missed')->count());
        $this->assertSame(0, BookingChangeLog::query()->where('booking_id', $closedId)->where('property_key', 'followup.missed')->count());

        // Idempotent for the same due.
        $this->assertSame(0, (new BookingFollowupMissedRecorder)->recordOverdueMisses());

        Carbon::setTestNow();
    }
}
