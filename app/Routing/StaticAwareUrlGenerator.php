<?php

namespace App\Routing;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Str;

/**
 * Sends theme/static files (assets/*) to STATIC_ASSET_URL / ASSET_URL (e.g. R2),
 * while leaving storage/ and other paths on the app origin.
 */
class StaticAwareUrlGenerator extends UrlGenerator
{
    public function asset($path, $secure = null)
    {
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $normalized = ltrim((string) $path, '/');
        $staticRoot = $this->staticAssetRoot();

        if ($staticRoot !== null && $this->isStaticThemePath($normalized)) {
            return Str::finish($staticRoot, '/').$normalized;
        }

        return parent::asset($path, $secure);
    }

    private function staticAssetRoot(): ?string
    {
        $root = rtrim((string) config('app.static_asset_url', ''), '/');

        return $root !== '' ? $root : null;
    }

    private function isStaticThemePath(string $path): bool
    {
        return str_starts_with($path, 'assets/') || $path === 'assets';
    }
}
