<?php

namespace Modules\CustomerModule\Services;

use Illuminate\Support\Facades\Schema;
use Modules\ProviderManagement\Entities\Provider;

/**
     * Drop providers that must not appear in the customer app from a cached home payload
     * (App Availability off, or subscribed only to Customer App–off categories).
     * Home cache is rebuild-only, so this keeps the customer app current without waiting for Reset.
 */
class CustomerHomeBundleAppAvailabilityFilter
{
    /**
     * @param  array<string, mixed>  $bundle
     * @return array<string, mixed>
     */
    public function apply(array $bundle): array
    {
        $ids = $this->collectProviderIds($bundle);
        if ($ids === []) {
            return $bundle;
        }

        if (! Schema::hasTable('providers')) {
            return $bundle;
        }

        $query = Provider::query()
            ->whereIn('id', $ids)
            ->availableInCustomerApp();

        if (Schema::hasTable('subscribed_services') && Schema::hasTable('categories')) {
            $query->hasCustomerAppVisibleSubscription();
        }

        $visible = array_flip(
            $query
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all()
        );

        foreach (['providers', 'nearby_providers'] as $key) {
            if (isset($bundle[$key]) && is_array($bundle[$key])) {
                $bundle[$key] = $this->filterProviderList($bundle[$key], $visible);
            }
        }

        if (isset($bundle['advertisements']) && is_array($bundle['advertisements'])) {
            $bundle['advertisements'] = $this->filterAdvertisementList($bundle['advertisements'], $visible);
        }

        if (isset($bundle['curated_sections']) && is_array($bundle['curated_sections'])) {
            foreach ($bundle['curated_sections'] as $sectionKey => $content) {
                if (! is_array($content) || ! $this->looksLikeProviderList($content)) {
                    continue;
                }
                $bundle['curated_sections'][$sectionKey] = $this->filterProviderList($content, $visible);
            }
        }

        return $bundle;
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
                if (is_array($provider) && isset($provider['id'])) {
                    $ids[] = (string) $provider['id'];
                }
            }
        }

        foreach ($bundle['advertisements']['data'] ?? [] as $advertisement) {
            if (! is_array($advertisement)) {
                continue;
            }
            if (isset($advertisement['provider_id'])) {
                $ids[] = (string) $advertisement['provider_id'];
            }
            if (isset($advertisement['provider']['id'])) {
                $ids[] = (string) $advertisement['provider']['id'];
            }
        }

        foreach ($bundle['curated_sections'] ?? [] as $section) {
            if (! is_array($section) || ! $this->looksLikeProviderList($section)) {
                continue;
            }
            foreach ($section['data'] ?? [] as $item) {
                if (is_array($item) && isset($item['id'])) {
                    $ids[] = (string) $item['id'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<string, mixed>  $list
     * @param  array<string, int>  $visible
     * @return array<string, mixed>
     */
    private function filterProviderList(array $list, array $visible): array
    {
        if (! isset($list['data']) || ! is_array($list['data'])) {
            return $list;
        }

        $list['data'] = array_values(array_filter(
            $list['data'],
            static function ($provider) use ($visible): bool {
                if (! is_array($provider) || ! isset($provider['id'])) {
                    return false;
                }

                return isset($visible[(string) $provider['id']]);
            }
        ));
        $list['total'] = count($list['data']);

        return $list;
    }

    /**
     * @param  array<string, mixed>  $list
     * @param  array<string, int>  $visible
     * @return array<string, mixed>
     */
    private function filterAdvertisementList(array $list, array $visible): array
    {
        if (! isset($list['data']) || ! is_array($list['data'])) {
            return $list;
        }

        $list['data'] = array_values(array_filter(
            $list['data'],
            static function ($advertisement) use ($visible): bool {
                if (! is_array($advertisement)) {
                    return false;
                }

                $providerId = $advertisement['provider_id']
                    ?? $advertisement['provider']['id']
                    ?? null;

                return $providerId !== null && isset($visible[(string) $providerId]);
            }
        ));
        $list['total'] = count($list['data']);

        return $list;
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function looksLikeProviderList(array $content): bool
    {
        if (! isset($content['data']) || ! is_array($content['data']) || $content['data'] === []) {
            return false;
        }

        $first = $content['data'][0];

        return is_array($first) && isset($first['company_name']);
    }
}
