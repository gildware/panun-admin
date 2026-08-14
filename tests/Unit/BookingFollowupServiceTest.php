<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Modules\BookingModule\Services\BookingFollowupService;
use Tests\TestCase;

class BookingFollowupServiceTest extends TestCase
{
    private BookingFollowupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BookingFollowupService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_short_notice_next_day_service_uses_service_day_morning(): void
    {
        $bookedAt = Carbon::parse('2026-08-14 17:00:00');
        $scheduledAt = Carbon::parse('2026-08-15 15:00:00');
        Carbon::setTestNow($bookedAt);

        $followUpAt = $this->service->defaultFollowupAtForNewBooking($scheduledAt, $bookedAt);

        $this->assertTrue($followUpAt->equalTo(Carbon::parse('2026-08-15 11:00:00')));
        $this->assertTrue($followUpAt->isFuture());
    }

    public function test_long_lead_uses_day_before_at_fixed_morning_time(): void
    {
        $bookedAt = Carbon::parse('2026-08-11 10:00:00');
        $scheduledAt = Carbon::parse('2026-08-13 15:00:00');

        $followUpAt = $this->service->defaultFollowupAtForNewBooking($scheduledAt, $bookedAt);

        $this->assertTrue($followUpAt->equalTo(Carbon::parse('2026-08-12 10:00:00')));
    }

    public function test_same_day_service_uses_one_hour_before(): void
    {
        $bookedAt = Carbon::parse('2026-08-14 17:00:00');
        $scheduledAt = Carbon::parse('2026-08-14 18:00:00');

        $followUpAt = $this->service->defaultFollowupAtForNewBooking($scheduledAt, $bookedAt);

        $this->assertTrue($followUpAt->equalTo(Carbon::parse('2026-08-14 17:00:00')));
    }

    public function test_same_day_late_booking_floors_to_booking_time(): void
    {
        $bookedAt = Carbon::parse('2026-08-14 17:00:00');
        $scheduledAt = Carbon::parse('2026-08-14 17:30:00');

        $followUpAt = $this->service->defaultFollowupAtForNewBooking($scheduledAt, $bookedAt);

        $this->assertTrue($followUpAt->equalTo($bookedAt));
    }

    public function test_early_morning_service_caps_before_service_hour(): void
    {
        $bookedAt = Carbon::parse('2026-08-14 22:00:00');
        $scheduledAt = Carbon::parse('2026-08-15 08:00:00');

        $followUpAt = $this->service->defaultFollowupAtForNewBooking($scheduledAt, $bookedAt);

        $this->assertTrue($followUpAt->equalTo(Carbon::parse('2026-08-15 07:00:00')));
    }
}
