<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Services\LeadOpenStatusService;
use Modules\WhatsAppModule\Services\WhatsAppLeadLifecycleService;
use Tests\TestCase;

class WhatsAppInboundOpenBookingLeadGateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone')->nullable();
            $table->string('user_type')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_phone')->nullable();
            $table->string('contact_person_phone')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('lead_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('ad_source_id')->nullable();
            $table->timestamp('date_time_of_lead_received')->nullable();
            $table->string('handled_by')->nullable();
            $table->string('created_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('next_followup_at')->nullable();
            $table->timestamp('non_responsive_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('booking_status')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_users', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->nullable();
            $table->string('channel')->nullable();
            $table->string('name')->nullable();
            $table->json('referral_json')->nullable();
            $table->string('referral_source_url')->nullable();
            $table->string('referral_source_id')->nullable();
            $table->string('referral_headline')->nullable();
            $table->string('referral_source_type')->nullable();
            $table->string('referral_body')->nullable();
            $table->timestamps();
        });

        Schema::create('party_alternate_phones', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 20);
            $table->uuid('owner_id');
            $table->string('phone_digits', 20)->unique();
            $table->unsignedBigInteger('linked_from_lead_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function test_should_create_new_lead_only_when_no_lead_or_booking_history(): void
    {
        $this->assertTrue(LeadOpenStatusService::shouldCreateNewLead(false, false));
        $this->assertFalse(LeadOpenStatusService::shouldCreateNewLead(true, false));
        $this->assertFalse(LeadOpenStatusService::shouldCreateNewLead(false, true));
        $this->assertFalse(LeadOpenStatusService::shouldCreateNewLead(true, true));
    }

    public function test_whatsapp_does_not_mint_when_customer_has_open_booking(): void
    {
        DB::table('users')->insert([
            'id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'phone' => '+919876543210',
            'user_type' => 'customer',
        ]);

        $closedLeadId = DB::table('leads')->insertGetId([
            'name' => 'Booked Customer',
            'phone_number' => '9876543210',
            'lead_type' => Lead::TYPE_CUSTOMER,
            'source_id' => 1,
            'non_responsive_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sources')->insert([
            'id' => 1,
            'name' => 'AI Chat',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('bookings')->insert([
            'id' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            'customer_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'lead_id' => $closedLeadId,
            'booking_status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Customer booked leads are closed without type-history rows in this fixture;
        // isLeadOpen falls through to status history — seed a completed booked status path
        // by marking via LeadOpenStatusService phone gate only (no open lead rows).
        Schema::create('lead_type_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->string('type');
            $table->json('data')->nullable();
            $table->timestamps();
        });
        Schema::create('customer_lead_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('base_type')->nullable();
        });
        $statusId = DB::table('customer_lead_statuses')->insertGetId([
            'name' => 'Booked',
            'base_type' => 'completed',
        ]);
        DB::table('lead_type_histories')->insert([
            'lead_id' => $closedLeadId,
            'type' => Lead::TYPE_CUSTOMER,
            'data' => json_encode(['customer_lead_status_id' => $statusId]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $before = (int) DB::table('leads')->count();
        $service = app(WhatsAppLeadLifecycleService::class);
        $lead = $service->ensureUnknownLeadForPhone('919876543210', 'Returning');

        $this->assertNotNull($lead);
        $this->assertSame($closedLeadId, (int) $lead->id);
        $this->assertSame($before, (int) DB::table('leads')->count());
    }

    public function test_completed_booking_blocks_auto_mint(): void
    {
        DB::table('users')->insert([
            'id' => 'cccccccc-cccc-cccc-cccc-cccccccccccc',
            'phone' => '+911122334455',
            'user_type' => 'customer',
        ]);

        DB::table('bookings')->insert([
            'id' => 'dddddddd-dddd-dddd-dddd-dddddddddddd',
            'customer_id' => 'cccccccc-cccc-cccc-cccc-cccccccccccc',
            'lead_id' => null,
            'booking_status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $openStatus = app(LeadOpenStatusService::class);
        $this->assertFalse($openStatus->phoneHasOpenBooking('1122334455'));
        $this->assertTrue($openStatus->phoneHasAnyBooking('1122334455'));

        $resolved = $openStatus->resolveInboundLead(collect(), '1122334455');
        $this->assertSame('existing', $resolved['action']);
        $this->assertFalse(LeadOpenStatusService::shouldCreateNewLead(
            $resolved['has_any_lead'],
            $resolved['has_any_booking']
        ));

        $before = (int) DB::table('leads')->count();
        $lead = app(WhatsAppLeadLifecycleService::class)
            ->ensureUnknownLeadForPhone('911122334455', 'Returning');
        $this->assertNull($lead);
        $this->assertSame($before, (int) DB::table('leads')->count());
    }

    public function test_closed_lead_blocks_auto_mint_and_does_not_auto_attach(): void
    {
        Schema::create('lead_type_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->string('type');
            $table->json('data')->nullable();
            $table->timestamps();
        });
        Schema::create('customer_lead_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('base_type')->nullable();
        });

        DB::table('sources')->insert([
            'id' => 2,
            'name' => 'AI Chat',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $statusId = DB::table('customer_lead_statuses')->insertGetId([
            'name' => 'Booked',
            'base_type' => 'completed',
        ]);
        $closedLeadId = DB::table('leads')->insertGetId([
            'name' => 'Closed Customer',
            'phone_number' => '9988776655',
            'lead_type' => Lead::TYPE_CUSTOMER,
            'source_id' => 2,
            'non_responsive_at' => null,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);
        DB::table('lead_type_histories')->insert([
            'lead_id' => $closedLeadId,
            'type' => Lead::TYPE_CUSTOMER,
            'data' => json_encode(['customer_lead_status_id' => $statusId]),
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $openStatus = app(LeadOpenStatusService::class);
        $leads = $openStatus->leadsForPhoneDigits('9988776655');
        $resolved = $openStatus->resolveInboundLead($leads, '9988776655');

        $this->assertSame('existing', $resolved['action']);
        $this->assertNull($resolved['lead']);

        $before = (int) DB::table('leads')->count();
        $lead = app(WhatsAppLeadLifecycleService::class)
            ->ensureUnknownLeadForPhone('919988776655', 'Returning');
        $this->assertNull($lead);
        $this->assertSame($before, (int) DB::table('leads')->count());
    }

    public function test_staff_can_manually_create_lead_even_with_closed_history(): void
    {
        Schema::create('lead_type_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->string('type');
            $table->json('data')->nullable();
            $table->timestamps();
        });
        Schema::create('customer_lead_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('base_type')->nullable();
        });
        Schema::create('lead_change_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->uuid('changed_by')->nullable();
            $table->json('changes')->nullable();
            $table->timestamps();
        });

        DB::table('sources')->insert([
            'id' => 3,
            'name' => 'AI Chat',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $statusId = DB::table('customer_lead_statuses')->insertGetId([
            'name' => 'Booked',
            'base_type' => 'completed',
        ]);
        $closedLeadId = DB::table('leads')->insertGetId([
            'name' => 'Closed Customer',
            'phone_number' => '8877665544',
            'lead_type' => Lead::TYPE_CUSTOMER,
            'source_id' => 3,
            'non_responsive_at' => null,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);
        DB::table('lead_type_histories')->insert([
            'lead_id' => $closedLeadId,
            'type' => Lead::TYPE_CUSTOMER,
            'data' => json_encode(['customer_lead_status_id' => $statusId]),
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $before = (int) DB::table('leads')->count();
        $lead = app(WhatsAppLeadLifecycleService::class)
            ->createLeadManuallyForPhone('918877665544', 'Staff Created');

        $this->assertNotNull($lead);
        $this->assertNotSame($closedLeadId, (int) $lead->id);
        $this->assertSame(Lead::TYPE_UNKNOWN, $lead->lead_type);
        $this->assertSame($before + 1, (int) DB::table('leads')->count());
    }
}
