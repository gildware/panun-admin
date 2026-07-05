<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\CustomerModule\Services\CustomerHomeContentVersion;
use Tests\TestCase;

class CustomerHomeContentVersionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_global_version_starts_at_zero_and_increments(): void
    {
        $this->assertSame('0', CustomerHomeContentVersion::global());

        CustomerHomeContentVersion::bumpGlobal();
        $this->assertSame('1', CustomerHomeContentVersion::global());

        CustomerHomeContentVersion::bumpGlobal();
        $this->assertSame('2', CustomerHomeContentVersion::global());
    }

    public function test_personal_version_is_scoped_per_user(): void
    {
        $this->assertSame('0', CustomerHomeContentVersion::personal(10));
        $this->assertSame('0', CustomerHomeContentVersion::personal(20));

        CustomerHomeContentVersion::bumpPersonal(10);

        $this->assertSame('1', CustomerHomeContentVersion::personal(10));
        $this->assertSame('0', CustomerHomeContentVersion::personal(20));
    }

    public function test_resolve_for_request_includes_global_layout_and_personal_parts(): void
    {
        CustomerHomeContentVersion::bumpGlobal();
        CustomerHomeContentVersion::bumpPersonal(7);

        $guest = CustomerHomeContentVersion::resolveForRequest('layout123');
        $this->assertSame('1:layout123', $guest);

        $user = CustomerHomeContentVersion::resolveForRequest('layout123', 7);
        $this->assertSame('1:layout123:1', $user);
    }
}
