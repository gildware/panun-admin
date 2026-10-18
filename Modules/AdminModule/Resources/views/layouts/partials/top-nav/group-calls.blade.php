@can('lead_view')
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('calls'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'phone_in_talk'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Calls'),
        ])
        <span class="material-icons expand-more-icon">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.calls.index'),
            'label' => translate('Calls'),
            'active' => request()->routeIs('admin.calls.index', 'admin.calls.show'),
        ])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.calls.api-logs'),
            'label' => translate('API_logs'),
            'active' => request()->is('admin/calls/api-logs*'),
        ])
    </div>
</div>
@endcan
