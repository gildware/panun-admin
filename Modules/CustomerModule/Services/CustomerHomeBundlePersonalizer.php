<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Http\Request;
use Modules\ProviderManagement\Entities\FavoriteProvider;
use Modules\ServiceManagement\Entities\FavoriteService;

class CustomerHomeBundlePersonalizer
{
    public function __construct(
        private CustomerHomeBundleComposer $composer,
    ) {}

    /**
     * @param  array<string, mixed>  $baseBundle
     * @return array<string, mixed>
     */
    public function apply(array $baseBundle, Request $request, int $userId): array
    {
        $bundle = json_decode(json_encode($baseBundle), true);
        if (! is_array($bundle)) {
            return $baseBundle;
        }

        $serviceIds = $this->collectServiceIds($bundle);
        $providerIds = $this->collectProviderIds($bundle);

        $favoriteServiceIds = $this->favoriteServiceIds($userId, $serviceIds);
        $favoriteProviderIds = $this->favoriteProviderIds($userId, $providerIds);

        $this->patchServiceFavorites($bundle, $favoriteServiceIds);
        $this->patchProviderFavorites($bundle, $favoriteProviderIds);

        if ($this->composer->layoutIncludesRecentlyViewed()) {
            $recentlyViewed = $this->composer->fetchRecentlyViewedSection($request);
            if ($recentlyViewed !== null) {
                $bundle['recently_viewed_services'] = $recentlyViewed;
            }
        }

        return $bundle;
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @return list<string>
     */
    private function collectServiceIds(array $bundle): array
    {
        $ids = [];

        foreach (['popular_services', 'trending_services', 'recommended_services', 'recently_viewed_services'] as $key) {
            foreach ($bundle[$key]['data'] ?? [] as $item) {
                if (isset($item['id'])) {
                    $ids[] = (string) $item['id'];
                }
            }
        }

        foreach ($bundle['featured_categories']['data'] ?? [] as $category) {
            foreach ($category['services_by_category'] ?? $category['services'] ?? [] as $service) {
                if (isset($service['id'])) {
                    $ids[] = (string) $service['id'];
                }
            }
        }

        foreach ($bundle['curated_sections'] ?? [] as $section) {
            foreach ($section['data'] ?? [] as $item) {
                if (isset($item['id'], $item['name'])) {
                    $ids[] = (string) $item['id'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @return list<string>
     */
    private function collectProviderIds(array $bundle): array
    {
        $ids = [];

        foreach (['providers', 'nearby_providers'] as $key) {
            foreach ($bundle[$key]['data'] ?? [] as $provider) {
                if (isset($provider['id'])) {
                    $ids[] = (string) $provider['id'];
                }
            }
        }

        foreach ($bundle['advertisements']['data'] ?? [] as $advertisement) {
            if (isset($advertisement['provider_id'])) {
                $ids[] = (string) $advertisement['provider_id'];
            }
            if (isset($advertisement['provider']['id'])) {
                $ids[] = (string) $advertisement['provider']['id'];
            }
        }

        foreach ($bundle['curated_sections'] ?? [] as $section) {
            foreach ($section['data'] ?? [] as $item) {
                if (isset($item['id'], $item['company_name'])) {
                    $ids[] = (string) $item['id'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<string>  $serviceIds
     * @return array<string, true>
     */
    private function favoriteServiceIds(int $userId, array $serviceIds): array
    {
        if ($serviceIds === []) {
            return [];
        }

        return FavoriteService::query()
            ->where('customer_user_id', $userId)
            ->whereIn('service_id', $serviceIds)
            ->pluck('service_id')
            ->mapWithKeys(fn ($id) => [(string) $id => true])
            ->all();
    }

    /**
     * @param  list<string>  $providerIds
     * @return array<string, true>
     */
    private function favoriteProviderIds(int $userId, array $providerIds): array
    {
        if ($providerIds === []) {
            return [];
        }

        return FavoriteProvider::query()
            ->where('customer_user_id', $userId)
            ->whereIn('provider_id', $providerIds)
            ->pluck('provider_id')
            ->mapWithKeys(fn ($id) => [(string) $id => true])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @param  array<string, true>  $favoriteServiceIds
     */
    private function patchServiceFavorites(array &$bundle, array $favoriteServiceIds): void
    {
        $patchList = function (array &$items) use ($favoriteServiceIds): void {
            foreach ($items as &$item) {
                if (! isset($item['id'])) {
                    continue;
                }
                $item['is_favorite'] = isset($favoriteServiceIds[(string) $item['id']]) ? 1 : 0;
            }
            unset($item);
        };

        foreach (['popular_services', 'trending_services', 'recommended_services', 'recently_viewed_services'] as $key) {
            if (isset($bundle[$key]['data']) && is_array($bundle[$key]['data'])) {
                $patchList($bundle[$key]['data']);
            }
        }

        if (isset($bundle['featured_categories']['data']) && is_array($bundle['featured_categories']['data'])) {
            foreach ($bundle['featured_categories']['data'] as &$category) {
                $services = $category['services_by_category'] ?? $category['services'] ?? null;
                if (! is_array($services)) {
                    continue;
                }
                $patchList($services);
                if (isset($category['services_by_category'])) {
                    $category['services_by_category'] = $services;
                } else {
                    $category['services'] = $services;
                }
            }
            unset($category);
        }

        foreach ($bundle['curated_sections'] ?? [] as $sectionKey => &$section) {
            if (! isset($section['data']) || ! is_array($section['data'])) {
                continue;
            }
            foreach ($section['data'] as &$item) {
                if (! isset($item['id'], $item['name'])) {
                    continue;
                }
                $item['is_favorite'] = isset($favoriteServiceIds[(string) $item['id']]) ? 1 : 0;
            }
            unset($item);
            $bundle['curated_sections'][$sectionKey] = $section;
        }
        unset($section);
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @param  array<string, true>  $favoriteProviderIds
     */
    private function patchProviderFavorites(array &$bundle, array $favoriteProviderIds): void
    {
        $patchProvider = function (array &$provider) use ($favoriteProviderIds): void {
            if (! isset($provider['id'])) {
                return;
            }
            $provider['is_favorite'] = isset($favoriteProviderIds[(string) $provider['id']]) ? 1 : 0;
        };

        foreach (['providers', 'nearby_providers'] as $key) {
            if (! isset($bundle[$key]['data']) || ! is_array($bundle[$key]['data'])) {
                continue;
            }
            foreach ($bundle[$key]['data'] as &$provider) {
                $patchProvider($provider);
            }
            unset($provider);
        }

        if (isset($bundle['advertisements']['data']) && is_array($bundle['advertisements']['data'])) {
            foreach ($bundle['advertisements']['data'] as &$advertisement) {
                if (isset($advertisement['provider']) && is_array($advertisement['provider'])) {
                    $patchProvider($advertisement['provider']);
                }
            }
            unset($advertisement);
        }

        foreach ($bundle['curated_sections'] ?? [] as $sectionKey => &$section) {
            if (! isset($section['data']) || ! is_array($section['data'])) {
                continue;
            }
            foreach ($section['data'] as &$item) {
                if (! isset($item['id'], $item['company_name'])) {
                    continue;
                }
                $patchProvider($item);
            }
            unset($item);
            $bundle['curated_sections'][$sectionKey] = $section;
        }
        unset($section);
    }
}
