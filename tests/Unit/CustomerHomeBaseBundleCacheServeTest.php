<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\CustomerModule\Services\CustomerHomeBaseBundleCache;
use Modules\CustomerModule\Services\CustomerHomeContentVersion;
use Tests\TestCase;

class CustomerHomeBaseBundleCacheServeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_remember_returns_stable_cache_hit(): void
    {
        $zoneId = 'zone-1';
        $locale = 'en';
        $payload = [
            'banners' => ['data' => [['id' => 1]], 'total' => 1],
            'categories' => ['data' => [], 'total' => 0],
        ];

        Cache::forever(
            CustomerHomeBaseBundleCache::cacheKey($zoneId, $locale),
            $payload
        );

        $cache = $this->app->make(CustomerHomeBaseBundleCache::class);
        $request = Request::create('/api/v1/customer/home-bundle', 'GET');
        $request->headers->set('zoneId', $zoneId);
        $request->headers->set('X-localization', $locale);

        $result = $cache->remember($request, 'layoutabc123');

        $this->assertTrue($result['fresh']);
        $this->assertSame('hit', $result['source']);
        $this->assertSame($payload, $result['bundle']);
    }

    public function test_content_version_bump_does_not_drop_cache(): void
    {
        $zoneId = 'zone-1';
        $locale = 'en';
        $payload = [
            'banners' => ['data' => [['id' => 99]], 'total' => 1],
            'categories' => ['data' => [], 'total' => 0],
        ];

        Cache::forever(
            CustomerHomeBaseBundleCache::cacheKey($zoneId, $locale),
            $payload
        );

        // Simulates unrelated version bump — cache key is independent of version.
        CustomerHomeContentVersion::bumpGlobal();

        $cache = $this->app->make(CustomerHomeBaseBundleCache::class);
        $request = Request::create('/api/v1/customer/home-bundle', 'GET');
        $request->headers->set('zoneId', $zoneId);
        $request->headers->set('X-localization', $locale);

        $result = $cache->remember($request, 'layoutabc123');

        $this->assertTrue($result['fresh']);
        $this->assertSame($payload, $result['bundle']);
    }

    public function test_remember_returns_empty_payload_on_total_miss_without_auto_warm(): void
    {
        $cache = $this->app->make(CustomerHomeBaseBundleCache::class);
        $request = Request::create('/api/v1/customer/home-bundle', 'GET');
        $request->headers->set('zoneId', 'never-warmed-zone');
        $request->headers->set('X-localization', 'en');

        $result = $cache->remember($request, 'layoutabc123');

        $this->assertFalse($result['fresh']);
        $this->assertSame('miss', $result['source']);
        $this->assertSame(CustomerHomeBaseBundleCache::emptyPayload(), $result['bundle']);
        $this->assertFalse(Cache::has('customer_home_zone_warm:never-warmed-zone'));
    }
}
