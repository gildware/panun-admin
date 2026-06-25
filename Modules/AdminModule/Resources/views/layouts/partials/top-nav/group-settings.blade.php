@canany(['business_view', 'subscription_package_view', 'subscriber_view', 'subscription_settings_view', 'page_view', 'landing_view', 'error_logs_view', 'cron_job_view', 'login_setup_view', 'language_view', 'gallery_view', 'backup_view', 'service_view', 'category_view', 'customer_view', 'provider_view', 'lead_view', 'booking_view', 'firebase_view', 'payment_method_view', 'configuration_view', 'ai_configuration_view', 'addon_view', 'addon_add', 'addon_update'])
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('settings'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        {{ translate('Settings') }} <span class="material-icons">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @canany(['business_view', 'subscription_package_view', 'subscriber_view', 'subscription_settings_view', 'page_view', 'landing_view', 'error_logs_view', 'cron_job_view'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('business_setup')])
            @can('business_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.business-settings.get-business-information'),
                    'label' => translate('business_Settings'),
                    'active' => request()->is('admin/business-settings/get-business-information'),
                ])
            @endcan

            @canany(['subscription_settings_view', 'subscriber_view', 'subscription_package_view'])
                @can('subscription_package_view')
                    @include('adminmodule::layouts.partials.top-nav._link', [
                        'href' => route('admin.subscription.package.list'),
                        'label' => translate('Subscription Package'),
                        'active' => request()->is('admin/subscription/package/*'),
                    ])
                @endcan
                @can('subscriber_view')
                    @include('adminmodule::layouts.partials.top-nav._link', [
                        'href' => route('admin.subscription.subscriber.list'),
                        'label' => translate('Subscriber List'),
                        'active' => request()->is('admin/subscription/subscriber/*'),
                    ])
                @endcan
                @can('subscription_settings_view')
                    @include('adminmodule::layouts.partials.top-nav._link', [
                        'href' => route('admin.subscription.settings'),
                        'label' => translate('Settings'),
                        'active' => request()->is('admin/subscription/settings'),
                    ])
                @endcan
            @endcanany

            @canany(['page_view', 'landing_view'])
                @can('page_view')
                    @include('adminmodule::layouts.partials.top-nav._link', [
                        'href' => route('admin.business-page-setup.list'),
                        'label' => translate('Business Pages'),
                        'active' => request()->is('admin/business-page-setup*'),
                    ])
                    @include('adminmodule::layouts.partials.top-nav._link', [
                        'href' => route('admin.social-media.index'),
                        'label' => translate('Social Media'),
                        'active' => request()->is('admin/social-media/*'),
                    ])
                @endcan
                @can('landing_view')
                    @include('adminmodule::layouts.partials.top-nav._link', [
                        'href' => route('admin.business-settings.get-landing-information', ['web_page' => 'text_setup']),
                        'label' => translate('landing_page_settings'),
                        'active' => request()->is('admin/business-settings/get-landing-information'),
                    ])
                @endcan
            @endcanany

            @can('error_logs_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.business-settings.seo.setting', ['page_type' => 'error_logs']),
                    'label' => translate('404 Logs'),
                    'active' => request()->is('admin/business-settings/seo-setting'),
                ])
            @endcan

            @can('cron_job_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.business-settings.cron-job.list'),
                    'label' => translate('Cron Job'),
                    'active' => request()->is('admin/business-settings/cron-job'),
                ])
            @endcan
        @endcanany

        @canany(['login_setup_view', 'language_view', 'gallery_view', 'backup_view', 'service_view', 'category_view', 'customer_view', 'provider_view', 'lead_view', 'booking_view', 'business_view'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('system_setup')])
            @can('login_setup_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.business-settings.login.setup'),
                    'label' => translate('Login Setup'),
                    'active' => request()->is('admin/business-settings/login/setup'),
                ])
            @endcan

            @can('language_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.configuration.language_setup'),
                    'label' => translate('Language Setup'),
                    'active' => request()->is('admin/configuration/language-setup') || request()->is('admin/language/translate/*'),
                ])
            @endcan

            @can('gallery_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.business-settings.get-gallery-setup'),
                    'label' => translate('Gallery'),
                    'active' => request()->is('admin/business-settings/get-gallery-setup*'),
                ])
            @endcan

            @can('backup_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.business-settings.get-database-backup'),
                    'label' => translate('Backup_Database'),
                    'active' => request()->is('admin/business-settings/get-database-backup'),
                ])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.system-maintenance.data-reset.index'),
                    'label' => translate('Reset_Operational_Data'),
                    'active' => request()->is('admin/system-maintenance/data-reset'),
                ])
            @endcan

            @canany(['business_view', 'configuration_view', 'backup_view'])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.system-logs.index'),
                    'label' => translate('System_Logs'),
                    'active' => request()->is('admin/system-logs*'),
                ])
            @endcanany

            @canany(['service_view', 'category_view', 'customer_view', 'provider_view', 'lead_view', 'booking_view', 'business_view'])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.data-transfer.index'),
                    'label' => translate('Data_Transfer'),
                    'active' => request()->is('admin/data-transfer*'),
                ])
            @endcanany
        @endcanany

        @canany(['firebase_view', 'payment_method_view', 'configuration_view', 'ai_configuration_view'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('3rd Party Setup')])
            @can('firebase_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.configuration.third-party', 'firebase-configuration'),
                    'label' => translate('Firebase'),
                    'active' => request()->is('admin/configuration/third-party/firebase-*'),
                ])
            @endcan
            @can('payment_method_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.configuration.third-party', ['webPage' => 'payment_config', 'type' => 'digital_payment']),
                    'label' => translate('Payment Methods'),
                    'active' => request()->is('admin/configuration/third-party/payment_config*') || request()->is('admin/configuration/offline*'),
                ])
            @endcan
            @can('ai_configuration_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.configuration.ai-configuration'),
                    'label' => translate('AI_Configuration'),
                    'active' => request()->is('admin/configuration/ai-configuration'),
                ])
            @endcan
            @can('configuration_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.configuration.third-party', 'map-api'),
                    'label' => translate('Other Configuration'),
                    'active' => (request()->is('admin/configuration/third-party/*') || request()->is('admin/configuration/ai-settings/*'))
                        && !request()->is('admin/configuration/third-party/firebase-*')
                        && !request()->is('admin/configuration/third-party/payment_config*'),
                ])
            @endcan
        @endcanany

        @canany(['addon_view', 'addon_add', 'addon_update'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('system_addon')])
            @canany(['addon_view', 'addon_add'])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.addon.index'),
                    'label' => translate('system_addons'),
                    'active' => Request::is('admin/addon*'),
                ])

                @if(count(config('addon_admin_routes')) > 0)
                    @foreach(config('addon_admin_routes') as $routes)
                        @foreach($routes as $route)
                            @include('adminmodule::layouts.partials.top-nav._link', [
                                'href' => $route['url'],
                                'label' => translate($route['name']),
                                'active' => Request::is($route['path']),
                            ])
                        @endforeach
                    @endforeach
                @endif
            @endcanany

            @canany(['addon_view', 'addon_update'])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.add-on-activation.index'),
                    'label' => translate('Add-on Activation'),
                    'active' => request()->is('admin/add-on-activation/index'),
                ])
            @endcanany
        @endcanany
    </div>
</div>
@endcanany
