<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\CustomerModule\Services\CustomerHomeCacheWarmState;
use Modules\CustomerModule\Services\CustomerHomeContentInvalidator;
use Modules\CustomerModule\Services\CustomerHomeContentVersion;
use Tests\TestCase;

class CustomerHomeCacheWarmStateTest extends TestCase
{
    public function test_reminder_shows_when_content_version_is_ahead_of_last_warm(): void
    {
        Cache::flush();

        CustomerHomeContentInvalidator::bumpGlobal(scheduleWarm: false);

        $this->assertTrue(CustomerHomeCacheWarmState::needsAdminReminder());
    }

    public function test_reminder_hides_after_mark_warmed(): void
    {
        Cache::flush();

        CustomerHomeContentInvalidator::bumpGlobal(scheduleWarm: false);
        CustomerHomeCacheWarmState::markWarmed();

        $this->assertFalse(CustomerHomeCacheWarmState::needsAdminReminder());
    }
}
