<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\LeadManagement\Services\SuperfoneCallExcelExport;
use Tests\TestCase;

class SuperfoneCallMatchFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('superfone_calls');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('bookings');

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('superfone_calls', function (Blueprint $table) {
            $table->id();
            $table->string('customer_phone')->nullable();
            $table->string('customer_phone_digits', 20)->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('booking_id')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('leads')->insert([
            ['id' => 1, 'created_at' => now(), 'updated_at' => now(), 'deleted_at' => null],
            ['id' => 2, 'created_at' => now(), 'updated_at' => now(), 'deleted_at' => now()],
        ]);
        DB::table('bookings')->insert([
            ['id' => 'booking-live', 'created_at' => now(), 'updated_at' => now(), 'deleted_at' => null],
            ['id' => 'booking-gone', 'created_at' => now(), 'updated_at' => now(), 'deleted_at' => now()],
        ]);
        DB::table('superfone_calls')->insert([
            $this->callRow(1, '9622922905', 1, null),
            $this->callRow(2, '9622922905', 2, null),
            $this->callRow(3, '9622922905', null, 'booking-live'),
            $this->callRow(4, '9622922905', null, 'booking-gone'),
            $this->callRow(5, '9622922905', null, null),
        ]);
    }

    public function test_unmatched_filter_includes_calls_whose_lead_or_booking_was_deleted(): void
    {
        $ids = $this->idsFor(['q' => '9622922905', 'matched' => 'no']);

        $this->assertSame([2, 4, 5], $ids);
    }

    public function test_matched_filter_keeps_only_calls_with_a_live_lead_or_booking(): void
    {
        $ids = $this->idsFor(['q' => '9622922905', 'matched' => 'yes']);

        $this->assertSame([1, 3], $ids);
    }

    /**
     * @param  array<string, string>  $input
     * @return list<int>
     */
    private function idsFor(array $input): array
    {
        return app(SuperfoneCallExcelExport::class)
            ->query(Request::create('/', 'GET', $input))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function callRow(int $id, string $phone, ?int $leadId, ?string $bookingId): array
    {
        return [
            'id' => $id,
            'customer_phone' => $phone,
            'customer_phone_digits' => $phone,
            'lead_id' => $leadId,
            'booking_id' => $bookingId,
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ];
    }
}
