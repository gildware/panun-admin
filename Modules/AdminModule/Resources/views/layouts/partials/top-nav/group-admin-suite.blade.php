@canany([
    'transaction_view', 'ledger_view', 'wallet_view', 'point_view',
    'analytics_view',
    'report_view', 'lead_report_view', 'referral_earning_view', 'welcome_bonus_view',
    'advertisement_view', 'campaign_view', 'whatsapp_marketing_campaign_view', 'whatsapp_marketing_bulk_view',
])
@php
    $groupActive = \App\Support\AdminNavRegistry::groupIsActive('finance')
        || \App\Support\AdminNavRegistry::groupIsActive('reports')
        || \App\Support\AdminNavRegistry::groupIsActive('marketing');
@endphp
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'admin_panel_settings'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Admin'),
        ])
        <span class="material-icons expand-more-icon">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu top-nav-dropdown--wide">
        @include('adminmodule::layouts.partials.top-nav._admin-dropdown-finance')
        @include('adminmodule::layouts.partials.top-nav._admin-dropdown-reports')
        @include('adminmodule::layouts.partials.top-nav._admin-dropdown-marketing')
    </div>
</div>
@endcanany
