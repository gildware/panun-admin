<?php

namespace App\Support;

use Illuminate\Http\Request;
use Modules\BookingModule\Entities\AppCustomRequest;

class AdminNavRegistry
{
    private static ?array $cachedMatch = null;

    private static ?array $cachedBreadcrumbs = null;

    private static ?array $cachedGroupSubmenu = null;
    /**
     * @return array<int, array{
     *     group_key: string,
     *     group: string,
     *     section: string|null,
     *     label: string,
     *     url: string,
     *     paths: array<int, string>,
     *     routes?: array<int, string>
     * }>
     */
    public static function items(): array
    {
        static $items = null;

        if ($items !== null) {
            return $items;
        }

        $items = array_merge(
            self::dashboardItems(),
            self::operationsItems(),
            self::leadsItems(),
            self::bookingsItems(),
            self::taskBoardItems(),
            self::progressItems(),
            self::processGuidesItems(),
            self::reportsItems(),
            self::financeItems(),
            self::marketingItems(),
            self::providerItems(),
            self::catalogItems(),
            self::customerItems(),
            self::utilityNavItems(),
            self::settingsItems(),
        );

        return $items;
    }

    /**
     * @return array{group_key: string, group: string, section: string|null, label: string, url: string, paths: array<int, string>}|null
     */
    public static function match(?Request $request = null): ?array
    {
        $request = $request ?? request();

        if (self::$cachedMatch !== null) {
            return self::$cachedMatch;
        }

        $best = null;
        $bestScore = -1;

        foreach (self::items() as $item) {
            if (! self::itemMatches($item, $request)) {
                continue;
            }

            $score = self::itemScore($item, $request);
            if ($score < 0) {
                continue;
            }

            if ($score > $bestScore) {
                $best = $item;
                $bestScore = $score;
            }
        }

        self::$cachedMatch = $best;

        return $best;
    }

    /**
     * @return array<int, array{label: string, url: string|null}>
     */
    public static function breadcrumbs(?Request $request = null): array
    {
        if (self::$cachedBreadcrumbs !== null) {
            return self::$cachedBreadcrumbs;
        }

        $request = $request ?? request();

        if ($request->is('admin/dashboard/finance')) {
            return self::$cachedBreadcrumbs = [
                ['label' => translate('dashboard'), 'url' => route('admin.dashboard')],
                ['label' => translate('Finance'), 'url' => null],
            ];
        }

        if ($request->is('admin/dashboard') && ! $request->is('admin/dashboard/*')) {
            return self::$cachedBreadcrumbs = [
                ['label' => translate('dashboard'), 'url' => route('admin.dashboard')],
            ];
        }

        $match = self::match($request);
        if (! $match) {
            return self::$cachedBreadcrumbs = [
                ['label' => translate('dashboard'), 'url' => route('admin.dashboard')],
            ];
        }

        $crumbs = [
            ['label' => translate('dashboard'), 'url' => route('admin.dashboard')],
            ['label' => $match['group'], 'url' => self::groupLandingUrl($match['group_key'])],
        ];

        if (! empty($match['section'])) {
            $crumbs[] = [
                'label' => $match['section'],
                'url' => self::sectionLandingUrl($match['group_key'], $match['section']),
            ];
        }

        $crumbs[] = ['label' => $match['label'], 'url' => null];

        return self::$cachedBreadcrumbs = $crumbs;
    }

    public static function groupIsActive(string $groupKey, ?Request $request = null): bool
    {
        $match = self::match($request);

        return ($match['group_key'] ?? null) === $groupKey;
    }

    /**
     * @return array<int, string>
     */
    public static function defaultPinKeys(): array
    {
        return config('admin.default_pinned_nav', [
            'booking.requests',
            'booking.verify',
            'social.whatsapp',
            'lead.index',
        ]);
    }

