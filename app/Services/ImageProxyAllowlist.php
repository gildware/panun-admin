<?php

namespace App\Services;

class ImageProxyAllowlist
{
    /**
     * Hostnames permitted for /image-proxy.
     * Always includes APP_URL host; IMAGE_PROXY_ALLOWED_HOSTS adds extras (CDN, localhost, etc.).
     *
     * @return list<string>
     */
    public static function hosts(): array
    {
        $hosts = array_map(
            static fn (string $value): string => strtolower(parse_url(trim($value), PHP_URL_HOST) ?: trim($value)),
            explode(',', (string) env('IMAGE_PROXY_ALLOWED_HOSTS', ''))
        );

        $appHost = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));
        if ($appHost !== '') {
            $hosts[] = $appHost;
        }

        return array_values(array_unique(array_filter($hosts)));
    }
}
