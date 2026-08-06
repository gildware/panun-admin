@can('analytics_view')
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('insights'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'insights'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Insights'),
        ])
        <span class="material-icons expand-more-icon">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
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
    </div>
</div>
@endcan
