@if(count(\App\Support\AdminReportsRegistry::visibleSections()) > 0)
@php($reportsActive = request()->routeIs('admin.reports.*') || \App\Support\AdminNavRegistry::groupIsActive('reports'))
<div class="top-nav-item top-nav-item--module-link">
    <a href="{{ route('admin.reports.index') }}"
       class="top-nav-trigger top-nav-trigger--module-link {{ $reportsActive ? 'active-menu is-active' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'assessment'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Reports'),
        ])
    </a>
</div>
@endif