    public static function pinKeyForUrl(string $url): ?string
    {
        $normalized = rtrim($url, '/');

        foreach (self::items() as $item) {
            if (rtrim($item['url'], '/') === $normalized) {
                return $item['pin_key'];
            }
        }

        return null;
    }

    /**
     * @return array<int, array{pin_key: string, label: string, url: string, paths: array<int, string>}>
     */
    public static function pinnableCatalog(): array
    {
        return array_values(array_map(static function (array $item) {
            return [
                'pin_key' => $item['pin_key'],
                'label' => $item['label'],
                'url' => $item['url'],
                'paths' => $item['paths'],
            ];
        }, self::items()));
    }

    /**
     * Sub-menu links for the currently active section only (hidden on dashboard).
     *
     * @return array{title: string, group_key: string, items: array<int, array{label: string, url: string, active: bool, count: int}>}|null
     */
    public static function groupSubmenu(?Request $request = null): ?array
    {
        if (self::$cachedGroupSubmenu !== null) {
            return self::$cachedGroupSubmenu;
        }

        $request = $request ?? request();
        $match = self::match($request);
        $groupKey = $match['group_key'] ?? null;

        if (! $groupKey || $groupKey === 'dashboard' || $groupKey === 'settings' || $groupKey === 'communications' || $groupKey === 'progress') {
            return self::$cachedGroupSubmenu = null;
        }

        if (admin_in_settings_module()) {
            return self::$cachedGroupSubmenu = null;
        }

        if (admin_in_marketing_module()) {
            return self::$cachedGroupSubmenu = AdminMarketingRegistry::groupSubmenu($request);
        }

        if (admin_in_reports_module()) {
            return self::$cachedGroupSubmenu = AdminReportsRegistry::groupSubmenu($request);
        }

        if (is_admin_employee() && $groupKey === 'team') {
            return self::$cachedGroupSubmenu = null;
        }

        $currentSectionKey = $match['section'] ?? '__root__';
        $items = [];

        foreach (self::items() as $item) {
            if ($item['group_key'] !== $groupKey) {
                continue;
            }

            $itemSectionKey = $item['section'] ?? '__root__';
            if ($itemSectionKey !== $currentSectionKey) {
                continue;
            }

            $items[] = [
                'label' => $item['label'],
                'url' => $item['url'],
                'active' => self::isSameNavItem($match, $item),
                'count' => AdminMenuCounts::badgeCountForUrl($item['url']),
            ];
        }

        if ($items === []) {
            return self::$cachedGroupSubmenu = null;
        }

        return self::$cachedGroupSubmenu = [
            'title' => $match['section'] ?? $match['group'],
            'group_key' => $groupKey,
            'items' => $items,
        ];
    }

    private static function isSameNavItem(?array $match, array $item): bool
    {
        if (! $match) {
            return false;
        }

        return $match['group_key'] === $item['group_key']
            && $match['label'] === $item['label']
            && rtrim($match['url'], '/') === rtrim($item['url'], '/');
    }

