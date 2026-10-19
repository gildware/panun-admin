@can('lead_view')
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('calls'))
@php($missedCallsMenuCount = (int) ($missed_calls_menu_count ?? 0))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'phone_in_talk'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Calls'),
            'count' => $missedCallsMenuCount,
            'badgeId' => 'calls-menu-missed-count',
        ])
        <span class="material-icons expand-more-icon">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.calls.index'),
            'label' => translate('Calls'),
            'active' => request()->routeIs('admin.calls.index', 'admin.calls.show'),
            'count' => $missedCallsMenuCount,
            'countId' => 'calls-menu-missed-count-link',
        ])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.calls.api-logs'),
            'label' => translate('API_logs'),
            'active' => request()->is('admin/calls/api-logs*'),
        ])
    </div>
</div>
@endcan
