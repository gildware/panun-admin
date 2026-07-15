<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\CustomerModule\Services\CustomerHomeCacheManager;
use Tests\TestCase;

class CustomerHomeCacheManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_ensure_zone_warm_is_noop(): void
    {
        CustomerHomeCacheManager::ensureZoneWarm('zone-a');

        $this->assertFalse(Cache::has('customer_home_zone_warm:zone-a'));
    }

    public function test_ensure_zone_warm_ignores_blank_zone_id(): void
    {
        CustomerHomeCacheManager::ensureZoneWarm('   ');

        $this->assertFalse(Cache::has('customer_home_zone_warm:'));
    }
}
