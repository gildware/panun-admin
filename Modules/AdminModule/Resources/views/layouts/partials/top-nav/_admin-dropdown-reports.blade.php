@canany(['report_view', 'lead_report_view', 'referral_earning_view', 'welcome_bonus_view', 'analytics_view'])
    @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('Reports')])
    @include('adminmodule::layouts.partials.top-nav._link', [
        'href' => route('admin.reports.index'),
        'label' => translate('Reports'),
        'active' => request()->routeIs('admin.reports.index'),
    ])
    @canany(['report_view', 'lead_report_view'])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.report.geographic'),
            'label' => translate('Zone_and_Area_Reports'),
            'active' => request()->routeIs('admin.report.geographic'),
        ])
    @endcanany
    @can('report_view')
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.report.booking'),
            'label' => translate('Booking_Reports'),
            'active' => request()->is('admin/report/booking'),
        ])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.report.business.overview'),
            'label' => translate('Business Reports'),
            'active' => request()->is('admin/report/business*'),
        ])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.report.provider'),
            'label' => translate('Provider Reports'),
            'active' => request()->is('admin/report/provider'),
        ])
    @endcan
    @can('lead_report_view')
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.lead.reports.inbound'),
            'label' => translate('Inbound_Lead_Reports'),
            'active' => request()->routeIs('admin.lead.reports.inbound') || request()->routeIs('admin.lead.reports.index'),
        ])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.lead.reports.outbound'),
            'label' => translate('Outbound_Lead_Reports'),
            'active' => request()->routeIs('admin.lead.reports.outbound'),
        ])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.lead.reports.user', ['user_id' => auth()->id()]),
            'label' => translate('User_Report'),
            'active' => request()->routeIs('admin.lead.reports.user'),
        ])
    @endcan
@endcanany
