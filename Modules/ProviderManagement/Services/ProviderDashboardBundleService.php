<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Http\Request;

class ProviderDashboardBundleService
{
    private function dashboardSections(): string
    {
        $sections = [
            'top_cards',
            'earning_stats',
            'booking_stats',
            'recent_bookings',
            'my_subscriptions',
        ];

        if ((int) ((business_config('bidding_status', 'bidding_system'))?->live_values ?? 0) === 1) {
            $sections[] = 'customized_post';
        }

        $sections[] = 'additional_info_count';

        return implode(',', $sections);
    }

    public function build(Request $request): array
    {
        $userId = auth('api')->id();
        $locale = strtolower((string) $request->header('X-localization', app()->getLocale()));
        $cacheKey = 'provider_dashboard_bundle:v1:'.$userId.':'.$locale;

        return ProviderApiResponseCache::remember(
            $cacheKey,
            fn () => $this->fetchBundle($request),
            ProviderApiResponseCache::DASHBOARD_BUNDLE_TTL
        );
    }

    private function fetchBundle(Request $request): array
    {
        $dashboard = $this->dispatchGet(
            $request,
            '/api/v1/provider/dashboard',
            ['sections' => $this->dashboardSections()]
        );

        $earning = $this->dispatchGet($request, '/api/v1/provider/dashboard/earning');

        return array_filter([
            'dashboard' => $dashboard,
            'earning' => $earning,
        ], fn ($value) => $value !== null);
    }

    private function dispatchGet(Request $parent, string $path, array $query = []): mixed
    {
        $uri = $path.($query !== [] ? '?'.http_build_query($query) : '');
        $server = ['HTTP_ACCEPT' => 'application/json'];

        foreach (['Authorization', 'X-localization'] as $name) {
            $value = $parent->header($name);
            if ($value !== null && $value !== '') {
                $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
            }
        }

        $sub = Request::create($uri, 'GET', [], [], [], $server);
        $sub->headers->set('Accept', 'application/json');
        if ($auth = $parent->header('Authorization')) {
            $sub->headers->set('Authorization', $auth);
        }

        $response = app()->handle($sub);
        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $decoded = json_decode($response->getContent(), true);

        return is_array($decoded) ? ($decoded['content'] ?? null) : null;
    }
}
