<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Services\AdminBookingListQueryService;
use Tests\TestCase;

class AdminBookingListQueryServiceTest extends TestCase
{
    private AdminBookingListQueryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createMinimalSchema();
        $this->service = new AdminBookingListQueryService;
    }

    public function test_status_tab_counts_follow_active_list_filters(): void
    {
        $zoneA = (string) Str::uuid();
        $zoneB = (string) Str::uuid();

        $this->insertBooking(['booking_status' => 'pending', 'zone_id' => $zoneA]);
        $this->insertBooking(['booking_status' => 'completed', 'zone_id' => $zoneA]);
        $this->insertBooking(['booking_status' => 'pending', 'zone_id' => $zoneB]);
        $this->insertBooking(['booking_status' => 'ongoing', 'zone_id' => $zoneB, 'is_repeated' => 1]);

        $filtered = Request::create('/admin/booking/list', 'GET', [
            'zone_ids' => [$zoneA],
        ]);

        $base = $this->service->applySharedFilters(Booking::query(), $filtered, [], false);
        $tabOptions = ['max_booking_amount' => 999999];

        $this->assertSame(2, (clone $base)->count(), 'All-tab count should match the filtered list, not the full table');
        $this->assertSame(1, (clone $base)->applyBookingListStatusTab('pending', $tabOptions)->count());
        $this->assertSame(1, (clone $base)->applyBookingListStatusTab('completed', $tabOptions)->count());
        $this->assertSame(0, (clone $base)->applyBookingListStatusTab('ongoing', $tabOptions)->count());

        $unfiltered = Request::create('/admin/booking/list', 'GET');
        $allRegular = $this->service->applySharedFilters(Booking::query(), $unfiltered, [], false);
        $this->assertSame(3, (clone $allRegular)->count(), 'Unfiltered regular list excludes repeat bookings');
    }

    public function test_date_range_filter_is_applied_to_counts(): void
    {
        $this->insertBooking([
            'booking_status' => 'accepted',
            'created_at' => '2026-08-01 10:00:00',
            'updated_at' => '2026-08-01 10:00:00',
        ]);
        $this->insertBooking([
            'booking_status' => 'accepted',
            'created_at' => '2026-08-20 10:00:00',
            'updated_at' => '2026-08-20 10:00:00',
        ]);

        $request = Request::create('/admin/booking/list', 'GET', [
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-31',
        ]);

        $base = $this->service->applySharedFilters(Booking::query(), $request, [], false);

        $this->assertSame(1, (clone $base)->count());
        $this->assertSame(1, (clone $base)->applyBookingListStatusTab('accepted', ['max_booking_amount' => 999999])->count());
        $this->assertSame(0, (clone $base)->applyBookingListStatusTab('pending', ['max_booking_amount' => 999999])->count());
    }

    private function createMinimalSchema(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('readable_id')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->uuid('provider_id')->nullable();
            $table->uuid('zone_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->uuid('sub_category_id')->nullable();
            $table->uuid('assignee_id')->nullable();
            $table->string('booking_status')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('total_booking_amount', 24, 2)->default(0);
            $table->boolean('is_verified')->default(0);
            $table->boolean('is_repeated')->default(0);
            $table->timestamp('service_schedule')->nullable();
            $table->timestamp('reopen_resolved_at')->nullable();
            $table->json('reopen_disputed_snapshot')->nullable();
            $table->string('settlement_outcome')->nullable();
            $table->json('settlement_snapshot')->nullable();
            $table->json('settlement_config')->nullable();
            $table->boolean('after_visit_cancel')->default(0);
            $table->timestamps();
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertBooking(array $overrides = []): void
    {
        $now = now();
        DB::table('bookings')->insert(array_merge([
            'id' => (string) Str::uuid(),
            'readable_id' => 'PK'.random_int(1000, 9999),
            'booking_status' => 'pending',
            'payment_method' => 'cash_after_service',
            'total_booking_amount' => 100,
            'is_verified' => 1,
            'is_repeated' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides));
    }
}