    private static function itemMatches(array $item, Request $request): bool
    {
        foreach ($item['paths'] as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        foreach ($item['routes'] ?? [] as $routeName) {
            if ($request->routeIs($routeName)) {
                return true;
            }
        }

        return false;
    }

    private static function itemScore(array $item, Request $request): int
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

    private static function groupLandingUrl(string $groupKey): ?string
    {
        foreach (self::items() as $item) {
            if ($item['group_key'] === $groupKey && ! empty($item['url'])) {
                return $item['url'];
            }
        }

        return null;
    }

    private static function sectionLandingUrl(string $groupKey, string $section): ?string
    {
        foreach (self::items() as $item) {
            if ($item['group_key'] === $groupKey && $item['section'] === $section && ! empty($item['url'])) {
                return $item['url'];
            }
        }

        return null;
    }

    private static function entry(
        string $groupKey,
        string $group,
        ?string $section,
        string $label,
        string $url,
        array $paths,
        array $routes = [],
        ?string $pinKey = null
    ): array {
        return [
            'group_key' => $groupKey,
            'group' => $group,
            'section' => $section,
            'label' => $label,
            'url' => $url,
            'paths' => $paths,
            'routes' => $routes,
            'pin_key' => $pinKey ?? self::makePinKey($url),
        ];
    }

    private static function makePinKey(string $url): string
    {
        $path = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');

        return str_replace('/', '.', $path) ?: 'root';
    }

    private static function dashboardItems(): array
    {
        if (is_admin_employee()) {
            return [
                self::entry('dashboard', translate('dashboard'), null, translate('dashboard'), route('admin.dashboard'), ['admin/dashboard'], ['admin.dashboard']),
            ];
        }

        return [
            self::entry('dashboard', translate('dashboard'), translate('Work'), translate('Work'), route('admin.dashboard'), ['admin/dashboard'], ['admin.dashboard']),
            self::entry('dashboard', translate('dashboard'), translate('Operations'), translate('Operations'), route('admin.dashboard.operations'), ['admin/dashboard/operations'], ['admin.dashboard.operations']),
            self::entry('dashboard', translate('dashboard'), translate('Finance'), translate('Finance'), route('admin.dashboard.finance'), ['admin/dashboard/finance'], ['admin.dashboard.finance']),
        ];
    }

    private static function operationsItems(): array
    {
        if (! is_admin_employee()) {
            return [];
        }

        $group = translate('WhatsApp_and_social_media');

        return [
            self::entry('operations', $group, null, translate('WhatsApp'), route('admin.whatsapp.conversations.index', ['channel' => 'whatsapp', 'tab' => 'chats']), ['admin/social-inbox/whatsapp/conversations*'], ['admin.whatsapp.conversations.index', 'admin.whatsapp.conversations.chat'], 'social.whatsapp'),
        ];
    }

    private static function leadsItems(): array
    {
        $group = translate('Leads');
        $appCustomRequestsUrl = route('admin.booking.app-custom-requests.index', ['status' => AppCustomRequest::STATUS_PENDING]);

        if (is_admin_employee()) {
            return [
                self::entry('leads', $group, null, translate('All_Leads'), route('admin.lead.index'), [
                    'admin/lead', 'admin/lead/show*', 'admin/lead/edit*', 'admin/lead/create*',
                ], ['admin.lead.index', 'admin.lead.show'], 'lead.index'),
                self::entry('leads', $group, null, translate('Web_Bookings'), route('admin.booking.web-bookings.index'), [
                    'admin/booking/web-bookings*',
                ], ['admin.booking.web-bookings.index', 'admin.booking.web-bookings.show']),
                self::entry('leads', $group, null, translate('Web_Provider_Requests'), route('admin.booking.web-provider-requests.index'), [
                    'admin/booking/web-provider-requests*',
                ], ['admin.booking.web-provider-requests.index', 'admin.booking.web-provider-requests.show']),
                self::entry('leads', $group, null, translate('App_Custom_Requests'), $appCustomRequestsUrl, [
                    'admin/booking/app-custom-requests*',
                ], ['admin.booking.app-custom-requests.index', 'admin.booking.app-custom-requests.show']),
                self::entry('leads', $group, null, translate('Outbound_Enquiry'), route('admin.lead.outbound-enquiry.index'), [
                    'admin/lead/outbound-enquiry*',
                ], ['admin.lead.outbound-enquiry.index']),
            ];
        }

        return [
            self::entry('leads', $group, null, translate('All_Leads'), route('admin.lead.index'), [
                'admin/lead', 'admin/lead/show*', 'admin/lead/edit*', 'admin/lead/create*',
            ], ['admin.lead.index', 'admin.lead.show'], 'lead.index'),
            self::entry('leads', $group, null, translate('Web_Bookings'), route('admin.booking.web-bookings.index'), [
                'admin/booking/web-bookings*',
            ], ['admin.booking.web-bookings.index', 'admin.booking.web-bookings.show']),
            self::entry('leads', $group, null, translate('Web_Provider_Requests'), route('admin.booking.web-provider-requests.index'), [
                'admin/booking/web-provider-requests*',
            ], ['admin.booking.web-provider-requests.index', 'admin.booking.web-provider-requests.show']),
            self::entry('leads', $group, null, translate('App_Custom_Requests'), $appCustomRequestsUrl, [
                'admin/booking/app-custom-requests*',
            ], ['admin.booking.app-custom-requests.index', 'admin.booking.app-custom-requests.show']),
            self::entry('leads', $group, null, translate('Outbound_Enquiry'), route('admin.lead.outbound-enquiry.index'), [
                'admin/lead/outbound-enquiry*',
            ], ['admin.lead.outbound-enquiry.index']),
        ];
    }

    private static function bookingsItems(): array
    {
        if (is_admin_employee()) {
            return [];
        }

        $group = translate('Bookings');

        return [
            self::entry('bookings', $group, null, translate('Booking_Requests'), route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']), [
                'admin/booking/list', 'admin/booking/details*', 'admin/booking/rebooking*',
                'admin/booking/success*', 'admin/booking/create',
            ], [], 'booking.requests'),
            self::entry('bookings', $group, null, translate('Repeat_booking'), route('admin.booking.repeat_list', ['booking_status' => 'all', 'service_type' => 'all']), [
                'admin/booking/repeat/list*', 'admin/booking/create/repeat', 'admin/booking/repeat-details*', 'admin/booking/repeat-single-details*',
            ], [], 'booking.repeat'),
            self::entry('bookings', $group, null, translate('verify_requests'), route('admin.booking.list.verification', ['booking_status' => 'pending', 'type' => 'pending']), ['admin/booking/list/verification*'], [], 'booking.verify'),
            self::entry('bookings', $group, null, translate('Booking_Review'), route('admin.booking.reviews.list'), ['admin/booking/reviews/list*']),
        ];
    }

