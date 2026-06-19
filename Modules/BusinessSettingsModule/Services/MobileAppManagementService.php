<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\CustomerModule\Services\CustomerApiResponseCache;
use Modules\CategoryManagement\Entities\Category;
use Modules\PromotionManagement\Entities\Banner;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;

class MobileAppManagementService
{
    public const SETTINGS_TYPE = 'mobile_app';

    public const HOME_SECTIONS_KEY = 'customer_app_home_sections';

    public const ICONS_KEY = 'mobile_app_icons';

    /** @var list<string> */
    public const ICON_VARIANTS = ['light', 'dark'];

    public const DATA_MODE_DEFAULT = 'default';

    public const DATA_MODE_MANUAL = 'manual';

    public const CONTENT_SERVICES = 'services';

    public const CONTENT_PROVIDERS = 'providers';

    public const CONTENT_BANNERS = 'banners';

    public const CONTENT_CATEGORIES = 'categories';

    public const CONTENT_SUB_CATEGORIES = 'sub_categories';

    /** @var list<string> */
    public const BANNER_MANUAL_KEYS = [
        'banners',
    ];

    /** @var list<string> */
    public const CATEGORY_MANUAL_KEYS = [
        'categories',
        'feathered_categories',
    ];

    /** @var list<string> */
    public const SERVICE_MANUAL_KEYS = [
        'popular_services',
        'recommended_services',
        'trending_services',
        'recently_viewed',
    ];

