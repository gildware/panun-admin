@canany(['report_view', 'analytics_view', 'lead_report_view'])
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('insights'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        {{ translate('Insights') }} <span class="material-icons">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @canany(['report_view', 'lead_report_view'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('Reports')])
            @can('report_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.report.business.overview'),
                    'label' => translate('Business Reports'),
                    'active' => request()->is('admin/report/business*'),
                ])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.report.booking'),
                    'label' => translate('Booking Reports'),
                    'active' => request()->is('admin/report/booking'),
                ])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.report.provider'),
                    'label' => translate('Provider Reports'),
                    'active' => request()->is('admin/report/provider'),
                ])
            @endcan
            @can('lead_report_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.lead.reports.index', ['tab' => 'inbound']),
                    'label' => translate('Lead_Reports'),
                    'active' => request()->routeIs('admin.lead.reports.index'),
                ])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.lead.reports.user', ['user_id' => auth()->id()]),
                    'label' => translate('User_Report'),
                    'active' => request()->routeIs('admin.lead.reports.user'),
                ])
            @endcan
        @endcanany

        @can('analytics_view')
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('Analytics')])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.analytics.search.keyword'),
                'label' => translate('Keyword_Search'),
                'active' => request()->is('admin/analytics/search/keyword'),
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.analytics.search.customer'),
                'label' => translate('Customer_Search'),
                'active' => request()->is('admin/analytics/search/customer'),
            ])
        @endcan
    </div>
</div>
@endcanany
