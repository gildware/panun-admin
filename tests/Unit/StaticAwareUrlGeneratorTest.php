<?php

namespace Tests\Unit;

use App\Routing\StaticAwareUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Tests\TestCase;

class StaticAwareUrlGeneratorTest extends TestCase
{
    public function test_assets_use_static_asset_url_when_configured(): void
    {
        config([
            'app.url' => 'https://live.example.com/public',
            'app.static_asset_url' => 'https://cdn.example.com/prod',
            'app.asset_url' => null,
        ]);

        $url = new StaticAwareUrlGenerator(new RouteCollection, Request::create('https://live.example.com'));

        $this->assertSame(
            'https://cdn.example.com/prod/assets/admin-module/css/style.css',
            $url->asset('assets/admin-module/css/style.css')
        );
    }

    public function test_storage_paths_stay_on_app_origin(): void
    {
        config([
            'app.url' => 'https://live.example.com/public',
            'app.static_asset_url' => 'https://cdn.example.com/prod',
            'app.asset_url' => null,
        ]);

        $url = new StaticAwareUrlGenerator(
            new RouteCollection,
            Request::create('https://live.example.com'),
            null
        );
        $url->forceRootUrl('https://live.example.com/public');

        $this->assertSame(
            'https://live.example.com/public/storage/provider/logo.webp',
            $url->asset('storage/provider/logo.webp')
        );
    }

    public function test_without_static_url_assets_use_app_origin(): void
    {
        config([
            'app.url' => 'https://live.example.com/public',
            'app.static_asset_url' => null,
            'app.asset_url' => null,
        ]);

        $url = new StaticAwareUrlGenerator(
            new RouteCollection,
            Request::create('https://live.example.com'),
            null
        );
        $url->forceRootUrl('https://live.example.com/public');

        $this->assertSame(
            'https://live.example.com/public/assets/landing/js/custom.js',
            $url->asset('assets/landing/js/custom.js')
        );
    }
}