    /** @var list<string> */
    public const PROVIDER_MANUAL_KEYS = [
        'nearby_providers',
        'recommended_providers',
        'highlight_providers',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public static function homeSectionDefinitions(): array
    {
        return [
            [
                'key' => 'search',
                'label' => 'Search bar',
                'description' => 'Home search field at the top of the screen.',
                'default_title' => null,
                'preview_type' => 'search',
                'icon' => 'search',
                'default_enabled' => true,
                'fixed' => true,
                'default_item_limit' => null,
                'conditional' => null,
                'supports_manual_data' => false,
                'content_type' => null,
            ],
            [
                'key' => 'banners',
                'label' => 'Banners',
                'description' => 'Promotional banner carousel.',
                'default_title' => null,
                'preview_type' => 'banner',
                'icon' => 'view_carousel',
                'default_enabled' => true,
                'fixed' => false,
                'default_item_limit' => 10,
                'conditional' => null,
                'supports_manual_data' => true,
                'content_type' => self::CONTENT_BANNERS,
            ],
            [
                'key' => 'categories',
                'label' => 'Categories',
                'description' => 'Main service categories grid.',
                'default_title' => 'All categories',
                'preview_type' => 'categories',
                'icon' => 'category',
                'default_enabled' => true,
                'fixed' => false,
                'default_item_limit' => 8,
                'conditional' => null,
                'supports_manual_data' => true,
                'content_type' => self::CONTENT_CATEGORIES,
            ],
            [
                'key' => 'highlight_providers',
                'label' => 'Highlight providers',
                'description' => 'Featured provider highlights (from advertisements).',
                'default_title' => 'Highlight providers',
                'preview_type' => 'highlight_providers',
                'icon' => 'stars',
                'default_enabled' => true,
                'fixed' => false,
                'default_item_limit' => null,
                'conditional' => null,
                'supports_manual_data' => true,
                'content_type' => self::CONTENT_PROVIDERS,
            ],
            [
                'key' => 'popular_services',
                'label' => 'Popular services',
                'description' => 'Horizontal list of popular services.',
                'default_title' => 'Popular services',
                'preview_type' => 'services_horizontal',
                'icon' => 'local_fire_department',
                'default_enabled' => true,
                'fixed' => false,
                'default_item_limit' => 10,
                'conditional' => null,
                'supports_manual_data' => true,
                'content_type' => self::CONTENT_SERVICES,
            ],
            [
                'key' => 'campaigns',
                'label' => 'Campaigns',
                'description' => 'Active promotional campaigns.',
                'default_title' => 'Campaigns',
                'preview_type' => 'campaign',
                'icon' => 'campaign',
                'default_enabled' => true,
                'fixed' => false,
                'default_item_limit' => null,
                'conditional' => null,
                'supports_manual_data' => false,
                'content_type' => null,
            ],
            [
                'key' => 'recommended_services',
                'label' => 'Recommended services',
                'description' => 'Curated recommended services block.',
                'default_title' => 'Recommended for you',
                'preview_type' => 'recommended',
                'icon' => 'thumb_up',
                'default_enabled' => true,
                'fixed' => false,
                'default_item_limit' => 10,
                'conditional' => null,
                'supports_manual_data' => true,
                'content_type' => self::CONTENT_SERVICES,
            ],
            [
                'key' => 'nearby_providers',
                'label' => 'Nearby providers',
                'description' => 'Providers near the customer location.',
                'default_title' => 'Nearby providers',
                'preview_type' => 'providers_horizontal',
                'icon' => 'location_on',
                'default_enabled' => true,
                'fixed' => false,
                'default_item_limit' => 10,
                'conditional' => 'direct_provider_booking',
                'supports_manual_data' => true,
                'content_type' => self::CONTENT_PROVIDERS,
            ],
            [
                'key' => 'explore_provider_card',
                'label' => 'Explore provider card',
                'description' => 'Call-to-action card to browse providers.',
                'default_title' => 'Explore providers',
                'preview_type' => 'explore_card',
                'icon' => 'explore',
                'default_enabled' => true,
                'fixed' => false,
                'default_item_limit' => null,
                'conditional' => 'direct_provider_booking',
                'supports_manual_data' => false,
                'content_type' => null,
            ],
            [
                'key' => 'recommended_providers',
                'label' => 'Recommended providers',
                'description' => 'Shown when direct provider booking is enabled.',
                'default_title' => 'Recommended providers',
                'preview_type' => 'providers_grid',
                'icon' => 'groups',
                'default_enabled' => true,
                'fixed' => false,
                'default_item_limit' => 6,
                'conditional' => 'direct_provider_booking',
                'supports_manual_data' => true,
                'content_type' => self::CONTENT_PROVIDERS,
            ],
            [
                'key' => 'create_post',
                'label' => 'Create post (bidding)',
                'description' => 'Custom post / bidding entry point.',
                'default_title' => 'Create custom post',
                'preview_type' => 'create_post',
                'icon' => 'post_add',
                'default_enabled' => true,
                'fixed' => false,
                'default_item_limit' => null,
                'conditional' => 'bidding_status',
                'supports_manual_data' => false,
                'content_type' => null,
            ],
            [
                'key' => 'recently_viewed',
                'label' => 'Recently viewed services',
                'description' => 'Logged-in customers only.',
                'default_title' => 'Recently viewed',
                'preview_type' => 'services_horizontal',
                'icon' => 'history',
                'default_enabled' => true,
                'fixed' => false,
                'default_item_limit' => 10,
                'conditional' => 'logged_in',
                'supports_manual_data' => true,
                'content_type' => self::CONTENT_SERVICES,
            ],
            [
                'key' => 'trending_services',
                'label' => 'Trending services',
                'description' => 'Trending services horizontal list.',
                'default_title' => 'Trending services',
                'preview_type' => 'services_horizontal',
                'icon' => 'trending_up',
                'default_enabled' => true,
                'fixed' => false,
                'default_item_limit' => 10,
                'conditional' => null,
                'supports_manual_data' => true,
                'content_type' => self::CONTENT_SERVICES,
            ],
            [
                'key' => 'feathered_categories',
                'label' => 'Featured categories',
                'description' => 'Feathered / featured category strip.',
                'default_title' => 'Featured categories',
                'preview_type' => 'categories_strip',
                'icon' => 'auto_awesome',
                'default_enabled' => true,
                'fixed' => false,
                'default_item_limit' => 8,
                'conditional' => null,
                'supports_manual_data' => true,
                'content_type' => self::CONTENT_CATEGORIES,
            ],
        ];
    }

    public static function isCustomSectionKey(string $key): bool
    {
        return str_starts_with($key, 'custom_');
    }

    public static function sectionSupportsManualData(string $key, ?string $contentType = null): bool
    {
        if (self::isCustomSectionKey($key)) {
            return true;
        }

        return in_array($key, array_merge(
            self::SERVICE_MANUAL_KEYS,
            self::PROVIDER_MANUAL_KEYS,
            self::BANNER_MANUAL_KEYS,
            self::CATEGORY_MANUAL_KEYS,
        ), true);
    }

    public static function sectionContentType(string $key, ?string $storedContentType = null): ?string
    {
        $allowed = [
            self::CONTENT_SERVICES,
            self::CONTENT_PROVIDERS,
            self::CONTENT_BANNERS,
            self::CONTENT_CATEGORIES,
            self::CONTENT_SUB_CATEGORIES,
        ];

        if (self::isCustomSectionKey($key)) {
            return in_array($storedContentType, $allowed, true)
                ? $storedContentType
                : self::CONTENT_SERVICES;
        }

        if (in_array($key, self::BANNER_MANUAL_KEYS, true)) {
            return self::CONTENT_BANNERS;
        }

        if (in_array($key, self::CATEGORY_MANUAL_KEYS, true)) {
            return self::CONTENT_CATEGORIES;
        }

        if (in_array($key, self::SERVICE_MANUAL_KEYS, true)) {
            return self::CONTENT_SERVICES;
        }

        if (in_array($key, self::PROVIDER_MANUAL_KEYS, true)) {
            return self::CONTENT_PROVIDERS;
        }

        return null;
    }

    public static function sectionDefaultDataHint(string $key, ?string $contentType = null): string
    {
        $langKey = match (true) {
            self::isCustomSectionKey($key) => match ($contentType) {
                self::CONTENT_BANNERS => 'mah_default_hint_banners',
                self::CONTENT_CATEGORIES => 'mah_default_hint_categories',
                self::CONTENT_SUB_CATEGORIES => 'mah_default_hint_sub_categories',
                self::CONTENT_PROVIDERS => 'mah_default_hint_providers',
                default => 'mah_default_hint_services',
            },
            $key === 'banners' => 'mah_default_hint_banners',
            $key === 'categories' => 'mah_default_hint_categories',
            $key === 'feathered_categories' => 'mah_default_hint_feathered_categories',
            $key === 'highlight_providers' => 'mah_default_hint_highlight_providers',
            $key === 'popular_services' => 'mah_default_hint_popular_services',
            $key === 'recommended_services' => 'mah_default_hint_recommended_services',
            $key === 'trending_services' => 'mah_default_hint_trending_services',
            $key === 'recently_viewed' => 'mah_default_hint_recently_viewed',
            $key === 'nearby_providers' => 'mah_default_hint_nearby_providers',
            $key === 'recommended_providers' => 'mah_default_hint_recommended_providers',
            default => 'mah_default_hint_services',
        };

        return translate($langKey);
    }

    public static function sectionManualDataHint(string $key, ?string $contentType = null): string
    {
        $langKey = match (true) {
            self::isCustomSectionKey($key) => match ($contentType) {
                self::CONTENT_BANNERS => 'mah_manual_hint_banners',
                self::CONTENT_CATEGORIES => 'mah_manual_hint_categories',
                self::CONTENT_SUB_CATEGORIES => 'mah_manual_hint_sub_categories',
                self::CONTENT_PROVIDERS => 'mah_manual_hint_providers',
                default => 'mah_manual_hint_services',
            },
            $key === 'banners' => 'mah_manual_hint_banners',
            $key === 'categories', $key === 'feathered_categories' => 'mah_manual_hint_categories',
            $key === 'highlight_providers', $key === 'nearby_providers', $key === 'recommended_providers' => 'mah_manual_hint_providers',
            $key === 'popular_services' => 'mah_manual_hint_popular_services',
            $key === 'recommended_services' => 'mah_manual_hint_recommended_services',
            $key === 'trending_services' => 'mah_manual_hint_trending_services',
            $key === 'recently_viewed' => 'mah_manual_hint_recently_viewed',
            default => 'mah_manual_hint_services',
        };

        return translate($langKey);
    }

    /**
     * @return array{byKey: array<string, string>, byContentType: array<string, string>}
     */
    public static function dataSourceHintsForAdmin(): array
    {
        $byKey = [];
        foreach (self::homeSectionDefinitions() as $def) {
            if (!($def['supports_manual_data'] ?? false)) {
                continue;
            }
            $key = (string) $def['key'];
            $byKey[$key] = [
                'default' => self::sectionDefaultDataHint($key, $def['content_type'] ?? null),
                'manual' => self::sectionManualDataHint($key, $def['content_type'] ?? null),
            ];
        }

        $byContentType = [];
        foreach ([self::CONTENT_SERVICES, self::CONTENT_PROVIDERS, self::CONTENT_BANNERS, self::CONTENT_CATEGORIES, self::CONTENT_SUB_CATEGORIES] as $type) {
            $byContentType[$type] = [
                'default' => self::sectionDefaultDataHint('custom_placeholder', $type),
                'manual' => self::sectionManualDataHint('custom_placeholder', $type),
            ];
        }

        return ['byKey' => $byKey, 'byContentType' => $byContentType];
    }

    /**
     * @return array<string, array{customer: list<array{key: string, label: string}>, provider: list<array{key: string, label: string}>}>
     */
    public static function iconGroupDefinitions(): array
    {
        return [
            'logos' => [
                'customer' => [
                    ['key' => 'customer_app_logo', 'label' => 'Customer app logo'],
                ],
                'provider' => [
                    ['key' => 'provider_app_login_logo', 'label' => 'Provider logo (login & splash)'],
                    ['key' => 'provider_app_home_logo', 'label' => 'Provider logo (home / app bar)'],
                ],
            ],
            'menu' => [
                'customer' => [
                    ['key' => 'profile', 'label' => 'Profile'],
                    ['key' => 'inbox', 'label' => 'Inbox / chat'],
                    ['key' => 'language', 'label' => 'Language'],
                    ['key' => 'settings', 'label' => 'Settings'],
                    ['key' => 'bookings', 'label' => 'Bookings'],
                    ['key' => 'vouchers', 'label' => 'Vouchers'],
                    ['key' => 'my_favorite', 'label' => 'My favorite'],
                    ['key' => 'custom_post', 'label' => 'My posts (bidding)'],
                    ['key' => 'wallet', 'label' => 'Wallet'],
                    ['key' => 'loyalty_point', 'label' => 'Loyalty point'],
                    ['key' => 'refer_and_earn', 'label' => 'Refer and earn'],
                    ['key' => 'service_area', 'label' => 'Service area'],
                    ['key' => 'help_support', 'label' => 'Help and support'],
                    ['key' => 'become_provider', 'label' => 'Become a provider'],
                    ['key' => 'logout', 'label' => 'Logout / sign in'],
                    ['key' => 'about_us', 'label' => 'About us (page)'],
                    ['key' => 'terms', 'label' => 'Terms and conditions (page)'],
                    ['key' => 'privacy_policy', 'label' => 'Privacy policy (page)'],
                    ['key' => 'cancellation_policy', 'label' => 'Cancellation policy (page)'],
                    ['key' => 'refund_policy', 'label' => 'Refund policy (page)'],
                    ['key' => 'other_pages', 'label' => 'Other business pages'],
                ],
                'provider' => [
                    ['key' => 'profile', 'label' => 'Profile'],
                    ['key' => 'subscription', 'label' => 'My subscription'],
                    ['key' => 'services', 'label' => 'Services'],
                    ['key' => 'chat', 'label' => 'Chat'],
                    ['key' => 'settings', 'label' => 'Settings'],
                    ['key' => 'payment_info', 'label' => 'Payment information'],
                    ['key' => 'notification_channel', 'label' => 'Notification channel'],
                    ['key' => 'withdraw_list', 'label' => 'Withdraw list'],
                    ['key' => 'reports', 'label' => 'Reports'],
                    ['key' => 'advertisements', 'label' => 'Advertisements'],
                    ['key' => 'business_plan', 'label' => 'Business plan'],
                    ['key' => 'help_support', 'label' => 'Help and support'],
                    ['key' => 'about_us', 'label' => 'About us (page)'],
                    ['key' => 'terms', 'label' => 'Terms and conditions (page)'],
                    ['key' => 'privacy_policy', 'label' => 'Privacy policy (page)'],
                    ['key' => 'cancellation_policy', 'label' => 'Cancellation policy (page)'],
                    ['key' => 'refund_policy', 'label' => 'Refund policy (page)'],
                    ['key' => 'other_pages', 'label' => 'Other business pages'],
                    ['key' => 'logout', 'label' => 'Logout / sign in'],
                ],
            ],
            'bottom_navigation' => [
                'customer' => [],
                'provider' => [
                    ['key' => 'bottom_dashboard', 'label' => 'Dashboard (bottom navigation)'],
                    ['key' => 'bottom_requests', 'label' => 'Requests (bottom navigation)'],
                    ['key' => 'post', 'label' => 'Post (bottom navigation)'],
                    ['key' => 'bottom_more', 'label' => 'More (bottom navigation)'],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getHomeSections(): array
    {
        return $this->mergeHomeSectionRows();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSectionByKey(string $key): ?array
    {
        foreach ($this->mergeHomeSectionRows() as $row) {
            if (($row['key'] ?? '') === $key) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Catalog + default lists for admin home-page live preview (real DB records).
     *
     * @return array{
     *     catalog: array{services: array<string, array{id: string, name: string, url: string}>, providers: array, banners: array, categories: array},
     *     defaults: array<string, list<array{id: string, name: string, url: string}>>
     * }
     */
    public function buildHomePagePreviewData(): array
    {
        $serviceIds = [];
        $providerIds = [];
        $bannerIds = [];
        $categoryIds = [];

        foreach ($this->mergeHomeSectionRows() as $row) {
            if (($row['data_mode'] ?? self::DATA_MODE_DEFAULT) !== self::DATA_MODE_MANUAL) {
                continue;
            }
            foreach ($row['service_ids'] ?? [] as $id) {
                $serviceIds[$id] = $id;
            }
            foreach ($row['provider_ids'] ?? [] as $id) {
                $providerIds[$id] = $id;
            }
            foreach ($row['banner_ids'] ?? [] as $id) {
                $bannerIds[$id] = $id;
            }
            foreach ($row['category_ids'] ?? [] as $id) {
                $categoryIds[$id] = $id;
            }
        }

        $catalog = [
            'services' => $this->previewServicesByIds(array_keys($serviceIds)),
            'providers' => $this->previewProvidersByIds(array_keys($providerIds)),
            'banners' => $this->previewBannersByIds(array_keys($bannerIds)),
            'categories' => $this->previewCategoriesByIds(array_keys($categoryIds)),
        ];

        $defaults = [
            'banners' => $this->previewBannerList(5),
            'categories' => $this->previewCategoryList(8),
            'sub_categories' => $this->previewSubCategoryList(8),
            'feathered_categories' => $this->previewCategoryList(6),
            'popular_services' => $this->previewPopularServices(5),
            'trending_services' => $this->previewTrendingServices(5),
            'recommended_services' => $this->previewRecommendedServices(5),
            'recently_viewed' => $this->previewPopularServices(4),
            'nearby_providers' => $this->previewProviderList(4),
            'recommended_providers' => $this->previewProviderList(4),
            'highlight_providers' => $this->previewProviderList(3),
        ];

        return [
            'catalog' => $catalog,
            'defaults' => $defaults,
        ];
    }

    /**
     * @param list<string> $ids
     * @return array<string, array{id: string, name: string, url: string}>
     */
    private function previewServicesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Service::query()
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (Service $s) => [$s->id => $this->formatServicePreview($s)])
            ->all();
    }

    /**
     * @param list<string> $ids
     * @return array<string, array{id: string, name: string, url: string}>
     */
    private function previewProvidersByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Provider::query()
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (Provider $p) => [$p->id => $this->formatProviderPreview($p)])
            ->all();
    }

    /**
     * @param list<string> $ids
     * @return array<string, array{id: string, name: string, url: string}>
     */
    private function previewBannersByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Banner::query()
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (Banner $b) => [$b->id => $this->formatBannerPreview($b)])
            ->all();
    }

    /**
     * @param list<string> $ids
     * @return array<string, array{id: string, name: string, url: string}>
     */
    private function previewCategoriesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Category::query()
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (Category $c) => [$c->id => $this->formatCategoryPreview($c)])
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, url: string}>
     */
    private function previewBannerList(int $limit): array
    {
        return Banner::query()
            ->ofStatus(1)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Banner $b) => $this->formatBannerPreview($b))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, url: string}>
     */
    private function previewCategoryList(int $limit): array
    {
        return Category::query()
            ->ofType('main')
            ->ofStatus(1)
            ->orderBy('position')
            ->limit($limit)
            ->get()
            ->map(fn (Category $c) => $this->formatCategoryPreview($c))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, url: string}>
     */
    private function previewSubCategoryList(int $limit): array
    {
        return Category::query()
            ->ofType('sub')
            ->ofStatus(1)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Category $c) => $this->formatCategoryPreview($c))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, url: string}>
     */
    private function previewPopularServices(int $limit): array
    {
        return Service::query()
            ->active()
            ->ofStatus(1)
            ->withCount('bookings')
            ->orderByDesc('bookings_count')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Service $s) => $this->formatServicePreview($s))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, url: string}>
     */
    private function previewTrendingServices(int $limit): array
    {
        return Service::query()
            ->active()
            ->ofStatus(1)
            ->withCount(['bookings' => function ($query) {
                $query->where('created_at', '>', now()->subDays(30)->endOfDay());
            }])
            ->orderByDesc('bookings_count')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Service $s) => $this->formatServicePreview($s))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, url: string}>
     */
    private function previewRecommendedServices(int $limit): array
    {
        return Service::query()
            ->active()
            ->ofStatus(1)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Service $s) => $this->formatServicePreview($s))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, url: string}>
     */
    private function previewProviderList(int $limit): array
    {
        return Provider::query()
            ->ofStatus(1)
            ->where('app_availability', 1)
            ->where('is_suspended', 0)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Provider $p) => $this->formatProviderPreview($p))
            ->values()
            ->all();
    }

    /**
     * @return array{id: string, name: string, url: string}
     */
    private function formatServicePreview(Service $service): array
    {
        return [
            'id' => (string) $service->id,
            'name' => (string) $service->name,
            'url' => (string) ($service->thumbnail_full_path ?: $service->cover_image_full_path ?: ''),
        ];
    }

    /**
     * @return array{id: string, name: string, url: string}
     */
    private function formatProviderPreview(Provider $provider): array
    {
        return [
            'id' => (string) $provider->id,
            'name' => (string) ($provider->company_name ?: 'Provider'),
            'url' => (string) ($provider->logo_full_path ?: $provider->cover_image_full_path ?: ''),
        ];
    }

    /**
     * @return array{id: string, name: string, url: string}
     */
    private function formatBannerPreview(Banner $banner): array
    {
        return [
            'id' => (string) $banner->id,
            'name' => (string) ($banner->banner_title ?: ('Banner #'.substr((string) $banner->id, 0, 8))),
            'url' => (string) ($banner->banner_image_full_path ?? ''),
        ];
    }

    /**
     * @return array{id: string, name: string, url: string}
     */
    private function formatCategoryPreview(Category $category): array
    {
        return [
            'id' => (string) $category->id,
            'name' => (string) ($category->name ?: ('Category #'.substr((string) $category->id, 0, 8))),
            'url' => (string) ($category->image_full_path ?? ''),
        ];
    }

    /**
     * @return list<array{id: string, text: string, image: string}>
     */
    public function searchCategoriesForPicker(string $query = '', int $limit = 30): array
    {
        $builder = Category::query()
            ->ofStatus(1)
            ->ofType('main');

        if ($query !== '') {
            $terms = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $builder->where(function ($outer) use ($terms) {
                foreach ($terms as $term) {
                    $outer->where(function ($inner) use ($term) {
                        $inner->where('name', 'like', '%'.$term.'%')
                            ->orWhere('slug', 'like', '%'.$term.'%')
                            ->orWhereHas('translations', function ($t) use ($term) {
                                $t->where('key', 'name')->where('value', 'like', '%'.$term.'%');
                            });
                    });
                }
            });
        }

        return $builder
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (Category $category) {
                $label = trim((string) ($category->name ?? ''));
                if ($label === '' && !empty($category->slug)) {
                    $label = ucwords(str_replace(['-', '_'], ' ', (string) $category->slug));
                }

                return [
                    'id' => (string) $category->id,
                    'text' => $label !== '' ? $label : ('Category #'.substr((string) $category->id, 0, 8)),
                    'image' => (string) ($category->image_full_path ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, text: string, image: string}>
     */
    public function searchSubCategoriesForPicker(string $query = '', int $limit = 30): array
    {
        $builder = Category::query()
            ->ofStatus(1)
            ->ofType('sub');

        if ($query !== '') {
            $terms = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $builder->where(function ($outer) use ($terms) {
                foreach ($terms as $term) {
                    $outer->where(function ($inner) use ($term) {
                        $inner->where('name', 'like', '%'.$term.'%')
                            ->orWhere('slug', 'like', '%'.$term.'%')
                            ->orWhereHas('translations', function ($t) use ($term) {
                                $t->where('key', 'name')->where('value', 'like', '%'.$term.'%');
                            });
                    });
                }
            });
        }

        return $builder
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (Category $category) {
                $label = trim((string) ($category->name ?? ''));
                if ($label === '' && !empty($category->slug)) {
                    $label = ucwords(str_replace(['-', '_'], ' ', (string) $category->slug));
                }

                return [
                    'id' => (string) $category->id,
                    'text' => $label !== '' ? $label : ('Sub category #'.substr((string) $category->id, 0, 8)),
                    'image' => (string) ($category->image_full_path ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{services: array<string, string>, providers: array<string, string>, banners: array<string, string>, categories: array<string, string>, sub_categories: array<string, string>}
     */
    public function resolvePicklistLabels(): array
    {
        $serviceIds = [];
        $providerIds = [];
        $bannerIds = [];
        $categoryIds = [];

        foreach ($this->mergeHomeSectionRows() as $row) {
            if (($row['data_mode'] ?? self::DATA_MODE_DEFAULT) !== self::DATA_MODE_MANUAL) {
                continue;
            }
            foreach ($row['service_ids'] ?? [] as $id) {
                $serviceIds[$id] = $id;
            }
            foreach ($row['provider_ids'] ?? [] as $id) {
                $providerIds[$id] = $id;
            }
            foreach ($row['banner_ids'] ?? [] as $id) {
                $bannerIds[$id] = $id;
            }
            foreach ($row['category_ids'] ?? [] as $id) {
                $categoryIds[$id] = $id;
            }
        }

        $services = [];
        if ($serviceIds !== []) {
            $services = Service::query()
                ->whereIn('id', array_keys($serviceIds))
                ->pluck('name', 'id')
                ->all();
        }

        $providers = [];
        if ($providerIds !== []) {
            $providers = Provider::query()
                ->whereIn('id', array_keys($providerIds))
                ->pluck('company_name', 'id')
                ->all();
        }

        $banners = [];
        if ($bannerIds !== []) {
            $banners = Banner::query()
                ->whereIn('id', array_keys($bannerIds))
                ->get()
                ->mapWithKeys(fn ($b) => [
                    $b->id => $b->banner_title ?: ('Banner #'.substr($b->id, 0, 8)),
                ])
                ->all();
        }

        $categories = [];
        $subCategories = [];
        if ($categoryIds !== []) {
            foreach (Category::query()->whereIn('id', array_keys($categoryIds))->get(['id', 'name', 'position']) as $category) {
                $label = $category->name ?: ('Category #'.substr((string) $category->id, 0, 8));
                // position 2 = sub-category, 1 = main (see Category::scopeOfType).
                if ((int) ($category->position ?? 1) === 2) {
                    $subCategories[(string) $category->id] = $label;
                } else {
                    $categories[(string) $category->id] = $label;
                }
            }
        }

        return [
            'services' => $services,
            'providers' => $providers,
            'banners' => $banners,
            'categories' => $categories,
            'sub_categories' => $subCategories,
        ];
    }

    /**
     * @param list<array<string, mixed>> $sections
     */
    public function saveHomeSections(array $sections): void
    {
        $allowed = collect(self::homeSectionDefinitions())->keyBy('key');
        $normalized = [];
        $seenKeys = [];

        foreach ($sections as $index => $row) {
            $key = (string) ($row['key'] ?? '');
            $isCustom = self::isCustomSectionKey($key);

            if ($isCustom) {
                if (!preg_match('/^custom_[a-z0-9_]{4,40}$/', $key)) {
                    continue;
                }
            } elseif (!$allowed->has($key)) {
                continue;
            }

            if (isset($seenKeys[$key])) {
                continue;
            }
            $seenKeys[$key] = true;

            $def = $isCustom ? null : $allowed->get($key);
            $contentType = self::sectionContentType(
                $key,
                isset($row['content_type']) ? (string) $row['content_type'] : null
            );

            $itemLimit = $row['item_limit'] ?? null;
            if ($itemLimit !== null && $itemLimit !== '') {
                $itemLimit = max(1, min(50, (int) $itemLimit));
            } else {
                $itemLimit = $isCustom ? 10 : $def['default_item_limit'];
            }

            $title = trim((string) ($row['title'] ?? ''));
            $dataMode = ($row['data_mode'] ?? self::DATA_MODE_DEFAULT) === self::DATA_MODE_MANUAL
                ? self::DATA_MODE_MANUAL
                : self::DATA_MODE_DEFAULT;

            $serviceIds = $this->normalizeUuidList($row['service_ids'] ?? []);
            $providerIds = $this->normalizeUuidList($row['provider_ids'] ?? []);
            $bannerIds = $this->normalizeUuidList($row['banner_ids'] ?? []);
            $categoryIds = $this->normalizeUuidList($row['category_ids'] ?? []);

            if ($dataMode === self::DATA_MODE_MANUAL) {
                $serviceIds = $contentType === self::CONTENT_SERVICES ? $serviceIds : [];
                $providerIds = $contentType === self::CONTENT_PROVIDERS ? $providerIds : [];
                $bannerIds = $contentType === self::CONTENT_BANNERS ? $bannerIds : [];
                $categoryIds = in_array($contentType, [self::CONTENT_CATEGORIES, self::CONTENT_SUB_CATEGORIES], true)
                    ? $categoryIds
                    : [];
            } else {
                $serviceIds = [];
                $providerIds = [];
                $bannerIds = [];
                $categoryIds = [];
            }

            $entry = [
                'key' => $key,
                'enabled' => ($def['fixed'] ?? false) ? true : (bool) ($row['enabled'] ?? false),
                'sort_order' => (int) ($row['sort_order'] ?? $index),
                'title' => $title !== '' ? $title : null,
                'item_limit' => $itemLimit,
                'data_mode' => $dataMode,
                'service_ids' => $serviceIds,
                'provider_ids' => $providerIds,
                'banner_ids' => $bannerIds,
                'category_ids' => $categoryIds,
            ];

            if ($isCustom) {
                $entry['is_custom'] = true;
                $entry['content_type'] = $contentType ?? self::CONTENT_SERVICES;
                $entry['label'] = $title !== '' ? $title : 'Custom section';
            }

            $normalized[] = $entry;
        }

        $this->persistJsonSetting(self::HOME_SECTIONS_KEY, $normalized);
        CustomerApiResponseCache::forgetConfigCaches();
    }

    /**
     * @return array{sections: list<array<string, mixed>>}
     */
    public function homeSectionsForApi(): array
    {
        $sections = [];
        foreach ($this->mergeHomeSectionRows() as $row) {
            $sections[] = [
                'key' => $row['key'],
                'enabled' => $row['enabled'],
                'sort_order' => $row['sort_order'],
                'title' => $row['title'],
                'item_limit' => $row['item_limit'],
                'data_mode' => $row['data_mode'],
                'content_type' => $row['content_type'],
                'is_custom' => $row['is_custom'] ?? false,
                'service_ids' => $row['data_mode'] === self::DATA_MODE_MANUAL ? ($row['service_ids'] ?? []) : [],
                'provider_ids' => $row['data_mode'] === self::DATA_MODE_MANUAL ? ($row['provider_ids'] ?? []) : [],
                'banner_ids' => $row['data_mode'] === self::DATA_MODE_MANUAL ? ($row['banner_ids'] ?? []) : [],
                'category_ids' => $row['data_mode'] === self::DATA_MODE_MANUAL ? ($row['category_ids'] ?? []) : [],
            ];
        }

        return ['sections' => $sections];
    }

    /**
     * @return array<string, array<string, array{light: ?string, dark: ?string}>>
     */
    public function getIcons(): array
    {
        $raw = $this->getJsonSetting(self::ICONS_KEY) ?: ['customer' => [], 'provider' => []];
        $out = ['customer' => [], 'provider' => []];

        foreach (['customer', 'provider'] as $app) {
            foreach ($raw[$app] ?? [] as $key => $value) {
                $out[$app][$key] = self::normalizeIconEntry($value);
            }
        }

        $legacy = $out['provider']['provider_app_logo'] ?? null;
        if ($legacy && ($legacy['light'] || $legacy['dark'])) {
            foreach (['provider_app_login_logo', 'provider_app_home_logo'] as $logoKey) {
                $current = $out['provider'][$logoKey] ?? ['light' => null, 'dark' => null];
                if (! $current['light'] && ! $current['dark']) {
                    $out['provider'][$logoKey] = $legacy;
                }
            }
        }

        return $out;
    }

    /**
     * @param array<string, array<string, array{light: ?string, dark: ?string}|string|null>> $icons
     */
    public function saveIcons(array $icons): void
    {
        $normalized = ['customer' => [], 'provider' => []];

        foreach (['customer', 'provider'] as $app) {
            foreach ($icons[$app] ?? [] as $key => $value) {
                $normalized[$app][$key] = self::normalizeIconEntry($value);
            }
        }

        $this->persistJsonSetting(self::ICONS_KEY, $normalized);
        CustomerApiResponseCache::forgetConfigCaches();
    }

    /**
     * @return array{light: ?string, dark: ?string}
     */
    public static function normalizeIconEntry(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            return ['light' => $value, 'dark' => $value];
        }

        if (!is_array($value)) {
            return ['light' => null, 'dark' => null];
        }

        $light = $value['light'] ?? null;
        $dark = $value['dark'] ?? null;

        return [
            'light' => is_string($light) && $light !== '' ? $light : null,
            'dark' => is_string($dark) && $dark !== '' ? $dark : null,
        ];
    }

    public function iconFilenameFor(string $app, string $key, string $variant): ?string
    {
        $icons = $this->getIcons();

        return $icons[$app][$key][$variant] ?? null;
    }

    /**
     * Bundled asset filename used when no custom upload exists (for admin hints).
     */
    public static function defaultIconAssetName(string $app, string $key): string
    {
        $map = $app === 'provider' ? self::providerDefaultIconAssets() : self::customerDefaultIconAssets();

        return $map[$key] ?? 'icon.png';
    }

    /**
     * @return array<string, string>
     */
    public static function customerDefaultIconAssets(): array
    {
        return [
            'customer_app_logo' => 'logo.png',
            'profile' => 'profile_icon.png',
            'inbox' => 'chat_image.png',
            'language' => 'select_language.png',
            'settings' => 'settings.png',
            'bookings' => 'bookings_icon.png',
            'vouchers' => 'voucher_icon.png',
            'my_favorite' => 'my_favorite.png',
            'custom_post' => 'custom_post_icon.png',
            'wallet' => 'wallet_menu.png',
            'loyalty_point' => 'my_point.png',
            'refer_and_earn' => 'share_icon.png',
            'service_area' => 'area_menu_icon.png',
            'help_support' => 'help_icon.png',
            'become_provider' => 'provider_image.png',
            'logout' => 'logout.png',
            'about_us' => 'about_us.png',
            'terms' => 'terms_icon.png',
            'privacy_policy' => 'privacy_policy_icon.png',
            'cancellation_policy' => 'cancellation_policy.png',
            'refund_policy' => 'refund_policy.png',
            'other_pages' => 'others_page.png',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function providerDefaultIconAssets(): array
    {
        return [
            'provider_app_login_logo' => 'logo.png',
            'provider_app_home_logo' => 'appbar_logo.png',
            'provider_app_logo' => 'logo.png',
            'profile' => 'profile_icon.png',
            'subscription' => 'my_subscription.png',
            'services' => 'services.png',
            'post' => 'create_post_icon_1.png',
            'bottom_dashboard' => 'dashboard.png',
            'bottom_requests' => 'requests.png',
            'bottom_more' => 'moree.png',
            'chat' => 'chat_image.png',
            'settings' => 'settings.png',
            'payment_info' => 'payment_info_icon.png',
            'notification_channel' => 'notification_setup.png',
            'withdraw_list' => 'transaction.png',
            'reports' => 'report_overview2.png',
            'advertisements' => 'menu_advertisement.png',
            'business_plan' => 'business_plan_icon.png',
            'help_support' => 'help_icon.png',
            'about_us' => 'about_us.png',
            'terms' => 'termsCondition.png',
            'privacy_policy' => 'privacyPolicy.png',
            'cancellation_policy' => 'cancellation.png',
            'refund_policy' => 'refund.png',
            'other_pages' => 'no-results.png',
            'logout' => 'logout.png',
        ];
    }

    public function iconFullPath(?string $filename): ?string
    {
        if (!$filename || $filename === 'def.png') {
            return null;
        }

        $imagePath = 'mobile-app/'.$filename;

        try {
            if (Storage::disk('s3')->exists($imagePath)) {
                return Storage::disk('s3')->url($imagePath);
            }
        } catch (\Throwable) {
            //
        }

        if (Storage::disk('public')->exists($imagePath)) {
            return mobile_app_icon_public_url($imagePath);
        }

        return null;
    }

    /**
     * Relative or absolute URL for mobile apps — paths like /storage/mobile-app/file.webp
     * so the app can prefix its own API base URL (fixes local / emulator host mismatches).
     */
    public function iconPathForApi(?string $filename): ?string
    {
        if (!$filename || $filename === 'def.png') {
            return null;
        }

        $imagePath = 'mobile-app/'.$filename;

        try {
            if (Storage::disk('s3')->exists($imagePath)) {
                return Storage::disk('s3')->url($imagePath);
            }
        } catch (\Throwable) {
            //
        }

        if (Storage::disk('public')->exists($imagePath)) {
            return '/storage/'.$imagePath;
        }

        return null;
    }

    /**
     * @return array{customer: array<string, array{light: ?string, dark: ?string}>, provider: array<string, array{light: ?string, dark: ?string}>}
     */
    public function iconsForApi(): array
    {
        $icons = $this->getIcons();
        $out = ['customer' => [], 'provider' => []];

        foreach (['customer', 'provider'] as $app) {
            foreach ($icons[$app] ?? [] as $key => $variants) {
                $out[$app][$key] = [
                    'light' => $this->iconPathForApi($variants['light'] ?? null),
                    'dark' => $this->iconPathForApi($variants['dark'] ?? null),
                ];
            }
        }

        return $out;
    }

    public static function generateCustomSectionKey(): string
    {
        return 'custom_'.Str::lower(Str::random(8));
    }

    /**
     * @return list<string>
     */
    private function normalizeUuidList(mixed $ids): array
    {
        if (!is_array($ids)) {
            return [];
        }

        $out = [];
        foreach ($ids as $id) {
            $id = trim((string) $id);
            if ($id !== '' && preg_match('/^[0-9a-f-]{36}$/i', $id)) {
                $out[] = $id;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mergeHomeSectionRows(): array
    {
        $stored = collect($this->getJsonSetting(self::HOME_SECTIONS_KEY));
        $storedByKey = $stored->keyBy('key');
        $sections = [];
        $order = 0;

        foreach (self::homeSectionDefinitions() as $def) {
            $sections[] = $this->hydrateSectionRow($def, $storedByKey->get($def['key'], []), $order);
            $order++;
        }

        $builtinKeys = collect(self::homeSectionDefinitions())->pluck('key')->all();

        foreach ($stored as $row) {
            $key = (string) ($row['key'] ?? '');
            if (!self::isCustomSectionKey($key) || in_array($key, $builtinKeys, true)) {
                continue;
            }
            $sections[] = $this->hydrateCustomSectionRow($row, $order);
            $order++;
        }

        usort($sections, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        return $sections;
    }

    /**
     * @param array<string, mixed> $def
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateSectionRow(array $def, array $row, int $fallbackOrder): array
    {
        $itemLimit = $row['item_limit'] ?? $def['default_item_limit'];
        if ($itemLimit !== null) {
            $itemLimit = (int) $itemLimit;
        }

        $title = isset($row['title']) && $row['title'] !== '' ? (string) $row['title'] : null;
        $dataMode = ($row['data_mode'] ?? self::DATA_MODE_DEFAULT) === self::DATA_MODE_MANUAL
            ? self::DATA_MODE_MANUAL
            : self::DATA_MODE_DEFAULT;

        return array_merge($def, [
            'enabled' => $def['fixed'] ? true : (bool) ($row['enabled'] ?? $def['default_enabled']),
            'sort_order' => (int) ($row['sort_order'] ?? $fallbackOrder),
            'title' => $title,
            'item_limit' => $itemLimit,
            'data_mode' => $dataMode,
            'content_type' => self::sectionContentType($def['key']),
            'is_custom' => false,
            'service_ids' => $this->normalizeUuidList($row['service_ids'] ?? []),
            'provider_ids' => $this->normalizeUuidList($row['provider_ids'] ?? []),
            'banner_ids' => $this->normalizeUuidList($row['banner_ids'] ?? []),
            'category_ids' => $this->normalizeUuidList($row['category_ids'] ?? []),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateCustomSectionRow(array $row, int $fallbackOrder): array
    {
        $key = (string) ($row['key'] ?? '');
        $contentType = self::sectionContentType($key, $row['content_type'] ?? null);
        $title = isset($row['title']) && $row['title'] !== '' ? (string) $row['title'] : null;
        $label = (string) ($row['label'] ?? $title ?? 'Custom section');
        $dataMode = ($row['data_mode'] ?? self::DATA_MODE_DEFAULT) === self::DATA_MODE_MANUAL
            ? self::DATA_MODE_MANUAL
            : self::DATA_MODE_DEFAULT;

        $previewType = match ($contentType) {
            self::CONTENT_PROVIDERS => 'providers_horizontal',
            self::CONTENT_BANNERS => 'banner',
            self::CONTENT_CATEGORIES => 'categories',
            self::CONTENT_SUB_CATEGORIES => 'sub_categories',
            default => 'services_horizontal',
        };

        $typeLabel = match ($contentType) {
            self::CONTENT_PROVIDERS => 'providers',
            self::CONTENT_BANNERS => 'banners',
            self::CONTENT_CATEGORIES => 'categories',
            self::CONTENT_SUB_CATEGORIES => 'sub categories',
            default => 'services',
        };

        return [
            'key' => $key,
            'label' => $label,
            'description' => 'Custom home section with manually selected '.$typeLabel.'.',
            'default_title' => $label,
            'preview_type' => $previewType,
            'icon' => match ($contentType) {
                self::CONTENT_PROVIDERS => 'groups',
                self::CONTENT_BANNERS => 'view_carousel',
                self::CONTENT_CATEGORIES, self::CONTENT_SUB_CATEGORIES => 'category',
                default => 'view_list',
            },
            'default_enabled' => true,
            'fixed' => false,
            'default_item_limit' => 10,
            'conditional' => $contentType === self::CONTENT_PROVIDERS ? 'direct_provider_booking' : null,
            'supports_manual_data' => true,
            'content_type' => $contentType,
            'is_custom' => true,
            'enabled' => (bool) ($row['enabled'] ?? true),
            'sort_order' => (int) ($row['sort_order'] ?? $fallbackOrder),
            'title' => $title,
            'item_limit' => max(1, min(50, (int) ($row['item_limit'] ?? 10))),
            'data_mode' => $dataMode,
            'service_ids' => $this->normalizeUuidList($row['service_ids'] ?? []),
            'provider_ids' => $this->normalizeUuidList($row['provider_ids'] ?? []),
            'banner_ids' => $this->normalizeUuidList($row['banner_ids'] ?? []),
            'category_ids' => $this->normalizeUuidList($row['category_ids'] ?? []),
        ];
    }

    private function getJsonSetting(string $keyName): array
    {
        $row = BusinessSettings::query()
            ->where('key_name', $keyName)
            ->where('settings_type', self::SETTINGS_TYPE)
            ->first();

        $values = $row?->live_values;
        if (is_string($values)) {
            $decoded = json_decode($values, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($values)) {
            return $values;
        }

        return [];
    }

    private function persistJsonSetting(string $keyName, array $payload): void
    {
        BusinessSettings::query()->updateOrCreate(
            ['key_name' => $keyName, 'settings_type' => self::SETTINGS_TYPE],
            [
                'live_values' => $payload,
                'test_values' => $payload,
                'mode' => 'live',
                'is_active' => 1,
            ],
        );
    }
}
