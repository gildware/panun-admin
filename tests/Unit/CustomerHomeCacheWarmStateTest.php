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

    public function test_rebuild_progress_and_failure_status(): void
    {
        Cache::flush();

        CustomerHomeCacheWarmState::markRebuildStarted(10);
        $running = CustomerHomeCacheWarmState::rebuildStatus();
        $this->assertSame(CustomerHomeCacheWarmState::STATUS_RUNNING, $running['status']);
        $this->assertSame(1, $running['percent']);
        $this->assertNotNull($running['started_at']);

        CustomerHomeCacheWarmState::markRebuildProgress(5, 10);
        $halfway = CustomerHomeCacheWarmState::rebuildStatus();
        $this->assertSame(50, $halfway['percent']);
        $this->assertNull($halfway['error']);

        CustomerHomeCacheWarmState::markRebuildFailed('Zone hydrate failed');
        $failed = CustomerHomeCacheWarmState::rebuildStatus();
        $this->assertSame(CustomerHomeCacheWarmState::STATUS_FAILED, $failed['status']);
        $this->assertSame('Zone hydrate failed', $failed['error']);
    }
}
