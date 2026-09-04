@canany(['provider_view', 'provider_add', 'onboarding_request_view'])
@php
    $groupActive = \App\Support\AdminNavRegistry::groupIsActive('providers');
    $providersMenuCount = (Gate::check('onboarding_request_view') ? (int) ($pending_providers ?? 0) + (int) ($denied_providers ?? 0) : 0)
        + (Gate::check('onboarding_request_view') ? (int) ($pending_showcase_items ?? 0) + (int) ($pending_profile_changes ?? 0) : 0);
@endphp
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'engineering'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Providers'),
            'count' => $providersMenuCount > 0 ? $providersMenuCount : null,
        ])
        <span class="material-icons expand-more-icon">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @can('onboarding_request_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.provider.onboarding_request', ['status' => 'onboarding']),
                'label' => translate('Onboarding_Request'),
                'active' => request()->is('admin/provider/onboarding*'),
                'count' => ($pending_providers ?? 0) + ($denied_providers ?? 0),
            ])
        @endcan
        @can('provider_add')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.provider.create'),
                'label' => translate('Add_New_Provider'),
                'active' => request()->is('admin/provider/create'),
                'fullPage' => true,
            ])
        @endcan
        @can('provider_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.provider.live-view'),
                'label' => translate('Provider_Live_View'),
                'active' => request()->is('admin/provider/live-view*'),
                'fullPage' => true,
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.provider.list', ['status' => 'all']),
                'label' => translate('Provider_List'),
                'active' => request()->is('admin/provider/list') || request()->is('admin/provider/details*') || request()->is('admin/provider/edit*') || request()->is('admin/provider/collect-cash*'),
            ])
        @endcan
        @can('onboarding_request_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.provider.showcase_approval', ['status' => 'pending']),
                'label' => translate('Work_Showcase_Approvals'),
                'active' => request()->is('admin/provider/showcase-approval*'),
                'count' => $pending_showcase_items ?? 0,
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.provider.profile_change_request', ['status' => 'pending']),
                'label' => translate('Profile_Update_Requests'),
                'active' => request()->is('admin/provider/profile-change*'),
                'count' => $pending_profile_changes ?? 0,
            ])
        @endcan
    </div>
</div>
@endcanany
