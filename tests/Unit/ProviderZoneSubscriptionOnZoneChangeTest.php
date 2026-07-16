<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\ProviderManagement\Services\ProviderProfileChangeRequestService;
use Tests\TestCase;

class ProviderZoneSubscriptionOnZoneChangeTest extends TestCase
{
    private ProviderProfileChangeRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createMinimalSchema();
        $this->service = app(ProviderProfileChangeRequestService::class);
    }

    public function test_adding_zones_keeps_all_subscriptions(): void
    {
        [$providerId, $zoneA, $zoneB, $subA, $subB] = $this->seedProviderWithSubscriptions();

        $this->service->unsubscribeSubCategoriesLostOnZoneRemoval(
            $providerId,
            [$zoneA],
            [$zoneA, $zoneB]
        );

        $this->assertSame(1, (int) SubscribedService::query()->find($subA)->is_subscribed);
        $this->assertSame(1, (int) SubscribedService::query()->find($subB)->is_subscribed);
    }

    public function test_removing_zone_unsubscribes_only_invalid_parent_categories(): void
    {
        [$providerId, $zoneA, $zoneB, $subA, $subB] = $this->seedProviderWithSubscriptions();

        $this->service->unsubscribeSubCategoriesLostOnZoneRemoval(
            $providerId,
            [$zoneA, $zoneB],
            [$zoneA]
        );

        $this->assertSame(1, (int) SubscribedService::query()->find($subA)->is_subscribed);
        $this->assertSame(0, (int) SubscribedService::query()->find($subB)->is_subscribed);
    }

    public function test_removing_all_zones_unsubscribes_everything(): void
    {
        [$providerId, $zoneA, $zoneB, $subA, $subB] = $this->seedProviderWithSubscriptions();

        $this->service->unsubscribeSubCategoriesLostOnZoneRemoval(
            $providerId,
            [$zoneA, $zoneB],
            []
        );

        $this->assertSame(0, (int) SubscribedService::query()->find($subA)->is_subscribed);
        $this->assertSame(0, (int) SubscribedService::query()->find($subB)->is_subscribed);
    }

    public function test_same_zone_set_does_not_change_subscriptions(): void
    {
        [$providerId, $zoneA, $zoneB, $subA, $subB] = $this->seedProviderWithSubscriptions();

        $this->service->unsubscribeSubCategoriesLostOnZoneRemoval(
            $providerId,
            [$zoneA, $zoneB],
            [$zoneB, $zoneA]
        );

        $this->assertSame(1, (int) SubscribedService::query()->find($subA)->is_subscribed);
        $this->assertSame(1, (int) SubscribedService::query()->find($subB)->is_subscribed);
    }

    private function createMinimalSchema(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->nullable();
            $table->string('name')->nullable();
            $table->integer('position')->default(1);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('category_zone', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('category_id');
            $table->foreignUuid('zone_id');
            $table->timestamps();
        });

        Schema::create('providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable();
            $table->foreignUuid('zone_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_email')->nullable();
            $table->boolean('is_active')->default(1);
            $table->boolean('is_approved')->default(1);
            $table->timestamps();
        });

        Schema::create('provider_zone', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('provider_id');
            $table->foreignUuid('zone_id');
            $table->timestamps();
        });

        Schema::create('subscribed_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id');
            $table->foreignUuid('category_id');
            $table->foreignUuid('sub_category_id');
            $table->boolean('is_subscribed')->default(0);
            $table->timestamps();
        });
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string}
     */
    private function seedProviderWithSubscriptions(): array
    {
        $now = now();
        $zoneA = (string) Str::uuid();
        $zoneB = (string) Str::uuid();
        $parentA = (string) Str::uuid();
        $parentB = (string) Str::uuid();
        $subCategoryA = (string) Str::uuid();
        $subCategoryB = (string) Str::uuid();
        $providerId = (string) Str::uuid();
        $subA = (string) Str::uuid();
        $subB = (string) Str::uuid();

        DB::table('zones')->insert([
            ['id' => $zoneA, 'name' => 'Zone A '.Str::random(6), 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $zoneB, 'name' => 'Zone B '.Str::random(6), 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('categories')->insert([
            ['id' => $parentA, 'parent_id' => null, 'name' => 'Parent A', 'position' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $parentB, 'parent_id' => null, 'name' => 'Parent B', 'position' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $subCategoryA, 'parent_id' => $parentA, 'name' => 'Sub A', 'position' => 2, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $subCategoryB, 'parent_id' => $parentB, 'name' => 'Sub B', 'position' => 2, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('category_zone')->insert([
            ['category_id' => $parentA, 'zone_id' => $zoneA, 'created_at' => $now, 'updated_at' => $now],
            ['category_id' => $parentB, 'zone_id' => $zoneB, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('providers')->insert([
            'id' => $providerId,
            'user_id' => null,
            'zone_id' => $zoneA,
            'company_name' => 'Zone Subscription Provider',
            'company_phone' => '7000000003',
            'company_email' => 'provider@test.local',
            'is_active' => 1,
            'is_approved' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('provider_zone')->insert([
            ['provider_id' => $providerId, 'zone_id' => $zoneA, 'created_at' => $now, 'updated_at' => $now],
            ['provider_id' => $providerId, 'zone_id' => $zoneB, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('subscribed_services')->insert([
            [
                'id' => $subA,
                'provider_id' => $providerId,
                'category_id' => $parentA,
                'sub_category_id' => $subCategoryA,
                'is_subscribed' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $subB,
                'provider_id' => $providerId,
                'category_id' => $parentB,
                'sub_category_id' => $subCategoryB,
                'is_subscribed' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        return [$providerId, $zoneA, $zoneB, $subA, $subB];
    }
}
