<?php

namespace App\Support;

use Illuminate\Http\Request;

class AdminNavRegistry
{
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
            self::insightsItems(),
            self::financeItems(),
            self::marketingItems(),
            self::providerItems(),
            self::catalogItems(),
            self::customerItems(),
            self::mobileAppItems(),
            self::teamItems(),
            self::settingsItems(),
            self::addonItems(),
        );

        return $items;
    }

    /**
     * @return array{group_key: string, group: string, section: string|null, label: string, url: string, paths: array<int, string>}|null
     */
    public static function match(?Request $request = null): ?array
    {
        $request = $request ?? request();
        $best = null;
        $bestScore = -1;

        foreach (self::items() as $item) {
            if (! self::itemMatches($item, $request)) {
                continue;
            }

            $score = self::itemScore($item, $request);
            if ($score > $bestScore) {
                $best = $item;
                $bestScore = $score;
            }
        }

        return $best;
    }

    /**
     * @return array<int, array{label: string, url: string|null}>
     */
    public static function breadcrumbs(?Request $request = null): array
    {
        $request = $request ?? request();

        if ($request->is('admin/dashboard')) {
            return [
                ['label' => translate('dashboard'), 'url' => route('admin.dashboard')],
            ];
        }

        $match = self::match($request);
        if (! $match) {
            return [
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

        return $crumbs;
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
     * @return array{title: string, group_key: string, items: array<int, array{label: string, url: string, active: bool}>}|null
     */
    public static function groupSubmenu(?Request $request = null): ?array
    {
        $request = $request ?? request();
        $match = self::match($request);
        $groupKey = $match['group_key'] ?? null;

        if (! $groupKey || $groupKey === 'dashboard') {
            return null;
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
            ];
        }

        if ($items === []) {
            return null;
        }

        return [
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
        $score = 0;

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
        return [
            self::entry('dashboard', translate('dashboard'), null, translate('dashboard'), route('admin.dashboard'), ['admin/dashboard']),
        ];
    }

    private static function operationsItems(): array
    {
        $group = translate('Operations');

        return [
            self::entry('operations', $group, translate('Lead_Management'), translate('Leads'), route('admin.lead.index'), [
                'admin/lead', 'admin/lead/show*', 'admin/lead/edit*',
            ], ['admin.lead.index', 'admin.lead.show'], 'lead.index'),
            self::entry('operations', $group, translate('Lead_Management'), translate('Outbound_Enquiry'), route('admin.lead.outbound-enquiry.index'), ['admin/lead/outbound-enquiry*']),
            self::entry('operations', $group, translate('Lead_Management'), translate('Lead_Configuration'), route('admin.lead.configuration.index'), ['admin/lead/configuration*']),
            self::entry('operations', $group, translate('Voice'), translate('Voice_Calls'), route('admin.voice-call.index'), ['admin/voice-call*']),
            self::entry('operations', $group, translate('WhatsApp_and_social_media'), translate('WhatsApp'), route('admin.whatsapp.conversations.index', ['channel' => 'whatsapp', 'tab' => 'chats']), ['admin/social-inbox/whatsapp/*'], [], 'social.whatsapp'),
            self::entry('operations', $group, translate('WhatsApp_and_social_media'), translate('Message_templates'), route('admin.whatsapp.booking-templates.edit', ['channel' => 'whatsapp']), ['admin/social-inbox/*/booking-message-templates*']),
            self::entry('operations', $group, translate('WhatsApp_and_social_media'), __('whatsapp_ai.page_title'), route('admin.whatsapp.ai-settings.edit', ['channel' => 'whatsapp']), ['admin/social-inbox/*/ai-support*']),
            self::entry('operations', $group, translate('WhatsApp_Marketing'), translate('Send_Bulk_Message'), route('admin.whatsapp.marketing.bulk.create', ['channel' => 'whatsapp']), ['admin/social-inbox/*/marketing/send']),
            self::entry('operations', $group, translate('WhatsApp_Marketing'), translate('campaigns'), route('admin.whatsapp.marketing.campaigns.index', ['channel' => 'whatsapp']), ['admin/social-inbox/*/marketing/campaigns*']),
            self::entry('operations', $group, translate('WhatsApp_Marketing'), translate('Templates'), route('admin.whatsapp.marketing.templates.index', ['channel' => 'whatsapp']), ['admin/social-inbox/*/marketing/templates*']),
            self::entry('operations', $group, translate('WhatsApp_Marketing'), translate('Reports'), route('admin.whatsapp.marketing.reports.index', ['channel' => 'whatsapp']), ['admin/social-inbox/*/marketing/reports*']),
            self::entry('operations', $group, translate('booking_management'), translate('Booking_Configuration'), route('admin.booking.configuration.index'), ['admin/booking/configuration*']),
            self::entry('operations', $group, translate('booking_management'), translate('Add_New_Booking'), route('admin.booking.create'), ['admin/booking/create']),
            self::entry('operations', $group, translate('booking_management'), translate('Add_New_Bidding'), route('admin.booking.post.create'), ['admin/booking/post/create']),
            self::entry('operations', $group, translate('booking_management'), translate('Customized_Requests'), route('admin.booking.post.list', ['type' => 'all']), ['admin/booking/post', 'admin/booking/post/details*']),
            self::entry('operations', $group, translate('booking_management'), translate('verify_requests'), route('admin.booking.list.verification', ['booking_status' => 'pending', 'type' => 'pending']), ['admin/booking/list/verification*'], [], 'booking.verify'),
            self::entry('operations', $group, translate('booking_management'), translate('Booking_Requests'), route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']), [
                'admin/booking/list', 'admin/booking/details*', 'admin/booking/repeat*', 'admin/booking/rebooking*',
                'admin/booking/todays-followups*', 'admin/booking/success*',
            ], [], 'booking.requests'),
            self::entry('operations', $group, translate('booking_management'), translate('Cancelled_by_provider'), route('admin.booking.list.cancelled_by_provider', ['service_type' => 'all']), ['admin/booking/list/cancelled-by-provider*'], [], 'booking.cancelled_by_provider'),
            self::entry('operations', $group, translate('booking_management'), translate('Special_scenario_bookings'), route('admin.booking.list.special_scenarios', ['scenario' => 'all']), ['admin/booking/list/special-scenarios*']),
            self::entry('operations', $group, translate('booking_management'), translate('Booking_Review'), route('admin.booking.reviews.list'), ['admin/booking/reviews/list*']),
            self::entry('operations', $group, null, translate('Talk_With_AI'), route('admin.business-ai.index'), ['admin/business-ai*']),
            self::entry('operations', $group, translate('Messages'), translate('Staff_Conversation'), route('admin.chat.staff'), ['admin/chat/staff*'], ['admin.chat.staff'], 'chat.staff'),
            self::entry('operations', $group, translate('Messages'), translate('Support_Messages'), route('admin.chat.support'), ['admin/chat/support*'], ['admin.chat.support'], 'chat.support'),
            self::entry('operations', $group, null, translate('In_App_Call_Monitor'), route('admin.in-app-calls.index'), ['admin/in-app-calls*'], ['admin.in-app-calls.index']),
        ];
    }

    private static function insightsItems(): array
    {
        $group = translate('Insights');

        return [
            self::entry('insights', $group, translate('Reports'), translate('Business Reports'), route('admin.report.business.overview'), ['admin/report/business*']),
            self::entry('insights', $group, translate('Reports'), translate('Booking Reports'), route('admin.report.booking'), ['admin/report/booking']),
            self::entry('insights', $group, translate('Reports'), translate('Provider Reports'), route('admin.report.provider'), ['admin/report/provider']),
            self::entry('insights', $group, translate('Reports'), translate('Lead_Reports'), route('admin.lead.reports.index', ['tab' => 'inbound']), ['admin/lead/reports'], ['admin.lead.reports.index']),
            self::entry('insights', $group, translate('Reports'), translate('User_Report'), route('admin.lead.reports.user', ['user_id' => auth()->id()]), ['admin/lead/reports/user*'], ['admin.lead.reports.user']),
            self::entry('insights', $group, translate('Reports'), translate('Daily_Employee_Report'), route('admin.report.daily-employee'), ['admin/report/daily-employee*'], ['admin.report.daily-employee']),
            self::entry('insights', $group, translate('Analytics'), translate('Keyword_Search'), route('admin.analytics.search.keyword'), ['admin/analytics/search/keyword']),
            self::entry('insights', $group, translate('Analytics'), translate('Customer_Search'), route('admin.analytics.search.customer'), ['admin/analytics/search/customer']),
        ];
    }

    private static function financeItems(): array
    {
        $group = translate('Finance');

        return [
            self::entry('finance', $group, null, translate('All Transactions'), route('admin.transaction.list', ['trx_type' => 'all']), ['admin/transaction/list*']),
            self::entry('finance', $group, null, translate('Razorpay_webhook_logs'), route('admin.transaction.razorpay_webhooks.index'), ['admin/transaction/razorpay-webhooks*']),
            self::entry('finance', $group, null, translate('Ledger'), route('admin.ledger.index'), ['admin/ledger*']),
            self::entry('finance', $group, null, translate('Pending_provider_balances'), route('admin.transaction.pending_provider_balances.index'), ['admin/transaction/pending-provider-balances*']),
            self::entry('finance', $group, null, translate('Transaction Reports'), route('admin.report.transaction', ['transaction_type' => 'all']), ['admin/report/transaction*']),
        ];
    }

    private static function marketingItems(): array
    {
        $group = translate('Marketing');

        return [
            self::entry('marketing', $group, translate('discounts'), translate('discount_list'), route('admin.discount.list'), ['admin/discount/list']),
            self::entry('marketing', $group, translate('discounts'), translate('add_new_discount'), route('admin.discount.create'), ['admin/discount/create']),
            self::entry('marketing', $group, translate('coupons'), translate('coupon_list'), route('admin.coupon.list'), ['admin/coupon/list']),
            self::entry('marketing', $group, translate('coupons'), translate('add_new_coupon'), route('admin.coupon.create'), ['admin/coupon/create']),
            self::entry('marketing', $group, translate('Wallet Bonus'), translate('bonus_list'), route('admin.bonus.list'), ['admin/bonus/list']),
            self::entry('marketing', $group, translate('Wallet Bonus'), translate('add_new_bonus'), route('admin.bonus.create'), ['admin/bonus/create']),
            self::entry('marketing', $group, translate('campaigns'), translate('campaign_list'), route('admin.campaign.list'), ['admin/campaign/list']),
            self::entry('marketing', $group, translate('campaigns'), translate('add_new_campaign'), route('admin.campaign.create'), ['admin/campaign/create']),
            self::entry('marketing', $group, translate('advertisements'), translate('Ads List'), route('admin.advertisements.ads-list', ['status' => 'all']), ['admin/advertisements/ads-list*']),
            self::entry('marketing', $group, translate('advertisements'), translate('New Ads Request'), route('admin.advertisements.new-ads-request', ['status' => 'new']), ['admin/advertisements/new-ads-request*']),
            self::entry('marketing', $group, null, translate('promotional_banners'), route('admin.banner.create'), ['admin/banner/*']),
            self::entry('marketing', $group, translate('notification_management'), translate('Send Notifications'), route('admin.push-notification.create'), ['admin/push-notification/*']),
            self::entry('marketing', $group, translate('notification_management'), translate('Push Notification'), route('admin.configuration.get-notification-setting', ['type' => 'customers']), ['admin/configuration/get-notification-setting*']),
            self::entry('marketing', $group, translate('notification_management'), translate('Notification Channel'), route('admin.business-settings.notification-channel', ['notification_type' => 'user']), ['admin/business-settings/notification-channel*']),
        ];
    }

    private static function providerItems(): array
    {
        $group = translate('Providers');

        return [
            self::entry('providers', $group, translate('provider_management'), translate('Onboarding_Request'), route('admin.provider.onboarding_request', ['status' => 'onboarding']), ['admin/provider/onboarding*']),
            self::entry('providers', $group, translate('provider_management'), translate('Work_Showcase_Approvals'), route('admin.provider.showcase_approval', ['status' => 'pending']), ['admin/provider/showcase-approval*']),
            self::entry('providers', $group, translate('provider_management'), translate('Profile_Update_Requests'), route('admin.provider.profile_change_request', ['status' => 'pending']), ['admin/provider/profile-change*']),
            self::entry('providers', $group, translate('providers'), translate('Provider_List'), route('admin.provider.list', ['status' => 'all']), [
                'admin/provider/list', 'admin/provider/details*', 'admin/provider/edit*', 'admin/provider/collect-cash*',
            ]),
            self::entry('providers', $group, translate('providers'), translate('Add_New_Provider'), route('admin.provider.create'), ['admin/provider/create']),
            self::entry('providers', $group, null, translate('Feedback_Configuration'), route('admin.provider.feedback-tags.index'), ['admin/provider/feedback-tags*']),
            self::entry('providers', $group, translate('Withdraws'), translate('Withdraw Requests'), route('admin.withdraw.request.list', ['status' => 'all']), ['admin/withdraw/request*']),
            self::entry('providers', $group, translate('Withdraws'), translate('Withdraw method setup'), route('admin.withdraw.method.list'), ['admin/withdraw/method*']),
        ];
    }

    private static function catalogItems(): array
    {
        $group = translate('Catalog');

        return [
            self::entry('catalog', $group, null, translate('Service Zones Setup'), route('admin.zone.create'), ['admin/zone/*']),
            self::entry('catalog', $group, translate('Categories'), translate('View_Catalog'), route('admin.catalog.view'), ['admin/catalog/view*']),
            self::entry('catalog', $group, translate('Categories'), translate('Category Setup'), route('admin.category.create'), ['admin/category/*']),
            self::entry('catalog', $group, translate('Categories'), translate('Sub Category Setup'), route('admin.sub-category.create'), ['admin/sub-category/*']),
            self::entry('catalog', $group, translate('services'), translate('service_list'), route('admin.service.index'), ['admin/service/list*', 'admin/service/edit*', 'admin/service/details*']),
            self::entry('catalog', $group, translate('services'), translate('add_new_service'), route('admin.service.create'), ['admin/service/create']),
            self::entry('catalog', $group, translate('services'), translate('New Service Requests'), route('admin.service.request.list'), ['admin/service/request/list*']),
        ];
    }

    private static function customerItems(): array
    {
        $group = translate('Customers');

        return [
            self::entry('customers', $group, translate('customer_management'), translate('customer_list'), route('admin.customer.index'), ['admin/customer/list', 'admin/customer/detail*', 'admin/customer/edit/*']),
            self::entry('customers', $group, translate('customer_management'), translate('add_new_customer'), route('admin.customer.create'), ['admin/customer/create']),
            self::entry('customers', $group, null, translate('Customer_Cart'), route('admin.customer-cart.index'), ['admin/customer-cart*']),
            self::entry('customers', $group, translate('customer_wallet'), translate('Add Fund to Wallet'), route('admin.customer.wallet.add-fund'), ['admin/customer/wallet/add-fund']),
            self::entry('customers', $group, translate('customer_wallet'), translate('Wallet Transactions'), route('admin.customer.wallet.report'), ['admin/customer/wallet/report']),
            self::entry('customers', $group, translate('loyalty_point'), translate('Loyalty Points Transactions'), route('admin.customer.loyalty-point.report'), ['admin/customer/loyalty-point/report']),
            self::entry('customers', $group, null, translate('Subscribed Newsletter'), route('admin.customer.newsletter.index'), ['admin/customer/newsletter/*']),
        ];
    }

    private static function mobileAppItems(): array
    {
        $group = translate('Mobile App');

        return [
            self::entry('mobile_app', $group, null, translate('AI'), route('admin.mobile-app-management.ai'), ['admin/mobile-app-management/ai*']),
            self::entry('mobile_app', $group, null, translate('App_Features'), route('admin.mobile-app-management.settings'), ['admin/mobile-app-management/settings*']),
            self::entry('mobile_app', $group, null, translate('Home_Page'), route('admin.mobile-app-management.home-page'), ['admin/mobile-app-management/home-page*']),
            self::entry('mobile_app', $group, null, translate('Icons_and_images'), route('admin.mobile-app-management.icons'), ['admin/mobile-app-management/icons*']),
        ];
    }

    private static function teamItems(): array
    {
        $group = translate('Team');

        return [
            self::entry('team', $group, null, translate('Employee Role Setup'), route('admin.role.index'), ['admin/role/*']),
            self::entry('team', $group, null, translate('employee_list'), route('admin.employee.index'), ['admin/employee/list', 'admin/employee/edit/*']),
            self::entry('team', $group, null, translate('add_new_employee'), route('admin.employee.create'), ['admin/employee/create']),
        ];
    }

    private static function settingsItems(): array
    {
        $group = translate('Settings');

        return [
            self::entry('settings', $group, translate('business_setup'), translate('business_Settings'), route('admin.business-settings.get-business-information'), ['admin/business-settings/get-business-information']),
            self::entry('settings', $group, translate('business_setup'), translate('Subscription Package'), route('admin.subscription.package.list'), ['admin/subscription/package/*']),
            self::entry('settings', $group, translate('business_setup'), translate('Subscriber List'), route('admin.subscription.subscriber.list'), ['admin/subscription/subscriber/*']),
            self::entry('settings', $group, translate('business_setup'), translate('Settings'), route('admin.subscription.settings'), ['admin/subscription/settings']),
            self::entry('settings', $group, translate('business_setup'), translate('Business Pages'), route('admin.business-page-setup.list'), ['admin/business-page-setup*']),
            self::entry('settings', $group, translate('business_setup'), translate('Social Media'), route('admin.social-media.index'), ['admin/social-media/*']),
            self::entry('settings', $group, translate('business_setup'), translate('landing_page_settings'), route('admin.business-settings.get-landing-information', ['web_page' => 'text_setup']), ['admin/business-settings/get-landing-information*']),
            self::entry('settings', $group, translate('business_setup'), translate('404 Logs'), route('admin.business-settings.seo.setting', ['page_type' => 'error_logs']), ['admin/business-settings/seo-setting*']),
            self::entry('settings', $group, translate('business_setup'), translate('Cron Job'), route('admin.business-settings.cron-job.list'), ['admin/business-settings/cron-job*']),
            self::entry('settings', $group, translate('system_setup'), translate('Login Setup'), route('admin.business-settings.login.setup'), ['admin/business-settings/login/setup*']),
            self::entry('settings', $group, translate('system_setup'), translate('Language Setup'), route('admin.configuration.language_setup'), ['admin/configuration/language-setup', 'admin/language/translate/*']),
            self::entry('settings', $group, translate('system_setup'), translate('Gallery'), route('admin.business-settings.get-gallery-setup'), ['admin/business-settings/get-gallery-setup*']),
            self::entry('settings', $group, translate('system_setup'), translate('Backup_Database'), route('admin.business-settings.get-database-backup'), ['admin/business-settings/get-database-backup']),
            self::entry('settings', $group, translate('system_setup'), translate('Reset_Operational_Data'), route('admin.system-maintenance.data-reset.index'), ['admin/system-maintenance/data-reset*']),
            self::entry('settings', $group, translate('system_setup'), translate('System_Logs'), route('admin.system-logs.index'), ['admin/system-logs*']),
            self::entry('settings', $group, translate('system_setup'), translate('Data_Transfer'), route('admin.data-transfer.index'), ['admin/data-transfer*']),
            self::entry('settings', $group, translate('3rd Party Setup'), translate('Firebase'), route('admin.configuration.third-party', 'firebase-configuration'), ['admin/configuration/third-party/firebase-*']),
            self::entry('settings', $group, translate('3rd Party Setup'), translate('Payment Methods'), route('admin.configuration.third-party', ['webPage' => 'payment_config', 'type' => 'digital_payment']), ['admin/configuration/third-party/payment_config*', 'admin/configuration/offline*']),
            self::entry('settings', $group, translate('3rd Party Setup'), translate('AI_Configuration'), route('admin.configuration.ai-configuration'), ['admin/configuration/ai-configuration']),
            self::entry('settings', $group, translate('3rd Party Setup'), translate('Other Configuration'), route('admin.configuration.third-party', 'map-api'), [
                'admin/configuration/third-party/*',
                'admin/configuration/ai-settings/*',
            ]),
            self::entry('settings', $group, translate('system_addon'), translate('system_addons'), route('admin.addon.index'), ['admin/addon*']),
            self::entry('settings', $group, translate('system_addon'), translate('Add-on Activation'), route('admin.add-on-activation.index'), ['admin/add-on-activation/index']),
            self::entry('settings', $group, null, translate('profile'), route('admin.profile_update'), ['admin/profile-update*']),
        ];
    }

    private static function addonItems(): array
    {
        $items = [];
        $group = translate('Settings');

        foreach (config('addon_admin_routes', []) as $routes) {
            foreach ($routes as $route) {
                $items[] = self::entry(
                    'settings',
                    $group,
                    translate('system_addon'),
                    translate($route['name']),
                    $route['url'],
                    [$route['path']]
                );
            }
        }

        return $items;
    }
}