    private static function taskBoardItems(): array
    {
        if (is_admin_employee()) {
            return [];
        }

        $group = translate('Task_Board');

        return [
            self::entry('task_board', $group, null, translate('Task_Board'), route('admin.task-board.index'), ['admin/task-board*']),
        ];
    }

    private static function progressItems(): array
    {
        $group = translate('Progress_Report');

        return [
            self::entry('progress', $group, null, translate('Progress_Report'), route('admin.my-progress', ['tab' => 'monthly']), [
                'admin/my-progress*',
            ], ['admin.my-progress']),
        ];
    }

    private static function processGuidesItems(): array
    {
        if (is_admin_employee()) {
            return [];
        }

        $group = translate('Process_Guides');

        return [
            self::entry('process_guides', $group, null, translate('Process_Guides'), route('admin.process-guides.index'), ['admin/process-guides*']),
        ];
    }

    private static function reportsItems(): array
    {
        if (is_admin_employee()) {
            return [];
        }

        $group = translate('Reports');
        $items = [
            self::entry('reports', $group, null, translate('Reports'), route('admin.reports.index'), ['admin/reports*'], ['admin.reports.index']),
        ];

        foreach (AdminReportsRegistry::sections() as $section) {
            foreach ($section['items'] as $item) {
                if (! AdminReportsRegistry::itemAllowed($item)) {
                    continue;
                }

                $items[] = self::entry(
                    'reports',
                    $group,
                    $section['label'],
                    $item['label'],
                    $item['url'],
                    $item['paths'],
                    $item['routes'] ?? []
                );
            }
        }

        return $items;
    }

