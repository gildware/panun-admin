<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\LeadManagement\Entities\Lead;
use Modules\WhatsAppModule\Services\WhatsAppLeadLifecycleService;
use Tests\TestCase;

class WhatsAppProviderInboundLeadTest extends TestCase
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
            $table->uuid('user_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('contact_person_phone')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->nullable();
            $table->string('lead_type')->nullable();
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

    public function test_onboarded_provider_whatsapp_does_not_create_a_lead(): void
    {
        DB::table('users')->insert([
            'id' => '11111111-1111-1111-1111-111111111111',
            'phone' => '+919876543210',
            'user_type' => 'provider-admin',
        ]);

        $service = app(WhatsAppLeadLifecycleService::class);

        $this->assertNull($service->ensureUnknownLeadForPhone('919876543210', 'Existing Provider'));
        $this->assertNull($service->ensureLeadTypeForPhone('919876543210', Lead::TYPE_PROVIDER, 'Existing Provider'));
        $this->assertSame(0, DB::table('leads')->count());
    }

    public function test_provider_company_phone_does_not_create_a_lead(): void
    {
        DB::table('providers')->insert([
            'id' => '22222222-2222-2222-2222-222222222222',
            'company_phone' => '+919111222333',
            'contact_person_phone' => '+919000000000',
        ]);

        $lead = app(WhatsAppLeadLifecycleService::class)
            ->ensureUnknownLeadForPhone('919111222333', 'Company Line');

        $this->assertNull($lead);
        $this->assertSame(0, DB::table('leads')->count());
    }

    public function test_customer_with_the_same_digit_style_is_not_treated_as_a_provider(): void
    {
        DB::table('users')->insert([
            'id' => '33333333-3333-3333-3333-333333333333',
            'phone' => '+918888777666',
            'user_type' => 'customer',
        ]);

        $service = app(WhatsAppLeadLifecycleService::class);
        $method = new \ReflectionMethod($service, 'isProviderAccountPhone');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($service, '8888777666'));
    }
}
