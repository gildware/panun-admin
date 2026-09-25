<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ZoneManagement\Services\ZoneCategoryInheritanceService;
use Tests\TestCase;

class ZoneCategoryInheritanceTest extends TestCase
{
    private ZoneCategoryInheritanceService $service;

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
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->unsignedInteger('position')->default(1);
        });

        Schema::create('category_zone', function (Blueprint $table) {
            $table->id();
            $table->uuid('category_id');
            $table->uuid('zone_id');
            $table->timestamps();
        });

        $this->service = app(ZoneCategoryInheritanceService::class);
    }

    public function test_new_child_zone_inherits_parent_category_and_its_subcategories(): void
    {
        $parentZone = $this->zone('Srinagar');
        $newZone = $this->zone('Central', $parentZone);
        $otherZone = $this->zone('Jammu');

        $category = $this->category('Plumbing', 1);
        $subA = $this->category('Leak repair', 2, $category);
        $subB = $this->category('Install', 2, $category);
        $other = $this->category('Electrical', 1);

        $this->link($category, $parentZone);
        $this->link($other, $otherZone);

        $this->service->inheritParentCategorySelection($newZone, $parentZone);

        $this->assertTrue($this->linked($category, $newZone));
        $this->assertTrue($this->linked($subA, $newZone));
        $this->assertTrue($this->linked($subB, $newZone));
        $this->assertFalse($this->linked($other, $newZone));
        $this->assertSame(1, $this->linkCount($category, $newZone));
    }

    public function test_new_zone_inherits_when_every_existing_sibling_leaf_is_selected(): void
    {
        $parentZone = $this->zone('Srinagar');
        $leafA = $this->zone('North', $parentZone);
        $leafB = $this->zone('South', $parentZone);
        $newZone = $this->zone('Central', $parentZone);

        $category = $this->category('Plumbing', 1);
        $sub = $this->category('Leak repair', 2, $category);
        $this->link($category, $leafA);
        $this->link($category, $leafB);

        $this->service->inheritParentCategorySelection($newZone, $parentZone);

        $this->assertTrue($this->linked($category, $newZone));
        $this->assertTrue($this->linked($sub, $newZone));
    }

    public function test_partial_parent_selection_does_not_inherit(): void
    {
        $parentZone = $this->zone('Srinagar');
        $leafA = $this->zone('North', $parentZone);
        $this->zone('South', $parentZone);
        $newZone = $this->zone('Central', $parentZone);

        $category = $this->category('Plumbing', 1);
        $sub = $this->category('Leak repair', 2, $category);
        $this->link($category, $leafA);

        $this->service->inheritParentCategorySelection($newZone, $parentZone);

        $this->assertFalse($this->linked($category, $newZone));
        $this->assertFalse($this->linked($sub, $newZone));
    }

    public function test_root_zone_does_not_inherit(): void
    {
        $root = $this->zone('Kashmir');
        $category = $this->category('Plumbing', 1);
        $this->link($category, $this->zone('Other'));

        $this->service->inheritParentCategorySelection($root, null);

        $this->assertSame(0, DB::table('category_zone')->where('zone_id', $root)->count());
    }

    private function zone(string $name, ?string $parentId = null): string
    {
        $id = (string) Str::uuid();
        DB::table('zones')->insert([
            'id' => $id,
            'parent_id' => $parentId,
            'name' => $name,
        ]);

        return $id;
    }

    private function category(string $name, int $position, ?string $parentId = null): string
    {
        $id = (string) Str::uuid();
        DB::table('categories')->insert([
            'id' => $id,
            'parent_id' => $parentId,
            'name' => $name,
            'position' => $position,
        ]);

        return $id;
    }

    private function link(string $categoryId, string $zoneId): void
    {
        DB::table('category_zone')->insert([
            'category_id' => $categoryId,
            'zone_id' => $zoneId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function linked(string $categoryId, string $zoneId): bool
    {
        return DB::table('category_zone')
            ->where('category_id', $categoryId)
            ->where('zone_id', $zoneId)
            ->exists();
    }

    private function linkCount(string $categoryId, string $zoneId): int
    {
        return DB::table('category_zone')
            ->where('category_id', $categoryId)
            ->where('zone_id', $zoneId)
            ->count();
    }
}
