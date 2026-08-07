<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Reports module — analytics insights and operational reports.
 */
final class AdminReportsRegistry
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
            self::section('insights', translate('Insights'), 'insights', [
                self::item(translate('Keyword_Search'), route('admin.analytics.search.keyword'), [
                    'admin/analytics/search/keyword',
                ], ['admin.analytics.search.keyword'], 'analytics_view'),
                self::item(translate('Customer_Search'), route('admin.analytics.search.customer'), [
                    'admin/analytics/search/customer',
                ], ['admin.analytics.search.customer'], 'analytics_view'),
            ]),
            self::section('business_reports', translate('Business Reports'), 'assessment', [
                self::item(translate('Booking Reports'), route('admin.report.booking'), [
                    'admin/report/booking',
                ], ['admin.report.booking'], 'report_view'),
                self::item(translate('Business Reports'), route('admin.report.business.overview'), [
                    'admin/report/business*',
                ], ['admin.report.business.overview'], 'report_view'),
                self::item(translate('Provider Reports'), route('admin.report.provider'), [
                    'admin/report/provider',
                ], ['admin.report.provider'], 'report_view'),
            ]),
            self::section('lead_reports', translate('Lead_Reports'), 'leaderboard', [
                self::item(translate('Lead_Reports'), route('admin.lead.reports.index'), [
                    'admin/lead/reports',
                ], ['admin.lead.reports.index'], 'lead_report_view'),
                self::item(translate('Inbound_Lead_Reports'), route('admin.lead.reports.inbound'), [
                    'admin/lead/reports/inbound*',
                ], ['admin.lead.reports.inbound'], 'lead_report_view'),
                self::item(translate('Outbound_Lead_Reports'), route('admin.lead.reports.outbound'), [
                    'admin/lead/reports/outbound*',
                ], ['admin.lead.reports.outbound'], 'lead_report_view'),
            ]),
            self::section('customer_reports', translate('Customers'), 'groups', [
                self::item(translate('Referral_Report'), route('admin.customer.referral-earning.report'), [
                    'admin/customer/referral-earning/report',
                ], ['admin.customer.referral-earning.report'], 'referral_earning_view'),
                self::item(translate('Welcome_Bonus_Report'), route('admin.customer.welcome-bonus.report'), [
                    'admin/customer/welcome-bonus/report',
                ], ['admin.customer.welcome-bonus.report'], 'welcome_bonus_view'),
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

        if ($request->routeIs('admin.reports.index')) {
            $sectionKey = $request->route('section', self::defaultSectionKey());

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

    public static function isReportsPage(?Request $request = null): bool
    {
        return self::match($request) !== null;
    }

    public static function defaultSectionKey(): string
    {
        $sections = self::visibleSections();

        return $sections[0]['key'] ?? 'insights';
    }

    public static function sectionLabel(string $key): string
    {
        foreach (self::sections() as $section) {
            if ($section['key'] === $key) {
                return $section['label'];
            }
        }

        return translate('Reports');
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
            'group_key' => 'reports',
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
