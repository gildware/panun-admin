<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ApiAudienceRequestHelpersTest extends TestCase
{
    public function test_customer_provider_nested_path_is_customer_not_provider(): void
    {
        $path = 'api/v1/customer/provider/list-by-sub-category';

        $this->assertSame('customer', api_audience_segment($path));
        $this->assertTrue(is_customer_api_request($path));
        $this->assertFalse(is_provider_api_request($path));
    }

    public function test_provider_app_path_is_provider(): void
    {
        $path = 'api/v1/provider/config';

        $this->assertSame('provider', api_audience_segment($path));
        $this->assertTrue(is_provider_api_request($path));
        $this->assertFalse(is_customer_api_request($path));
    }

    public function test_legacy_wildcard_misclassifies_customer_provider_routes(): void
    {
        // Documents why segment helpers exist: Laravel * spans slashes.
        $this->assertTrue(
            \Illuminate\Support\Str::is('api/*/provider/*', 'api/v1/customer/provider/list-by-sub-category')
        );
    }
}
