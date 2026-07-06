<?php

namespace Modules\CustomerModule\Services;

/**
 * Shrinks cached home-bundle JSON for faster transfer and parsing on mobile.
 */
class CustomerHomeBundlePayloadSlimmer
{
    /** @var list<string> */
    private const SERVICE_LIST_KEYS = [
        'popular_services',
        'trending_services',
        'recommended_services',
        'recently_viewed_services',
    ];

    /** @var list<string> */
    private const PROVIDER_LIST_KEYS = [
        'providers',
        'nearby_providers',
    ];

    /**
     * @param  array<string, mixed>  $bundle
     * @return array<string, mixed>
     */
    public static function slim(array $bundle): array
    {
        foreach (self::SERVICE_LIST_KEYS as $key) {
            if (isset($bundle[$key]) && is_array($bundle[$key])) {
                $bundle[$key] = CustomerServicePayloadSlimmer::slimList($bundle[$key]);
            }
        }

        foreach (self::PROVIDER_LIST_KEYS as $key) {
            if (isset($bundle[$key]) && is_array($bundle[$key])) {
                $bundle[$key] = CustomerProviderPayloadSlimmer::slimList($bundle[$key]);
            }
        }

        if (isset($bundle['categories']) && is_array($bundle['categories'])) {
            $bundle['categories'] = CustomerCategoryPayloadSlimmer::slimGridList($bundle['categories']);
        }

        if (isset($bundle['sub_categories']) && is_array($bundle['sub_categories'])) {
            $bundle['sub_categories'] = CustomerCategoryPayloadSlimmer::slimGridList($bundle['sub_categories']);
        }

        if (isset($bundle['featured_categories']) && is_array($bundle['featured_categories'])) {
            $bundle['featured_categories'] = CustomerCategoryPayloadSlimmer::slimFeaturedList($bundle['featured_categories']);
        }

        if (isset($bundle['advertisements']) && is_array($bundle['advertisements'])) {
            $bundle['advertisements'] = self::slimAdvertisementList($bundle['advertisements']);
        }

        if (isset($bundle['curated_sections']) && is_array($bundle['curated_sections'])) {
            foreach ($bundle['curated_sections'] as $sectionKey => $content) {
                if (! is_array($content)) {
                    continue;
                }

                if (CustomerServicePayloadSlimmer::looksLikeServiceList($content)) {
                    $bundle['curated_sections'][$sectionKey] = CustomerServicePayloadSlimmer::slimList($content);
                    continue;
                }

                if (self::looksLikeProviderList($content)) {
                    $bundle['curated_sections'][$sectionKey] = CustomerProviderPayloadSlimmer::slimList($content);
                }
            }
        }

        return $bundle;
    }

    /**
     * @param  array<string, mixed>  $list
     * @return array<string, mixed>
     */
    private static function slimAdvertisementList(array $list): array
    {
        if (! isset($list['data']) || ! is_array($list['data'])) {
            return $list;
        }

        $list['data'] = array_map(function ($item) {
            if (! is_array($item)) {
                return $item;
            }

            if (isset($item['provider']) && is_array($item['provider'])) {
                $item['provider'] = CustomerProviderPayloadSlimmer::slimAdvertisementProvider($item['provider']);
            }

            return $item;
        }, $list['data']);

        return $list;
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private static function looksLikeProviderList(array $content): bool
    {
        if (! isset($content['data']) || ! is_array($content['data']) || $content['data'] === []) {
            return false;
        }

        $first = $content['data'][0];

        return is_array($first) && isset($first['company_name']);
    }
}
