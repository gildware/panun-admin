<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\LeadManagement\Entities\SuperfoneCall;
use Modules\LeadManagement\Entities\SuperfoneCallContact;
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
            $table->string('disposition', 64)->nullable();
            $table->string('outcome', 16)->nullable();
            $table->dateTime('started_at')->nullable();
            $table->string('customer_phone_digits', 20)->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('superfone_call_contacts');
        Schema::create('superfone_call_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('superfone_call_id');
            $table->string('customer_phone_digits', 20)->nullable();
            $table->string('channel', 16);
            $table->text('remarks');
            $table->unsignedBigInteger('employee_user_id')->nullable();
            $table->dateTime('contacted_at');
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

    public function test_a_manual_contact_clears_every_earlier_miss_from_that_number(): void
    {
        $this->storeCall('miss-1', 'INBOUND', 'missed', '9876543210', '2026-10-21 10:00:00');
        $marked = $this->storeCall('miss-2', 'INBOUND', 'missed', '9876543210', '2026-10-21 10:10:00');
        $this->storeCall('other-miss', 'INBOUND', 'missed', '9000000001', '2026-10-21 10:12:00');

        SuperfoneCallContact::query()->create([
            'superfone_call_id' => $marked->id,
            'customer_phone_digits' => '9876543210',
            'channel' => 'chat',
            'remarks' => 'Sent a WhatsApp message',
            'contacted_at' => '2026-10-21 10:15:00',
        ]);

        $this->assertSame(1, SuperfoneCall::query()->awaitingCallback()->count());
        $this->assertSame('other-miss', SuperfoneCall::query()->awaitingCallback()->value('cdr_uuid'));
    }

    public function test_a_miss_after_the_contact_counts_again(): void
    {
        $marked = $this->storeCall('early-miss', 'INBOUND', 'missed', '9876543210', '2026-10-21 10:00:00');
        SuperfoneCallContact::query()->create([
            'superfone_call_id' => $marked->id,
            'customer_phone_digits' => '9876543210',
            'channel' => 'phone',
            'remarks' => 'Called back',
            'contacted_at' => '2026-10-21 10:05:00',
        ]);
        $this->storeCall('later-miss', 'INBOUND', 'missed', '9876543210', '2026-10-21 10:10:00');

        $this->assertSame(1, SuperfoneCall::query()->awaitingCallback()->count());
        $this->assertSame('later-miss', SuperfoneCall::query()->awaitingCallback()->value('cdr_uuid'));
    }

    public function test_a_contact_clears_its_own_call_even_when_the_saved_clock_is_earlier(): void
    {
        $call = $this->storeCall('today-miss', 'INBOUND', 'missed', '9876543210', '2026-10-22 14:00:00');
        SuperfoneCallContact::query()->create([
            'superfone_call_id' => $call->id,
            'customer_phone_digits' => '9876543210',
            'channel' => 'not_picked',
            'remarks' => 'Customer did not pick up',
            'contacted_at' => '2026-10-22 08:30:00',
        ]);

        $this->assertSame(0, SuperfoneCall::query()->awaitingCallback()->count());
        $this->assertSame(1, SuperfoneCall::query()->contacted()->count());
    }

    public function test_an_earlier_outbound_does_not_clear_a_later_miss(): void
    {
        $this->storeCall('callback', 'OUTBOUND', 'answered', '9876543210', '2026-10-21 09:00:00');
        $this->storeCall('later-miss', 'INBOUND', 'missed', '9876543210', '2026-10-21 10:00:00');

        $this->assertSame(1, SuperfoneCall::query()->awaitingCallback()->count());
    }

    public function test_an_unallocated_inbound_call_needs_a_callback(): void
    {
        $this->storeCall('unallocated', 'INBOUND', 'other', '9876543210', '2026-10-21 10:00:00', 'UNALLOCATED');
        $this->storeCall('outbound-unallocated', 'OUTBOUND', 'other', '9000000002', '2026-10-21 10:05:00', 'UNALLOCATED');
        $this->storeCall('answered', 'INBOUND', 'answered', '9000000001', '2026-10-21 10:06:00', 'ANSWER');

        $open = SuperfoneCall::query()->awaitingCallback()->pluck('cdr_uuid')->all();

        $this->assertSame(['unallocated'], $open);
        $this->assertSame(0, SuperfoneCall::query()->missed()->count());
    }

    public function test_one_outbound_clears_earlier_misses_and_unallocated_calls_together(): void
    {
        $this->storeCall('miss', 'INBOUND', 'missed', '9876543210', '2026-10-21 10:00:00');
        $this->storeCall('unallocated', 'INBOUND', 'other', '9876543210', '2026-10-21 10:05:00', 'UNALLOCATED');
        $this->storeCall('other-unallocated', 'INBOUND', 'other', '9000000001', '2026-10-21 10:06:00', 'UNALLOCATED');

        $this->assertSame(3, SuperfoneCall::query()->awaitingCallback()->count());

        $this->storeCall('callback', 'OUTBOUND', 'unanswered', '9876543210', '2026-10-21 10:10:00');

        $this->assertSame(['other-unallocated'], SuperfoneCall::query()->awaitingCallback()->pluck('cdr_uuid')->all());
    }

    public function test_a_manual_contact_clears_earlier_unallocated_calls_from_that_number(): void
    {
        $this->storeCall('unallocated-1', 'INBOUND', 'other', '9876543210', '2026-10-21 10:00:00', 'UNALLOCATED');
        $marked = $this->storeCall('unallocated-2', 'INBOUND', 'other', '9876543210', '2026-10-21 10:10:00', 'UNALLOCATED');
        $this->storeCall('other-unallocated', 'INBOUND', 'other', '9000000001', '2026-10-21 10:12:00', 'UNALLOCATED');

        SuperfoneCallContact::query()->create([
            'superfone_call_id' => $marked->id,
            'customer_phone_digits' => '9876543210',
            'channel' => 'phone',
            'remarks' => 'Called back',
            'contacted_at' => '2026-10-21 10:15:00',
        ]);

        $this->assertSame(['other-unallocated'], SuperfoneCall::query()->awaitingCallback()->pluck('cdr_uuid')->all());
    }

    public function test_an_unallocated_call_after_the_callback_counts_again(): void
    {
        $this->storeCall('early', 'INBOUND', 'other', '9876543210', '2026-10-21 10:00:00', 'UNALLOCATED');
        $this->storeCall('callback', 'OUTBOUND', 'answered', '9876543210', '2026-10-21 10:05:00');
        $this->storeCall('later', 'INBOUND', 'other', '9876543210', '2026-10-21 10:10:00', 'UNALLOCATED');

        $this->assertSame(['later'], SuperfoneCall::query()->awaitingCallback()->pluck('cdr_uuid')->all());
    }

    private function storeCall(string $uuid, string $direction, string $outcome, ?string $digits, string $startedAt, ?string $disposition = null): SuperfoneCall
    {
        return SuperfoneCall::query()->create([
            'cdr_uuid' => $uuid,
            'direction' => $direction,
            'disposition' => $disposition,
            'outcome' => $outcome,
            'customer_phone_digits' => $digits,
            'started_at' => $startedAt,
        ]);
    }
}
