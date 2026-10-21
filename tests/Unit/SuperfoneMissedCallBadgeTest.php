<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\LeadManagement\Entities\SuperfoneCall;
use Tests\TestCase;

class SuperfoneMissedCallBadgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('superfone_calls');
        Schema::create('superfone_calls', function (Blueprint $table) {
            $table->id();
            $table->string('cdr_uuid', 64)->unique();
            $table->string('direction', 16)->nullable();
            $table->string('outcome', 16)->nullable();
            $table->dateTime('started_at')->nullable();
            $table->string('customer_phone_digits', 20)->nullable();
            $table->timestamps();
        });
    }

    public function test_one_outbound_clears_every_earlier_miss_from_that_number(): void
    {
        foreach (['10:00:00', '10:05:00', '10:10:00', '10:15:00', '10:20:00'] as $index => $time) {
            $this->storeCall('miss-'.$index, 'INBOUND', 'missed', '9876543210', '2026-10-21 '.$time);
        }
        $this->storeCall('other-miss', 'INBOUND', 'missed', '9000000001', '2026-10-21 10:12:00');
        $this->storeCall('no-number', 'INBOUND', 'missed', null, '2026-10-21 10:12:00');

        $this->assertSame(7, SuperfoneCall::query()->awaitingCallback()->count());

        $this->storeCall('callback', 'OUTBOUND', 'unanswered', '9876543210', '2026-10-21 10:25:00');

        $this->assertSame(2, SuperfoneCall::query()->awaitingCallback()->count());
        $this->assertSame(7, SuperfoneCall::query()->missed()->count());
    }

    public function test_a_miss_after_the_callback_counts_again(): void
    {
        $this->storeCall('early-miss', 'INBOUND', 'missed', '9876543210', '2026-10-21 10:00:00');
        $this->storeCall('callback', 'OUTBOUND', 'answered', '9876543210', '2026-10-21 10:05:00');
        $this->storeCall('later-miss', 'INBOUND', 'missed', '9876543210', '2026-10-21 10:10:00');

        $this->assertSame(1, SuperfoneCall::query()->awaitingCallback()->count());
        $this->assertSame('later-miss', SuperfoneCall::query()->awaitingCallback()->value('cdr_uuid'));
    }

    public function test_an_earlier_outbound_does_not_clear_a_later_miss(): void
    {
        $this->storeCall('callback', 'OUTBOUND', 'answered', '9876543210', '2026-10-21 09:00:00');
        $this->storeCall('later-miss', 'INBOUND', 'missed', '9876543210', '2026-10-21 10:00:00');

        $this->assertSame(1, SuperfoneCall::query()->awaitingCallback()->count());
    }

    private function storeCall(string $uuid, string $direction, string $outcome, ?string $digits, string $startedAt): void
    {
        SuperfoneCall::query()->create([
            'cdr_uuid' => $uuid,
            'direction' => $direction,
            'outcome' => $outcome,
            'customer_phone_digits' => $digits,
            'started_at' => $startedAt,
        ]);
    }
}
