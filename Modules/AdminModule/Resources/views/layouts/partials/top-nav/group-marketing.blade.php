@canany(['discount_view', 'discount_add', 'coupon_view', 'coupon_add', 'bonus_view', 'bonus_add', 'campaign_view', 'campaign_add', 'advertisement_view', 'advertisement_add', 'banner_add', 'banner_view', 'push_notification_view', 'push_notification_add', 'notification_message_view', 'notification_message_add', 'notification_message_update', 'notification_channel_view', 'notification_channel_add'])
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('marketing'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        {{ translate('Marketing') }} <span class="material-icons">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @canany(['discount_view', 'discount_add'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('discounts')])
            @can('discount_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.discount.list'),
                    'label' => translate('discount_list'),
                    'active' => request()->is('admin/discount/list'),
                ])
            @endcan
            @can('discount_add')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.discount.create'),
                    'label' => translate('add_new_discount'),
                    'active' => request()->is('admin/discount/create'),
                ])
            @endcan
        @endcanany

        @canany(['coupon_view', 'coupon_add'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('coupons')])
            @can('coupon_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.coupon.list'),
                    'label' => translate('coupon_list'),
                    'active' => request()->is('admin/coupon/list'),
                ])
            @endcan
            @can('coupon_add')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.coupon.create'),
                    'label' => translate('add_new_coupon'),
                    'active' => request()->is('admin/coupon/create'),
                ])
            @endcan
        @endcanany

        @canany(['bonus_view', 'bonus_add'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('Wallet Bonus')])
            @can('bonus_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.bonus.list'),
                    'label' => translate('bonus_list'),
                    'active' => request()->is('admin/bonus/list'),
                ])
            @endcan
            @can('bonus_add')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.bonus.create'),
                    'label' => translate('add_new_bonus'),
                    'active' => request()->is('admin/bonus/create'),
                ])
            @endcan
        @endcanany

        @canany(['campaign_view', 'campaign_add'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('campaigns')])
            @can('campaign_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.campaign.list'),
                    'label' => translate('campaign_list'),
                    'active' => request()->is('admin/campaign/list'),
                ])
            @endcan
            @can('campaign_add')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.campaign.create'),
                    'label' => translate('add_new_campaign'),
                    'active' => request()->is('admin/campaign/create'),
                ])
            @endcan
        @endcanany

        @canany(['advertisement_view', 'advertisement_add'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('advertisements')])
            @can('advertisement_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.advertisements.ads-list', ['status' => 'all']),
                    'label' => translate('Ads List'),
                    'active' => request()->is('admin/advertisements/ads-list'),
                ])
            @endcan
            @can('advertisement_add')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.advertisements.new-ads-request', ['status' => 'new']),
                    'label' => translate('New Ads Request'),
                    'active' => request()->is('admin/advertisements/new-ads-request'),
                ])
            @endcan
        @endcanany

        @canany(['banner_add', 'banner_view'])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.banner.create'),
                'label' => translate('promotional_banners'),
                'active' => request()->is('admin/banner/*'),
            ])
        @endcanany

        @canany(['push_notification_view', 'push_notification_add', 'notification_message_view', 'notification_message_add', 'notification_message_update', 'notification_channel_view', 'notification_channel_add'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('notification_management')])
            @canany(['push_notification_add', 'push_notification_view'])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.push-notification.create'),
                    'label' => translate('Send Notifications'),
                    'active' => request()->is('admin/push-notification/*'),
                ])
            @endcanany
            @canany(['notification_message_view', 'notification_message_add', 'notification_message_update'])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.configuration.get-notification-setting', ['type' => 'customers']),
                    'label' => translate('Push Notification'),
                    'active' => request()->is('admin/configuration/get-notification-setting'),
                ])
            @endcanany
            @canany(['notification_channel_view', 'notification_channel_add'])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.business-settings.notification-channel', ['notification_type' => 'user']),
                    'label' => translate('Notification Channel'),
                    'active' => request()->is('admin/business-settings/notification-channel'),
                ])
            @endcanany
        @endcanany
    </div>
</div>
@endcanany