    private static function financeItems(): array
    {
        $group = translate('Finance');

        if (is_admin_employee()) {
            return [
                self::entry('finance', $group, null, translate('Ledger'), route('admin.ledger.index'), ['admin/ledger*']),
                self::entry('finance', $group, null, translate('Transactions'), route('admin.transaction.list', ['trx_type' => 'all']), ['admin/transaction/list*']),
                self::entry('finance', $group, null, translate('Pending_provider_balances'), route('admin.transaction.pending_provider_balances.index'), ['admin/transaction/pending-provider-balances*']),
                self::entry('finance', $group, null, translate('Withdraw Requests'), route('admin.withdraw.request.list', ['status' => 'all']), ['admin/withdraw/request*']),
            ];
        }

        return [
            self::entry('finance', $group, null, translate('All Transactions'), route('admin.transaction.list', ['trx_type' => 'all']), ['admin/transaction/list*']),
            self::entry('finance', $group, null, translate('Ledger'), route('admin.ledger.index'), ['admin/ledger*']),
            self::entry('finance', $group, null, translate('Wallet Transactions'), route('admin.customer.wallet.report'), ['admin/customer/wallet/report']),
            self::entry('finance', $group, null, translate('Loyalty Points Transactions'), route('admin.customer.loyalty-point.report'), ['admin/customer/loyalty-point/report']),
            self::entry('finance', $group, null, translate('Pending_provider_balances'), route('admin.transaction.pending_provider_balances.index'), ['admin/transaction/pending-provider-balances*']),
            self::entry('finance', $group, null, translate('Withdraw Requests'), route('admin.withdraw.request.list', ['status' => 'all']), ['admin/withdraw/request*']),
        ];
    }

    private static function marketingItems(): array
    {
        if (is_admin_employee()) {
            return [];
        }

        $group = translate('Marketing');
        $items = [
            self::entry('marketing', $group, null, translate('Marketing'), route('admin.marketing.index'), ['admin/marketing*'], ['admin.marketing.index']),
        ];

        foreach (AdminMarketingRegistry::sections() as $section) {
            foreach ($section['items'] as $item) {
                if (! AdminMarketingRegistry::itemAllowed($item)) {
                    continue;
                }

                $items[] = self::entry(
                    'marketing',
                    $group,
                    $section['label'],
                    $item['label'],
                    $item['url'],
                    $item['paths'],
                    $item['routes'] ?? []
                );
            }
        }

        return $items;
    }

    private static function providerItems(): array
    {
        $group = translate('Providers');

        return [
            self::entry('providers', $group, null, translate('Onboarding_Request'), route('admin.provider.onboarding_request', ['status' => 'onboarding']), ['admin/provider/onboarding*']),
            self::entry('providers', $group, null, translate('Add_New_Provider'), route('admin.provider.create'), ['admin/provider/create']),
            self::entry('providers', $group, null, translate('Provider_Live_View'), route('admin.provider.live-view'), ['admin/provider/live-view*']),
            self::entry('providers', $group, null, translate('Provider_List'), route('admin.provider.list', ['status' => 'all']), [
                'admin/provider/list', 'admin/provider/details*', 'admin/provider/edit*', 'admin/provider/collect-cash*',
            ]),
            self::entry('providers', $group, null, translate('Work_Showcase_Approvals'), route('admin.provider.showcase_approval', ['status' => 'pending']), ['admin/provider/showcase-approval*']),
            self::entry('providers', $group, null, translate('Profile_Update_Requests'), route('admin.provider.profile_change_request', ['status' => 'pending']), ['admin/provider/profile-change*']),
        ];
    }

