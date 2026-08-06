<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Settings module — all configuration pages grouped by section.
 * Work nav links to admin.settings.index; individual config pages keep their URLs.
 */
final class AdminSettingsRegistry
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
            self::section('business', translate('Business'), 'storefront', [
                self::item(translate('business_Settings'), route('admin.business-settings.get-business-information'), ['admin/business-settings/get-business-information'], [], 'business_view'),
                self::item(translate('Subscription Package'), route('admin.subscription.package.list'), ['admin/subscription/package/*'], [], 'subscription_package_view'),
                self::item(translate('Subscriber List'), route('admin.subscription.subscriber.list'), ['admin/subscription/subscriber/*'], [], 'subscriber_view'),
                self::item(translate('Settings'), route('admin.subscription.settings'), ['admin/subscription/settings'], [], 'subscription_settings_view'),
                self::item(translate('Business Pages'), route('admin.business-page-setup.list'), ['admin/business-page-setup*'], [], 'page_view'),
                self::item(translate('Social Media'), route('admin.social-media.index'), ['admin/social-media/*'], [], 'page_view'),
                self::item(translate('landing_page_settings'), route('admin.business-settings.get-landing-information', ['web_page' => 'text_setup']), ['admin/business-settings/get-landing-information*'], [], 'landing_view'),
                self::item(translate('404 Logs'), route('admin.business-settings.seo.setting', ['page_type' => 'error_logs']), ['admin/business-settings/seo-setting*'], [], 'error_logs_view'),
                self::item(translate('Cron Job'), route('admin.business-settings.cron-job.list'), ['admin/business-settings/cron-job*'], [], 'cron_job_view'),
            ]),
            self::section('leads', translate('Leads'), 'tune', [
                self::item(translate('Lead_Configuration'), route('admin.lead.configuration.index'), ['admin/lead/configuration*'], ['admin.lead.configuration.index'], 'lead_configuration_view'),
            ]),
            self::section('bookings', translate('Bookings'), 'event_available', [
                self::item(translate('Booking_Configuration'), route('admin.booking.configuration.index'), ['admin/booking/configuration*'], ['admin.booking.configuration.index'], 'booking_configuration_view'),
            ]),
            self::section('whatsapp', translate('WhatsApp'), 'forum', [
                self::item(translate('Message_templates'), route('admin.whatsapp.booking-templates.edit', ['channel' => 'whatsapp']), ['admin/social-inbox/*/booking-message-templates*'], [], 'whatsapp_message_template_view'),
                self::item(__('whatsapp_ai.page_title'), route('admin.whatsapp.ai-settings.edit', ['channel' => 'whatsapp']), ['admin/social-inbox/*/ai-support*'], [], 'whatsapp_chat_view'),
                self::item(translate('Meta_CAPI_Events'), route('admin.whatsapp.meta-capi-events.index', ['channel' => 'whatsapp']), ['admin/social-inbox/*/meta-capi-events*'], [], 'whatsapp_chat_view'),
                self::item(translate('Send_Bulk_Message'), route('admin.whatsapp.marketing.bulk.create', ['channel' => 'whatsapp']), ['admin/social-inbox/*/marketing/send'], [], 'whatsapp_marketing_bulk_view'),
                self::item(translate('campaigns'), route('admin.whatsapp.marketing.campaigns.index', ['channel' => 'whatsapp']), ['admin/social-inbox/*/marketing/campaigns*'], [], 'whatsapp_marketing_campaign_view'),
                self::item(translate('Templates'), route('admin.whatsapp.marketing.templates.index', ['channel' => 'whatsapp']), ['admin/social-inbox/*/marketing/templates*'], [], 'whatsapp_marketing_template_view'),
                self::item(translate('Reports'), route('admin.whatsapp.marketing.reports.index', ['channel' => 'whatsapp']), ['admin/social-inbox/*/marketing/reports*'], [], 'whatsapp_marketing_report_view'),
            ]),
            self::section('customers', translate('Customers'), 'groups', [
                self::item(translate('Welcome_Bonus_Settings'), route('admin.customer.settings', ['web_page' => 'welcome_bonus']), ['admin/customer/settings'], [], 'welcome_bonus_view'),
                self::item(translate('Referral_Settings'), route('admin.customer.settings', ['web_page' => 'referral_earning']), ['admin/customer/settings'], [], 'referral_earning_view'),
            ]),
            self::section('providers', translate('Providers'), 'engineering', [
                self::item(translate('Feedback_Configuration'), route('admin.provider.feedback-tags.index'), ['admin/provider/feedback-tags*'], [], 'provider_feedback_config_view'),
                self::item(translate('Withdraw method setup'), route('admin.withdraw.method.list'), ['admin/withdraw/method*'], [], 'withdraw_add'),
            ]),
            self::section('catalog', translate('Catalog'), 'category', [
                self::item(translate('service_overview_defaults'), route('admin.service-overview.defaults'), ['admin/service-overview/*'], [], 'service_update'),
            ]),
            self::section('team', translate('Team'), 'badge', [
                self::item(translate('Employee Role Setup'), route('admin.role.index'), ['admin/role/*'], ['admin.role.index'], ['role_view', 'role_add']),
                self::item(translate('employee_list'), route('admin.employee.index'), ['admin/employee/list', 'admin/employee/edit/*'], ['admin.employee.index'], 'employee_view'),
                self::item(translate('add_new_employee'), route('admin.employee.create'), ['admin/employee/create'], ['admin.employee.create'], 'employee_add'),
            ]),
            self::section('marketing', translate('Marketing'), 'campaign', [
                self::item(translate('Push Notification'), route('admin.configuration.get-notification-setting', ['type' => 'customers']), ['admin/configuration/get-notification-setting*'], [], ['notification_message_view', 'notification_message_add', 'notification_message_update']),
                self::item(translate('Notification Channel'), route('admin.business-settings.notification-channel', ['notification_type' => 'user']), ['admin/business-settings/notification-channel*'], [], ['notification_channel_view', 'notification_channel_add']),
            ]),
            self::section('mobile_app', translate('Mobile App'), 'phone_iphone', [
                self::item(translate('AI'), route('admin.mobile-app-management.ai'), ['admin/mobile-app-management/ai*'], [], ['mobile_app_ai_view', 'ai_configuration_view']),
                self::item(translate('App_Features'), route('admin.mobile-app-management.settings'), ['admin/mobile-app-management/settings*'], [], 'mobile_app_home_page_view'),
                self::item(translate('Home_Page'), route('admin.mobile-app-management.home-page'), ['admin/mobile-app-management/home-page*'], [], 'mobile_app_home_page_view'),
                self::item(translate('Icons_and_images'), route('admin.mobile-app-management.icons'), ['admin/mobile-app-management/icons*'], [], 'mobile_app_icons_view'),
            ]),
            self::section('integrations', translate('Integrations'), 'hub', [
                self::item(translate('Talk_With_AI'), route('admin.business-ai.index'), ['admin/business-ai*'], ['admin.business-ai.index'], 'business_view'),
                self::item(translate('Firebase'), route('admin.configuration.third-party', 'firebase-configuration'), ['admin/configuration/third-party/firebase-*'], [], 'firebase_view'),
                self::item(translate('Payment Methods'), route('admin.configuration.third-party', ['webPage' => 'payment_config', 'type' => 'digital_payment']), ['admin/configuration/third-party/payment_config*', 'admin/configuration/offline*'], [], 'payment_method_view'),
                self::item(translate('AI_Configuration'), route('admin.configuration.ai-configuration'), ['admin/configuration/ai-configuration'], [], 'ai_configuration_view'),
                self::item(translate('Other Configuration'), route('admin.configuration.third-party', 'map-api'), [
                    'admin/configuration/third-party/map-api*',
                    'admin/configuration/third-party/sms-module*',
                    'admin/configuration/third-party/social-login*',
                    'admin/configuration/third-party/recaptcha*',
                    'admin/configuration/third-party/storage-connection*',
                    'admin/configuration/third-party/email-config*',
                    'admin/configuration/third-party/notification-settings*',
                    'admin/configuration/ai-settings/*',
                ], [], 'configuration_view'),
            ]),
            self::section('system', translate('System'), 'dns', [
                self::item(translate('Login Setup'), route('admin.business-settings.login.setup'), ['admin/business-settings/login/setup*'], [], 'login_setup_view'),
                self::item(translate('Language Setup'), route('admin.configuration.language_setup'), ['admin/configuration/language-setup', 'admin/language/translate/*'], [], 'language_view'),
                self::item(translate('Gallery'), route('admin.business-settings.get-gallery-setup'), ['admin/business-settings/get-gallery-setup*'], [], 'gallery_view'),
                self::item(translate('Backup_Database'), route('admin.business-settings.get-database-backup'), ['admin/business-settings/get-database-backup'], [], 'backup_view'),
                array_merge(
                    self::item(translate('Reset_home_cache'), route('admin.settings.home-cache'), ['admin/settings/home-cache'], ['admin.settings.home-cache']),
                    ['super_admin_only' => true]
                ),
                self::item(translate('Reset_Operational_Data'), route('admin.system-maintenance.data-reset.index'), ['admin/system-maintenance/data-reset*'], [], 'backup_view'),
                self::item(translate('System_Logs'), route('admin.system-logs.index'), ['admin/system-logs*'], ['admin.system-logs.index'], ['business_view', 'configuration_view', 'backup_view']),
                self::item(translate('Data_Transfer'), route('admin.data-transfer.index'), ['admin/data-transfer*'], ['admin.data-transfer.index'], ['service_view', 'category_view', 'customer_view', 'provider_view', 'lead_view', 'booking_view', 'business_view']),
                self::item(translate('system_addons'), route('admin.addon.index'), ['admin/addon*'], [], ['addon_view', 'addon_add']),
                self::item(translate('Add-on Activation'), route('admin.add-on-activation.index'), ['admin/add-on-activation/index'], [], ['addon_view', 'addon_update']),
                self::item(translate('profile'), route('admin.profile_update'), ['admin/profile-update*'], ['admin.profile_update'], null),
            ]),
        ];

        foreach (config('addon_admin_routes', []) as $routes) {
            foreach ($routes as $route) {
                $sections = self::appendItem($sections, 'system', self::item(
                    translate($route['name']),
                    $route['url'],
                    [$route['path']],
                    [],
                    ['addon_view', 'addon_add']
                ));
            }
        }

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

        if ($request->routeIs('admin.settings.index')) {
            $sectionKey = $request->route('section', 'business');

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

    public static function isSettingsPage(?Request $request = null): bool
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

        return translate('Settings');
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
        if (! empty($item['super_admin_only']) && ! is_super_admin()) {
            return false;
        }

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

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    private static function appendItem(array $sections, string $sectionKey, array $item): array
    {
        foreach ($sections as $index => $section) {
            if ($section['key'] !== $sectionKey) {
                continue;
            }

            $sections[$index]['items'][] = $item;
        }

        return $sections;
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
