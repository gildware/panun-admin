<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\CustomerModule\Services\CustomerHomeBaseBundleCache;
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
        $zoneId = 'zone-leaf';
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

    public function test_multi_zone_header_hits_leaf_cache(): void
    {
        $leaf = 'zone-leaf';
        $parent = 'zone-parent';
        $payload = [
            'banners' => ['data' => [['id' => 99]], 'total' => 1],
            'categories' => ['data' => [['id' => 'c1']], 'total' => 1],
            'popular_services' => ['data' => [['id' => 's1']], 'total' => 1],
        ];

        Cache::forever(
            CustomerHomeBaseBundleCache::cacheKey($leaf, 'en'),
            $payload
        );

        $cache = $this->app->make(CustomerHomeBaseBundleCache::class);
        $request = Request::create('/api/v1/customer/home-bundle', 'GET');
        $request->headers->set('zoneId', $parent.','.$leaf);
        $request->headers->set('X-localization', 'en');

        $result = $cache->remember($request, 'layoutabc123');

        $this->assertTrue($result['fresh']);
        $this->assertSame($payload, $result['bundle']);
        $this->assertContains($result['source'], ['hit', 'hit_normalized']);
    }

    public function test_parse_zone_tokens(): void
    {
        $this->assertSame(
            ['a', 'b'],
            CustomerHomeBaseBundleCache::parseZoneTokens('a,b')
        );
        $this->assertSame(
            ['a', 'b'],
            CustomerHomeBaseBundleCache::parseZoneTokens('[a, b]')
        );
    }

    public function test_locale_falls_back_to_en(): void
    {
        $zoneId = 'zone-leaf';
        $payload = [
            'categories' => ['data' => [['id' => 'c1']], 'total' => 1],
        ];
        Cache::forever(
            CustomerHomeBaseBundleCache::cacheKey($zoneId, 'en'),
            $payload
        );

        $cache = $this->app->make(CustomerHomeBaseBundleCache::class);
        $request = Request::create('/api/v1/customer/home-bundle', 'GET');
        $request->headers->set('zoneId', $zoneId);
        $request->headers->set('X-localization', 'hi');

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
    }
}
