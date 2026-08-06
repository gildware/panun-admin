@canany(['report_view', 'lead_report_view', 'referral_earning_view', 'welcome_bonus_view', 'analytics_view'])
    @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('Reports')])
    @include('adminmodule::layouts.partials.top-nav._link', [
        'href' => route('admin.reports.index'),
        'label' => translate('Reports'),
        'active' => request()->routeIs('admin.reports.*') || \App\Support\AdminNavRegistry::groupIsActive('reports'),
    ])
@endcanany
