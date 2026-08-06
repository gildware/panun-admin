<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Marketing module — provider ads, app campaigns, and WhatsApp marketing.
 */
final class AdminMarketingRegistry
{
    private static ?array $cachedMatch = null;

    /**
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     icon: string,
     *     items: array<int, array{label: string, url: string, paths: array<int, string>, routes?: array<int, string>, permission?: string|array}>
     * }>
     */
    public static function sections(): array
    {
        static $sections = null;

        if ($sections !== null) {
            return $sections;
        }

        $sections = [
            self::section('provider_advertisement', translate('advertisements'), 'campaign', [
                self::item(translate('Ads List'), route('admin.advertisements.ads-list', ['status' => 'all']), [
                    'admin/advertisements/ads-list*',
                    'admin/advertisements/details*',
                    'admin/advertisements/edit*',
                    'admin/advertisements/payment-update*',
                    'admin/advertisements/dates-update*',
                    'admin/advertisements/re-submit*',
                ], ['admin.advertisements.ads-list'], 'advertisement_view'),
                self::item(translate('New Ads Request'), route('admin.advertisements.new-ads-request', ['status' => 'new']), [
                    'admin/advertisements/new-ads-request*',
                ], ['admin.advertisements.new-ads-request'], 'advertisement_view'),
                self::item(translate('Create_new_advertisements'), route('admin.advertisements.ads-create'), [
                    'admin/advertisements/ads-create*',
                ], ['admin.advertisements.ads-create'], 'advertisement_add'),
            ]),
            self::section('app_campaigns', translate('campaigns'), 'phone_iphone', [
                self::item(translate('campaign_list'), route('admin.campaign.list'), [
                    'admin/campaign/list*',
                    'admin/campaign/edit*',
                ], ['admin.campaign.list'], 'campaign_view'),
                self::item(translate('add_new_campaign'), route('admin.campaign.create'), [
                    'admin/campaign/create*',
                ], ['admin.campaign.create'], 'campaign_add'),
            ]),
            self::section('whatsapp_marketing', translate('WhatsApp_Marketing'), 'forum', [
                self::item(translate('Send_Bulk_Message'), route('admin.whatsapp.marketing.bulk.create', ['channel' => 'whatsapp']), [
                    'admin/social-inbox/*/marketing/send*',
                ], ['admin.whatsapp.marketing.bulk.create'], 'whatsapp_marketing_bulk_view'),
                self::item(translate('campaigns'), route('admin.whatsapp.marketing.campaigns.index', ['channel' => 'whatsapp']), [
                    'admin/social-inbox/*/marketing/campaigns*',
                ], ['admin.whatsapp.marketing.campaigns.index'], 'whatsapp_marketing_campaign_view'),
                self::item(translate('Templates'), route('admin.whatsapp.marketing.templates.index', ['channel' => 'whatsapp']), [
                    'admin/social-inbox/*/marketing/templates*',
                ], ['admin.whatsapp.marketing.templates.index'], 'whatsapp_marketing_template_view'),
                self::item(translate('Reports'), route('admin.whatsapp.marketing.reports.index', ['channel' => 'whatsapp']), [
                    'admin/social-inbox/*/marketing/reports*',
                ], ['admin.whatsapp.marketing.reports.index'], 'whatsapp_marketing_report_view'),
            ]),
        ];

        return $sections;
    }

    /**
     * @return array{section_key: string, section_label: string, item: array}|null
     */
    public static function match(?Request $request = null): ?array
    {
        if (self::$cachedMatch !== null) {
            return self::$cachedMatch;
        }

        $request = $request ?? request();

        if ($request->routeIs('admin.marketing.index')) {
            $sectionKey = $request->route('section', 'provider_advertisement');

            return self::$cachedMatch = [
                'section_key' => $sectionKey,
                'section_label' => self::sectionLabel($sectionKey),
                'item' => null,
            ];
        }

        $best = null;
        $bestScore = -1;

        foreach (self::sections() as $section) {
            foreach ($section['items'] as $item) {
                if (! self::itemAllowed($item)) {
                    continue;
                }

                $score = self::scoreItem($item, $request);
                if ($score < 0) {
                    continue;
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = [
                        'section_key' => $section['key'],
                        'section_label' => $section['label'],
                        'item' => $item,
                    ];
                }
            }
        }

        return self::$cachedMatch = $best;
    }

    public static function isMarketingPage(?Request $request = null): bool
    {
        return self::match($request) !== null;
    }

    public static function sectionLabel(string $key): string
    {
        foreach (self::sections() as $section) {
            if ($section['key'] === $key) {
                return $section['label'];
            }
        }

        return translate('Marketing');
    }

    /**
     * @return array<int, array{key: string, label: string, icon: string, items: array}>
     */
    public static function visibleSections(): array
    {
        return array_values(array_filter(array_map(static function (array $section) {
            $items = array_values(array_filter($section['items'], [self::class, 'itemAllowed']));

            if ($items === []) {
                return null;
            }

            $section['items'] = $items;

            return $section;
        }, self::sections())));
    }

    public static function itemAllowed(array $item): bool
    {
        $permission = $item['permission'] ?? null;

        if ($permission === null) {
            return true;
        }

        if (is_array($permission)) {
            return Gate::any($permission);
        }

        return Gate::check($permission);
    }

    public static function itemIsActive(array $item, ?Request $request = null): bool
    {
        $request = $request ?? request();

        foreach ($item['routes'] ?? [] as $routeName) {
            if ($request->routeIs($routeName)) {
                return true;
            }
        }

        foreach ($item['paths'] as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{title: string, group_key: string, items: array<int, array{label: string, url: string, active: bool, count: int}>}|null
     */
    public static function groupSubmenu(?Request $request = null): ?array
    {
        $request = $request ?? request();
        $match = self::match($request);

        if (! $match) {
            return null;
        }

        $section = collect(self::visibleSections())->firstWhere('key', $match['section_key']);

        if (! $section) {
            return null;
        }

        $items = [];

        foreach ($section['items'] as $item) {
            $items[] = [
                'label' => $item['label'],
                'url' => $item['url'],
                'active' => self::itemIsActive($item, $request),
                'count' => 0,
            ];
        }

        if ($items === []) {
            return null;
        }

        return [
            'title' => $section['label'],
            'group_key' => 'marketing',
            'items' => $items,
        ];
    }

    private static function section(string $key, string $label, string $icon, array $items): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'items' => $items,
        ];
    }

    /**
     * @param  string|array<int, string>|null  $permission
     */
    private static function item(string $label, string $url, array $paths, array $routes = [], string|array|null $permission = null): array
    {
        return [
            'label' => $label,
            'url' => $url,
            'paths' => $paths,
            'routes' => $routes,
            'permission' => $permission,
        ];
    }

    private static function scoreItem(array $item, Request $request): int
    {
        $score = -1;

        foreach ($item['paths'] as $pattern) {
            if ($request->is($pattern)) {
                $score = max($score, strlen(str_replace('*', '', $pattern)) * 10 + substr_count($pattern, '/'));
            }
        }

        foreach ($item['routes'] ?? [] as $routeName) {
            if ($request->routeIs($routeName)) {
                $score = max($score, 500 + strlen($routeName));
            }
        }

        return $score;
    }
}