    private static function catalogItems(): array
    {
        $group = translate('Catalog');

        if (is_admin_employee()) {
            return [
                self::entry('catalog', $group, null, translate('View_Catalog'), route('admin.catalog.view'), ['admin/catalog/view*']),
                self::entry('catalog', $group, null, translate('Categories'), route('admin.category.create'), ['admin/category/*']),
                self::entry('catalog', $group, null, translate('Sub_Categories'), route('admin.sub-category.create'), ['admin/sub-category/*']),
                self::entry('catalog', $group, null, translate('services'), route('admin.service.index'), ['admin/service/list*', 'admin/service/edit*', 'admin/service/detail*', 'admin/service/create']),
                self::entry('catalog', $group, null, translate('New_Service_Requests'), route('admin.service.request.list'), ['admin/service/request/list*']),
            ];
        }

        return [
            self::entry('catalog', $group, null, translate('Service Zones Setup'), route('admin.zone.create'), ['admin/zone/*']),
            self::entry('catalog', $group, null, translate('View_Catalog'), route('admin.catalog.view'), ['admin/catalog/view*']),
            self::entry('catalog', $group, null, translate('Categories'), route('admin.category.create'), ['admin/category/*']),
            self::entry('catalog', $group, null, translate('Sub_Categories'), route('admin.sub-category.create'), ['admin/sub-category/*']),
            self::entry('catalog', $group, null, translate('services'), route('admin.service.index'), ['admin/service/list*', 'admin/service/edit*', 'admin/service/detail*']),
            self::entry('catalog', $group, null, translate('New_Service_Requests'), route('admin.service.request.list'), ['admin/service/request/list*']),
            self::entry('catalog', $group, null, translate('add_new_service'), route('admin.service.create'), ['admin/service/create']),
        ];
    }

    private static function customerItems(): array
    {
        $group = translate('Customers');

        if (is_admin_employee()) {
            return [
                self::entry('customers', $group, null, translate('customer_list'), route('admin.customer.index'), ['admin/customer/list', 'admin/customer/detail*', 'admin/customer/edit/*']),
                self::entry('customers', $group, null, translate('Customer_Cart'), route('admin.customer-cart.index'), ['admin/customer-cart*']),
                self::entry('customers', $group, null, translate('add_new_customer'), route('admin.customer.create'), ['admin/customer/create']),
            ];
        }

        return [
            self::entry('customers', $group, null, translate('customer_list'), route('admin.customer.index'), ['admin/customer/list', 'admin/customer/detail*', 'admin/customer/edit/*']),
            self::entry('customers', $group, null, translate('Customer_Cart'), route('admin.customer-cart.index'), ['admin/customer-cart*']),
            self::entry('customers', $group, null, translate('add_new_customer'), route('admin.customer.create'), ['admin/customer/create']),
        ];
    }

    private static function utilityNavItems(): array
    {
        if (is_admin_employee()) {
            return [];
        }

        $group = translate('Messages');

        return [
            self::entry('communications', $group, null, translate('WhatsApp'), route('admin.whatsapp.conversations.index', ['channel' => 'whatsapp', 'tab' => 'chats']), ['admin/social-inbox/whatsapp/conversations*'], ['admin.whatsapp.conversations.index', 'admin.whatsapp.conversations.chat'], 'social.whatsapp'),
            self::entry('communications', $group, null, translate('Staff_Conversation'), route('admin.chat.staff'), ['admin/chat/staff*'], ['admin.chat.staff'], 'chat.staff'),
            self::entry('communications', $group, null, translate('Support_Messages'), route('admin.chat.support'), ['admin/chat/support*'], ['admin.chat.support'], 'chat.support'),
        ];
    }

    private static function settingsItems(): array
    {
        if (is_admin_employee()) {
            return [];
        }

        $group = translate('Settings');
        $items = [
            self::entry('settings', $group, null, translate('Settings'), route('admin.settings.index'), ['admin/settings*'], ['admin.settings.index']),
        ];

        foreach (AdminSettingsRegistry::sections() as $section) {
            foreach ($section['items'] as $item) {
                $items[] = self::entry(
                    'settings',
                    $group,
                    $section['label'],
                    $item['label'],
                    $item['url'],
                    $item['paths'],
                    $item['routes'] ?? []
                );
            }
        }

        return $items;
    }
}
