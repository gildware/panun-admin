<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\CustomerModule\Services\CustomerHomeContentInvalidator;
use Modules\CustomerModule\Services\CustomerHomeContentVersion;
use Tests\TestCase;

class CustomerHomeContentInvalidatorTest extends TestCase
{
    public function test_bump_global_increments_version(): void
    {
        Cache::flush();

        CustomerHomeContentInvalidator::bumpGlobal(scheduleWarm: false);

        $this->assertSame('1', CustomerHomeContentVersion::global());
    }

    public function test_bump_personal_increments_user_version(): void
    {
        Cache::flush();

        CustomerHomeContentInvalidator::bumpPersonal(42);

        $this->assertSame('1', CustomerHomeContentVersion::personal(42));
    }
}
