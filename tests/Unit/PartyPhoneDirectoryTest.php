<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\LeadManagement\Entities\PartyAlternatePhone;
use Modules\LeadManagement\Services\PartyPhoneDirectory;
use Tests\TestCase;

class PartyPhoneDirectoryTest extends TestCase
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
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('user_type')->nullable();
            $table->uuid('provider_id')->nullable();
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

    public function test_phone_is_unique_across_people_including_extras(): void
    {
        DB::table('users')->insert([
            'id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'first_name' => 'Asha',
            'last_name' => 'Khan',
            'phone' => '+919876543210',
            'user_type' => 'customer',
        ]);

        $dir = app(PartyPhoneDirectory::class);
        $dir->addAlternate(
            PartyAlternatePhone::OWNER_CUSTOMER,
            'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            '919111222333'
        );

        $this->assertSame(
            ['9876543210', '9111222333'],
            $dir->phonesForOwner(PartyAlternatePhone::OWNER_CUSTOMER, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa')
        );

        DB::table('users')->insert([
            'id' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            'first_name' => 'Other',
            'last_name' => 'Person',
            'phone' => '+919000000000',
            'user_type' => 'customer',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $dir->addAlternate(
            PartyAlternatePhone::OWNER_CUSTOMER,
            'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            '919111222333'
        );
    }

    public function test_find_owner_resolves_alternate_phone(): void
    {
        DB::table('users')->insert([
            'id' => 'cccccccc-cccc-cccc-cccc-cccccccccccc',
            'first_name' => 'Ravi',
            'last_name' => 'Sharma',
            'phone' => '+918888777666',
            'user_type' => 'customer',
        ]);

        $dir = app(PartyPhoneDirectory::class);
        $dir->addAlternate(
            PartyAlternatePhone::OWNER_CUSTOMER,
            'cccccccc-cccc-cccc-cccc-cccccccccccc',
            '917777666555'
        );

        $owner = $dir->findOwner('917777666555');
        $this->assertNotNull($owner);
        $this->assertSame(PartyAlternatePhone::OWNER_CUSTOMER, $owner['owner_type']);
        $this->assertSame('cccccccc-cccc-cccc-cccc-cccccccccccc', $owner['owner_id']);
        $this->assertSame('alternate', $owner['via']);
    }

    public function test_should_create_new_lead_gate_still_holds(): void
    {
        $this->assertTrue(\Modules\LeadManagement\Services\LeadOpenStatusService::shouldCreateNewLead(false, false));
        $this->assertFalse(\Modules\LeadManagement\Services\LeadOpenStatusService::shouldCreateNewLead(false, true));
    }
}
