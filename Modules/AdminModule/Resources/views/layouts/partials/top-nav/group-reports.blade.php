@if(count(\App\Support\AdminReportsRegistry::visibleSections()) > 0)
@php($reportsActive = request()->routeIs('admin.reports.*') || \App\Support\AdminNavRegistry::groupIsActive('reports'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $reportsActive ? 'is-active' : '' }}">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'assessment'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Reports'),
        ])
        <span class="material-icons expand-more-icon">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @include('adminmodule::layouts.partials.top-nav._admin-dropdown-reports')
    </div>
</div>
@endif
