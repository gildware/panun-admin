<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\BusinessSettingsModule\Services\MobileAppManagementService;
use Modules\CustomerModule\Services\CustomerHomeContentVersion;
use Tests\TestCase;

class CustomerHomeBundleVersionHttpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->mock(MobileAppManagementService::class, function ($mock): void {
            $mock->shouldReceive('homeSectionsForApi')->andReturn([
                'sections' => [
                    [
                        'key' => 'banners',
                        'enabled' => true,
                        'sort_order' => 1,
                        'title' => null,
                        'item_limit' => 10,
                        'data_mode' => 'default',
                        'content_type' => 'banners',
                        'is_custom' => false,
                        'service_ids' => [],
                        'provider_ids' => [],
                        'banner_ids' => [],
                        'category_ids' => [],
                        'campaign_ids' => [],
                    ],
                ],
            ]);
        });
    }

    public function test_home_bundle_version_endpoint_returns_version_and_layout_hash(): void
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/v1/customer/home-bundle/version', [
            'X-localization' => 'en',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'response_code',
            'content' => ['version', 'layout_hash'],
        ]);

        $version = (string) $response->json('content.version');
        $layoutHash = (string) $response->json('content.layout_hash');

        $this->assertNotSame('', $version);
        $this->assertNotSame('', $layoutHash);
        $this->assertStringContainsString($layoutHash, $version);
    }

    public function test_version_endpoint_reflects_global_bump(): void
    {
        $this->withoutMiddleware();

        $before = $this->getJson('/api/v1/customer/home-bundle/version')->json('content.version');

        CustomerHomeContentVersion::bumpGlobal();

        $after = $this->getJson('/api/v1/customer/home-bundle/version')->json('content.version');

        $this->assertNotSame($before, $after);
    }
}
