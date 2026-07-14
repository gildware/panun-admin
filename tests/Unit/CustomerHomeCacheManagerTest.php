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

    public function test_ensure_zone_warm_ignores_blank_zone_id(): void
    {
        CustomerHomeCacheManager::ensureZoneWarm('   ');

        $this->assertFalse(Cache::has('customer_home_zone_warm:'));
    }

    public function test_ensure_zone_warm_is_throttled_when_lock_present(): void
    {
        Cache::put('customer_home_zone_warm:zone-a', 1, 120);

        // Early-return throttle path — must not attempt a real warm (needs DB).
        CustomerHomeCacheManager::ensureZoneWarm('zone-a');

        $this->assertTrue(Cache::has('customer_home_zone_warm:zone-a'));
    }
}
