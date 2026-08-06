@canany(['wallet_add', 'wallet_view', 'customer_view', 'customer_add', 'point_view', 'newsletter_view', 'welcome_bonus_view', 'referral_earning_view'])
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('customers'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        {{ translate('Customers') }} <span class="material-icons">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @canany(['customer_view', 'customer_add'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('customer_management')])
            @can('customer_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.customer.index'),
                    'label' => translate('customer_list'),
                    'active' => request()->is('admin/customer/list') || request()->is('admin/customer/detail*') || request()->is('admin/customer/edit/*'),
                ])
            @endcan
            @can('customer_add')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.customer.create'),
                    'label' => translate('add_new_customer'),
                    'active' => request()->is('admin/customer/create'),
                ])
            @endcan
        @endcanany

        @can('customer_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.customer-cart.index'),
                'label' => translate('Customer_Cart'),
                'active' => request()->is('admin/customer-cart*'),
                'count' => $customer_cart_not_contacted_count ?? 0,
            ])
        @endcan

        @canany(['wallet_add', 'wallet_view'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('customer_wallet')])
            @can('wallet_add')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.customer.wallet.add-fund'),
                    'label' => translate('Add Fund to Wallet'),
                    'active' => request()->is('admin/customer/wallet/add-fund'),
                ])
            @endcan
            @can('wallet_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.customer.wallet.report'),
                    'label' => translate('Wallet Transactions'),
                    'active' => request()->is('admin/customer/wallet/report'),
                ])
            @endcan
        @endcanany

        @can('welcome_bonus_view')
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('Welcome_Bonus')])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.customer.welcome-bonus.report'),
                'label' => translate('Welcome_Bonus_Report'),
                'active' => request()->is('admin/customer/welcome-bonus/report'),
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.customer.settings', ['web_page' => 'welcome_bonus']),
                'label' => translate('Welcome_Bonus_Settings'),
                'active' => request()->is('admin/customer/settings') && request('web_page') == 'welcome_bonus',
            ])
        @endcan

        @can('point_view')
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('loyalty_point')])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.customer.loyalty-point.report'),
                'label' => translate('Loyalty Points Transactions'),
                'active' => request()->is('admin/customer/loyalty-point/report'),
            ])
        @endcan

        @can('referral_earning_view')
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('refer_and_earn')])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.customer.referral-earning.report'),
                'label' => translate('Referral_Report'),
                'active' => request()->is('admin/customer/referral-earning/report'),
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.customer.settings', ['web_page' => 'referral_earning']),
                'label' => translate('Referral_Settings'),
                'active' => request()->is('admin/customer/settings') && request('web_page') == 'referral_earning',
            ])
        @endcan

        @can('newsletter_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.customer.newsletter.index'),
                'label' => translate('Subscribed Newsletter'),
                'active' => request()->is('admin/customer/newsletter/*'),
            ])
        @endcan
    </div>
</div>
@endcanany
