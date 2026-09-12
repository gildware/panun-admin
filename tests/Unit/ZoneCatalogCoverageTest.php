<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ZoneManagement\Entities\Zone;
use Tests\TestCase;

class ZoneCatalogCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }

    public function test_leaf_zone_includes_parent_district_for_catalog_matching(): void
    {
        $districtId = (string) Str::uuid();
        $leafId = (string) Str::uuid();

        DB::table('zones')->insert([
            [
                'id' => $districtId,
                'parent_id' => null,
                'name' => 'Srinagar District',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $leafId,
                'parent_id' => $districtId,
                'name' => 'Central Srinagar',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $ids = Zone::catalogZoneIdsForCustomer($leafId);

        $this->assertContains($leafId, $ids);
        $this->assertContains($districtId, $ids);
    }
}
