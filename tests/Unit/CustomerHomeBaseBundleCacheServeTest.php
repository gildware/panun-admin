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

    public function test_remember_returns_versioned_cache_without_composing(): void
    {
        $layoutHash = 'layoutabc123';
        $zoneId = 'zone-1';
        $locale = 'en';
        $payload = [
            'banners' => ['data' => [['id' => 1]], 'total' => 1],
            'categories' => ['data' => [], 'total' => 0],
        ];

        Cache::put(
            CustomerHomeBaseBundleCache::cacheKey($zoneId, $locale, $layoutHash),
            $payload,
            60
        );

        $cache = $this->app->make(CustomerHomeBaseBundleCache::class);
        $request = Request::create('/api/v1/customer/home-bundle', 'GET');
        $request->headers->set('zoneId', $zoneId);
        $request->headers->set('X-localization', $locale);

        $result = $cache->remember($request, $layoutHash);

        $this->assertTrue($result['fresh']);
        $this->assertSame('versioned', $result['source']);
        $this->assertSame($payload, $result['bundle']);
    }

    public function test_remember_falls_back_to_latest_alias_after_version_bump(): void
    {
        $layoutHash = 'layoutabc123';
        $zoneId = 'zone-1';
        $locale = 'en';
        $stale = [
            'banners' => ['data' => [['id' => 99]], 'total' => 1],
            'categories' => ['data' => [], 'total' => 0],
        ];

        Cache::put(
            CustomerHomeBaseBundleCache::latestCacheKey($zoneId, $locale, $layoutHash),
            $stale,
            60
        );

        // Bump so versioned key misses; latest alias should still serve.
        CustomerHomeContentVersion::bumpGlobal();

        $cache = $this->app->make(CustomerHomeBaseBundleCache::class);
        $request = Request::create('/api/v1/customer/home-bundle', 'GET');
        $request->headers->set('zoneId', $zoneId);
        $request->headers->set('X-localization', $locale);

        $result = $cache->remember($request, $layoutHash);

        $this->assertFalse($result['fresh']);
        $this->assertSame('latest', $result['source']);
        $this->assertSame($stale, $result['bundle']);
    }

    public function test_remember_returns_empty_payload_on_total_miss(): void
    {
        $cache = $this->app->make(CustomerHomeBaseBundleCache::class);
        $request = Request::create('/api/v1/customer/home-bundle', 'GET');
        $request->headers->set('zoneId', 'never-warmed-zone');
        $request->headers->set('X-localization', 'en');

        $result = $cache->remember($request, 'layoutabc123');

        $this->assertFalse($result['fresh']);
        $this->assertSame('miss', $result['source']);
        $this->assertSame(CustomerHomeBaseBundleCache::emptyPayload(), $result['bundle']);
    }
}
